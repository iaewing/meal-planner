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

    it('downloads and attaches multiple images in order', function () {
        Storage::fake('s3');
        Http::fake([
            'https://example.com/front.jpg' => Http::response('front', 200, ['Content-Type' => 'image/jpeg']),
            'https://example.com/back.png' => Http::response('back', 200, ['Content-Type' => 'image/png']),
        ]);

        $recipe = Recipe::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Recipe card',
        ]);

        callRecipeImportServiceMethod(
            'downloadAndAttachImages',
            $recipe,
            [
                'https://example.com/front.jpg',
                'https://example.com/back.png',
            ]
        );

        $recipe->refresh();

        expect($recipe->images)->toHaveCount(2);
        expect($recipe->image_path)->toBe($recipe->images[0]->path);
        expect($recipe->images[0]->disk)->toBe('s3');
        expect($recipe->images[1]->disk)->toBe('s3');

        Storage::disk('s3')->assertExists($recipe->images[0]->path);
        Storage::disk('s3')->assertExists($recipe->images[1]->path);
    });
});
