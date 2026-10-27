<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        return ProductResource::collection(Product::orderBy('name')->get());
    }

    public function lowStock(Request $request)
    {
        $validated = $request->validate(['threshold' => ['nullable', 'integer', 'min:0']]);

        $threshold = (int) ($validated['threshold'] ?? config('inventory.low_stock_threshold'));

        return ProductResource::collection(Product::lowStock($threshold)->get())
            ->additional(['meta' => ['threshold' => $threshold]]);
    }
}
