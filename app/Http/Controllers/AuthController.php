<?php

/**
 * @OA\Info(
 *     title="OmPay API",
 *     version="1.0.0",
 *     description="API de paiement mobile OmPay avec authentification OTP"
 * )
 *
 * @OA\Server(
 *     url="http://127.0.0.1:8000",
 *     description="Serveur de développement"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */

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

    /**
     * @OA\Post(
     *     path="/api/auth/register",
     *     summary="Inscription d'un nouvel utilisateur",
     *     description="Crée un compte utilisateur et envoie automatiquement un code OTP par SMS",
     *     operationId="registerUser",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom","telephone","pin"},
     *             @OA\Property(property="nom", type="string", example="Papa Sidy Fall", description="Nom de l'utilisateur"),
     *             @OA\Property(property="telephone", type="string", example="771234567", description="Numéro de téléphone"),
     *             @OA\Property(property="pin", type="string", example="1234", description="Code PIN à 4 chiffres")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Utilisateur créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="succes", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Utilisateur créé avec succès. Vérifiez votre téléphone pour le code OTP."),
     *             @OA\Property(property="donnees", type="object",
     *                 @OA\Property(property="utilisateur", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="uuid", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                     @OA\Property(property="nom", type="string", example="Papa Sidy Fall"),
     *                     @OA\Property(property="telephone", type="string", example="771234567")
     *                 ),
     *                 @OA\Property(property="message_complementaire", type="string", example="Un code OTP a été envoyé à votre téléphone pour finaliser l'inscription")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="succes", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Les données fournies ne sont pas valides"),
     *             @OA\Property(property="erreurs", type="object",
     *                 @OA\Property(property="telephone", type="array", @OA\Items(type="string", example="Ce numéro de téléphone est déjà utilisé"))
     *             )
     *         )
     *     )
     * )
     */
    public function register(RegisterRequest $request)
    {
        // Créer l'utilisateur avec statut inactif (l'Observer enverra automatiquement l'OTP)
        $user = User::create([
            'uuid' => Str::uuid()->toString(),
            'nom' => $request->nom,
            'telephone' => $request->telephone,
            'pin' => Hash::make($request->pin),
            'statut' => 'inactif',
        ]);

        return $this->successResponse(null, 'Un code OTP a été envoyé à votre téléphone pour finaliser l\'inscription.', 201);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     summary="Connexion utilisateur",
     *     description="Authentifie l'utilisateur et envoie un code OTP par SMS",
     *     operationId="loginUser",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone","pin"},
     *             @OA\Property(property="telephone", type="string", example="771234567", description="Numéro de téléphone"),
     *             @OA\Property(property="pin", type="string", example="1234", description="Code PIN à 4 chiffres")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Code OTP envoyé",
     *         @OA\JsonContent(
     *             @OA\Property(property="succes", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Veuillez vérifier votre téléphone avec le code OTP"),
     *             @OA\Property(property="donnees", type="object",
     *                 @OA\Property(property="utilisateur", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="uuid", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                     @OA\Property(property="nom", type="string", example="Papa Sidy Fall"),
     *                     @OA\Property(property="telephone", type="string", example="771234567")
     *                 ),
     *                 @OA\Property(property="message_complementaire", type="string", example="Un code OTP a été envoyé à votre numéro de téléphone")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Utilisateur non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="succes", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Numéro de téléphone non enregistré")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="PIN incorrect",
     *         @OA\JsonContent(
     *             @OA\Property(property="succes", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="PIN incorrect")
     *         )
     *     )
     * )
     */
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

        $otpController->sendOtp($sendOtpRequest);

        return $this->successResponse(null, 'Un code OTP a été envoyé à votre numéro de téléphone');
    }
}
