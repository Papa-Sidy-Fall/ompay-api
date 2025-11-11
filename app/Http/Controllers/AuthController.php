<?php

namespace App\Http\Controllers;

use App\Http\Controllers\OtpController;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\SendOtpRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request)
    {
        // Créer l'utilisateur complet (l'Observer enverra automatiquement l'OTP)
        $user = User::create([
            'uuid' => Str::uuid()->toString(),
            'nom' => $request->nom,
            'telephone' => $request->telephone,
            'pin' => Hash::make($request->pin),
        ]);

        return $this->successResponse([
            'utilisateur' => $user,
            'message_complementaire' => 'Un code OTP a été envoyé à votre téléphone pour finaliser l\'inscription'
        ], 'Utilisateur créé avec succès. Vérifiez votre téléphone pour le code OTP.', 201);
    }

    public function login(LoginRequest $request)
    {
        // Vérifier que l'utilisateur existe
        $user = User::where('telephone', $request->telephone)->first();
        if (!$user) {
            return $this->errorResponse('Numéro de téléphone non enregistré', 404);
        }

        if (!Hash::check($request->pin, $user->pin)) {
            return $this->errorResponse('PIN incorrect', 401);
        }

        // Générer et envoyer OTP par SMS via l'endpoint dédié
        $otpController = app(OtpController::class);
        $sendOtpRequest = new SendOtpRequest();
        $sendOtpRequest->merge([
            'telephone' => $request->telephone,
            'type' => 'connexion'
        ]);

        $otpResponse = $otpController->sendOtp($sendOtpRequest);

        if ($otpResponse->getStatusCode() !== 200) {
            return $this->errorResponse('Erreur lors de l\'envoi du code OTP', 500);
        }

        return $this->successResponse([
            'utilisateur' => $user,
            'message_complementaire' => 'Un code OTP a été envoyé à votre numéro de téléphone'
        ], 'Veuillez vérifier votre téléphone avec le code OTP');
    }
}
