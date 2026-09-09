<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObservationImportFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'observation_import_id',
        'filename',
        'stored_path',
        'position',
        'status',
        'observation_count',
        'inserted_count',
        'duplicate_count',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'position' => 'integer',
        'observation_count' => 'integer',
        'inserted_count' => 'integer',
        'duplicate_count' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function observationImport(): BelongsTo
    {
        return $this->belongsTo(ObservationImport::class);
    }
}
