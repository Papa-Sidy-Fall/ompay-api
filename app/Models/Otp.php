<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Otp extends Model
{
    use HasFactory;

    protected $fillable = [
        'telephone',
        'code',
        'type',
        'expire_at',
        'utilise',
    ];

    protected $casts = [
        'expire_at' => 'datetime',
        'utilise' => 'boolean',
    ];

    /**
     * Générer un code OTP aléatoire
     */
    public static function generateCode()
    {
        return str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Vérifier si le code OTP est expiré
     */
    public function isExpired()
    {
        return Carbon::now()->isAfter($this->expire_at);
    }

    /**
     * Marquer le code comme utilisé
     */
    public function markAsUsed()
    {
        $this->update(['utilise' => true]);
    }

    /**
     * Scope pour les codes non utilisés
     */
    public function scopeUnused($query)
    {
        return $query->where('utilise', false);
    }

    /**
     * Scope pour les codes non expirés
     */
    public function scopeNotExpired($query)
    {
        return $query->where('expire_at', '>', Carbon::now());
    }

    /**
     * Trouver un code OTP valide pour un téléphone et type
     */
    public static function findValidOtp($telephone, $code, $type)
    {
        return self::where('telephone', $telephone)
            ->where('code', $code)
            ->where('type', $type)
            ->unused()
            ->notExpired()
            ->first();
    }
}
