<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaction;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        // Pour les transferts, vérifier que le destinataire existe
        $destinataire = null;
        if ($request->type === 'transfert') {
            $destinataire = User::where('telephone', $request->numero_destinataire)->first();
            if (!$destinataire) {
                return $this->errorResponse('Destinataire non trouvé', 404);
            }
        }

        // Exécuter la transaction immédiatement
        DB::transaction(function () use ($user, $request, $destinataire) {
            // Créer la transaction
            Transaction::create([
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'utilisateur_uuid' => $user->uuid,
                'type' => $request->type,
                'montant' => -$request->montant,
                'destinataire_uuid' => $destinataire?->uuid,
                'description' => $request->type === 'transfert' ? 'Transfert' : 'Paiement marchand',
                'statut' => 'confirmee',
            ]);

            // Pour les transferts, créditer le destinataire
            if ($request->type === 'transfert' && $destinataire) {
                Transaction::create([
                    'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                    'utilisateur_uuid' => $destinataire->uuid,
                    'type' => 'depot',
                    'montant' => $request->montant,
                    'description' => 'Transfert reçu',
                    'statut' => 'confirmee',
                ]);
            }
        });

        return $this->successResponse([
            'type' => $request->type,
            'montant' => $request->montant,
            'nouveau_solde' => $user->fresh()->solde,
        ], 'Transaction effectuée avec succès.');
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
