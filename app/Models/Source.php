<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    use HasFactory;

    protected $fillable = [
        'libelle',
        'url',
    ];

    public function priceObservations(): HasMany
    {
        return $this->hasMany(PartPriceObservation::class, 'source_id');
    }
}
