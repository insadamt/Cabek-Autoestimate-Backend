<?php

namespace App\Jobs;

use App\Models\ObservationImport;
use App\Services\ObservationImportArchivePreparer;
use App\Services\ObservationImportWorkflow;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PrepareObservationImport implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 600;

    public int $backoff = 15;

    public function __construct(public int $observationImportId) {}

    public function handle(
        ObservationImportArchivePreparer $archivePreparer,
        ObservationImportWorkflow $workflow,
    ): void {
        $observationImport = ObservationImport::findOrFail($this->observationImportId);

        if ($observationImport->status === 'validating') {
            $workflow->dispatchNextValidation($observationImport);

            return;
        }

        if ($observationImport->status === 'importing') {
            $workflow->dispatchNextImport($observationImport);

            return;
        }

        if (! in_array($observationImport->status, ['queued', 'preparing'], true)) {
            return;
        }

        $observationImport->update([
            'status' => 'preparing',
            'started_at' => $observationImport->started_at ?? now(),
            'finished_at' => null,
            'error_message' => null,
        ]);

        $archivePreparer->prepare($observationImport);

        $observationImport->update(['status' => 'validating']);
        $workflow->dispatchNextValidation($observationImport);
    }

    public function failed(?Throwable $exception): void
    {
        $observationImport = ObservationImport::find($this->observationImportId);

        if ($observationImport === null) {
            return;
        }

        if (is_string($observationImport->archive_path)) {
            Storage::disk('local')->delete($observationImport->archive_path);
        }

        $observationImport->update([
            'archive_path' => null,
            'status' => 'failed',
            'error_message' => $exception?->getMessage() ?? 'The ZIP archive could not be prepared.',
            'finished_at' => now(),
        ]);
    }
}
