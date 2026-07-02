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
                    'source_units' => [$unit],
                ]);

                continue;
            }

            $group = $groups->get($matchingGroupKey);
            $group['total_quantity'] += $this->convertQuantity($quantity, $unit, $group['unit'], $first->name);
            $group['source_units'][] = $unit;
            $groups->put($matchingGroupKey, $group);
        }

        return $groups->map(function (array $group) use ($first) {
            if (count(array_unique($group['source_units'])) > 1) {
                $display = UnitConverter::chooseDisplayUnit(
                    $group['total_quantity'],
                    $group['unit'],
                    $first->name,
                );

                $group['total_quantity'] = $display['quantity'];
                $group['unit'] = $display['unit'];
            } else {
                $group['total_quantity'] = round($group['total_quantity'], 2);
            }

            unset($group['source_units']);

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

        return UnitConverter::canConvert($fromUnit, $toUnit, $ingredientName);
    }

    private function convertQuantity(float $quantity, ?string $fromUnit, ?string $toUnit, string $ingredientName): float
    {
        if ($fromUnit === $toUnit) {
            return $quantity;
        }

        return $quantity * UnitConverter::determineConversionFactor($fromUnit, $toUnit, $ingredientName);
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
