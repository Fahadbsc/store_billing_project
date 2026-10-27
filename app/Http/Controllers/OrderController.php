<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderHistoryRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Customer;
use App\Services\OrderService;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request, OrderService $orders)
    {
        $order = $orders->create($request->validated());

        return (new OrderResource($order))->response()->setStatusCode(201);
    }

    public function index(OrderHistoryRequest $request)
    {
        $customer = Customer::where('email', $request->validated('email'))->first();

        if (! $customer) {
            return response()->json(['message' => 'No customer found for that email.'], 404);
        }

        $orders = $customer->orders()
            ->with('customer', 'items.product')
            ->latest()->latest('id')
            ->paginate(15);

        return OrderResource::collection($orders);
    }
}
