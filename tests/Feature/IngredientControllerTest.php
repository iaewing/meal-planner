<?php

use App\Models\Ingredient;
use App\Models\IngredientUnit;
use App\Models\User;

describe('creating recipes', function () {
    it('creates an ingredient with no existing matching name', closure: function () {
        $ingredientName = 'Cheese Cheese Cheese!';
        $ingredientUnit = 'tsp';
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('ingredients.store'), [
                'name' => $ingredientName,
                'unit' => $ingredientUnit
            ])
            ->assertRedirect(route('ingredients.index'));

        $ingredient = Ingredient::query()->where('name', strtolower($ingredientName))->first();

        $this->assertDatabaseHas('ingredients', [
            'name' => strtolower($ingredientName),
        ]);
        $this->assertDatabaseHas('ingredient_units', [
            'ingredient_id' => $ingredient->id,
            'unit' => $ingredientUnit,
            'is_default' => true
        ]);
    });

    it('handles missing fields', closure: function () {
        $ingredientName = 'Cheese Cheese Cheese!';
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('ingredients.store'), [
                'name' => $ingredientName,
            ])
            ->assertStatus(422);

        $this->assertDatabaseEmpty(Ingredient::class);
    });

    it('does not create an ingredient when there is an existing matching named ingredient but still creates a new ingredient unit' , closure: function () {
        $ingredientName = 'Beach Balls';
        $user = User::factory()->create();

        $baseIngredient = Ingredient::factory()->create([
            'name' => $ingredientName,
        ]);
        $baseIngredientUnit = IngredientUnit::factory()->create([
            'ingredient_id' => $baseIngredient->id,
            'unit' => 'tsp',
            'conversion_factor' => 1,
            'is_default' => true
        ]);

        $this->actingAs($user)
            ->postJson(route('ingredients.store'), [
                'name' => $ingredientName,
                'unit' => 'tbsp'
            ])
            ->assertRedirect(route('ingredients.index'));

        $this->assertDatabaseCount('ingredients', 1);

        $this->assertDatabaseHas('ingredient_units', [
            'ingredient_id' => $baseIngredient->id,
            'unit' => 'tbsp',
            'conversion_factor' => 0.333,
            'is_default' => false
        ]);
        $this->assertDatabaseCount('ingredient_units', 2);

    });
});
