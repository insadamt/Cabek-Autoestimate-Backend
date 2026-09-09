<?php

namespace App\Jobs;

use App\Models\ObservationImportFile;
use App\Services\ObservationImportWorkflow;
use App\Services\PartPriceObservationPayloadValidator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ValidateObservationImportFile implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public int $backoff = 10;

    public function __construct(public int $observationImportFileId) {}

    public function handle(
        PartPriceObservationPayloadValidator $payloadValidator,
        ObservationImportWorkflow $workflow,
    ): void {
        $importFile = ObservationImportFile::with('observationImport')->findOrFail($this->observationImportFileId);

        if (! in_array($importFile->status, ['pending_validation', 'validating'], true)) {
            return;
        }

        $importFile->update([
            'status' => 'validating',
            'started_at' => $importFile->started_at ?? now(),
            'error_message' => null,
        ]);

        try {
            $rawJson = Storage::disk('local')->get($importFile->stored_path);
            $observations = $payloadValidator->validateRawJson($rawJson, 'json_file');
        } catch (ValidationException $exception) {
            $importFile->update([
                'status' => 'failed',
                'error_message' => $this->validationMessage($exception),
                'finished_at' => now(),
            ]);

            $observationImport = $importFile->observationImport;
            $observationImport->validated_files++;
            $observationImport->failed_files++;
            $observationImport->save();

            $workflow->dispatchNextValidation($observationImport);

            return;
        } catch (Throwable $exception) {
            $importFile->update(['status' => 'pending_validation']);

            throw $exception;
        }

        $importFile->update([
            'status' => 'valid',
            'observation_count' => count($observations),
            'finished_at' => now(),
        ]);

        $observationImport = $importFile->observationImport;
        $observationImport->validated_files++;
        $observationImport->total_observations += count($observations);
        $observationImport->save();

        $workflow->dispatchNextValidation($observationImport);
    }

    public function failed(?Throwable $exception): void
    {
        $importFile = ObservationImportFile::with('observationImport')->find($this->observationImportFileId);

        if ($importFile === null || $importFile->status === 'valid') {
            return;
        }

        $importFile->update([
            'status' => 'failed',
            'error_message' => $exception?->getMessage() ?? 'The JSON file could not be validated.',
            'finished_at' => now(),
        ]);

        $observationImport = $importFile->observationImport;
        $observationImport->failed_files++;
        $observationImport->update([
            'status' => 'failed',
            'error_message' => 'Validation stopped because a queued job failed.',
            'finished_at' => now(),
        ]);
    }

    private function validationMessage(ValidationException $exception): string
    {
        $messages = [];

        foreach ($exception->errors() as $fieldMessages) {
            foreach ($fieldMessages as $message) {
                $messages[] = $message;

                if (count($messages) >= 10) {
                    break 2;
                }
            }
        }

        return implode(' ', $messages);
    }
}
