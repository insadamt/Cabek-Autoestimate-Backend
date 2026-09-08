<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartPriceObservation extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'model_id',
        'part_id',
        'source_id',
        'production_from',
        'production_to',
        'type',
        'price',
        'source_reference',
        'source_url',
        'details',
        'observed_at',
    ];

    protected $casts = [
        'production_from' => 'integer',
        'production_to' => 'integer',
        'price' => 'decimal:2',
        'observed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'model_id');
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class, 'part_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class, 'source_id');
    }
}
