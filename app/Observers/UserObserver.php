<?php

namespace App\Observers;

use App\Models\Otp;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class UserObserver
{
    /**
     * Handle the User "created" event.
     * Envoie automatiquement un code OTP par SMS lors de la création d'un utilisateur
     */
    public function created(User $user): void
    {
        // Générer le QR code pour l'utilisateur
        $user->generateQrCode();

        // Générer et envoyer un code OTP pour l'inscription
        $otp = Otp::create([
            'telephone' => $user->telephone,
            'code' => Otp::generateCode(),
            'type' => 'inscription',
            'expire_at' => now()->addMinutes(5),
        ]);

        // Envoyer le SMS avec le code OTP via Twilio
        try {
            $twilioSid = config('services.twilio.sid');
            $twilioToken = config('services.twilio.token');
            $twilioFrom = config('services.twilio.from');

            Log::info("Tentative envoi SMS - SID: " . ($twilioSid ? 'Défini' : 'Non défini') . ", Token: " . ($twilioToken ? 'Défini' : 'Non défini') . ", From: {$twilioFrom}");

            if (!$twilioSid || !$twilioToken || !$twilioFrom) {
                Log::error("Configuration Twilio incomplète - SID, Token ou From manquant");
                Log::info("Code OTP inscription (Twilio config failed) pour {$user->telephone}: {$otp->code}");
                return;
            }

            $twilio = new Client($twilioSid, $twilioToken);
            $message = $twilio->messages->create(
                $user->telephone,
                [
                    'from' => $twilioFrom,
                    'body' => "Votre code de vérification OmPay est : {$otp->code}. Ce code expire dans 5 minutes."
                ]
            );
            Log::info("Code OTP inscription envoyé avec succès à {$user->telephone}: {$otp->code} (Message SID: {$message->sid})");
        } catch (\Exception $e) {
            Log::error("Erreur envoi SMS OTP pour {$user->telephone}: " . $e->getMessage());
            Log::error("Détails Twilio - SID: {$twilioSid}, From: {$twilioFrom}, To: {$user->telephone}");
            // En développement, log du code si l'SMS échoue
            Log::info("Code OTP inscription (SMS failed) pour {$user->telephone}: {$otp->code}");
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
