<?php

use App\Jobs\ImportRecipeFromUrl;
use App\Models\Ingredient;
use App\Models\IngredientUnit;
use App\Models\Recipe;
use App\Models\User;
use App\Services\RecipeImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

describe('creating recipes', function () {
    it('creates a recipe with steps', closure: function () {
        $recipeName = 'Cheese Cheese Cheese!';
        $recipeDescription = 'Great for friends';
        $servingCount = 420;
        $ingredientQuantity = 69;
        $ingredientModel = Ingredient::factory()->create();
        $secondIngredient = Ingredient::factory()->create();
        $recipeSteps = [
            'Crack the egg',
            'Cook the egg',
        ];
        $ingredientPayload = [
            [
                'ingredient_id' => $ingredientModel->id,
                'name' => $ingredientModel->name,
                'quantity' => $ingredientQuantity,
                'unit' => $ingredientModel->unit,
            ],
            [
                'ingredient_id' => $secondIngredient->id,
                'name' => $secondIngredient->name,
                'quantity' => $ingredientQuantity,
                'unit' => $secondIngredient->unit,
            ],
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
            'quantity' => $ingredientPayload[0]['quantity'],
            'unit' => $ingredientPayload[0]['unit'],
        ]);
        $this->assertDatabaseHas('recipe_ingredients', [
            'recipe_id' => $recipe->id,
            'ingredient_id' => $secondIngredient->id,
            'quantity' => $ingredientPayload[1]['quantity'],
            'unit' => $ingredientPayload[1]['unit'],
        ]);
        $this->assertDatabaseHas('recipe_steps', [
            'recipe_id' => $recipe->id,
            'instruction' => $recipeSteps[0],
            'order' => 0,
        ]);
        $this->assertDatabaseHas('recipe_steps', [
            'recipe_id' => $recipe->id,
            'instruction' => $recipeSteps[1],
            'order' => 1,
        ]);
    });

    it('creates a recipe with multiple images', closure: function () {
        Storage::fake('public');

        $ingredient = Ingredient::factory()->create();
        $payload = createRecipePayload(
            ingredient: [
                [
                    'ingredient_id' => $ingredient->id,
                    'name' => $ingredient->name,
                    'quantity' => 1,
                    'unit' => $ingredient->unit,
                ],
            ],
        );
        $payload['images'] = [
            UploadedFile::fake()->image('first.jpg'),
            UploadedFile::fake()->image('second.jpg'),
        ];

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('recipes.store'), $payload)
            ->assertRedirect();

        $recipe = Recipe::query()->where('name', $payload['name'])->firstOrFail();

        expect($recipe->images)->toHaveCount(2);
        expect($recipe->image_path)->toBe($recipe->images->first()->path);

        Storage::disk('public')->assertExists($recipe->images[0]->path);
        Storage::disk('public')->assertExists($recipe->images[1]->path);
    });

    it('stores the selected ingredient unit on recipe ingredients', closure: function () {
        $user = User::factory()->create();
        $ingredient = Ingredient::factory()->create(['name' => 'flour']);
        $unit = IngredientUnit::factory()->create([
            'ingredient_id' => $ingredient->id,
            'unit' => 'cup',
            'is_default' => true,
            'conversion_factor' => 1,
        ]);

        $payload = createRecipePayload(
            ingredient: [
                [
                    'ingredient_id' => $ingredient->id,
                    'name' => $ingredient->name,
                    'quantity' => 2,
                    'unit' => 'cup',
                ],
            ],
        );

        $this->actingAs($user)
            ->post(route('recipes.store'), $payload)
            ->assertRedirect();

        $recipe = Recipe::query()->where('name', $payload['name'])->firstOrFail();

        $this->assertDatabaseHas('recipe_ingredients', [
            'recipe_id' => $recipe->id,
            'ingredient_id' => $ingredient->id,
            'unit' => 'cup',
            'ingredient_unit_id' => $unit->id,
        ]);
    });
});

describe('editing recipes', function () {
    it('adds multiple images to a recipe', closure: function () {
        Storage::fake('public');

        $user = User::factory()->create();
        $recipe = Recipe::create([
            'user_id' => $user->id,
            'name' => 'Soup',
            'description' => 'Warm',
        ]);

        $this->actingAs($user)
            ->post(route('recipes.update', $recipe), [
                '_method' => 'PUT',
                'name' => $recipe->name,
                'description' => $recipe->description,
                'source_url' => null,
                'images' => [
                    UploadedFile::fake()->image('first.jpg'),
                    UploadedFile::fake()->image('second.jpg'),
                ],
            ])
            ->assertRedirect(route('recipes.show', $recipe));

        $recipe->refresh();

        expect($recipe->images)->toHaveCount(2);
        Storage::disk('public')->assertExists($recipe->images[0]->path);
        Storage::disk('public')->assertExists($recipe->images[1]->path);
    });

    it('updates the selected ingredient unit on recipe ingredients', closure: function () {
        $user = User::factory()->create();
        $ingredient = Ingredient::factory()->create(['name' => 'flour']);
        $cup = IngredientUnit::factory()->create([
            'ingredient_id' => $ingredient->id,
            'unit' => 'cup',
            'is_default' => true,
            'conversion_factor' => 1,
        ]);
        $tablespoon = IngredientUnit::factory()->create([
            'ingredient_id' => $ingredient->id,
            'unit' => 'tbsp',
            'is_default' => false,
            'conversion_factor' => 0.063,
        ]);
        $recipe = Recipe::create([
            'user_id' => $user->id,
            'name' => 'Pancakes',
            'description' => 'Breakfast',
        ]);
        $recipe->ingredients()->attach($ingredient, [
            'quantity' => 1,
            'unit' => 'cup',
            'ingredient_unit_id' => $cup->id,
        ]);

        $this->actingAs($user)
            ->post(route('recipes.update', $recipe), [
                '_method' => 'PUT',
                'name' => $recipe->name,
                'description' => $recipe->description,
                'source_url' => null,
                'ingredients' => [
                    [
                        'ingredient_id' => $ingredient->id,
                        'quantity' => 3,
                        'unit' => 'tbsp',
                        'notes' => '',
                    ],
                ],
            ])
            ->assertRedirect(route('recipes.show', $recipe));

        $this->assertDatabaseHas('recipe_ingredients', [
            'recipe_id' => $recipe->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => 3,
            'unit' => 'tbsp',
            'ingredient_unit_id' => $tablespoon->id,
        ]);
    });
});

describe('importing recipes', function () {
    it('queues URL imports', closure: function () {
        Queue::fake();

        $user = User::factory()->create();
        $url = 'https://example.com/recipes/soup';

        $this->actingAs($user)
            ->postJson(route('recipes.import-url'), ['url' => $url])
            ->assertAccepted()
            ->assertJson([
                'success' => true,
                'message' => 'Recipe import queued. It will appear in your recipes once the import finishes.',
            ]);

        Queue::assertPushed(ImportRecipeFromUrl::class, function (ImportRecipeFromUrl $job) use ($url, $user) {
            return $job->url === $url && $job->userId === $user->id;
        });
    });

    it('imports recipes from multiple uploaded images', closure: function () {
        Storage::fake('public');

        $user = User::factory()->create();
        $recipe = Recipe::create([
            'user_id' => $user->id,
            'name' => 'Scanned card',
        ]);

        $this->mock(RecipeImportService::class, function ($mock) use ($recipe, $user) {
            $mock->shouldReceive('importFromImage')
                ->once()
                ->withArgs(function (array $paths, int $userId) use ($user) {
                    return $userId === $user->id
                        && count($paths) === 2
                        && str_contains($paths[0], 'recipe-imports')
                        && str_contains($paths[1], 'recipe-imports');
                })
                ->andReturn($recipe);
        });

        $this->actingAs($user)
            ->post(route('recipes.import-image'), [
                'images' => [
                    UploadedFile::fake()->image('front.jpg'),
                    UploadedFile::fake()->image('back.jpg'),
                ],
            ])
            ->assertRedirect(route('recipes.edit', $recipe));
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
        'unit' => 'Whole',
    ],
    $steps = [
        'Eat the cheese',
        'Enjoy',
    ]
) {
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
            'ingredients' => $ingredient,
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
                'protein' => null,
            ],
        ];
}
