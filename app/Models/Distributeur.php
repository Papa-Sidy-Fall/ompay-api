<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Distributeur extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'nom',
        'localisation',
        'solde',
        'actif',
    ];

    protected $casts = [
        'solde' => 'decimal:2',
        'actif' => 'boolean',
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

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function peutDeposer($montant)
    {
        return $this->actif && $this->solde >= $montant;
    }
}
