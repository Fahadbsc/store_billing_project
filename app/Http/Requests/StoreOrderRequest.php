<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $customer = $this->input('customer');
        if (is_array($customer) && isset($customer['email']) && is_string($customer['email'])) {
            $customer['email'] = mb_strtolower(trim($customer['email']));
            $this->merge(['customer' => $customer]);
        }
    }

    public function rules(): array
    {
        $email = $this->input('customer.email');
        $isNewCustomer = is_string($email) && ! Customer::where('email', $email)->exists();

        return [
            'customer' => ['required', 'array'],
            'customer.email' => ['required', 'email:rfc', 'max:255'],
            // Name is only mandatory when the email is not already on file.
            'customer.name' => [Rule::requiredIf($isNewCustomer), 'nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
