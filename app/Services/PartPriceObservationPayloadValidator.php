<?php

namespace App\Services;

use App\Http\Requests\StorePartPriceObservationsRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JsonException;

class PartPriceObservationPayloadValidator
{
    public function validateRawJson(string $rawJson, string $inputKey = 'json_payload'): array
    {
        if (trim($rawJson) === '') {
            throw ValidationException::withMessages([
                $inputKey => 'The JSON file is empty.',
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
