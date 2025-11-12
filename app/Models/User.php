<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Str;
use BaconQrCode\Writer;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public $incrementing = true;
    protected $keyType = 'int';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'nom',
        'telephone',
        'pin',
        'statut',
        'qr_code',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'pin',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'pin' => 'hashed',
    ];

    public function getAuthIdentifier()
    {
        return $this->id;
    }

    public function getAuthIdentifierName()
    {
        return 'id';
    }

    public function getBalanceAttribute()
    {
        return $this->transactions()->sum('montant');
    }

    public function getSoldeAttribute()
    {
        return $this->getBalanceAttribute();
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'utilisateur_uuid', 'uuid');
    }

    /**
     * Générer un QR code basé sur le numéro de téléphone
     */
    public function generateQrCode()
    {
        try {
            // Utiliser BaconQrCode avec SVG (plus simple)
            $renderer = new ImageRenderer(
                new RendererStyle(400),
                new SvgImageBackEnd()
            );
            $writer = new Writer($renderer);
            $svgContent = $writer->writeString($this->telephone);

            // Convertir en base64
            $this->qr_code = base64_encode($svgContent);
            $this->save();

            return $this->qr_code;
        } catch (\Exception $e) {
            // En cas d'erreur, utiliser une approche alternative simple
            $this->qr_code = base64_encode('<svg>QR Code for: ' . $this->telephone . '</svg>');
            $this->save();
            return $this->qr_code;
        }
    }
}
