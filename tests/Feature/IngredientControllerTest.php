<?php

use App\Models\Ingredient;
use App\Models\User;

describe('creating recipes', function () {
    it('creates an ingredient', closure: function () {
        $ingredientName = 'Cheese Cheese Cheese!';
        $ingredientUnit = 420;
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('ingredients.store'), [
                'name' => $ingredientName,
                'unit' => $ingredientUnit
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('ingredients', [
            'name' => $ingredientName,
            'unit' => $ingredientUnit,
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
});
