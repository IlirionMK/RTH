<?php

namespace App\Http\Requests;

use App\Models\Point;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdatePointRequest extends FormRequest
{
    public function authorize(): bool
    {
        $point = $this->route('point');

        return $point instanceof Point && Gate::allows('update', $point->trip);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'latitude' => ['sometimes', 'required', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'required', 'numeric', 'between:-180,180'],
            'position' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'latitude' => __('latitude'),
            'longitude' => __('longitude'),
            'position' => __('position'),
        ];
    }
}
