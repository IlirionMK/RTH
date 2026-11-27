<?php

namespace App\Http\Requests;

use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

#[QueryParameter('query', description: 'Part of the trip name or of the name or email address of its author.')]
class IndexAdminTripRequest extends FormRequest
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
            'query' => ['nullable', 'string', 'max:100'],
        ];
    }
}
