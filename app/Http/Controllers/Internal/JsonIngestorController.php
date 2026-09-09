<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Jobs\PrepareObservationImport;
use App\Models\ObservationImport;
use App\Services\PartPriceObservationIngestor;
use App\Services\PartPriceObservationPayloadValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class JsonIngestorController extends Controller
{
    public function index(): View
    {
        return view('internal.ingest.index');
    }

    public function validateUpload(
        Request $request,
        PartPriceObservationPayloadValidator $payloadValidator,
    ): RedirectResponse {
        $this->validateUploadInput($request);

        if ($this->isZipUpload($request)) {
            return $this->queueZipImport($request, false);
        }

        $observations = $this->validatedObservationsFromUpload($request, $payloadValidator);

        return redirect()
            ->route('internal.ingest')
            ->withInput($request->except('json_file'))
            ->with('ingest_preview', [
                'received' => count($observations),
                'observations' => array_slice($observations, 0, 20),
                'truncated' => count($observations) > 20,
                'source' => $request->hasFile('json_file') ? 'file' : 'paste',
            ]);
    }

    public function ingestUpload(
        Request $request,
        PartPriceObservationIngestor $ingestor,
        PartPriceObservationPayloadValidator $payloadValidator,
    ): RedirectResponse {
        $this->validateUploadInput($request);

        if ($this->isZipUpload($request)) {
            return $this->queueZipImport($request, true);
        }

        $observations = $this->validatedObservationsFromUpload($request, $payloadValidator);
        $result = $ingestor->ingest($observations);

        return redirect()
            ->route('internal.ingest')
            ->with('ingest_result', $result);
    }

    private function validateUploadInput(Request $request): void
    {
        $request->validate([
            'json_file' => [
                'nullable',
                'file',
                'extensions:json,zip',
                'max:'.(int) config('observation_imports.max_archive_kb'),
            ],
            'json_payload' => ['nullable', 'string', 'max:5242880'],
        ]);

        if (! $request->hasFile('json_file') && ! is_string($request->input('json_payload'))) {
            throw ValidationException::withMessages([
                'json_payload' => 'Choose a JSON or ZIP file, or paste a JSON payload.',
            ]);
        }

        $file = $request->file('json_file');

        if (
            $file !== null
            && strtolower($file->getClientOriginalExtension()) === 'json'
            && $file->getSize() > (int) config('observation_imports.max_json_bytes')
        ) {
            throw ValidationException::withMessages([
                'json_file' => 'Individual JSON files may not exceed 5 MB.',
            ]);
        }
    }

    private function validatedObservationsFromUpload(
        Request $request,
        PartPriceObservationPayloadValidator $payloadValidator,
    ): array {
        $rawJson = null;
        $inputKey = 'json_payload';

        if ($request->hasFile('json_file')) {
            $rawJson = file_get_contents($request->file('json_file')->getRealPath());
            $inputKey = 'json_file';
        } elseif (is_string($request->input('json_payload'))) {
            $rawJson = $request->input('json_payload');
        }

        if (! is_string($rawJson)) {
            throw ValidationException::withMessages([
                $inputKey => 'The JSON payload could not be read.',
            ]);
        }

        return $payloadValidator->validateRawJson($rawJson, $inputKey);
    }

    private function isZipUpload(Request $request): bool
    {
        $file = $request->file('json_file');

        return $file !== null
            && strtolower($file->getClientOriginalExtension()) === 'zip';
    }

    private function queueZipImport(Request $request, bool $autoImport): RedirectResponse
    {
        $file = $request->file('json_file');

        if ($file === null) {
            throw ValidationException::withMessages([
                'json_file' => 'Choose a ZIP archive to queue.',
            ]);
        }

        $observationImport = ObservationImport::create([
            'original_filename' => substr($file->getClientOriginalName(), 0, 255),
            'status' => 'queued',
            'auto_import' => $autoImport,
        ]);

        $archivePath = $file->storeAs(
            'observation-imports/'.$observationImport->id,
            'archive.zip',
            'local',
        );

        if (! is_string($archivePath)) {
            $observationImport->delete();

            throw ValidationException::withMessages([
                'json_file' => 'The ZIP archive could not be stored for queued processing.',
            ]);
        }

        $observationImport->update(['archive_path' => $archivePath]);
        PrepareObservationImport::dispatch($observationImport->id);

        return redirect()->route('internal.imports.show', $observationImport);
    }
}
