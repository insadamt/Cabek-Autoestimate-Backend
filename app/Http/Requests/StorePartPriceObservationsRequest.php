<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePartPriceObservationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxYear = now()->year + 1;

        return [
            'observations' => ['required', 'array', 'min:1', 'max:500'],
            'observations.*' => [
                'required',
                'array:marque,model,part,production_from,production_to,type,price,source,source_reference,source_url,details,observed_at',
            ],
            'observations.*.marque' => ['required', 'string', 'max:100'],
            'observations.*.model' => ['required', 'string', 'max:150'],
            'observations.*.part' => ['required', 'string', 'max:255'],
            'observations.*.production_from' => ['nullable', 'integer', 'between:1900,'.$maxYear],
            'observations.*.production_to' => ['nullable', 'integer', 'between:1900,'.$maxYear],
            'observations.*.type' => ['required', 'string', Rule::in(['original', 'adaptable', 'occasion'])],
            'observations.*.price' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'observations.*.source' => ['required', 'string', 'max:150', 'exists:sources,libelle'],
            'observations.*.source_reference' => ['nullable', 'string', 'max:255'],
            'observations.*.source_url' => ['nullable', 'url', 'max:2048'],
            'observations.*.details' => ['nullable', 'string', 'max:5000'],
            'observations.*.observed_at' => ['required', 'date_format:Y-m-d H:i:s', 'before_or_equal:now'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                $extraTopLevelKeys = array_diff(array_keys($this->all()), ['observations']);

                if ($extraTopLevelKeys !== []) {
                    $validator->errors()->add(
                        'request',
                        'The JSON root may contain only the observations key.'
                    );
                }

                foreach ($this->input('observations', []) as $index => $observation) {
                    $from = $observation['production_from'] ?? null;
                    $to = $observation['production_to'] ?? null;

                    if ($from !== null && $to !== null && (int) $to < (int) $from) {
                        $validator->errors()->add(
                            "observations.$index.production_to",
                            'The production_to year must be greater than or equal to production_from.'
                        );
                    }
                }
            },
        ];
    }

    public function validatedObservations(): array
    {
        return $this->validated()['observations'];
    }
}
