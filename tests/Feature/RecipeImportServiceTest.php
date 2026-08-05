<?php

use App\Models\Recipe;
use App\Models\User;
use App\Services\RecipeImportService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\DomCrawler\Crawler;

function callRecipeImportServiceMethod(string $method, mixed ...$arguments): mixed
{
    $reflection = new ReflectionMethod(RecipeImportService::class, $method);
    $reflection->setAccessible(true);

    return $reflection->invoke(app(RecipeImportService::class), ...$arguments);
}

describe('recipe import image extraction', function () {
    it('extracts and normalizes multiple image urls from structured data and html', function () {
        $html = <<<'HTML'
            <html>
                <head>
                    <meta property="og:image" content="/cards/front.jpg">
                    <meta name="twitter:image" content="https://example.com/cards/front.jpg">
                    <link rel="image_src" href="//cdn.example.com/cards/social.webp">
                </head>
                <body>
                    <article class="recipe-card">
                        <img data-src="/cards/back.jpg" src="data:image/gif;base64,skip">
                        <img srcset="/cards/tiny.jpg 320w, /cards/large.jpg 1200w">
                    </article>
                </body>
            </html>
        HTML;

        $urls = callRecipeImportServiceMethod(
            'extractImageUrls',
            new Crawler($html),
            [
                'image' => [
                    'https://example.com/cards/front.jpg',
                    ['@type' => 'ImageObject', 'url' => '/cards/detail.png'],
                ],
            ],
            'https://example.com/recipes/soup'
        );

        expect($urls)->toBe([
            'https://example.com/cards/front.jpg',
            'https://example.com/cards/detail.png',
            'https://cdn.example.com/cards/social.webp',
            'https://example.com/cards/back.jpg',
            'https://example.com/cards/large.jpg',
        ]);
    });
});

describe('recipe card ocr parsing', function () {
    it('merges ingredients from the front and instructions from the back', function () {
        $front = <<<'TEXT'
Chocolate Chip Cookies
Ingredients
2 cups flour
1 cup sugar
TEXT;

        $back = <<<'TEXT'
1. Preheat oven to 350F
2. Mix dry ingredients
3. Bake for 12 minutes
TEXT;

        $sections = callRecipeImportServiceMethod('parseOcrTextFromSides', [$front, $back]);

        expect($sections)->toMatchArray([
            'title' => 'Chocolate Chip Cookies',
            'ingredients' => ['2 cups flour', '1 cup sugar'],
            'instructions' => [
                'Preheat oven to 350F',
                'Mix dry ingredients',
                'Bake for 12 minutes',
            ],
        ]);
    });

    it('detects numbered instructions without a section header', function () {
        $sections = callRecipeImportServiceMethod('parseOcrText', <<<'TEXT'
Grandma's Bread
1. Proof the yeast in warm water
2. Knead the dough for 10 minutes
TEXT);

        expect($sections['title'])->toBe("Grandma's Bread")
            ->and($sections['instructions'])->toBe([
                'Proof the yeast in warm water',
                'Knead the dough for 10 minutes',
            ]);
    });
});
