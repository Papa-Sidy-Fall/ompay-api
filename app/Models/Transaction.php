<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'utilisateur_uuid',
        'type',
        'montant',
        'destinataire_uuid',
        'description',
        'statut',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_uuid', 'uuid');
    }

    public function destinataire()
    {
        return $this->belongsTo(User::class, 'destinataire_uuid', 'uuid');
    }
}
