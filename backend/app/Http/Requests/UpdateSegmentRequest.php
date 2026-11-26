<?php

namespace App\Http\Requests;

use App\Enums\Transport;
use App\Models\Segment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateSegmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $segment = $this->route('segment');

        return $segment instanceof Segment && Gate::allows('update', $segment->trip);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'transport' => ['required', Rule::enum(Transport::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'transport' => __('transport'),
        ];
    }
}
