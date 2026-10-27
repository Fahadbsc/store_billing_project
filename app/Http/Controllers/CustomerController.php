<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderHistoryRequest;
use App\Models\Customer;

class CustomerController extends Controller
{
    /** Used by the billing screen to auto-fill the name when the email exists. */
    public function lookup(OrderHistoryRequest $request)
    {
        $customer = Customer::where('email', $request->validated('email'))->first();

        if (! $customer) {
            return response()->json(['message' => 'Customer not found.'], 404);
        }

        return response()->json(['data' => ['name' => $customer->name, 'email' => $customer->email]]);
    }
}
