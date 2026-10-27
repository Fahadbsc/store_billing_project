<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Fixed items (mirrors the wireframe) so demos are predictable.
        $fixed = [
            ['COLGATE-100', 'Colgate Toothpaste', 50.00, 8, 40],
            ['PARLEG-50',   'Parle-G Biscuit',    10.00, 8, 120],
            ['BREAD-400',   'Bread',              35.00, 5, 4],
            ['MILK-1L',     'Milk 1L',            28.00, 0, 9],
            ['EGGS-12',     'Eggs (12)',          84.00, 0, 2],
        ];

        foreach ($fixed as [$code, $name, $price, $tax, $stock]) {
            Product::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'price' => $price, 'tax_percent' => $tax, 'stock' => $stock],
            );
        }

        Product::factory()->count(10)->create();

        Customer::updateOrCreate(['email' => 'thomas@example.com'], ['name' => 'Thomas']);
        Customer::factory()->count(5)->create();
    }
}
