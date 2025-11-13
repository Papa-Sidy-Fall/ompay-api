<?php

/**
 * @OA\Tag(
 *     name="OTP",
 *     description="Gestion des codes OTP pour l'authentification"
 * )
 */

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
     * @OA\Post(
     *     path="/api/otp/send",
     *     summary="Envoyer un code OTP",
     *     description="Envoie un code OTP par SMS pour l'authentification ou les transactions",
     *     operationId="sendOtp",
     *     tags={"OTP"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone","type"},
     *             @OA\Property(property="telephone", type="string", example="771234567", description="Numéro de téléphone"),
     *             @OA\Property(property="type", type="string", enum={"inscription", "connexion", "transaction"}, example="connexion", description="Type d'OTP")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Code OTP envoyé",
     *         @OA\JsonContent(
     *             @OA\Property(property="succes", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Code OTP envoyé avec succès"),
     *             @OA\Property(property="donnees", type="object",
     *                 @OA\Property(property="expire_at", type="string", format="date-time", example="2025-11-11T09:56:29.000000Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Téléphone non enregistré (pour connexion)",
     *         @OA\JsonContent(
     *             @OA\Property(property="succes", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Ce numéro de téléphone n'est pas enregistré")
     *         )
     *     )
     * )
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
                $twilioSid = config('services.twilio.sid');
                $twilioToken = config('services.twilio.token');
                $twilioFrom = config('services.twilio.from');

                Log::info("Tentative envoi SMS sendOtp - SID: " . ($twilioSid ? 'Défini' : 'Non défini') . ", Token: " . ($twilioToken ? 'Défini' : 'Non défini') . ", From: {$twilioFrom}");

                if (!$twilioSid || !$twilioToken || !$twilioFrom) {
                    Log::error("Configuration Twilio incomplète dans sendOtp");
                    Log::info("Code OTP (Twilio config failed) pour {$request->telephone} ({$request->type}): {$code}");
                } else {
                    $twilio = new Client($twilioSid, $twilioToken);
                    $message = "Votre code OTP OmPay est: {$code}. Valide jusqu'à {$expireAt->format('H:i')}.";

                    $sms = $twilio->messages->create(
                        $request->telephone,
                        [
                            'from' => $twilioFrom,
                            'body' => $message
                        ]
                    );

                    Log::info("Code OTP envoyé par SMS pour {$request->telephone} ({$request->type}): {$code} (Message SID: {$sms->sid})");
                }
            } catch (\Exception $e) {
                // En développement, si Twilio échoue, on log juste le code
                Log::warning("Échec envoi SMS Twilio pour {$request->telephone}: " . $e->getMessage());
                Log::error("Détails Twilio sendOtp - SID: {$twilioSid}, From: {$twilioFrom}, To: {$request->telephone}");
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
     *
     * @OA\Post(
     *     path="/api/otp/verify",
     *     summary="Vérifier un code OTP",
     *     description="Vérifie le code OTP et retourne un token JWT pour la connexion ou confirme l'inscription",
     *     operationId="verifyOtp",
     *     tags={"OTP"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone","code","type"},
     *             @OA\Property(property="telephone", type="string", example="771234567", description="Numéro de téléphone"),
     *             @OA\Property(property="code", type="string", example="0939", description="Code OTP à 4 chiffres"),
     *             @OA\Property(property="type", type="string", enum={"inscription", "connexion", "transaction"}, example="inscription", description="Type d'OTP")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Code OTP vérifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="succes", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Code OTP vérifié avec succès"),
     *             @OA\Property(property="donnees", type="object",
     *                 @OA\Property(property="telephone", type="string", example="771234567"),
     *                 @OA\Property(property="type", type="string", example="inscription"),
     *                 @OA\Property(property="verifie", type="boolean", example=true),
     *                 @OA\Property(property="utilisateur_existe", type="boolean", example=true),
     *                 @OA\Property(property="utilisateur", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="uuid", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                     @OA\Property(property="nom", type="string", example="Papa Sidy Fall"),
     *                     @OA\Property(property="telephone", type="string", example="771234567")
     *                 ),
     *                 @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...", description="Token JWT pour l'authentification (uniquement pour connexion)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Code OTP invalide ou expiré",
     *         @OA\JsonContent(
     *             @OA\Property(property="succes", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Code OTP invalide ou expiré")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données de requête invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="succes", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Les données fournies ne sont pas valides"),
     *             @OA\Property(property="erreurs", type="object",
     *                 @OA\Property(property="code", type="array", @OA\Items(type="string", example="Le code OTP doit contenir exactement 4 caractères"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur interne du serveur",
     *         @OA\JsonContent(
     *             @OA\Property(property="succes", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur lors de la vérification du code OTP")
     *         )
     *     )
     * )
     */
    public function verifyOtp(VerifyOtpRequest $request)
    {
        try {
            Log::info("Tentative de vérification OTP: telephone={$request->telephone}, code={$request->code}, type={$request->type}");

            // Trouver le code OTP valide
            $otp = Otp::findValidOtp($request->telephone, $request->code, $request->type);

            if (!$otp) {
                Log::warning("Aucun OTP valide trouvé pour telephone={$request->telephone}, code={$request->code}, type={$request->type}");
                return $this->errorResponse('Code OTP invalide ou expiré', 400);
            }

            Log::info("OTP trouvé et valide: id={$otp->id}, expire_at={$otp->expire_at}");

            // Retourner les informations selon le type
            $data = [
                'telephone' => $request->telephone,
                'type' => $request->type,
                'verifie' => true,
            ];

            // Pour l'inscription et la connexion, retourner seulement le token et le type
            if ($request->type === 'inscription' || $request->type === 'connexion') {
                $user = User::where('telephone', $request->telephone)->first();
                if ($user) {
                    // Pour l'inscription, activer le compte
                    if ($request->type === 'inscription') {
                        $user->update(['statut' => 'actif']);
                    }

                    $token = $user->createToken('OmPay')->accessToken;
                    $data['token'] = $token;
                    $data['type'] = $request->type;

                    // Marquer le code comme utilisé seulement après succès
                    $otp->markAsUsed();
                } else {
                    // Pour l'inscription, si l'utilisateur n'existe pas, c'est anormal
                    // car il devrait être créé lors de register
                    return $this->errorResponse('Utilisateur non trouvé', 404);
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
