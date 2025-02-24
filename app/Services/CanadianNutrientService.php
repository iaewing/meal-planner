<?php

namespace App\Services;

use App\Models\Ingredient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\App;

class CanadianNutrientService
{
    protected $baseUrl = 'https://food-nutrition.canada.ca/api/canadian-nutrient-file';

    public function searchIngredient(string $query, ?string $lang = null): array
    {
        $lang = $lang ?? App::getLocale();
        $lang = in_array($lang, ['en', 'fr']) ? $lang : 'en';
        
        $cacheKey = "cnf_search_{$lang}_" . md5($query);

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($query, $lang) {
            // Search in specified language
            $results = $this->searchWithLanguage($query, $lang);
            
            // If no results and searching in French, try English as fallback
            if (empty($results) && $lang === 'fr') {
                $results = $this->searchWithLanguage($query, 'en');
            }

            return $results;
        });
    }

    protected function searchWithLanguage(string $query, string $lang): array
    {
        $response = Http::get("{$this->baseUrl}/food", [
            'q' => $query,
            'lang' => $lang,
        ]);

        return $response->json()['foods'] ?? [];
    }

    public function getIngredientNutrition(int $foodId, ?string $lang = null): array
    {
        $lang = $lang ?? App::getLocale();
        $lang = in_array($lang, ['en', 'fr']) ? $lang : 'en';
        
        $cacheKey = "cnf_nutrition_{$lang}_{$foodId}";

        return Cache::remember($cacheKey, now()->addMonth(), function () use ($foodId, $lang) {
            $response = Http::get("{$this->baseUrl}/food/{$foodId}", [
                'lang' => $lang,
            ]);

            $data = $response->json();
            
            // Map CNF nutrient IDs to our nutrition fields
            $nutrientMap = [
                '208' => 'calories',      // Energy (kcal) / Énergie (kcal)
                '204' => 'fat',           // Total fat / Lipides totaux
                '606' => 'saturated_fat', // Saturated fat / Lipides saturés
                '601' => 'cholesterol',   // Cholesterol / Cholestérol
                '307' => 'sodium',        // Sodium / Sodium
                '205' => 'carbohydrates', // Total carbohydrates / Glucides totaux
                '291' => 'fiber',         // Dietary fiber / Fibres alimentaires
                '269' => 'sugar',         // Total sugars / Sucres totaux
                '203' => 'protein',       // Protein / Protéines
            ];

            $nutrition = [
                'serving_size' => $data['servingSize'] ?? 100,
                'serving_unit' => $this->normalizeUnit($data['servingSizeUnit'] ?? 'g', $lang),
            ];

            foreach ($data['nutrients'] as $nutrient) {
                if (isset($nutrientMap[$nutrient['nutrientId']])) {
                    $key = $nutrientMap[$nutrient['nutrientId']];
                    $nutrition[$key] = $nutrient['value'];
                }
            }

            return $nutrition;
        });
    }

    protected function normalizeUnit(string $unit, string $lang): string
    {
        // Map French units to English
        $frToEn = [
            'ml' => 'ml',
            'mL' => 'ml',
            'g' => 'g',
            'mg' => 'mg',
            'L' => 'l',
            'l' => 'l',
            'kg' => 'kg',
            'tasse' => 'cup',
            'tasses' => 'cup',
            'cuillère à soupe' => 'tbsp',
            'cuillères à soupe' => 'tbsp',
            'cuillère à thé' => 'tsp',
            'cuillères à thé' => 'tsp',
        ];

        $unit = strtolower(trim($unit));
        return $lang === 'fr' ? ($frToEn[$unit] ?? $unit) : $unit;
    }

    public function findBestMatch(string $ingredientName, ?string $lang = null): ?int
    {
        $results = $this->searchIngredient($ingredientName, $lang);
        
        if (empty($results)) {
            return null;
        }

        // Simple scoring system based on name similarity
        $bestMatch = null;
        $highestScore = 0;

        foreach ($results as $result) {
            // Get both English and French names if available
            $names = [
                strtolower($result['name']),
                strtolower($result['name_fr'] ?? ''),
            ];

            foreach ($names as $name) {
                if (empty($name)) continue;

                similar_text(
                    strtolower($ingredientName),
                    $name,
                    $percent
                );

                if ($percent > $highestScore) {
                    $highestScore = $percent;
                    $bestMatch = $result['id'];
                }
            }
        }

        // Only return if we have a reasonably good match
        return $highestScore > 60 ? $bestMatch : null;
    }

    public function updateIngredientNutrition(Ingredient $ingredient, int $foodId): void
    {
        // Get nutrition data in both languages
        $nutritionEn = $this->getIngredientNutrition($foodId, 'en');
        $nutritionFr = $this->getIngredientNutrition($foodId, 'fr');
        
        // Use English data as base but store French units as alternatives
        $nutrition = array_merge($nutritionEn, [
            'serving_unit_fr' => $nutritionFr['serving_unit'],
        ]);
        
        $ingredient->nutrition()->updateOrCreate(
            ['ingredient_id' => $ingredient->id],
            $nutrition
        );
    }

    public function getLocalizedNutritionLabel(array $nutrition, string $lang = null): array
    {
        $lang = $lang ?? App::getLocale();
        
        $labels = [
            'en' => [
                'calories' => 'Calories',
                'fat' => 'Total Fat',
                'saturated_fat' => 'Saturated Fat',
                'cholesterol' => 'Cholesterol',
                'sodium' => 'Sodium',
                'carbohydrates' => 'Total Carbohydrates',
                'fiber' => 'Dietary Fiber',
                'sugar' => 'Total Sugars',
                'protein' => 'Protein',
            ],
            'fr' => [
                'calories' => 'Calories',
                'fat' => 'Lipides totaux',
                'saturated_fat' => 'Lipides saturés',
                'cholesterol' => 'Cholestérol',
                'sodium' => 'Sodium',
                'carbohydrates' => 'Glucides totaux',
                'fiber' => 'Fibres alimentaires',
                'sugar' => 'Sucres totaux',
                'protein' => 'Protéines',
            ],
        ];

        $currentLabels = $labels[$lang] ?? $labels['en'];
        $result = [];

        foreach ($nutrition as $key => $value) {
            if (isset($currentLabels[$key])) {
                $result[$currentLabels[$key]] = $value;
            }
        }

        return $result;
    }
}