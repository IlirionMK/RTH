<?php

namespace App\Http\Requests;

use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

#[QueryParameter('year', description: 'Only trips that started in this year.')]
class ShowStatisticsRequest extends FormRequest
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
            'year' => ['sometimes', 'integer', 'digits:4'],
        ];
    }
}
