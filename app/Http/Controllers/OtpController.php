<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResendOtpRequest;
use App\Http\Requests\SendOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Models\Otp;
use App\Models\User;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;
use Illuminate\Support\Str;

class OtpController extends Controller
{
    use ApiResponse;

    /**
     * Envoyer un code OTP
     */
    public function sendOtp(SendOtpRequest $request)
    {
        try {
            // Pour l'inscription, on ne vérifie pas si l'utilisateur existe
            // L'observer s'en chargera automatiquement

            // Pour la connexion, vérifier que l'utilisateur existe
            if ($request->type === 'connexion') {
                $existingUser = User::where('telephone', $request->telephone)->first();
                if (!$existingUser) {
                    return $this->errorResponse('Ce numéro de téléphone n\'est pas enregistré', 404);
                }
            }

            // Désactiver les anciens codes OTP non utilisés pour ce téléphone et ce type
            Otp::where('telephone', $request->telephone)
                ->where('type', $request->type)
                ->where('utilise', false)
                ->update(['utilise' => true]);

            // Générer un nouveau code OTP
            $code = Otp::generateCode();
            $expireAt = Carbon::now()->addMinutes(5); // Expire dans 5 minutes

            // Créer le code OTP
            Otp::create([
                'telephone' => $request->telephone,
                'code' => $code,
                'type' => $request->type,
                'expire_at' => $expireAt,
            ]);

            // Envoyer le SMS avec Twilio
            try {
                $twilio = new Client(config('services.twilio.sid'), config('services.twilio.token'));
                $message = "Votre code OTP OmPay est: {$code}. Valide jusqu'à {$expireAt->format('H:i')}.";

                $twilio->messages->create(
                    $request->telephone,
                    [
                        'from' => config('services.twilio.from'),
                        'body' => $message
                    ]
                );

                Log::info("Code OTP envoyé par SMS pour {$request->telephone} ({$request->type}): {$code}");
            } catch (\Exception $e) {
                // En développement, si Twilio échoue, on log juste le code
                Log::warning("Échec envoi SMS Twilio pour {$request->telephone}: " . $e->getMessage());
                Log::info("Code OTP (SMS failed) pour {$request->telephone} ({$request->type}): {$code}");
            }

            return $this->successResponse(
                ['expire_at' => $expireAt->toISOString()],
                'Code OTP envoyé avec succès'
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi du code OTP: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de l\'envoi du code OTP', 500);
        }
    }

    /**
     * Vérifier un code OTP
     */
    public function verifyOtp(VerifyOtpRequest $request)
    {
        try {
            // Trouver le code OTP valide
            $otp = Otp::findValidOtp($request->telephone, $request->code, $request->type);

            if (!$otp) {
                return $this->errorResponse('Code OTP invalide ou expiré', 400);
            }

            // Marquer le code comme utilisé
            $otp->markAsUsed();

            // Retourner les informations selon le type
            $data = [
                'telephone' => $request->telephone,
                'type' => $request->type,
                'verifie' => true,
            ];

            // Pour l'inscription, créer l'utilisateur si nécessaire
            if ($request->type === 'inscription') {
                $user = User::where('telephone', $request->telephone)->first();
                if ($user) {
                    $data['utilisateur_existe'] = true;
                    $data['utilisateur'] = $user;
                } else {
                    // Pour l'inscription, on ne crée pas l'utilisateur ici
                    // Il sera créé lors de l'appel à /api/auth/register
                    $data['utilisateur_existe'] = false;
                    $data['utilisateur'] = null;
                }
            }

            // Pour la connexion, retourner l'utilisateur avec token
            if ($request->type === 'connexion') {
                $user = User::where('telephone', $request->telephone)->first();
                if ($user) {
                    $token = $user->createToken('OmPay')->accessToken;
                    $data['utilisateur'] = $user;
                    $data['token'] = $token;
                }
            }

            return $this->successResponse($data, 'Code OTP vérifié avec succès');

        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification du code OTP: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la vérification du code OTP', 500);
        }
    }

    /**
     * Renvoyer un code OTP
     */
    public function resendOtp(ResendOtpRequest $request)
    {
        try {
            // Vérifier que l'utilisateur existe
            $user = User::where('telephone', $request->telephone)->first();
            if (!$user) {
                return $this->errorResponse('Ce numéro de téléphone n\'existe pas dans notre système', 404);
            }

            // Désactiver les anciens codes OTP non utilisés pour ce téléphone et ce type
            Otp::where('telephone', $request->telephone)
                ->where('type', $request->type)
                ->where('utilise', false)
                ->update(['utilise' => true]);

            // Générer un nouveau code OTP
            $code = Otp::generateCode();
            $expireAt = Carbon::now()->addMinutes(5); // Expire dans 5 minutes

            // Créer le code OTP
            Otp::create([
                'telephone' => $request->telephone,
                'code' => $code,
                'type' => $request->type,
                'expire_at' => $expireAt,
            ]);

            // Envoyer le SMS avec Twilio
            try {
                $twilio = new Client(config('services.twilio.sid'), config('services.twilio.token'));
                $message = "Votre code OTP OmPay est: {$code}. Valide jusqu'à {$expireAt->format('H:i')}.";

                $twilio->messages->create(
                    $request->telephone,
                    [
                        'from' => config('services.twilio.from'),
                        'body' => $message
                    ]
                );

                Log::info("Nouveau code OTP envoyé par SMS pour {$request->telephone} ({$request->type}): {$code}");
            } catch (\Exception $e) {
                // En développement, si Twilio échoue, on log juste le code
                Log::warning("Échec envoi SMS Twilio pour {$request->telephone}: " . $e->getMessage());
                Log::info("Nouveau code OTP (SMS failed) pour {$request->telephone} ({$request->type}): {$code}");
            }

            return $this->successResponse(
                ['expire_at' => $expireAt->toISOString()],
                'Nouveau code OTP envoyé avec succès'
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors du renvoi du code OTP: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors du renvoi du code OTP', 500);
        }
    }
}
