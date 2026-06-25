<?php

use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;
use App\Services\GroceryListService;

it('combines duplicate recipe ingredients in the same unit', function () {
    $user = User::factory()->create();
    $ingredient = Ingredient::factory()->create(['name' => 'flour']);
    $recipe = createRecipeWithIngredient($user, $ingredient, 2, 'cup');
    $mealPlan = createMealPlan($user);

    $mealPlan->recipes()->attach($recipe, ['planned_date' => '2026-06-25', 'meal_type' => 'dinner']);
    $mealPlan->recipes()->attach($recipe, ['planned_date' => '2026-06-26', 'meal_type' => 'dinner']);

    $ingredients = app(GroceryListService::class)->ingredientsFor($mealPlan);

    expect($ingredients)->toHaveCount(1)
        ->and($ingredients->first())->toMatchArray([
            'id' => $ingredient->id,
            'name' => 'flour',
            'total_quantity' => 4.0,
            'unit' => 'cup',
        ]);
});

it('converts compatible units before summing grocery list quantities', function () {
    $user = User::factory()->create();
    $ingredient = Ingredient::factory()->create(['name' => 'flour']);
    $cupRecipe = createRecipeWithIngredient($user, $ingredient, 1, 'cup');
    $tablespoonRecipe = createRecipeWithIngredient($user, $ingredient, 3, 'tbsp');
    $mealPlan = createMealPlan($user);

    $mealPlan->recipes()->attach($cupRecipe, ['planned_date' => '2026-06-25', 'meal_type' => 'dinner']);
    $mealPlan->recipes()->attach($tablespoonRecipe, ['planned_date' => '2026-06-26', 'meal_type' => 'dinner']);

    $ingredients = app(GroceryListService::class)->ingredientsFor($mealPlan);

    expect($ingredients)->toHaveCount(1)
        ->and($ingredients->first())->toMatchArray([
            'id' => $ingredient->id,
            'name' => 'flour',
            'total_quantity' => 1.19,
            'unit' => 'cup',
        ]);
});

it('keeps incompatible units separate', function () {
    $user = User::factory()->create();
    $ingredient = Ingredient::factory()->create(['name' => 'flour']);
    $cupRecipe = createRecipeWithIngredient($user, $ingredient, 1, 'cup');
    $gramRecipe = createRecipeWithIngredient($user, $ingredient, 200, 'g');
    $mealPlan = createMealPlan($user);

    $mealPlan->recipes()->attach($cupRecipe, ['planned_date' => '2026-06-25', 'meal_type' => 'dinner']);
    $mealPlan->recipes()->attach($gramRecipe, ['planned_date' => '2026-06-26', 'meal_type' => 'dinner']);

    $ingredients = app(GroceryListService::class)->ingredientsFor($mealPlan);

    expect($ingredients)->toHaveCount(2)
        ->and($ingredients->pluck('unit')->all())->toBe(['cup', 'g'])
        ->and($ingredients->pluck('total_quantity')->all())->toBe([1.0, 200.0]);
});

function createMealPlan(User $user): MealPlan
{
    return MealPlan::create([
        'user_id' => $user->id,
        'name' => 'Week',
        'start_date' => '2026-06-25',
        'end_date' => '2026-06-30',
    ]);
}

function createRecipeWithIngredient(User $user, Ingredient $ingredient, float $quantity, string $unit): Recipe
{
    $recipe = Recipe::create([
        'user_id' => $user->id,
        'name' => fake()->words(2, true),
    ]);

    $recipe->ingredients()->attach($ingredient, [
        'quantity' => $quantity,
        'unit' => $unit,
    ]);

    return $recipe;
}
