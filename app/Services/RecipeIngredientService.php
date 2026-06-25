<?php

namespace App\Services;

use App\Models\IngredientUnit;
use App\Models\Recipe;
use Illuminate\Support\Str;

class RecipeIngredientService
{
    public function attach(Recipe $recipe, int $ingredientId, array $data): void
    {
        $recipe->ingredients()->attach($ingredientId, $this->pivotData($ingredientId, $data));
    }

    public function sync(Recipe $recipe, array $ingredients): void
    {
        $recipe->ingredients()->sync(collect($ingredients)->mapWithKeys(function (array $ingredient) {
            return [
                $ingredient['ingredient_id'] => $this->pivotData($ingredient['ingredient_id'], $ingredient),
            ];
        }));
    }

    private function pivotData(int $ingredientId, array $data): array
    {
        $unit = $this->normalizeUnit($data['unit'] ?? null);

        return [
            'quantity' => $data['quantity'],
            'unit' => $unit,
            'ingredient_unit_id' => $this->resolveIngredientUnitId($ingredientId, $unit),
            'notes' => $data['notes'] ?? '',
        ];
    }

    private function resolveIngredientUnitId(int $ingredientId, ?string $unit): ?int
    {
        if (! $unit) {
            return null;
        }

        return IngredientUnit::query()
            ->where('ingredient_id', $ingredientId)
            ->where('unit', $unit)
            ->value('id');
    }

    private function normalizeUnit(?string $unit): ?string
    {
        if ($unit === null) {
            return null;
        }

        $unit = Str::lower(trim($unit));

        return $unit === '' ? null : $unit;
    }
}
