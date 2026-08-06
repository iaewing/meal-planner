<?php

use App\Models\Ingredient;
use App\Models\Recipe;

it('normalizes ingredients when the only entry is mangled', function (string $discombobulatedIngredient, string $normalizedIngredient) {
    Ingredient::factory()->create(['name' => $discombobulatedIngredient]);
    $this->artisan('ingredients:normalize');

    $normalizedIngredientModel = Ingredient::query()->where('name', $normalizedIngredient)->first();
    expect($normalizedIngredientModel->name)->toEqual($normalizedIngredient)
        ->and(Ingredient::all())->toHaveCount(1);
})->with([
    ['1 cup all-purpose flour', 'allpurpose flour'],
    ['2 tbsp sugar', 'sugar'],
    ['1/2 teaspoon salt', 'salt'],
    ['2 large eggs', 'large egg'],
    ['1 (14.5 ounce) can diced tomatoes', 'diced tomato'],
    ['1/4 cup chopped fresh parsley', 'parsley'],
    ['2 cloves garlic, minced', 'cloves garlic'],
    ['1/2 cup (1 stick) unsalted butter', 'unsalted butter'],
    ['1 pound ground beef', 'ground beef'],
    ['1/2 cup milk', 'milk'],
    ['1/4 teaspoon black pepper', 'black pepper'],
    ['1/2 teaspoon vanilla extract', 'vanilla extract'],
    ['bacon, cut into ½-inch pieces', 'bacon'],
    ['butter (optional)', 'butter'],
    ['chopped fresh parsley (for garnish)', 'parsley'],
    ['¾ cup uncooked white rice', 'white rice'],
    ['½ cups cold water', 'water'],
    ['¼ teaspoon salt', 'salt'],
    ['⅔ cup golden raisins', 'golden raisin'],
    ['⅓ cup white sugar', 'white sugar'],
]);

it('normalizes ingredients when there is an existing normalized corresponding ingredient', function (string $discombobulatedIngredient, string $normalizedIngredient) {
    $normalizedIngredientModel = Ingredient::factory()->create(['name' => $normalizedIngredient]);
    $mangledIngredient = Ingredient::factory()->create(['name' => $discombobulatedIngredient]);
    $recipe = Recipe::factory()->create();

    $recipe->ingredients()->attach($mangledIngredient->id, [
        'quantity' => 1,
        'unit' => 'cup',
        'notes' => 'some notes',
    ]);
    $this->artisan('ingredients:normalize');

    expect($normalizedIngredientModel->name)->toEqual($normalizedIngredient)
        ->and(Ingredient::all())->toHaveCount(1)
        ->and($recipe->fresh()->ingredients()->count())->toEqual(1)
        ->and($recipe->ingredients()->first()->name)->toEqual($normalizedIngredient)
        ->and(Recipe::all())->toHaveCount(1);
})->with([
    ['1 cup all-purpose flour', 'allpurpose flour'],
    ['2 tbsp sugar', 'sugar'],
    ['1/2 teaspoon salt', 'salt'],
    ['2 large eggs', 'large egg'],
    ['1 (14.5 ounce) can diced tomatoes', 'diced tomato'],
    ['1/4 cup chopped fresh parsley', 'parsley'],
    ['2 cloves garlic, minced', 'cloves garlic'],
    ['1/2 cup (1 stick) unsalted butter', 'unsalted butter'],
    ['1 pound ground beef', 'ground beef'],
    ['1/2 cup milk', 'milk'],
    ['1/4 teaspoon black pepper', 'black pepper'],
    ['1/2 teaspoon vanilla extract', 'vanilla extract'],
    ['bacon, cut into ½-inch pieces', 'bacon'],
    ['butter (optional)', 'butter'],
    ['chopped fresh parsley (for garnish)', 'parsley'],
    ['¾ cup uncooked white rice', 'white rice'],
    ['½ cups cold water', 'water'],
    ['¼ teaspoon salt', 'salt'],
    ['⅔ cup golden raisins', 'golden raisin'],
    ['⅓ cup white sugar', 'white sugar'],
]);