<?php

namespace App\Services;

/**
 * All money maths is done in integer paise to avoid float drift.
 * Tax is rounded half-up per line, so line totals always add up to the bill.
 */
class PriceCalculator
{
    private const DENOMINATIONS = [500, 200, 100, 50, 20, 10, 5, 2, 1];

    public function toPaise(string|int|float $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    public function toDecimal(int $paise): string
    {
        return sprintf('%s%d.%02d', $paise < 0 ? '-' : '', intdiv(abs($paise), 100), abs($paise) % 100);
    }

    /** @return array{unit_price:int, subtotal:int, tax:int, total:int} (all in paise) */
    public function line(string|int|float $unitPrice, string|int|float $taxPercent, int $quantity): array
    {
        $unit = $this->toPaise($unitPrice);
        $subtotal = $unit * $quantity;
        $basisPoints = (int) round(((float) $taxPercent) * 100);
        $tax = intdiv($subtotal * $basisPoints + 5000, 10000); // half-up

        return [
            'unit_price' => $unit,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $subtotal + $tax,
        ];
    }

    /**
     * Greedy change breakdown for whole rupees; leftover paise reported separately.
     *
     * @return array{denominations: array<int,int>, remainder_paise: int}
     */
    public function change(int $balancePaise): array
    {
        $rupees = intdiv($balancePaise, 100);
        $breakdown = [];

        foreach (self::DENOMINATIONS as $note) {
            if ($rupees >= $note) {
                $breakdown[$note] = intdiv($rupees, $note);
                $rupees %= $note;
            }
        }

        return ['denominations' => $breakdown, 'remainder_paise' => $balancePaise % 100];
    }
}
