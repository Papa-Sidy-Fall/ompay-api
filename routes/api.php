<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompteController;
use App\Http\Controllers\DistributeurController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - OmPay
|--------------------------------------------------------------------------
|
| Routes pour l'API OmPay avec authentification automatique par Observer
|
*/

// OTP routes (pour vérification des codes)
Route::post('/otp/verify', [OtpController::class, 'verifyOtp']);
Route::post('/otp/resend', [OtpController::class, 'resendOtp']);

// Auth routes (OTP envoyé automatiquement par Observer)
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes (nécessitent un token Bearer)
Route::middleware('auth:api')->group(function () {
    // Compte
    Route::get('/compte', [CompteController::class, 'show']);
    Route::get('/compte/{id}/solde', [CompteController::class, 'solde']);
    Route::post('/compte/{id}/transaction', [CompteController::class, 'createTransaction']);
    Route::get('/compte/{id}/transactions', [CompteController::class, 'transactions']);

    // Anciens endpoints (maintenir pour compatibilité)
    Route::post('/transactions/pay', [TransactionController::class, 'pay']);
    Route::post('/transactions/transfert', [TransactionController::class, 'transfer']);
    Route::post('/transactions/depot', [DistributeurController::class, 'depot']);
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/{uuid}', [TransactionController::class, 'show']);
    Route::get('/distributeurs', [DistributeurController::class, 'index']);
});

// Route pour reset la base de données (ADMIN seulement - À SUPPRIMER APRÈS USAGE)
/**
 * @OA\Get(
 *     path="/api/admin/reset-database",
 *     summary="Reset de la base de données (ADMIN)",
 *     description="⚠️ ENDPOINT DANGEREUX - Reset complet de la base de données avec migrations et seeders. À SUPPRIMER APRÈS USAGE !",
 *     operationId="resetDatabase",
 *     tags={"Administration"},
 *     @OA\Parameter(
 *         name="secret",
 *         in="query",
 *         required=true,
 *         description="Clé secrète d'administration",
 *         @OA\Schema(type="string", example="ompay-admin-2025")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Base de données reset avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Base de données reset avec succès"),
 *             @OA\Property(property="details", type="string", example="Toutes les tables ont été recréées et les seeders exécutés")
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Accès non autorisé",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Accès non autorisé"),
 *             @OA\Property(property="message", type="string", example="Clé secrète requise")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Erreur lors du reset",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Erreur lors du reset"),
 *             @OA\Property(property="message", type="string", example="Détails de l'erreur")
 *         )
 *     )
 * )
 */
Route::get('/admin/reset-database', function () {
    // ⚠️ ENDPOINT DANGEREUX - À SUPPRIMER APRÈS USAGE EN PRODUCTION

    // Vérification de sécurité basique
    $secret = request()->query('secret');
    $expectedSecret = env('ADMIN_SECRET', 'ompay-admin-2025');

    if ($secret !== $expectedSecret) {
        return response()->json([
            'error' => 'Accès non autorisé',
            'message' => 'Clé secrète requise'
        ], 403);
    }

    try {
        // Exécuter migrate:fresh --seed
        \Illuminate\Support\Facades\Artisan::call('migrate:fresh --seed');

        // Récupérer le résultat
        $output = \Illuminate\Support\Facades\Artisan::output();

        return response()->json([
            'success' => true,
            'message' => 'Base de données reset avec succès',
            'details' => 'Toutes les tables ont été recréées et les seeders exécutés',
            'output' => $output
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Erreur lors du reset',
            'message' => $e->getMessage()
        ], 500);
    }
});

// Route pour la documentation Swagger
Route::get('/documentation', function () {
    $path = storage_path('docs/api-docs.json');
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $data = json_decode($content, true);
        return response()->json($data);
    }

    return response()->json(['error' => 'Documentation not found'], 404);
});