<?php

namespace App\Services;

use App\Models\Recipe;
use App\Models\Ingredient;

class NutritionCalculationService
{
    protected $scalingService;

    public function __construct(RecipeScalingService $scalingService)
    {
        $this->scalingService = $scalingService;
    }

    public function calculateRecipeNutrition(Recipe $recipe): array
    {
        $totalNutrition = [
            'calories' => 0,
            'fat' => 0,
            'saturated_fat' => 0,
            'cholesterol' => 0,
            'sodium' => 0,
            'carbohydrates' => 0,
            'fiber' => 0,
            'sugar' => 0,
            'protein' => 0,
        ];

        foreach ($recipe->ingredients as $ingredient) {
            $nutrition = $this->calculateIngredientNutrition($ingredient, $ingredient->pivot);
            foreach ($totalNutrition as $key => &$value) {
                $value += $nutrition[$key] ?? 0;
            }
        }

        // Round all values to 1 decimal place
        return array_map(function ($value) {
            return round($value, 1);
        }, $totalNutrition);
    }

    protected function calculateIngredientNutrition($ingredient, $pivot): array
    {
        $nutrition = $ingredient->nutrition;
        if (!$nutrition) {
            return [];
        }

        try {
            // Convert recipe quantity to the same unit as nutrition serving
            $quantity = $this->scalingService->convertUnit(
                $pivot->quantity,
                $pivot->unit,
                $nutrition->serving_unit
            );

            // Calculate scaling factor based on serving size
            $scaleFactor = $quantity / $nutrition->serving_size;

            // Scale all nutrition values
            return array_map(function ($value) use ($scaleFactor) {
                return $value * $scaleFactor;
            }, $nutrition->getNutritionValues());

        } catch (\InvalidArgumentException $e) {
            // If units can't be converted, return empty nutrition
            return [];
        }
    }

    public function estimateNutritionFromCNF(Ingredient $ingredient): array
    {
        $canadianNutrientService = app(CanadianNutrientService::class);
        
        // Try to find a matching food in the CNF database
        $foodId = $canadianNutrientService->findBestMatch($ingredient->name);
        
        if (!$foodId) {
            return [
                'serving_size' => 100,
                'serving_unit' => 'g',
                'calories' => 0,
                'fat' => 0,
                'saturated_fat' => 0,
                'cholesterol' => 0,
                'sodium' => 0,
                'carbohydrates' => 0,
                'fiber' => 0,
                'sugar' => 0,
                'protein' => 0,
            ];
        }

        // Get nutrition data and update the ingredient
        $canadianNutrientService->updateIngredientNutrition($ingredient, $foodId);
        
        return $ingredient->fresh()->nutrition->toArray();
    }
}