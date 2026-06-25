<?php

namespace App\Services;

use App\Models\MealPlan;
use App\Utilities\UnitConverter;
use Illuminate\Support\Collection;

class GroceryListService
{
    public function ingredientsFor(MealPlan $mealPlan): Collection
    {
        return $mealPlan->recipes()
            ->with('ingredients')
            ->get()
            ->flatMap(fn ($recipe) => $recipe->ingredients)
            ->groupBy('id')
            ->flatMap(fn (Collection $ingredients) => $this->aggregateIngredient($ingredients))
            ->values();
    }

    private function aggregateIngredient(Collection $ingredients): Collection
    {
        $first = $ingredients->first();
        $groups = collect();

        foreach ($ingredients as $ingredient) {
            $unit = $this->normalizeUnit($ingredient->pivot->unit);
            $quantity = (float) $ingredient->pivot->quantity;

            $matchingGroupKey = $this->findCompatibleGroupKey($groups, $unit, $first->name);

            if ($matchingGroupKey === null) {
                $groups->push([
                    'id' => $first->id,
                    'name' => $first->name,
                    'total_quantity' => $quantity,
                    'unit' => $unit,
                ]);

                continue;
            }

            $group = $groups->get($matchingGroupKey);
            $group['total_quantity'] += $this->convertQuantity($quantity, $unit, $group['unit'], $first->name);
            $groups->put($matchingGroupKey, $group);
        }

        return $groups->map(function (array $group) {
            $group['total_quantity'] = round($group['total_quantity'], 2);

            return $group;
        });
    }

    private function findCompatibleGroupKey(Collection $groups, ?string $unit, string $ingredientName): ?int
    {
        foreach ($groups as $key => $group) {
            if ($this->canConvert($unit, $group['unit'], $ingredientName)) {
                return $key;
            }
        }

        return null;
    }

    private function canConvert(?string $fromUnit, ?string $toUnit, string $ingredientName): bool
    {
        if ($fromUnit === $toUnit) {
            return true;
        }

        if (! $fromUnit || ! $toUnit) {
            return false;
        }

        return $this->isKnownConversion($fromUnit, $toUnit, $ingredientName);
    }

    private function convertQuantity(float $quantity, ?string $fromUnit, ?string $toUnit, string $ingredientName): float
    {
        if ($fromUnit === $toUnit) {
            return $quantity;
        }

        return $quantity * UnitConverter::determineConversionFactor($fromUnit, $toUnit, $ingredientName);
    }

    private function isKnownConversion(string $fromUnit, string $toUnit, string $ingredientName): bool
    {
        $fromUnit = strtolower($fromUnit);
        $toUnit = strtolower($toUnit);
        $ingredientKey = strtolower(str_replace(' ', '_', $ingredientName));

        if (
            isset(UnitConverter::CUSTOM_CONVERSIONS[$ingredientKey][$fromUnit])
            && isset(UnitConverter::CUSTOM_CONVERSIONS[$ingredientKey][$toUnit])
        ) {
            return true;
        }

        if (
            isset(UnitConverter::VOLUME_CONVERSIONS[$fromUnit])
            && isset(UnitConverter::VOLUME_CONVERSIONS[$toUnit])
        ) {
            return true;
        }

        return isset(UnitConverter::WEIGHT_CONVERSIONS[$fromUnit])
            && isset(UnitConverter::WEIGHT_CONVERSIONS[$toUnit]);
    }

    private function normalizeUnit(?string $unit): ?string
    {
        if ($unit === null) {
            return null;
        }

        $unit = strtolower(trim($unit));

        return $unit === '' ? null : $unit;
    }
}
