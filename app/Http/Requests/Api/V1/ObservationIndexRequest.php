<?php

namespace App\Http\Requests\Api\V1;

use App\Models\VehicleModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ObservationIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'marque_id' => ['nullable', 'integer', 'exists:marques,id'],
            'model_id' => ['nullable', 'integer', 'exists:models,id'],
            'part_id' => ['nullable', 'integer', 'exists:parts,id'],
            'type' => ['nullable', Rule::in(['original', 'adaptable', 'occasion'])],
            'year' => ['nullable', 'integer', 'between:1900,'.(now()->year + 1)],
            'source_id' => ['nullable', 'integer', 'exists:sources,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled(['marque_id', 'model_id'])) {
                return;
            }

            $belongsToMarque = VehicleModel::query()
                ->whereKey($this->integer('model_id'))
                ->where('marque_id', $this->integer('marque_id'))
                ->exists();

            if (! $belongsToMarque) {
                $validator->errors()->add(
                    'model_id',
                    'The selected model does not belong to the selected marque.',
                );
            }
        });
    }
}
