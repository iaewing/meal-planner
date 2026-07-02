<?php

use App\Models\Ingredient;
use App\Models\IngredientUnit;
use App\Models\User;

describe('managing ingredients', function () {
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
            IngredientUnit::factory()->create([
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
            IngredientUnit::factory()->create([
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
            IngredientUnit::factory()->create([
                'ingredient_id' => $baseIngredient->id,
                'unit' => 'ml',
                'conversion_factor' => 1,
                'is_default' => true
            ]);
            IngredientUnit::factory()->create([
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

        it('stores a null conversion factor for incompatible units', closure: function () {
            $ingredientName = 'flour';
            $user = User::factory()->create();

            $baseIngredient = Ingredient::factory()->create([
                'name' => $ingredientName,
            ]);
            IngredientUnit::factory()->create([
                'ingredient_id' => $baseIngredient->id,
                'unit' => 'cup',
                'conversion_factor' => 1,
                'is_default' => true,
            ]);

            $this->actingAs($user)
                ->postJson(route('ingredients.store'), [
                    'name' => $ingredientName,
                    'units' => [
                        ['unit' => 'slice', 'is_default' => false],
                    ],
                ])
                ->assertRedirect(route('ingredients.index'));

            $this->assertDatabaseHas('ingredient_units', [
                'ingredient_id' => $baseIngredient->id,
                'unit' => 'slice',
                'conversion_factor' => null,
                'is_default' => false,
            ]);
        });
    });
});

describe('adding units', function () {
    it('adds a compatible unit to an existing ingredient', closure: function () {
        $user = User::factory()->create();
        $ingredient = Ingredient::factory()->create(['name' => 'flour']);
        IngredientUnit::factory()->create([
            'ingredient_id' => $ingredient->id,
            'unit' => 'cup',
            'conversion_factor' => 1,
            'is_default' => true,
        ]);

        $this->actingAs($user)
            ->post(route('ingredients.add-unit', $ingredient), [
                'unit' => 'tbsp',
                'is_default' => false,
            ])
            ->assertRedirect(route('ingredients.index'));

        $this->assertDatabaseHas('ingredient_units', [
            'ingredient_id' => $ingredient->id,
            'unit' => 'tbsp',
            'conversion_factor' => 15.997,
            'is_default' => false,
        ]);
    });

    it('changes the default unit via addUnit on an existing unit', closure: function () {
        $user = User::factory()->create();
        $ingredient = Ingredient::factory()->create(['name' => 'flour']);
        IngredientUnit::factory()->create([
            'ingredient_id' => $ingredient->id,
            'unit' => 'tsp',
            'conversion_factor' => 1,
            'is_default' => true,
        ]);
        IngredientUnit::factory()->create([
            'ingredient_id' => $ingredient->id,
            'unit' => 'tbsp',
            'conversion_factor' => 0.333,
            'is_default' => false,
        ]);

        $this->actingAs($user)
            ->post(route('ingredients.add-unit', $ingredient), [
                'unit' => 'tbsp',
                'is_default' => true,
            ])
            ->assertRedirect(route('ingredients.index'));

        $this->assertDatabaseHas('ingredient_units', [
            'ingredient_id' => $ingredient->id,
            'unit' => 'tbsp',
            'conversion_factor' => 1.0,
            'is_default' => true,
        ]);

        $this->assertDatabaseHas('ingredient_units', [
            'ingredient_id' => $ingredient->id,
            'unit' => 'tsp',
            'conversion_factor' => 3.0,
            'is_default' => false,
        ]);
    });

    it('stores a null conversion factor for incompatible units', closure: function () {
        $user = User::factory()->create();
        $ingredient = Ingredient::factory()->create(['name' => 'flour']);
        IngredientUnit::factory()->create([
            'ingredient_id' => $ingredient->id,
            'unit' => 'cup',
            'conversion_factor' => 1,
            'is_default' => true,
        ]);

        $this->actingAs($user)
            ->post(route('ingredients.add-unit', $ingredient), [
                'unit' => 'slice',
                'is_default' => false,
            ])
            ->assertRedirect(route('ingredients.index'));

        $this->assertDatabaseHas('ingredient_units', [
            'ingredient_id' => $ingredient->id,
            'unit' => 'slice',
            'conversion_factor' => null,
            'is_default' => false,
        ]);
    });
});
