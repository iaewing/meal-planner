<?php

namespace App\Services;

use App\Models\Recipe;
use Carbon\Carbon;

class MealPlanRandomizerService
{
    /**
     * Generate a random meal plan for the given date range and user
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param int $userId
     * @param array $mealTypes
     * @return array
     */
    public function generateRandomMealPlan(Carbon $startDate, Carbon $endDate, int $userId, array $mealTypes = ['breakfast', 'lunch', 'dinner']): array
    {
        $recipes = Recipe::query()
            ->where('user_id', $userId)
            ->get();

        if ($recipes->isEmpty()) {
            return [];
        }

        $meals = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            foreach ($mealTypes as $mealType) {
                // Get a random recipe
                $recipe = $recipes->random();
                
                $meals[] = [
                    'recipe_id' => $recipe->id,
                    'planned_date' => $currentDate->format('Y-m-d'),
                    'meal_type' => $mealType,
                ];
            }
            
            $currentDate->addDay();
        }

        return $meals;
    }
} 