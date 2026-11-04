<?php

use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;
use Carbon\Carbon;

describe('meal plan controller', function () {
    it('preserves duplicate recipes across multiple days or meals when updating a meal plan', function () {
        $user = User::factory()->create();
        $recipe = Recipe::create(['user_id' => $user->id, 'name' => 'Leftover Stew']);
        $mealPlan = MealPlan::create([
            'user_id' => $user->id,
            'name' => 'Weekly Plan',
            'start_date' => '2026-11-04',
            'end_date' => '2026-11-10',
        ]);

        $this->actingAs($user)
            ->put(route('meal-plans.update', $mealPlan), [
                'name' => 'Weekly Plan',
                'start_date' => '2026-11-04',
                'end_date' => '2026-11-10',
                'meals' => [
                    [
                        'recipe_id' => $recipe->id,
                        'planned_date' => '2026-11-04',
                        'meal_type' => 'dinner',
                    ],
                    [
                        'recipe_id' => $recipe->id,
                        'planned_date' => '2026-11-05',
                        'meal_type' => 'lunch',
                    ],
                ],
            ])
            ->assertRedirect(route('meal-plans.show', $mealPlan));

        $updatedRecipes = $mealPlan->fresh()->recipes;
        expect($updatedRecipes)->toHaveCount(2)
            ->and($updatedRecipes->pluck('pivot.planned_date')->all())->toBe(['2026-11-04', '2026-11-05'])
            ->and($updatedRecipes->pluck('pivot.meal_type')->all())->toBe(['dinner', 'lunch']);
    });

    it('serializes meal plan start_date and end_date as Y-m-d strings', function () {
        $user = User::factory()->create();
        $mealPlan = MealPlan::create([
            'user_id' => $user->id,
            'name' => 'Date Serialization Plan',
            'start_date' => '2026-11-04',
            'end_date' => '2026-11-10',
        ]);

        $serialized = $mealPlan->toArray();
        expect($serialized['start_date'])->toBe('2026-11-04')
            ->and($serialized['end_date'])->toBe('2026-11-10');
    });

    it('filters current and previous meal plans before pagination', function () {
        $user = User::factory()->create();

        for ($i = 0; $i < 12; $i++) {
            MealPlan::create([
                'user_id' => $user->id,
                'name' => "Past Plan {$i}",
                'start_date' => Carbon::now()->subWeeks($i + 2)->startOfWeek()->format('Y-m-d'),
                'end_date' => Carbon::now()->subWeeks($i + 2)->endOfWeek()->format('Y-m-d'),
            ]);
        }

        $currentPlan = MealPlan::create([
            'user_id' => $user->id,
            'name' => 'Active Current Plan',
            'start_date' => Carbon::now()->startOfWeek()->format('Y-m-d'),
            'end_date' => Carbon::now()->endOfWeek()->format('Y-m-d'),
        ]);
        $currentPlan->forceFill(['created_at' => Carbon::now()->subHour()])->save();

        $response = $this->actingAs($user)
            ->get(route('meal-plans.index'))
            ->assertOk();

        $props = $response->viewData('page')['props'];
        expect($props['mealPlans']['data'])->toHaveCount(1)
            ->and($props['mealPlans']['data'][0]['name'])->toBe('Active Current Plan')
            ->and($props['previousMealPlans']['data'])->toHaveCount(10)
            ->and($props['previousMealPlans']['total'])->toBe(12);
    });

    it('rejects another users recipe and dates outside the meal plan range on store and update', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ownRecipe = Recipe::create(['user_id' => $user->id, 'name' => 'Own Recipe']);
        $otherRecipe = Recipe::create(['user_id' => $otherUser->id, 'name' => 'Other Recipe']);

        $this->actingAs($user)
            ->post(route('meal-plans.store'), [
                'name' => 'Invalid Recipe Plan',
                'start_date' => '2026-11-04',
                'end_date' => '2026-11-10',
                'meals' => [
                    [
                        'recipe_id' => $otherRecipe->id,
                        'planned_date' => '2026-11-05',
                        'meal_type' => 'dinner',
                    ],
                ],
            ])
            ->assertSessionHasErrors('meals.0.recipe_id');

        $this->actingAs($user)
            ->post(route('meal-plans.store'), [
                'name' => 'Out of Range Plan',
                'start_date' => '2026-11-04',
                'end_date' => '2026-11-10',
                'meals' => [
                    [
                        'recipe_id' => $ownRecipe->id,
                        'planned_date' => '2026-11-12',
                        'meal_type' => 'dinner',
                    ],
                ],
            ])
            ->assertSessionHasErrors('meals.0.planned_date');

        $mealPlan = MealPlan::create([
            'user_id' => $user->id,
            'name' => 'Existing Plan',
            'start_date' => '2026-11-04',
            'end_date' => '2026-11-10',
        ]);

        $this->actingAs($user)
            ->put(route('meal-plans.update', $mealPlan), [
                'name' => 'Existing Plan',
                'start_date' => '2026-11-04',
                'end_date' => '2026-11-10',
                'meals' => [
                    [
                        'recipe_id' => $otherRecipe->id,
                        'planned_date' => '2026-11-03',
                        'meal_type' => 'dinner',
                    ],
                ],
            ])
            ->assertSessionHasErrors(['meals.0.recipe_id', 'meals.0.planned_date']);
    });
});
