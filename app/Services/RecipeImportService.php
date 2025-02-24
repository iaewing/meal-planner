<?php

namespace App\Services;

use App\Models\Recipe;
use App\Models\Ingredient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;
use Illuminate\Support\Str;

class RecipeImportService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 10,
            'verify' => false
        ]);
    }

    public function importFromUrl(string $url, int $userId): Recipe
    {
        $response = $this->client->get($url);
        $html = (string) $response->getBody();
        $crawler = new Crawler($html);

        // Try to detect the recipe format
        if (str_contains($url, 'allrecipes.com')) {
            return $this->parseAllRecipes($crawler, $url, $userId);
        } elseif (str_contains($url, 'foodnetwork.com')) {
            return $this->parseFoodNetwork($crawler, $url, $userId);
        }

        // Generic JSON-LD parser as fallback
        return $this->parseJsonLd($crawler, $url, $userId);
    }

    protected function parseJsonLd(Crawler $crawler, string $url, int $userId): Recipe
    {
        $jsonLd = $crawler->filter('script[type="application/ld+json"]')->each(function ($node) {
            return json_decode($node->text(), true);
        });

        $recipeData = collect($jsonLd)->first(function ($item) {
            return isset($item['@type']) && $item['@type'] === 'Recipe';
        });

        if (!$recipeData) {
            throw new \Exception('No recipe data found');
        }

        $recipe = Recipe::create([
            'user_id' => $userId,
            'name' => $recipeData['name'],
            'description' => $recipeData['description'] ?? null,
            'source_url' => $url,
        ]);

        // Import ingredients
        foreach ($recipeData['recipeIngredient'] as $ingredientText) {
            $parsed = $this->parseIngredientText($ingredientText);
            
            $ingredient = Ingredient::firstOrCreate(
                ['name' => $parsed['name']],
                ['unit' => $parsed['unit']]
            );

            $recipe->ingredients()->attach($ingredient->id, [
                'quantity' => $parsed['quantity'],
                'unit' => $parsed['unit'],
                'notes' => $parsed['notes'],
            ]);
        }

        // Import steps
        foreach ($recipeData['recipeInstructions'] as $index => $instruction) {
            $text = is_array($instruction) ? ($instruction['text'] ?? $instruction['description']) : $instruction;
            
            $recipe->steps()->create([
                'instruction' => $text,
                'order' => $index + 1,
            ]);
        }

        // Import image if available
        if (isset($recipeData['image'])) {
            $imageUrl = is_array($recipeData['image']) ? $recipeData['image'][0] : $recipeData['image'];
            $this->downloadAndAttachImage($recipe, $imageUrl);
        }

        // Parse nutrition information if available
        if (isset($recipeData['nutrition'])) {
            $nutrition = [];
            $nutritionMap = [
                'calories' => 'calories',
                'fatContent' => 'fat',
                'saturatedFatContent' => 'saturated_fat',
                'cholesterolContent' => 'cholesterol',
                'sodiumContent' => 'sodium',
                'carbohydrateContent' => 'carbohydrates',
                'fiberContent' => 'fiber',
                'sugarContent' => 'sugar',
                'proteinContent' => 'protein',
            ];

            foreach ($nutritionMap as $jsonKey => $dbKey) {
                if (isset($recipeData['nutrition'][$jsonKey])) {
                    $value = $recipeData['nutrition'][$jsonKey];
                    // Extract numeric value from string (e.g., "240 calories" -> 240)
                    if (preg_match('/(\d+(?:\.\d+)?)/i', $value, $matches)) {
                        $nutrition[$dbKey] = floatval($matches[1]);
                    }
                }
            }

            if (!empty($nutrition)) {
                $recipe->nutrition = $nutrition;
            }
        }

        // Parse servings if available
        if (isset($recipeData['recipeYield'])) {
            if (is_array($recipeData['recipeYield'])) {
                $recipeData['recipeYield'] = $recipeData['recipeYield'][0];
            }
            if (preg_match('/(\d+)/i', $recipeData['recipeYield'], $matches)) {
                $recipe->servings = intval($matches[1]);
            }
        }

        // Parse cooking times
        foreach (['prepTime', 'cookTime', 'totalTime'] as $timeField) {
            if (isset($recipeData[$timeField])) {
                $duration = new \DateInterval($recipeData[$timeField]);
                $minutes = ($duration->h * 60) + $duration->i;
                $recipe->{Str::snake($timeField)} = $minutes;
            }
        }

        return $recipe;
    }

    public function importFromImage(string $imagePath, int $userId): Recipe
    {
        $ocr = new TesseractOCR($imagePath);
        $text = $ocr->run();

        // Split text into sections
        $sections = $this->parseOcrText($text);

        $recipe = Recipe::create([
            'user_id' => $userId,
            'name' => $sections['title'],
            'description' => $sections['description'] ?? null,
            'image_path' => str_replace(storage_path('app/public/'), '', $imagePath),
        ]);

        // Process ingredients
        foreach ($sections['ingredients'] as $ingredientText) {
            $parsed = $this->parseIngredientText($ingredientText);
            
            $ingredient = Ingredient::firstOrCreate(
                ['name' => $parsed['name']],
                ['unit' => $parsed['unit']]
            );

            $recipe->ingredients()->attach($ingredient->id, [
                'quantity' => $parsed['quantity'],
                'unit' => $parsed['unit'],
                'notes' => $parsed['notes'],
            ]);
        }

        // Process steps
        foreach ($sections['instructions'] as $index => $instruction) {
            $recipe->steps()->create([
                'instruction' => $instruction,
                'order' => $index + 1,
            ]);
        }

        return $recipe;
    }

    protected function parseOcrText(string $text): array
    {
        $lines = explode("\n", $text);
        $sections = [
            'title' => '',
            'ingredients' => [],
            'instructions' => [],
        ];

        $currentSection = 'title';
        $sections['title'] = $lines[0];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            if (preg_match('/ingredients/i', $line)) {
                $currentSection = 'ingredients';
                continue;
            }

            if (preg_match('/instructions|directions|method/i', $line)) {
                $currentSection = 'instructions';
                continue;
            }

            if ($currentSection === 'ingredients' && $this->looksLikeIngredient($line)) {
                $sections['ingredients'][] = $line;
            } elseif ($currentSection === 'instructions') {
                $sections['instructions'][] = $line;
            }
        }

        return $sections;
    }

    protected function parseIngredientText(string $text): array
    {
        $text = strtolower(trim($text));
        
        // Common unit patterns
        $units = [
            'cup|cups|c\.',
            'tablespoon|tablespoons|tbsp|tbs|tb',
            'teaspoon|teaspoons|tsp|ts',
            'pound|pounds|lb|lbs',
            'ounce|ounces|oz',
            'gram|grams|g',
            'kilogram|kilograms|kg',
            'milliliter|milliliters|ml',
            'liter|liters|l',
            'pinch|pinches',
            'dash|dashes',
            'handful|handfuls',
            'piece|pieces|pcs',
            'clove|cloves',
            'bunch|bunches',
            'can|cans',
            'package|packages|pkg',
        ];

        $unitPattern = '(' . implode('|', $units) . ')';
        
        // Enhanced regex to handle fractions and decimals
        $pattern = '/^((?:\d+\s+)?\d+\/\d+|\d*\.?\d+)?\s*' . $unitPattern . '?\s*(.+?)(?:\s*,\s*(.+))?$/i';
        
        if (preg_match($pattern, $text, $matches)) {
            return [
                'quantity' => $matches[1] ?? '1',
                'unit' => $matches[2] ?? null,
                'name' => $matches[3] ?? $text,
                'notes' => $matches[4] ?? null,
            ];
        }

        // Fallback for unparseable ingredients
        return [
            'quantity' => '1',
            'unit' => null,
            'name' => $text,
            'notes' => null,
        ];
    }

    protected function looksLikeIngredient(string $line): bool
    {
        return preg_match('/^\d|cup|tablespoon|teaspoon|pound|ounce|gram/i', $line);
    }

    protected function downloadAndAttachImage(Recipe $recipe, string $imageUrl): void
    {
        try {
            $response = Http::get($imageUrl);
            $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            $path = "recipe-images/{$recipe->id}-" . uniqid() . ".{$extension}";
            
            Storage::disk('public')->put($path, $response->body());
            $recipe->update(['image_path' => $path]);
        } catch (\Exception $e) {
            // Log error but don't fail the import
            \Log::error("Failed to download recipe image: {$e->getMessage()}");
        }
    }

    protected function parseAllRecipes(Crawler $crawler, string $url, int $userId): Recipe
    {
        // First try JSON-LD as AllRecipes usually has good structured data
        try {
            return $this->parseJsonLd($crawler, $url, $userId);
        } catch (\Exception $e) {
            // Fallback to HTML parsing if JSON-LD fails
        }

        $name = $crawler->filter('h1')->text();
        $description = $crawler->filter('[class*="recipe-summary"]')->text('');

        $recipe = Recipe::create([
            'user_id' => $userId,
            'name' => $name,
            'description' => $description,
            'source_url' => $url,
        ]);

        // Parse ingredients
        $crawler->filter('[class*="ingredients-item"]')->each(function (Crawler $node) use ($recipe) {
            $text = $node->text();
            $parsed = $this->parseIngredientText($text);
            
            $ingredient = Ingredient::firstOrCreate(
                ['name' => $parsed['name']],
                ['unit' => $parsed['unit']]
            );

            $recipe->ingredients()->attach($ingredient->id, [
                'quantity' => $parsed['quantity'],
                'unit' => $parsed['unit'],
                'notes' => $parsed['notes'],
            ]);
        });

        // Parse steps
        $crawler->filter('[class*="instructions-section"] [class*="paragraph"]')->each(function (Crawler $node, $index) use ($recipe) {
            $recipe->steps()->create([
                'instruction' => $node->text(),
                'order' => $index + 1,
            ]);
        });

        // Get main recipe image
        try {
            $imageUrl = $crawler->filter('[class*="recipe-hero"] img')->attr('src');
            if ($imageUrl) {
                $this->downloadAndAttachImage($recipe, $imageUrl);
            }
        } catch (\Exception $e) {
            // Image is optional, continue if not found
        }

        return $recipe;
    }

    protected function parseFoodNetwork(Crawler $crawler, string $url, int $userId): Recipe
    {
        // First try JSON-LD as Food Network usually has good structured data
        try {
            return $this->parseJsonLd($crawler, $url, $userId);
        } catch (\Exception $e) {
            // Fallback to HTML parsing if JSON-LD fails
        }

        $name = $crawler->filter('.o-AssetTitle__a-HeadlineText')->text();
        $description = $crawler->filter('.o-AssetDescription__a-Description')->text('');

        $recipe = Recipe::create([
            'user_id' => $userId,
            'name' => $name,
            'description' => $description,
            'source_url' => $url,
        ]);

        // Parse ingredients
        $crawler->filter('.o-Ingredients__a-Ingredient')->each(function (Crawler $node) use ($recipe) {
            $text = $node->text();
            $parsed = $this->parseIngredientText($text);
            
            $ingredient = Ingredient::firstOrCreate(
                ['name' => $parsed['name']],
                ['unit' => $parsed['unit']]
            );

            $recipe->ingredients()->attach($ingredient->id, [
                'quantity' => $parsed['quantity'],
                'unit' => $parsed['unit'],
                'notes' => $parsed['notes'],
            ]);
        });

        // Parse steps
        $crawler->filter('.o-Method__m-Step')->each(function (Crawler $node, $index) use ($recipe) {
            $recipe->steps()->create([
                'instruction' => $node->text(),
                'order' => $index + 1,
            ]);
        });

        // Get main recipe image
        try {
            $imageUrl = $crawler->filter('.m-MediaBlock__a-Image img')->attr('src');
            if ($imageUrl) {
                $this->downloadAndAttachImage($recipe, $imageUrl);
            }
        } catch (\Exception $e) {
            // Image is optional, continue if not found
        }

        return $recipe;
    }

    // Add a method to convert fractions to decimals for consistent storage
    protected function fractionToDecimal(string $fraction): float
    {
        if (strpos($fraction, '/') === false) {
            return floatval($fraction);
        }

        $parts = explode(' ', $fraction);
        if (count($parts) > 1) {
            $whole = intval($parts[0]);
            $fraction = $parts[1];
        } else {
            $whole = 0;
            $fraction = $parts[0];
        }

        list($numerator, $denominator) = explode('/', $fraction);
        return $whole + (intval($numerator) / intval($denominator));
    }
}