<?php

namespace App\Http\Requests;

use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

#[QueryParameter('query', description: 'Part of the name, at least 2 letters. Without it the list shows all public profiles.')]
class IndexPublicProfileRequest extends FormRequest
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
            'query' => ['nullable', 'string', 'min:2', 'max:100'],
        ];
    }
}
