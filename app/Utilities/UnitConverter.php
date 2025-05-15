<?php

namespace App\Utilities;

class UnitConverter
{
    // Volume conversions (relative to milliliters)
    const VOLUME_CONVERSIONS = [
        'ml' => 1.0,
        'l' => 1000.0,
        'tsp' => 4.93,
        'tbsp' => 14.79,
        'cup' => 236.59,
        'floz' => 29.57,
        'pint' => 473.18,
        'quart' => 946.35,
        'gallon' => 3785.41,
    ];

    // Weight conversions (relative to grams)
    const WEIGHT_CONVERSIONS = [
        'g' => 1.0,
        'kg' => 1000.0,
        'mg' => 0.001,
        'oz' => 28.35,
        'lb' => 453.59,
    ];

    // For units like "slices", "pieces", "heads" that don't have standard conversions
    const CUSTOM_CONVERSIONS = [
        // Example for common ingredients - this would need to be expanded
        'garlic_powder' => [
            'tsp' => 1.0,
            'tbsp' => 3.0,
            'g' => 5.0,  // approximate: 1 tsp ≈ 5g
        ],
        'salt' => [
            'tsp' => 1.0,
            'tbsp' => 3.0,
            'g' => 6.0,
        ],
        'bacon' => [
            'slice' => 1.0,
            'oz' => 1.0,
            'lb' => 16.0,
        ],
    ];

    public static function determineConversionFactor(string $fromUnit, string $toUnit, ?string $ingredientName = null): float
    {
        $fromUnitLower = strtolower($fromUnit);
        $toUnitLower = strtolower($toUnit);

        if ($ingredientName) {
            $ingredientKey = strtolower(str_replace(' ', '_', $ingredientName));

            if (isset(self::CUSTOM_CONVERSIONS[$ingredientKey])) {
                try {
                    return self::getCustomConversion($fromUnitLower, $toUnitLower, $ingredientKey);
                } catch (\InvalidArgumentException $e) {
                    // If custom conversion fails, continue to generic conversions
                }
            }
        }
        $isFromVolume = isset(self::VOLUME_CONVERSIONS[$fromUnitLower]);
        $isToVolume = isset(self::VOLUME_CONVERSIONS[$toUnitLower]);

        if ($isFromVolume && $isToVolume) {
            return self::getVolumeConversion($fromUnitLower, $toUnitLower);
        }

        $isFromWeight = isset(self::WEIGHT_CONVERSIONS[$fromUnitLower]);
        $isToWeight = isset(self::WEIGHT_CONVERSIONS[$toUnitLower]);

        if ($isFromWeight && $isToWeight) {
            return self::getWeightConversion($fromUnitLower, $toUnitLower);
        }

            //TODO: Consider alternate behaviour for a bad conversion attempt
        return 1.0;
    }

    /**
     * Get conversion factor between two volume units
     */
    public static function getVolumeConversion(string $fromUnit, string $toUnit): float
    {
        if (!isset(self::VOLUME_CONVERSIONS[$fromUnit]) || !isset(self::VOLUME_CONVERSIONS[$toUnit])) {
            throw new \InvalidArgumentException("Unknown volume unit: $fromUnit or $toUnit");
        }

        // Convert from $fromUnit to base unit (ml), then to $toUnit
        return round(self::VOLUME_CONVERSIONS[$fromUnit] / self::VOLUME_CONVERSIONS[$toUnit], 3);
    }

    /**
     * Get conversion factor between two weight units
     */
    public static function getWeightConversion(string $fromUnit, string $toUnit): float
    {
        if (!isset(self::WEIGHT_CONVERSIONS[$fromUnit]) || !isset(self::WEIGHT_CONVERSIONS[$toUnit])) {
            throw new \InvalidArgumentException("Unknown weight unit: $fromUnit or $toUnit");
        }

        // Convert from $fromUnit to base unit (g), then to $toUnit
        return round(self::WEIGHT_CONVERSIONS[$fromUnit] / self::WEIGHT_CONVERSIONS[$toUnit], 3);
    }

    /**
     * Get conversion factor for a specific ingredient
     */
    public static function getCustomConversion(string $fromUnit, string $toUnit, string $ingredientKey, ): float
    {
        if (!isset(self::CUSTOM_CONVERSIONS[$ingredientKey])) {
            throw new \InvalidArgumentException("No custom conversions for: $ingredientKey");
        }

        $conversions = self::CUSTOM_CONVERSIONS[$ingredientKey];
        if (!isset($conversions[$fromUnit]) || !isset($conversions[$toUnit])) {
            throw new \InvalidArgumentException("Unknown unit for $ingredientKey: $fromUnit or $toUnit");
        }

        // Convert from $fromUnit to $toUnit using the custom conversions
        return round($conversions[$fromUnit] / $conversions[$toUnit], 3);
    }
}