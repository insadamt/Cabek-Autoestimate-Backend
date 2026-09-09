<?php

namespace Database\Factories;

use App\Models\ObservationImport;
use App\Models\ObservationImportFile;
use Illuminate\Database\Eloquent\Factories\Factory;

class ObservationImportFileFactory extends Factory
{
    protected $model = ObservationImportFile::class;

    public function definition(): array
    {
        return [
            'observation_import_id' => ObservationImport::factory(),
            'filename' => 'batch_0001.json',
            'stored_path' => 'observation-imports/test/files/000001.json',
            'position' => 1,
            'status' => 'pending_validation',
        ];
    }
}
