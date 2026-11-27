<?php

namespace App\Http\Requests;

use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

#[QueryParameter('query', description: 'Name of the place, at least 2 letters.')]
class IndexPlaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'min:2', 'max:100'],
        ];
    }
}
