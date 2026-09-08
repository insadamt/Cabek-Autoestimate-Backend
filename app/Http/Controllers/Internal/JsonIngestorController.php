<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePartPriceObservationsRequest;
use App\Services\PartPriceObservationIngestor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use JsonException;

class JsonIngestorController extends Controller
{
    public function index(): View
    {
        return view('internal.ingest.index');
    }

    public function validateUpload(Request $request): RedirectResponse
    {
        $observations = $this->validatedObservationsFromUpload($request);

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
    ): RedirectResponse {
        $observations = $this->validatedObservationsFromUpload($request);
        $result = $ingestor->ingest($observations);

        return redirect()
            ->route('internal.ingest')
            ->with('ingest_result', $result);
    }

    private function validatedObservationsFromUpload(Request $request): array
    {
        $request->validate([
            'json_file' => ['nullable', 'file', 'extensions:json', 'max:5120'],
            'json_payload' => ['nullable', 'string', 'max:5242880'],
        ]);

        $rawJson = null;
        $inputKey = 'json_payload';

        if ($request->hasFile('json_file')) {
            $rawJson = file_get_contents($request->file('json_file')->getRealPath());
            $inputKey = 'json_file';
        } elseif (is_string($request->input('json_payload'))) {
            $rawJson = $request->input('json_payload');
        }

        if (! is_string($rawJson) || trim($rawJson) === '') {
            throw ValidationException::withMessages([
                'json_payload' => 'Choose a JSON file or paste a JSON payload.',
            ]);
        }

        try {
            $payload = json_decode($rawJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw ValidationException::withMessages([
                $inputKey => 'Invalid JSON: '.$exception->getMessage(),
            ]);
        }

        if (! is_array($payload)) {
            throw ValidationException::withMessages([
                $inputKey => 'The JSON root must be an object containing the observations key.',
            ]);
        }

        $payloadRequest = new StorePartPriceObservationsRequest;
        $validator = Validator::make($payload, $payloadRequest->rules());

        $validator->after(
            fn ($validator) => StorePartPriceObservationsRequest::addPayloadValidationErrors($validator, $payload)
        );

        return $validator->validate()['observations'];
    }
}
