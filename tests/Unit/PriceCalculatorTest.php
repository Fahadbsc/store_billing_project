<?php

namespace Tests\Unit;

use App\Services\PriceCalculator;
use PHPUnit\Framework\TestCase;

class PriceCalculatorTest extends TestCase
{
    public function test_line_totals_include_tax(): void
    {
        $line = (new PriceCalculator())->line('50.00', '8.00', 2);

        $this->assertSame(10000, $line['subtotal']);
        $this->assertSame(800, $line['tax']);
        $this->assertSame(10800, $line['total']);
    }

    public function test_tax_rounds_half_up(): void
    {
        // 0.05 * 10% = 0.005 -> rounds up to 0.01
        $this->assertSame(1, (new PriceCalculator())->line('0.05', '10.00', 1)['tax']);
    }

    public function test_zero_tax(): void
    {
        $this->assertSame(0, (new PriceCalculator())->line('28.00', '0', 3)['tax']);
    }

    public function test_decimal_conversion(): void
    {
        $calc = new PriceCalculator();
        $this->assertSame('22.80', $calc->toDecimal(2280));
        $this->assertSame('0.05', $calc->toDecimal(5));
    }

    public function test_change_breakdown(): void
    {
        $this->assertSame(
            ['denominations' => [20 => 1, 2 => 1], 'remainder_paise' => 80],
            (new PriceCalculator())->change(2280)
        );
    }
}
