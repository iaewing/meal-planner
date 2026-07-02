<?php

namespace App\Services;

class IngredientNormalizer
{
    public function basicNormalize(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/\s+/', ' ', $name);

        return $name;
    }

    public function normalize(string $name): string
    {
        $basic = $this->basicNormalize($name);

        if (! str_contains($basic, ' ')) {
            return $this->singularizeWord($basic);
        }

        return $basic;
    }

    private function singularizeWord(string $word): string
    {
        if (preg_match('/ies$/', $word) && strlen($word) > 4) {
            return preg_replace('/ies$/', 'y', $word);
        }

        if (preg_match('/oes$/', $word)) {
            return preg_replace('/oes$/', 'o', $word);
        }

        if (str_ends_with($word, 's') && ! str_ends_with($word, 'ss') && strlen($word) > 3) {
            return substr($word, 0, -1);
        }

        return $word;
    }
}
