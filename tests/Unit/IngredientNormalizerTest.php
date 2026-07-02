<?php

use App\Services\IngredientNormalizer;

describe('ingredient normalization', function () {
    it('lowercases and trims names', function () {
        $normalizer = new IngredientNormalizer;

        expect($normalizer->basicNormalize('  Flour  '))->toBe('flour');
    });

    it('singularizes single-word plurals for deduplication', function () {
        $normalizer = new IngredientNormalizer;

        expect($normalizer->normalize('tomatoes'))->toBe('tomato')
            ->and($normalizer->normalize('onions'))->toBe('onion');
    });

    it('keeps multi-word ingredient names distinct', function () {
        $normalizer = new IngredientNormalizer;

        expect($normalizer->normalize('cherry tomatoes'))->toBe('cherry tomatoes')
            ->and($normalizer->normalize('tomato'))->toBe('tomato')
            ->and($normalizer->normalize('cherry tomatoes'))->not->toBe($normalizer->normalize('tomato'));
    });

    it('does not alter words that only look plural', function () {
        $normalizer = new IngredientNormalizer;

        expect($normalizer->normalize('rice'))->toBe('rice');
    });
});
