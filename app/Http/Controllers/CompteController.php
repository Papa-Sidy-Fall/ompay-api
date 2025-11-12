<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaction;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class CompteController extends Controller
{
    use ApiResponse;

    /**
     * Afficher les informations du compte
     */
    public function show(Request $request)
    {
        $user = $request->user();

        return $this->successResponse([
            'utilisateur' => $user,
            'solde' => $user->solde,
            'qr_code' => $user->qr_code,
        ], 'Informations du compte récupérées avec succès');
    }

    /**
     * Afficher uniquement le solde du compte
     */
    public function solde(Request $request, $id)
    {
        // Vérifier que l'utilisateur demande son propre solde ou est admin
        $user = $request->user();
        if ($user->id != $id && $user->statut !== 'admin') {
            return $this->errorResponse('Accès non autorisé', 403);
        }

        $compteUser = User::find($id);
        if (!$compteUser) {
            return $this->errorResponse('Utilisateur non trouvé', 404);
        }

        return $this->successResponse([
            'solde' => $compteUser->solde,
        ], 'Solde récupéré avec succès');
    }

    /**
     * Créer une transaction (paiement ou transfert)
     */
    public function createTransaction(Request $request, $id)
    {
        $user = $request->user();

        // Validation
        $request->validate([
            'type' => 'required|in:payer,transfert',
            'montant' => 'required|numeric|min:0.01',
            'numero_destinataire' => 'required_if:type,transfert|string',
            'code_marchand' => 'required_if:type,payer|string',
        ]);

        // Vérifier le solde
        if ($user->solde < $request->montant) {
            return $this->errorResponse('Solde insuffisant', 400);
        }

        // Générer et envoyer OTP pour la transaction
        $otpController = app(OtpController::class);
        $sendOtpRequest = new \App\Http\Requests\SendOtpRequest();
        $sendOtpRequest->merge([
            'telephone' => $user->telephone,
            'type' => 'transaction'
        ]);

        $otpController->sendOtp($sendOtpRequest);

        // Créer la transaction en attente
        $transaction = Transaction::create([
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'utilisateur_uuid' => $user->uuid,
            'type' => $request->type,
            'montant' => $request->type === 'transfert' ? -$request->montant : -$request->montant,
            'destinataire_uuid' => $request->type === 'transfert' ? User::where('telephone', $request->numero_destinataire)->first()?->uuid : null,
            'description' => $request->type === 'transfert' ? 'Transfert' : 'Paiement marchand',
            'statut' => 'en_attente',
        ]);

        return $this->successResponse([
            'transaction_id' => $transaction->uuid,
            'type' => $request->type,
            'montant' => $request->montant,
            'otp_required' => true,
        ], 'Transaction initiée. Veuillez confirmer avec le code OTP envoyé.');
    }

    /**
     * Confirmer une transaction avec OTP
     */
    public function confirmTransaction(Request $request, $id, $transactionId)
    {
        $user = $request->user();

        // Validation
        $request->validate([
            'code' => 'required|string|size:4',
        ]);

        // Vérifier que l'utilisateur confirme sa propre transaction
        if ($user->id != $id) {
            return $this->errorResponse('Accès non autorisé', 403);
        }

        // Trouver la transaction en attente
        $transaction = Transaction::where('uuid', $transactionId)
            ->where('utilisateur_uuid', $user->uuid)
            ->where('statut', 'en_attente')
            ->first();

        if (!$transaction) {
            return $this->errorResponse('Transaction non trouvée ou déjà confirmée', 404);
        }

        // Vérifier le code OTP
        $otp = \App\Models\Otp::findValidOtp($user->telephone, $request->code, 'transaction');

        if (!$otp) {
            return $this->errorResponse('Code OTP invalide ou expiré', 400);
        }

        // Marquer l'OTP comme utilisé
        $otp->markAsUsed();

        // Confirmer la transaction
        $transaction->update(['statut' => 'confirmee']);

        return $this->successResponse([
            'transaction' => $transaction,
        ], 'Transaction confirmée avec succès');
    }

    /**
     * Lister les transactions avec pagination et filtrage
     */
    public function transactions(Request $request, $id)
    {
        $user = $request->user();

        // Vérifier que l'utilisateur demande ses propres transactions
        if ($user->id != $id) {
            return $this->errorResponse('Accès non autorisé', 403);
        }

        $query = Transaction::where('utilisateur_uuid', $user->uuid)
            ->orderBy('created_at', 'desc');

        // Filtrage par type
        if ($request->has('type') && in_array($request->type, ['payer', 'transfert', 'depot'])) {
            $query->where('type', $request->type);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $transactions = $query->paginate($perPage);

        return $this->successResponse([
            'transactions' => $transactions->items(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
                'last_page' => $transactions->lastPage(),
            ]
        ], 'Transactions récupérées avec succès');
    }
}
