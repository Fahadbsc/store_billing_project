<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Jobs\SendOrderConfirmation;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private readonly PriceCalculator $calculator)
    {
    }

    /**
     * @param array{customer: array{email:string, name?:?string}, items: array<int, array{product_id:int, quantity:int}>, amount_paid?: mixed} $data
     */
    public function create(array $data): Order
    {
        // Same product listed twice? Merge into one line.
        $quantities = collect($data['items'])
            ->groupBy('product_id')
            ->map(fn ($rows) => (int) $rows->sum('quantity'));

        $order = DB::transaction(function () use ($data, $quantities) {
            // Row-level lock, ordered by id so concurrent multi-product orders
            // acquire locks in the same order (no deadlocks). The stock check
            // and deduction both happen while the lock is held.
            $products = Product::whereIn('id', $quantities->keys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $shortages = [];
            foreach ($quantities as $productId => $qty) {
                $product = $products[$productId];
                if ($product->stock < $qty) {
                    $shortages[] = [
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'requested' => $qty,
                        'available' => $product->stock,
                    ];
                }
            }
            if ($shortages) {
                throw new InsufficientStockException($shortages);
            }

            $lines = [];
            $subtotal = $tax = 0;
            foreach ($quantities as $productId => $qty) {
                $product = $products[$productId];
                $line = $this->calculator->line($product->price, $product->tax_percent, $qty);
                $lines[] = [$product, $qty, $line];
                $subtotal += $line['subtotal'];
                $tax += $line['tax'];
            }
            $grandTotal = $subtotal + $tax;

            $paid = $balance = null;
            if (isset($data['amount_paid']) && $data['amount_paid'] !== '') {
                $paid = $this->calculator->toPaise($data['amount_paid']);
                if ($paid < $grandTotal) {
                    throw ValidationException::withMessages([
                        'amount_paid' => 'Amount given is less than the grand total.',
                    ]);
                }
                $balance = $paid - $grandTotal;
            }

            $customer = Customer::createOrFirst(
                ['email' => $data['customer']['email']],
                ['name' => $data['customer']['name'] ?? ''],
            );

            $order = Order::create([
                'customer_id' => $customer->id,
                'subtotal' => $this->calculator->toDecimal($subtotal),
                'tax_total' => $this->calculator->toDecimal($tax),
                'grand_total' => $this->calculator->toDecimal($grandTotal),
                'amount_paid' => $paid === null ? null : $this->calculator->toDecimal($paid),
                'balance_returned' => $balance === null ? null : $this->calculator->toDecimal($balance),
            ]);

            foreach ($lines as [$product, $qty, $line]) {
                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $this->calculator->toDecimal($line['unit_price']),
                    'tax_percent' => $product->tax_percent,
                    'line_subtotal' => $this->calculator->toDecimal($line['subtotal']),
                    'line_tax' => $this->calculator->toDecimal($line['tax']),
                    'line_total' => $this->calculator->toDecimal($line['total']),
                ]);

                $product->decrement('stock', $qty);
            }

            return $order;
        });

        // Dispatched only after the transaction has committed.
        SendOrderConfirmation::dispatch($order)->afterCommit();

        return $order->load('customer', 'items.product');
    }
}
