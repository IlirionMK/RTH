<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

#[QueryParameter('query', description: 'Part of the name or email address.')]
#[QueryParameter('role', description: 'Only accounts with this role.')]
#[QueryParameter('status', description: 'Only `active` or only `blocked` accounts.')]
class IndexAdminUserRequest extends FormRequest
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
            'role' => ['nullable', Rule::enum(Role::class)],
            'status' => ['nullable', Rule::in(['active', 'blocked'])],
        ];
    }
}
