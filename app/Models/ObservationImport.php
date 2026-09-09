<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ObservationImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_filename',
        'archive_path',
        'status',
        'auto_import',
        'total_files',
        'validated_files',
        'processed_files',
        'total_observations',
        'processed_observations',
        'inserted_observations',
        'duplicate_observations',
        'failed_files',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'auto_import' => 'boolean',
        'total_files' => 'integer',
        'validated_files' => 'integer',
        'processed_files' => 'integer',
        'total_observations' => 'integer',
        'processed_observations' => 'integer',
        'inserted_observations' => 'integer',
        'duplicate_observations' => 'integer',
        'failed_files' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function files(): HasMany
    {
        return $this->hasMany(ObservationImportFile::class);
    }
}
