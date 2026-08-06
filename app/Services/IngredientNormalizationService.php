<?php

namespace App\Services;

use Illuminate\Support\Str;

class IngredientNormalizationService
{
    public function normalize(string $name): string
    {
        $name = strtolower($name);

        // Remove quantities and units
        $name = preg_replace('/\d+\/\d+|\d+\.\d+|\d+/', '', $name);
        $name = preg_replace('/\b(cups|cup|oz|ounce|ounces|grams|gram|g|kg|kgs|ml|l|tsp|tbsp|teaspoon|tablespoon|pounds|pound|lb|lbs)\b/', '', $name);

        // Remove common modifiers and junk
        $name = str_replace(['(optional)', 'for garnish', 'cut into pieces', 'minced', 'chopped', 'fresh', 'freshly', 'can', 'uncooked', 'dry', 'cold', 'warm', 'hot', 'of the cheese of your choice', 'deselect all', 'ingredients', 'stick', 'cut into', 'inch', 'pieces', 'piece'], '', $name);
        
        // Remove unicode fractions
        $name = preg_replace('/[\x{00BC}-\x{00BE}\x{2150}-\x{215E}]/u', '', $name);

        // Remove punctuation
        $name = preg_replace('/[^\w\s]/', '', $name);

        return Str::singular(trim($name));
    }
}
