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
     * Effectuer un paiement marchand
     */
    public function payer(Request $request, $id)
    {
        $user = $request->user();

        // Validation
        $request->validate([
            'montant' => 'required|numeric|min:0.01',
            'code_marchand' => 'required|string',
        ]);

        // Vérifier le solde
        if ($user->solde < $request->montant) {
            return $this->errorResponse('Solde insuffisant', 400);
        }

        // Vérifier que le marchand existe
        $marchand = DB::table('marchands')
            ->where('code_marchand', $request->code_marchand)
            ->where('statut', 'actif')
            ->first();

        if (!$marchand) {
            return $this->errorResponse('Marchand non trouvé ou inactif', 404);
        }

        // Exécuter le paiement immédiatement
        DB::transaction(function () use ($user, $request, $marchand) {
            Transaction::create([
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'utilisateur_uuid' => $user->uuid,
                'type' => 'payer',
                'montant' => -$request->montant,
                'description' => 'Paiement marchand - ' . $marchand->nom,
                'statut' => 'confirmee',
            ]);
        });

        return $this->successResponse([
            'type' => 'payer',
            'montant' => $request->montant,
            'marchand' => $marchand->nom,
            'nouveau_solde' => $user->fresh()->solde,
        ], 'Paiement effectué avec succès.');
    }

    /**
     * Effectuer un transfert d'argent
     */
    public function transfert(Request $request, $id)
    {
        $user = $request->user();

        // Validation
        $request->validate([
            'montant' => 'required|numeric|min:0.01',
            'numero_destinataire' => 'required|string',
        ]);

        // Vérifier le solde
        if ($user->solde < $request->montant) {
            return $this->errorResponse('Solde insuffisant', 400);
        }

        // Vérifier que le destinataire existe et est actif
        $destinataire = User::where('telephone', $request->numero_destinataire)
            ->where('statut', 'actif')
            ->first();

        if (!$destinataire) {
            return $this->errorResponse('Destinataire non trouvé ou inactif', 404);
        }

        // Empêcher les transferts vers soi-même
        if ($destinataire->id === $user->id) {
            return $this->errorResponse('Impossible de transférer vers soi-même', 400);
        }

        // Exécuter le transfert immédiatement
        DB::transaction(function () use ($user, $request, $destinataire) {
            // Débiter l'expéditeur
            Transaction::create([
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'utilisateur_uuid' => $user->uuid,
                'type' => 'transfert',
                'montant' => -$request->montant,
                'destinataire_uuid' => $destinataire->uuid,
                'description' => 'Transfert envoyé',
                'statut' => 'confirmee',
            ]);

            // Créditer le destinataire
            Transaction::create([
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'utilisateur_uuid' => $destinataire->uuid,
                'type' => 'depot',
                'montant' => $request->montant,
                'description' => 'Transfert reçu de ' . $user->nom,
                'statut' => 'confirmee',
            ]);
        });

        return $this->successResponse([
            'type' => 'transfert',
            'montant' => $request->montant,
            'destinataire' => $destinataire->nom,
            'numero_destinataire' => $destinataire->telephone,
            'nouveau_solde' => $user->fresh()->solde,
        ], 'Transfert effectué avec succès.');
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

        $query = Transaction::with(['destinataire:id,nom,telephone'])
            ->where('utilisateur_uuid', $user->uuid)
            ->orderBy('created_at', 'desc');

        // Filtrage par type
        if ($request->has('type') && in_array($request->type, ['payer', 'transfert', 'depot'])) {
            $query->where('type', $request->type);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $transactions = $query->paginate($perPage);

        // Transformer les données pour inclure les infos lisibles
        $transformedTransactions = $transactions->getCollection()->map(function ($transaction) use ($user) {
            return [
                'uuid' => $transaction->uuid,
                'type' => $transaction->type,
                'montant' => $transaction->montant,
                'description' => $transaction->description,
                'statut' => $transaction->statut,
                'created_at' => $transaction->created_at,
                // Informations de l'expéditeur (toujours l'utilisateur connecté)
                'expediteur' => [
                    'nom' => $user->nom,
                    'telephone' => $user->telephone,
                ],
                // Informations du destinataire (si applicable)
                'destinataire' => $transaction->destinataire ? [
                    'nom' => $transaction->destinataire->nom,
                    'telephone' => $transaction->destinataire->telephone,
                ] : null,
            ];
        });

        return $this->successResponse([
            'transactions' => $transformedTransactions,
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
                'last_page' => $transactions->lastPage(),
            ]
        ], 'Transactions récupérées avec succès');
    }
}
