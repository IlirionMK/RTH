<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmailChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');
        $hash = $this->route('hash');

        return $user instanceof User
            && $user->pending_email !== null
            && is_string($hash)
            && hash_equals(sha1($user->pending_email), $hash);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
