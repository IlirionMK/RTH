<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminUserRequest extends FormRequest
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
            'role' => ['sometimes', Rule::enum(Role::class), Rule::prohibitedIf($this->changesOwnAccount(...))],
            'is_blocked' => ['sometimes', 'boolean', Rule::prohibitedIf($this->changesOwnAccount(...))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role.prohibited' => __('You cannot change your own role.'),
            'is_blocked.prohibited' => __('You cannot block your own account.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'is_blocked' => __('blocking'),
        ];
    }

    private function changesOwnAccount(): bool
    {
        $user = $this->route('user');

        return $user instanceof User && $user->is($this->user());
    }
}
