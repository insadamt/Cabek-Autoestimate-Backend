<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\ObservationImport;
use App\Services\ObservationImportWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use RuntimeException;

class ObservationImportController extends Controller
{
    public function show(ObservationImport $observationImport): View
    {
        return view('internal.imports.show', [
            'import' => $observationImport,
        ]);
    }

    public function status(ObservationImport $observationImport): JsonResponse
    {
        $observationImport->refresh();

        $currentFile = $observationImport->files()
            ->whereIn('status', ['validating', 'importing'])
            ->orderBy('position')
            ->first();

        $anchor = $currentFile?->position
            ?? min(
                max($observationImport->processed_files + 1, $observationImport->validated_files + 1, 1),
                max($observationImport->total_files, 1),
            );

        $fromPosition = max(1, $anchor - 5);
        $toPosition = $anchor + 9;

        $files = $observationImport->files()
            ->whereBetween('position', [$fromPosition, $toPosition])
            ->orderBy('position')
            ->get()
            ->map(fn ($file): array => [
                'position' => $file->position,
                'filename' => $file->filename,
                'status' => $file->status,
                'observations' => $file->observation_count,
                'inserted' => $file->inserted_count,
                'duplicates' => $file->duplicate_count,
                'error' => $file->error_message,
            ])
            ->values();

        $failedFiles = $observationImport->files()
            ->where('status', 'failed')
            ->orderBy('position')
            ->limit(20)
            ->get(['position', 'filename', 'error_message'])
            ->map(fn ($file): array => [
                'position' => $file->position,
                'filename' => $file->filename,
                'error' => $file->error_message,
            ])
            ->values();

        [$phase, $progress] = $this->phaseAndProgress($observationImport);

        return response()->json([
            'id' => $observationImport->id,
            'status' => $observationImport->status,
            'phase' => $phase,
            'progress' => $progress,
            'total_files' => $observationImport->total_files,
            'validated_files' => $observationImport->validated_files,
            'processed_files' => $observationImport->processed_files,
            'total_observations' => $observationImport->total_observations,
            'processed_observations' => $observationImport->processed_observations,
            'inserted' => $observationImport->inserted_observations,
            'duplicates' => $observationImport->duplicate_observations,
            'failed_files' => $observationImport->failed_files,
            'current_file' => $currentFile?->filename,
            'error' => $observationImport->error_message,
            'can_start' => $observationImport->status === 'ready',
            'files' => $files,
            'failed_file_details' => $failedFiles,
        ]);
    }

    public function start(
        ObservationImport $observationImport,
        ObservationImportWorkflow $workflow,
    ): RedirectResponse {
        try {
            $workflow->startImport($observationImport);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('internal.imports.show', $observationImport)
                ->withErrors(['import' => $exception->getMessage()]);
        }

        return redirect()->route('internal.imports.show', $observationImport);
    }

    private function phaseAndProgress(ObservationImport $observationImport): array
    {
        if ($observationImport->status === 'completed') {
            return ['Completed', 100];
        }

        if ($observationImport->status === 'ready') {
            return ['Validation complete', 100];
        }

        if ($observationImport->status === 'importing') {
            return ['Importing JSON files', $this->percentage($observationImport->processed_files, $observationImport->total_files)];
        }

        if ($observationImport->status === 'validating') {
            return ['Validating JSON files', $this->percentage($observationImport->validated_files, $observationImport->total_files)];
        }

        if ($observationImport->status === 'failed') {
            $completedSteps = $observationImport->processed_files > 0
                ? $observationImport->processed_files
                : $observationImport->validated_files;

            return ['Failed', $this->percentage($completedSteps, $observationImport->total_files)];
        }

        return ['Preparing archive', 0];
    }

    private function percentage(int $completed, int $total): int
    {
        if ($total < 1) {
            return 0;
        }

        return min((int) floor(($completed / $total) * 100), 100);
    }
}
