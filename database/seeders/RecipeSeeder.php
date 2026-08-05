<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class RecipeSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('email', 'test@example.com')->first();

        if (! $user) {
            return;
        }

        foreach ($this->recipes() as $definition) {
            $recipe = Recipe::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'name' => $definition['name'],
                ],
                [
                    'description' => $definition['description'],
                    'servings' => $definition['servings'],
                    'rating' => $definition['rating'] ?? null,
                    'image_name' => $definition['image_name'] ?? null,
                ]
            );

            $ingredientSync = [];

            foreach ($definition['ingredients'] as $ingredientDefinition) {
                $ingredient = Ingredient::query()->firstOrCreate([
                    'name' => $ingredientDefinition['name'],
                ]);

                $ingredientSync[$ingredient->id] = [
                    'quantity' => $ingredientDefinition['quantity'],
                    'unit' => $ingredientDefinition['unit'],
                    'notes' => $ingredientDefinition['notes'] ?? '',
                ];
            }

            $recipe->ingredients()->sync($ingredientSync);

            $recipe->steps()->delete();

            foreach ($definition['steps'] as $order => $instruction) {
                $recipe->steps()->create([
                    'instruction' => $instruction,
                    'order' => $order,
                ]);
            }
        }
    }

    /**
     * @return list<array{name: string, description: string, servings: int, rating?: int, ingredients: list<array{name: string, quantity: float, unit: string, notes?: string}>, steps: list<string>}>
     */
    private function recipes(): array
    {
        return [
            [
                'name' => 'Scrambled Eggs',
                'description' => 'Quick stovetop eggs with butter.',
                'servings' => 2,
                'rating' => 4,
                'ingredients' => [
                    ['name' => 'eggs', 'quantity' => 4, 'unit' => ''],
                    ['name' => 'unsalted butter', 'quantity' => 2, 'unit' => 'tbsp'],
                    ['name' => 'salt', 'quantity' => 0.25, 'unit' => 'tsp'],
                ],
                'steps' => [
                    'Whisk eggs with salt.',
                    'Melt butter in a nonstick pan over medium-low heat.',
                    'Add eggs and stir gently until just set.',
                ],
                'image_name' => 'scrambled-eggs.jpg',
            ],
            [
                'name' => 'Buttermilk Pancakes',
                'description' => 'Fluffy weekend pancakes.',
                'servings' => 4,
                'rating' => 5,
                'ingredients' => [
                    ['name' => 'flour', 'quantity' => 2, 'unit' => 'cup'],
                    ['name' => 'eggs', 'quantity' => 2, 'unit' => ''],
                    ['name' => 'milk', 'quantity' => 1.5, 'unit' => 'cup'],
                    ['name' => 'unsalted butter', 'quantity' => 3, 'unit' => 'tbsp'],
                    ['name' => 'baking powder', 'quantity' => 2, 'unit' => 'tsp'],
                ],
                'steps' => [
                    'Whisk dry ingredients together.',
                    'Whisk wet ingredients, then combine with dry until just mixed.',
                    'Cook on a greased griddle until bubbles form, flip, and finish cooking.',
                ],
                'image_name' => 'buttermilk-pancakes.jpg',
            ],
            [
                'name' => 'Grilled Lemon Chicken',
                'description' => 'Simple weeknight chicken with olive oil and lemon.',
                'servings' => 4,
                'rating' => 4,
                'ingredients' => [
                    ['name' => 'chicken breast', 'quantity' => 1.5, 'unit' => 'lb'],
                    ['name' => 'olive oil', 'quantity' => 2, 'unit' => 'tbsp'],
                    ['name' => 'lemon', 'quantity' => 1, 'unit' => ''],
                    ['name' => 'salt', 'quantity' => 1, 'unit' => 'tsp'],
                ],
                'steps' => [
                    'Marinate chicken with olive oil, lemon juice, and salt for 20 minutes.',
                    'Grill over medium-high heat until cooked through.',
                    'Rest for 5 minutes before slicing.',
                ],
            ],
            [
                'name' => 'Garlic Butter Pasta',
                'description' => 'Pantry pasta with garlic and a light flour-thickened sauce.',
                'servings' => 4,
                'ingredients' => [
                    ['name' => 'pasta', 'quantity' => 12, 'unit' => 'oz'],
                    ['name' => 'garlic', 'quantity' => 4, 'unit' => 'clove'],
                    ['name' => 'olive oil', 'quantity' => 3, 'unit' => 'tbsp'],
                    ['name' => 'flour', 'quantity' => 3, 'unit' => 'tbsp'],
                    ['name' => 'unsalted butter', 'quantity' => 4, 'unit' => 'tbsp'],
                ],
                'steps' => [
                    'Boil pasta in salted water until al dente.',
                    'Saute garlic in olive oil and butter until fragrant.',
                    'Stir in flour, then loosen with pasta water and toss with cooked pasta.',
                ],
            ],
            [
                'name' => 'Overnight Oats',
                'description' => 'No-cook breakfast prepared the night before.',
                'servings' => 1,
                'rating' => 3,
                'ingredients' => [
                    ['name' => 'oats', 'quantity' => 0.5, 'unit' => 'cup'],
                    ['name' => 'milk', 'quantity' => 0.5, 'unit' => 'cup'],
                    ['name' => 'honey', 'quantity' => 1, 'unit' => 'tbsp'],
                ],
                'steps' => [
                    'Combine oats, milk, and honey in a jar.',
                    'Refrigerate overnight.',
                    'Stir and serve cold or warmed.',
                ],
            ],
        ];
    }
}
