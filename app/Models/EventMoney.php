<?php

namespace App\Models;

final class EventMoney
{
    public const PATTERN = '(?:[0-9]{1,9}|[0-9]{1,3}(?:\.[0-9]{3}){1,2})(?:,[0-9]{1,2})?';

    public static function cents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode(',', str_replace('.', '', $amount), 2), 2, '');
        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }

    public static function format(int $amount): string
    {
        return 'R$ '.number_format($amount / 100, 2, ',', '.');
    }
}
