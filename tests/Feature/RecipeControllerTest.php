<?php

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\User;

describe('creating recipes', function () {
    it('creates a recipe with steps', closure: function () {
        $recipeName = 'Cheese Cheese Cheese!';
        $recipeDescription = 'Great for friends';
        $servingCount = 420;
        $ingredientQuantity = 69;
        $ingredientModel = Ingredient::factory()->create();
        $recipeSteps = [
            'Crack the egg',
            'Cook the egg'
        ];
        $ingredientPayload = [
            'ingredient_id' => $ingredientModel->id,
            'name' => $ingredientModel->name,
            'quantity' => $ingredientQuantity,
            'unit' => $ingredientModel->unit,
        ];

        $payload = createRecipePayload(
            $recipeName,
            $recipeDescription,
            $servingCount,
            $ingredientPayload,
            $recipeSteps
        );
        $user = User::factory()->create();
        $this->actingAs($user)
            ->postJson('/recipes', $payload)
            ->assertRedirect(route('recipes.show', '1'));

        $this->assertDatabaseHas('recipes', [
            'user_id' => $user->id,
            'name' => $recipeName,
            'description' => $recipeDescription,
            'servings' => $servingCount,
        ]);

        $recipe = Recipe::query()->where('name', $recipeName)->first();

        $this->assertDatabaseHas('recipe_ingredients', [
            'recipe_id' => $recipe->id,
            'ingredient_id' => $ingredientModel->id,
            'quantity' => $ingredientPayload['quantity'],
            'unit' => $ingredientPayload['unit'],
        ]);
        $this->assertDatabaseHas('recipe_steps', [
            'recipe_id' => $recipe->id,
            'instruction' => $recipeSteps[0],
            'order' => 0
        ]);
        $this->assertDatabaseHas('recipe_steps', [
            'recipe_id' => $recipe->id,
            'instruction' => $recipeSteps[1],
            'order' => 1
        ]);
    });
});

function createRecipePayload(
    $name = 'Cheese!',
    $description = 'Great for food',
    $servingCount = 42,
    $ingredient = [
        'id' => '42',
        'name' => 'Cheese',
        'quantity' => '1',
        'unit' => 'Whole'
    ],
    $steps = [
        'Eat the cheese',
        'Enjoy'
    ]
)
{
    return
        [
            'name' => $name,
            'description' => $description,
            'source_url' => null,
            'image' => null,
            'servings' => $servingCount,
            'prep_time' => null,
            'cook_time' => null,
            'total_time' => null,
            'ingredients' => [$ingredient],
            'steps' => $steps,
            'nutrition' => [
                'calories' => null,
                'fat' => null,
                'saturated_fat' => null,
                'cholesterol' => null,
                'sodium' => null,
                'carbohydrates' => null,
                'fiber' => null,
                'sugar' => null,
                'protein' => null
            ]
        ];
}