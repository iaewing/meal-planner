<?php

namespace App\Services;

class RecipeScalingService
{
    protected $unitConversions = [
        'volume' => [
            'ml' => 1,
            'l' => 1000,
            'tsp' => 4.92892,
            'tbsp' => 14.7868,
            'cup' => 236.588,
            'fl oz' => 29.5735,
            'pint' => 473.176,
            'quart' => 946.353,
            'gallon' => 3785.41,
        ],
        'weight' => [
            'g' => 1,
            'kg' => 1000,
            'oz' => 28.3495,
            'lb' => 453.592,
        ],
    ];

    protected $commonUnitTypes = [
        'ml' => 'volume',
        'milliliter' => 'volume',
        'l' => 'volume',
        'liter' => 'volume',
        'tsp' => 'volume',
        'teaspoon' => 'volume',
        'tbsp' => 'volume',
        'tablespoon' => 'volume',
        'cup' => 'volume',
        'fl oz' => 'volume',
        'fluid ounce' => 'volume',
        'pint' => 'volume',
        'quart' => 'volume',
        'gallon' => 'volume',
        'g' => 'weight',
        'gram' => 'weight',
        'kg' => 'weight',
        'kilogram' => 'weight',
        'oz' => 'weight',
        'ounce' => 'weight',
        'lb' => 'weight',
        'pound' => 'weight',
    ];

    public function scaleRecipe(array $ingredients, float $scaleFactor): array
    {
        return array_map(function ($ingredient) use ($scaleFactor) {
            $ingredient['quantity'] *= $scaleFactor;
            return $this->optimizeUnit($ingredient);
        }, $ingredients);
    }

    public function convertUnit(float $quantity, string $fromUnit, string $toUnit): float
    {
        $fromUnit = $this->normalizeUnit($fromUnit);
        $toUnit = $this->normalizeUnit($toUnit);

        if (!isset($this->commonUnitTypes[$fromUnit]) || !isset($this->commonUnitTypes[$toUnit])) {
            throw new \InvalidArgumentException('Unsupported unit conversion');
        }

        $fromType = $this->commonUnitTypes[$fromUnit];
        $toType = $this->commonUnitTypes[$toUnit];

        if ($fromType !== $toType) {
            throw new \InvalidArgumentException('Cannot convert between different unit types');
        }

        $baseValue = $quantity * $this->unitConversions[$fromType][$fromUnit];
        return $baseValue / $this->unitConversions[$toType][$toUnit];
    }

    protected function optimizeUnit(array $ingredient): array
    {
        if (!$ingredient['unit']) {
            return $ingredient;
        }

        $unit = $this->normalizeUnit($ingredient['unit']);
        $type = $this->commonUnitTypes[$unit] ?? null;

        if (!$type) {
            return $ingredient;
        }

        $quantity = $ingredient['quantity'];
        $currentUnit = $unit;

        // Convert to the most appropriate unit
        foreach ($this->getUnitHierarchy($type) as $targetUnit => $threshold) {
            if ($quantity >= $threshold) {
                $newQuantity = $this->convertUnit($quantity, $currentUnit, $targetUnit);
                if ($this->isCleanNumber($newQuantity)) {
                    $ingredient['quantity'] = $newQuantity;
                    $ingredient['unit'] = $targetUnit;
                    break;
                }
            }
        }

        return $ingredient;
    }

    protected function getUnitHierarchy(string $type): array
    {
        if ($type === 'volume') {
            return [
                'gallon' => 3785,
                'quart' => 946,
                'pint' => 473,
                'cup' => 237,
                'fl oz' => 30,
                'tbsp' => 15,
                'tsp' => 5,
                'ml' => 1,
            ];
        }

        if ($type === 'weight') {
            return [
                'kg' => 1000,
                'lb' => 454,
                'oz' => 28,
                'g' => 1,
            ];
        }

        return [];
    }

    protected function normalizeUnit(string $unit): string
    {
        $unit = strtolower(trim($unit));
        
        $normalizations = [
            'cups' => 'cup',
            'tablespoons' => 'tbsp',
            'teaspoons' => 'tsp',
            'ounces' => 'oz',
            'pounds' => 'lb',
            'grams' => 'g',
            'kilograms' => 'kg',
            'milliliters' => 'ml',
            'liters' => 'l',
            // Add more normalizations as needed
        ];

        return $normalizations[$unit] ?? $unit;
    }

    protected function isCleanNumber(float $number): bool
    {
        // Consider a number "clean" if it has at most 2 decimal places
        // and is not too close to another common fraction
        $rounded = round($number, 2);
        return abs($number - $rounded) < 0.0001;
    }
}