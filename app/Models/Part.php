<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Part extends Model
{
    use HasFactory;

    protected $fillable = [
        'libelle',
    ];

    public function priceObservations(): HasMany
    {
        return $this->hasMany(PartPriceObservation::class, 'part_id');
    }
}
