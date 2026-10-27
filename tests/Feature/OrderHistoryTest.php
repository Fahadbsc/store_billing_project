<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_only_that_customers_orders(): void
    {
        Queue::fake();
        $product = Product::factory()->create(['stock' => 50]);
        $item = [['product_id' => $product->id, 'quantity' => 1]];

        foreach (['a@example.com', 'a@example.com', 'b@example.com'] as $email) {
            $this->postJson('/api/orders', ['customer' => ['email' => $email, 'name' => 'X'], 'items' => $item])
                ->assertCreated();
        }

        $this->getJson('/api/orders?email=A@example.com')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.customer.email', 'a@example.com');
    }

    public function test_unknown_email_is_404_and_bad_email_is_422(): void
    {
        $this->getJson('/api/orders?email=nobody@example.com')->assertNotFound();
        $this->getJson('/api/orders?email=not-an-email')->assertStatus(422);
        $this->getJson('/api/orders')->assertStatus(422);
    }

    public function test_customer_with_no_orders_gets_empty_list(): void
    {
        Customer::factory()->create(['email' => 'empty@example.com']);

        $this->getJson('/api/orders?email=empty@example.com')->assertOk()->assertJsonCount(0, 'data');
    }
}
