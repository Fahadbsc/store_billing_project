<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LowStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_uses_configured_threshold_and_is_strictly_below(): void
    {
        config(['inventory.low_stock_threshold' => 10]);
        Product::factory()->create(['name' => 'Low', 'stock' => 9]);
        Product::factory()->create(['name' => 'Edge', 'stock' => 10]);
        Product::factory()->create(['name' => 'High', 'stock' => 50]);

        $this->getJson('/api/products/low-stock')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Low')
            ->assertJsonPath('meta.threshold', 10);
    }

    public function test_threshold_can_be_overridden_per_request(): void
    {
        Product::factory()->create(['stock' => 20]);

        $this->getJson('/api/products/low-stock?threshold=30')->assertJsonCount(1, 'data');
        $this->getJson('/api/products/low-stock?threshold=-1')->assertStatus(422);
    }
}
