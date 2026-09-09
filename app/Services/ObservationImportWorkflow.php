<?php

namespace App\Services;

use App\Jobs\ProcessObservationImportFile;
use App\Jobs\ValidateObservationImportFile;
use App\Models\ObservationImport;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ObservationImportWorkflow
{
    public function dispatchNextValidation(ObservationImport $observationImport): void
    {
        $observationImport->refresh();

        if ($observationImport->status !== 'validating') {
            return;
        }

        $nextFile = $observationImport->files()
            ->where('status', 'pending_validation')
            ->orderBy('position')
            ->first();

        if ($nextFile !== null) {
            ValidateObservationImportFile::dispatch($nextFile->id);

            return;
        }

        if ($observationImport->files()->where('status', 'validating')->exists()) {
            return;
        }

        if ($observationImport->failed_files > 0) {
            Storage::disk('local')->deleteDirectory('observation-imports/'.$observationImport->id.'/files');

            $observationImport->update([
                'status' => 'failed',
                'error_message' => 'Validation failed for '.$observationImport->failed_files.' JSON file(s). Nothing was imported.',
                'finished_at' => now(),
            ]);

            return;
        }

        if ($observationImport->auto_import) {
            $this->startImport($observationImport);

            return;
        }

        $observationImport->update([
            'status' => 'ready',
            'error_message' => null,
        ]);
    }

    public function startImport(ObservationImport $observationImport): void
    {
        $observationImport->refresh();

        if ($observationImport->status === 'importing') {
            $this->dispatchNextImport($observationImport);

            return;
        }

        if (! in_array($observationImport->status, ['ready', 'validating'], true)) {
            throw new RuntimeException('This import is not ready to start.');
        }

        if (
            $observationImport->total_files < 1
            || $observationImport->validated_files !== $observationImport->total_files
            || $observationImport->failed_files > 0
        ) {
            throw new RuntimeException('All JSON files must pass validation before import can start.');
        }

        $observationImport->files()
            ->where('status', 'valid')
            ->update([
                'status' => 'queued',
                'started_at' => null,
                'finished_at' => null,
                'error_message' => null,
            ]);

        $observationImport->update([
            'status' => 'importing',
            'error_message' => null,
            'finished_at' => null,
        ]);

        $this->dispatchNextImport($observationImport);
    }

    public function dispatchNextImport(ObservationImport $observationImport): void
    {
        $observationImport->refresh();

        if ($observationImport->status !== 'importing') {
            return;
        }

        $nextFile = $observationImport->files()
            ->where('status', 'queued')
            ->orderBy('position')
            ->first();

        if ($nextFile !== null) {
            ProcessObservationImportFile::dispatch($nextFile->id);

            return;
        }

        if ($observationImport->files()->where('status', 'importing')->exists()) {
            return;
        }

        if ($observationImport->failed_files > 0) {
            $observationImport->update([
                'status' => 'failed',
                'finished_at' => now(),
            ]);

            return;
        }

        if ($observationImport->processed_files === $observationImport->total_files) {
            Storage::disk('local')->deleteDirectory('observation-imports/'.$observationImport->id.'/files');

            $observationImport->update([
                'status' => 'completed',
                'finished_at' => now(),
            ]);
        }
    }
}
