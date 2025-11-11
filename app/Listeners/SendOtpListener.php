<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Mail\SendOtpMail;
use App\Models\Otp;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOtpListener
{
    /**
     * Handle the UserRegistered event.
     */
    public function handle(UserRegistered $event): void
    {
        $user = $event->user;

        // Générer et envoyer un code OTP pour l'inscription
        $otp = Otp::create([
            'email' => $user->email,
            'code' => Otp::generateCode(),
            'type' => 'inscription',
            'expire_at' => now()->addMinutes(5),
        ]);

        // Envoyer l'email avec le code OTP
        try {
            Mail::to($user->email)->send(new SendOtpMail($otp->code, 'inscription', $otp->expire_at));
            Log::info("Code OTP inscription envoyé à {$user->email}: {$otp->code}");
        } catch (\Exception $e) {
            Log::error("Erreur envoi email OTP pour {$user->email}: " . $e->getMessage());
            // En développement, log du code si l'email échoue
            Log::info("Code OTP inscription (email failed) pour {$user->email}: {$otp->code}");
        }
    }
}
