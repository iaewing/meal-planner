<?php

use App\Models\Ingredient;
use App\Services\IngredientService;

it('deduplicates ingredients by normalized name', function () {
    $existing = Ingredient::factory()->create(['name' => 'tomato']);

    $ingredient = app(IngredientService::class)->findOrCreate('Tomatoes');

    expect($ingredient->id)->toBe($existing->id)
        ->and(Ingredient::query()->count())->toBe(1);
});

it('creates separate ingredients for distinct multi-word names', function () {
    Ingredient::factory()->create(['name' => 'tomato']);

    $ingredient = app(IngredientService::class)->findOrCreate('cherry tomatoes');

    expect($ingredient->name)->toBe('cherry tomatoes')
        ->and(Ingredient::query()->count())->toBe(2);
});

it('preserves recipe-specific wording in notes when deduplicating', function () {
    Ingredient::factory()->create(['name' => 'tomato']);
    $service = app(IngredientService::class);

    $ingredient = $service->findOrCreate('tomato');
    $notes = $service->mergeNotesForDisplay('tomatoes', $ingredient, 'diced');

    expect($notes)->toBe('tomatoes, diced');
});

it('deduplicates ingredients added through the controller', function () {
    $user = \App\Models\User::factory()->create();
    Ingredient::factory()->create(['name' => 'tomato']);

    $this->actingAs($user)
        ->postJson(route('ingredients.store'), [
            'name' => 'Tomatoes',
            'units' => [
                ['unit' => 'cup', 'is_default' => true],
            ],
        ])
        ->assertRedirect(route('ingredients.index'));

    expect(Ingredient::query()->count())->toBe(1);

    $this->assertDatabaseHas('ingredient_units', [
        'unit' => 'cup',
        'is_default' => true,
    ]);
});
