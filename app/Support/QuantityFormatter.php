<?php

namespace App\Support;

class QuantityFormatter
{
    public static function format(float $quantity): float|int
    {
        $rounded = round($quantity, 2);

        if (abs($rounded - round($rounded)) < 0.001) {
            return (int) round($rounded);
        }

        return $rounded;
    }
}
