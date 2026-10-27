<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->query('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->query('email')))]);
        }
    }

    public function rules(): array
    {
        return ['email' => ['required', 'email:rfc']];
    }
}
