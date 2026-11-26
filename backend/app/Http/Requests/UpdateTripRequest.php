<?php

namespace App\Http\Requests;

use App\Models\Trip;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('trip'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'starts_on' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'ends_on' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'starts_on' => __('start date'),
            'ends_on' => __('end date'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $trip = $this->route('trip');

        if ($trip instanceof Trip) {
            $this->mergeIfMissing([
                'starts_on' => $trip->starts_on->toDateString(),
                'ends_on' => $trip->ends_on?->toDateString(),
            ]);
        }
    }
}
