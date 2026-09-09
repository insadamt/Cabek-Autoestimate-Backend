<?php

namespace Database\Factories;

use App\Models\ObservationImport;
use Illuminate\Database\Eloquent\Factories\Factory;

class ObservationImportFactory extends Factory
{
    protected $model = ObservationImport::class;

    public function definition(): array
    {
        return [
            'original_filename' => 'observations.zip',
            'archive_path' => null,
            'status' => 'queued',
            'auto_import' => true,
        ];
    }
}
