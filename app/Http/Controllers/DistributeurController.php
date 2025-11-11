<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepotRequest;
use App\Models\Distributeur;
use App\Models\Transaction;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DistributeurController extends Controller
{
    use ApiResponse;

    public function depot(DepotRequest $request)
    {
        $user = $request->user();

        DB::transaction(function () use ($request, $user) {
            $description = $request->description ?: 'Dépôt d\'argent';

            // Créditer le compte utilisateur
            Transaction::create([
                'uuid' => Str::uuid()->toString(),
                'utilisateur_uuid' => $user->uuid,
                'type' => 'depot',
                'montant' => $request->montant,
                'description' => $description,
            ]);
        });

        return $this->successResponse([
            'nouveau_solde' => $user->fresh()->balance,
            'montant_depose' => $request->montant,
        ], 'Dépôt effectué avec succès');
    }

    public function index()
    {
        $distributeurs = Distributeur::actif()->get(['uuid', 'nom', 'localisation', 'solde']);

        return $this->successResponse(['distributeurs' => $distributeurs]);
    }
}
