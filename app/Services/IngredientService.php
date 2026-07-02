<?php

namespace App\Services;

use App\Models\Ingredient;

class IngredientService
{
    public function __construct(private IngredientNormalizer $normalizer) {}

    public function findOrCreate(string $rawName): Ingredient
    {
        $normalizedName = $this->normalizer->normalize($rawName);

        $ingredient = Ingredient::query()
            ->where('normalized_name', $normalizedName)
            ->first();

        if ($ingredient) {
            return $ingredient;
        }

        return Ingredient::create([
            'name' => $this->normalizer->basicNormalize($rawName),
            'normalized_name' => $normalizedName,
        ]);
    }

    public function mergeNotesForDisplay(string $rawName, Ingredient $ingredient, ?string $notes): ?string
    {
        $basicName = $this->normalizer->basicNormalize($rawName);

        if ($basicName === $ingredient->name) {
            return $notes ?: null;
        }

        if ($notes) {
            return $basicName.', '.$notes;
        }

        return $basicName;
    }
}
