<?php

namespace App\Jobs;

use App\Models\ObservationImportFile;
use App\Services\ObservationImportWorkflow;
use App\Services\PartPriceObservationIngestor;
use App\Services\PartPriceObservationPayloadValidator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessObservationImportFile implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    public int $backoff = 10;

    public function __construct(public int $observationImportFileId) {}

    public function handle(
        PartPriceObservationPayloadValidator $payloadValidator,
        PartPriceObservationIngestor $ingestor,
        ObservationImportWorkflow $workflow,
    ): void {
        $importFile = ObservationImportFile::with('observationImport')->findOrFail($this->observationImportFileId);

        if (! in_array($importFile->status, ['queued', 'importing'], true)) {
            return;
        }

        $importFile->update([
            'status' => 'importing',
            'started_at' => $importFile->started_at ?? now(),
            'error_message' => null,
        ]);

        try {
            $rawJson = Storage::disk('local')->get($importFile->stored_path);
            $observations = $payloadValidator->validateRawJson($rawJson, 'json_file');
            $result = $ingestor->ingest($observations);
        } catch (Throwable $exception) {
            $importFile->update(['status' => 'queued']);

            throw $exception;
        }

        $importFile->update([
            'status' => 'completed',
            'observation_count' => $result['received'],
            'inserted_count' => $result['inserted'],
            'duplicate_count' => $result['skipped_duplicates'],
            'finished_at' => now(),
        ]);

        Storage::disk('local')->delete($importFile->stored_path);

        $observationImport = $importFile->observationImport;
        $observationImport->processed_files++;
        $observationImport->processed_observations += $result['received'];
        $observationImport->inserted_observations += $result['inserted'];
        $observationImport->duplicate_observations += $result['skipped_duplicates'];
        $observationImport->save();

        $workflow->dispatchNextImport($observationImport);
    }

    public function failed(?Throwable $exception): void
    {
        $importFile = ObservationImportFile::with('observationImport')->find($this->observationImportFileId);

        if ($importFile === null || $importFile->status === 'completed') {
            return;
        }

        $importFile->update([
            'status' => 'failed',
            'error_message' => $exception?->getMessage() ?? 'The JSON file could not be imported.',
            'finished_at' => now(),
        ]);

        $observationImport = $importFile->observationImport;
        $observationImport->failed_files++;
        $observationImport->update([
            'status' => 'failed',
            'error_message' => 'Import stopped at '.$importFile->filename.'.',
            'finished_at' => now(),
        ]);
    }
}
