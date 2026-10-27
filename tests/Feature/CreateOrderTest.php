<?php

namespace Tests\Feature;

use App\Jobs\SendOrderConfirmation;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CreateOrderTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $items, array $extra = []): array
    {
        return array_merge([
            'customer' => ['email' => 'thomas@example.com', 'name' => 'Thomas'],
            'items' => $items,
        ], $extra);
    }

    public function test_creates_order_computes_totals_deducts_stock_and_queues_email(): void
    {
        Queue::fake();
        $colgate = Product::factory()->create(['price' => 50, 'tax_percent' => 8, 'stock' => 10]);
        $parle = Product::factory()->create(['price' => 10, 'tax_percent' => 8, 'stock' => 20]);

        $res = $this->postJson('/api/orders', $this->payload([
            ['product_id' => $colgate->id, 'quantity' => 2],
            ['product_id' => $parle->id, 'quantity' => 5],
        ], ['amount_paid' => 250]));

        $res->assertCreated()
            ->assertJsonPath('data.subtotal', '150.00')
            ->assertJsonPath('data.tax_total', '12.00')
            ->assertJsonPath('data.grand_total', '162.00')
            ->assertJsonPath('data.balance_returned', '88.00')
            ->assertJsonPath('data.items.0.line_total', '108.00')
            ->assertJsonPath('data.items.1.line_total', '54.00');

        $this->assertSame(8, $colgate->fresh()->stock);
        $this->assertSame(15, $parle->fresh()->stock);
        $this->assertDatabaseCount('order_items', 2);
        Queue::assertPushed(SendOrderConfirmation::class, 1);
    }

    public function test_insufficient_stock_is_rejected_and_nothing_changes(): void
    {
        Queue::fake();
        $ok = Product::factory()->create(['stock' => 10]);
        $short = Product::factory()->create(['stock' => 1]);

        $this->postJson('/api/orders', $this->payload([
            ['product_id' => $ok->id, 'quantity' => 3],
            ['product_id' => $short->id, 'quantity' => 2],
        ]))
            ->assertStatus(422)
            ->assertJsonPath('errors.items.0.product_id', $short->id)
            ->assertJsonPath('errors.items.0.available', 1);

        $this->assertSame(10, $ok->fresh()->stock);   // no partial deduction
        $this->assertSame(1, $short->fresh()->stock);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('customers', 0);   // customer creation rolled back too
        Queue::assertNothingPushed();
    }

    public function test_last_unit_goes_to_exactly_one_of_two_orders(): void
    {
        Queue::fake();
        $product = Product::factory()->create(['stock' => 1]);
        $items = [['product_id' => $product->id, 'quantity' => 1]];

        $first = $this->postJson('/api/orders', $this->payload($items));
        $second = $this->postJson('/api/orders', [
            'customer' => ['email' => 'other@example.com', 'name' => 'Other'],
            'items' => $items,
        ]);

        $this->assertSame([201, 422], [$first->status(), $second->status()]);
        $this->assertSame(0, $product->fresh()->stock);
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_duplicate_product_lines_are_merged_before_stock_check(): void
    {
        Queue::fake();
        $product = Product::factory()->create(['stock' => 3]);

        // 2 + 2 = 4 > 3: each line alone would pass, together they must not.
        $this->postJson('/api/orders', $this->payload([
            ['product_id' => $product->id, 'quantity' => 2],
            ['product_id' => $product->id, 'quantity' => 2],
        ]))->assertStatus(422);

        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_existing_customer_does_not_need_name_and_is_not_duplicated(): void
    {
        Queue::fake();
        Customer::factory()->create(['email' => 'thomas@example.com', 'name' => 'Thomas']);
        $product = Product::factory()->create(['stock' => 5]);

        $this->postJson('/api/orders', [
            'customer' => ['email' => 'THOMAS@example.com'],
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated()->assertJsonPath('data.customer.name', 'Thomas');

        $this->assertDatabaseCount('customers', 1);
    }

    public function test_new_customer_requires_name(): void
    {
        $product = Product::factory()->create(['stock' => 5]);

        $this->postJson('/api/orders', [
            'customer' => ['email' => 'new@example.com'],
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(422)->assertJsonValidationErrors('customer.name');
    }

    public function test_amount_paid_below_grand_total_is_rejected_and_rolled_back(): void
    {
        Queue::fake();
        $product = Product::factory()->create(['price' => 100, 'tax_percent' => 0, 'stock' => 5]);

        $this->postJson('/api/orders', $this->payload(
            [['product_id' => $product->id, 'quantity' => 1]],
            ['amount_paid' => 50],
        ))->assertStatus(422)->assertJsonValidationErrors('amount_paid');

        $this->assertSame(5, $product->fresh()->stock);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_validation_rules(): void
    {
        $this->postJson('/api/orders', [])->assertStatus(422);
        $this->postJson('/api/orders', $this->payload([]))->assertJsonValidationErrors('items');
        $this->postJson('/api/orders', $this->payload([['product_id' => 999, 'quantity' => 1]]))
            ->assertJsonValidationErrors('items.0.product_id');

        $product = Product::factory()->create(['stock' => 5]);
        $this->postJson('/api/orders', $this->payload([['product_id' => $product->id, 'quantity' => 0]]))
            ->assertJsonValidationErrors('items.0.quantity');
    }

    public function test_confirmation_job_logs_the_email(): void
    {
        Log::spy();
        $order = Order::create([
            'customer_id' => Customer::factory()->create()->id,
            'subtotal' => 10, 'tax_total' => 0, 'grand_total' => 10,
        ]);

        (new SendOrderConfirmation($order))->handle();

        Log::shouldHaveReceived('info')->once();
    }
}
