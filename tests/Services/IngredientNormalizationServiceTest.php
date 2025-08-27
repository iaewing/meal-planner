<?php

namespace Tests\Services;

use App\Services\IngredientNormalizationService;

it('normalizes ingredients', function (string $discombobulatedIngredient, string $normalizedIngredient) {
    $normalizationService = new IngredientNormalizationService;
    $result = $normalizationService->normalize($discombobulatedIngredient);

    expect($result)->toEqual($normalizedIngredient);
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
