<?php

use App\Models\Recipe;
use App\Models\User;
use App\Services\InstagramImportService;
use App\Services\RecipeImportService;

describe('instagram import support', function () {
    it('detects instagram post urls', function () {
        $service = app(InstagramImportService::class);

        expect($service->supports('https://www.instagram.com/p/ABC123/'))->toBeTrue()
            ->and($service->supports('https://www.instagram.com/reel/ABC123/'))->toBeTrue()
            ->and($service->supports('https://www.allrecipes.com/recipe/123'))->toBeFalse();
    });

    it('extracts captions from open graph metadata', function () {
        $html = <<<'HTML'
            <html>
                <head>
                    <meta property="og:description" content="Sheet Pan Chicken&#10;Ingredients&#10;2 cups rice&#10;Directions&#10;1. Bake for 20 minutes">
                </head>
            </html>
        HTML;

        $caption = app(InstagramImportService::class)->extractCaptionFromHtml($html);

        expect($caption)->toContain('Sheet Pan Chicken')
            ->and($caption)->toContain('2 cups rice');
    });

    it('imports a recipe from an instagram caption', function () {
        $user = User::factory()->create();
        $html = <<<'HTML'
            <html>
                <head>
                    <meta property="og:description" content="Sheet Pan Chicken&#10;Ingredients&#10;2 cups rice&#10;1 tbsp olive oil&#10;Directions&#10;1. Bake for 20 minutes&#10;2. Serve hot">
                </head>
            </html>
        HTML;

        $importService = Mockery::mock(RecipeImportService::class, [
            app(\App\Services\IngredientService::class),
            app(InstagramImportService::class),
        ])->makePartial()->shouldAllowMockingProtectedMethods();

        $importService->shouldReceive('fetchPageHtml')
            ->once()
            ->andReturn($html);

        $recipe = $importService->importFromUrl('https://www.instagram.com/p/ABC123/', $user->id);

        expect($recipe)->toBeInstanceOf(Recipe::class)
            ->and($recipe->name)->toBe('Sheet Pan Chicken')
            ->and($recipe->source_url)->toBe('https://www.instagram.com/p/ABC123/')
            ->and($recipe->ingredients)->toHaveCount(2)
            ->and($recipe->steps)->toHaveCount(2);
    });
});
