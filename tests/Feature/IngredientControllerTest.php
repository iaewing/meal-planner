<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\IngredientUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;


//these tests need to be updated to account ofr ingredient controller changes
describe('creating recipes', function () {
    it('creates an ingredient with no existing matching name', closure: function () {
        $ingredientName = 'Cheese Cheese Cheese!';
        $ingredientUnit = 'tsp';
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('ingredients.store'), [
                'name' => $ingredientName,
                'units' => [
                    ['unit' => $ingredientUnit, 'is_default' => true]
                ]
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

    describe('existing ingredient', function () {
        it('creates a new non-default ingredient unit', closure: function () {
            $ingredientName = 'Beach Balls';
            $user = User::factory()->create();

            $baseIngredient = Ingredient::factory()->create([
                'name' => strtolower($ingredientName),
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
                    'units' => [
                        ['unit' => 'tbsp', 'is_default' => false]
                    ]
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

        it('creates a new ingredient unit that replaces the default unit', closure: function () {
            $ingredientName = 'Beach Balls';
            $user = User::factory()->create();

            $baseIngredient = Ingredient::factory()->create([
                'name' => strtolower($ingredientName),
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
                    'units' => [
                        ['unit' => 'tbsp', 'is_default' => true]
                    ]
                ])
                ->assertRedirect(route('ingredients.index'));

            $this->assertDatabaseCount('ingredients', 1);

            $this->assertDatabaseHas('ingredient_units', [
                'ingredient_id' => $baseIngredient->id,
                'unit' => 'tbsp',
                'conversion_factor' => 1.0,
                'is_default' => true
            ]);

            $this->assertDatabaseHas('ingredient_units', [
                'ingredient_id' => $baseIngredient->id,
                'unit' => 'tsp',
                'is_default' => false,
                'conversion_factor' => 3.0
            ]);
            
            $this->assertDatabaseCount('ingredient_units', 2);
        });

        it('edits existing ingredient unit conversion factors when a new default unit is created', closure: function () {
            $ingredientName = 'Beach Balls';
            $user = User::factory()->create();

            $baseIngredient = Ingredient::factory()->create([
                'name' => strtolower($ingredientName),
            ]);
            $baseIngredientUnit = IngredientUnit::factory()->create([
                'ingredient_id' => $baseIngredient->id,
                'unit' => 'ml',
                'conversion_factor' => 1,
                'is_default' => true
            ]);
            $secondExistingIngredientUnit = IngredientUnit::factory()->create([
                'ingredient_id' => $baseIngredient->id,
                'unit' => 'gallon',
                'conversion_factor' => 3785.41,
                'is_default' => false
            ]);

            $this->actingAs($user)
                ->postJson(route('ingredients.store'), [
                    'name' => $ingredientName,
                    'units' => [
                        ['unit' => 'tbsp', 'is_default' => true]
                    ]
                ])
                ->assertRedirect(route('ingredients.index'));

            $this->assertDatabaseCount('ingredients', 1);

            $this->assertDatabaseHas('ingredient_units', [
                'ingredient_id' => $baseIngredient->id,
                'unit' => 'tbsp',
                'conversion_factor' => 1.0,
                'is_default' => true
            ]);

            $this->assertDatabaseHas('ingredient_units', [
                'ingredient_id' => $baseIngredient->id,
                'unit' => 'ml',
                'is_default' => false,
                'conversion_factor' => 14.79
            ]);

            $this->assertDatabaseHas('ingredient_units', [
                'ingredient_id' => $baseIngredient->id,
                'unit' => 'gallon',
                'is_default' => false,
                'conversion_factor' => 0.004
            ]);
            
            $this->assertDatabaseCount('ingredient_units', 3);
        });
    });
});
