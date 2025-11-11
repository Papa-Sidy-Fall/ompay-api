<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmTransactionRequest;
use App\Http\Requests\PayRequest;
use App\Http\Requests\SendOtpRequest;
use App\Http\Requests\TransferRequest;
use App\Mail\SendOtpMail;
use App\Models\Otp;
use App\Models\Transaction;
use App\Models\User;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TransactionController extends Controller
{
    use ApiResponse;

    public function pay(PayRequest $request)
    {
        $user = $request->user();

        if ($user->balance < $request->montant) {
            return $this->errorResponse('Solde insuffisant', 400);
        }

        // Générer et envoyer un code OTP pour la transaction
        $otp = Otp::create([
            'telephone' => $user->telephone,
            'code' => Otp::generateCode(),
            'type' => 'transaction',
            'expire_at' => Carbon::now()->addMinutes(5),
        ]);

        // Envoyer le SMS avec le code OTP
        $otpController = app(OtpController::class);
        $sendOtpRequest = new SendOtpRequest();
        $sendOtpRequest->merge([
            'telephone' => $user->telephone,
            'type' => 'transaction'
        ]);
        $otpController->sendOtp($sendOtpRequest);

        return $this->successResponse([
            'transaction_id' => Str::uuid()->toString(),
            'montant' => $request->montant,
            'description' => $request->description,
            'otp_required' => true,
            'expire_at' => $otp->expire_at->toISOString(),
        ], 'Code OTP envoyé pour confirmer la transaction');
    }

    public function transfer(TransferRequest $request)
    {
        $user = $request->user();
        $destinataire = User::where('uuid', $request->destinataire_uuid)->first();

        if ($user->uuid === $destinataire->uuid) {
            return $this->errorResponse('Vous ne pouvez pas vous transférer à vous-même', 400);
        }

        if ($user->balance < $request->montant) {
            return $this->errorResponse('Solde insuffisant', 400);
        }

        // Générer et envoyer un code OTP pour la transaction
        $otp = Otp::create([
            'telephone' => $user->telephone,
            'code' => Otp::generateCode(),
            'type' => 'transaction',
            'expire_at' => Carbon::now()->addMinutes(5),
        ]);

        // Envoyer le SMS avec le code OTP
        $otpController = app(OtpController::class);
        $sendOtpRequest = new SendOtpRequest();
        $sendOtpRequest->merge([
            'telephone' => $user->telephone,
            'type' => 'transaction'
        ]);
        $otpController->sendOtp($sendOtpRequest);

        return $this->successResponse([
            'transaction_id' => Str::uuid()->toString(),
            'montant' => $request->montant,
            'destinataire_uuid' => $request->destinataire_uuid,
            'description' => $request->description,
            'otp_required' => true,
            'expire_at' => $otp->expire_at->toISOString(),
        ], 'Code OTP envoyé pour confirmer la transaction');
    }

    public function index(Request $request)
    {
        $transactions = $request->user()->transactions()->orderBy('created_at', 'desc')->get();

        return $this->successResponse(['transactions' => $transactions]);
    }

    public function show(Request $request, $uuid)
    {
        $transaction = Transaction::where('uuid', $uuid)
            ->where('utilisateur_uuid', $request->user()->uuid)
            ->first();

        if (!$transaction) {
            return $this->errorResponse('Transaction non trouvée', 404);
        }

        return $this->successResponse(['transaction' => $transaction]);
    }

    /**
     * Confirmer une transaction avec OTP
     */
    public function confirmTransaction(ConfirmTransactionRequest $request)
    {
        $user = $request->user();

        // Vérifier le code OTP
        $otp = Otp::findValidOtp($user->telephone, $request->code, 'transaction');

        if (!$otp) {
            return $this->errorResponse('Code OTP invalide ou expiré', 400);
        }

        // Marquer l'OTP comme utilisé
        $otp->markAsUsed();

        // Simulation : paiement confirmé (en production, récupérez les détails depuis cache/session)
        $montant = 50.00;
        $description = 'Paiement confirmé';

        DB::transaction(function () use ($user, $montant, $description) {
            Transaction::create([
                'uuid' => Str::uuid()->toString(),
                'utilisateur_uuid' => $user->uuid,
                'type' => 'paiement',
                'montant' => -$montant,
                'description' => $description,
            ]);
        });

        return $this->successResponse(null, 'Transaction confirmée avec succès');
    }
}
