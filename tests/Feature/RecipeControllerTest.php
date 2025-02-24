<?php

use App\Models\Ingredient;
use App\Models\User;

describe('creating recipes', function () {
    it('creates a recipe', closure: function () {
        $recipeName = 'Cheese Cheese Cheese!';
        $recipeDescription = 'Great for friends';
        $servingCount = 420;
        $ingredientQuantity = 69;
        $ingredientModel = Ingredient::factory()->create();
        $ingredientPayload = [
            'ingredient_id' => $ingredientModel->id,
            'name' => $ingredientModel->name,
            'quantity' => $ingredientQuantity,
            'unit' => $ingredientModel->unit,
        ];

        $payload = createRecipePayload($recipeName, $recipeDescription, $servingCount, $ingredientPayload);
        $user = User::factory()->create();
        $response = $this->actingAs($user)
            ->postJson('/recipes', $payload)
            ->assertSuccessful();

        $this->assertDatabaseHas('recipes', [
            'user_id' => $user->id,
            'name' => $recipeName,
            'description' => $recipeDescription,
            'servings' => $servingCount,
        ]);

        $this->assertDatabaseHas('recipe_ingredients', [
            'recipe_id' => '1',
            'ingredient_id' => $ingredientModel->id,
            'quantity' => $ingredientPayload['quantity'],
            'unit' => $ingredientPayload['unit'],
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
            'steps' => [
                'Eat the Cheese',
                'Enjoy'
            ],
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