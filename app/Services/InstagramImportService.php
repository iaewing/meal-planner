<?php

namespace App\Services;

use Symfony\Component\DomCrawler\Crawler;

class InstagramImportService
{
    public function supports(string $url): bool
    {
        return (bool) preg_match('#instagram\.com/(?:p|reel|tv)/#i', $url);
    }

    public function extractCaptionFromHtml(string $html): ?string
    {
        $crawler = new Crawler($html);
        $nodes = $crawler->filter('meta[property="og:description"]');

        if ($nodes->count() === 0) {
            return null;
        }

        $caption = html_entity_decode($nodes->first()->attr('content') ?? '', ENT_QUOTES);

        return trim($caption) === '' ? null : trim($caption);
    }

    public function parseCaption(string $caption, RecipeImportService $recipeImportService): array
    {
        $reflection = new \ReflectionMethod(RecipeImportService::class, 'parseOcrTextFromSides');
        $reflection->setAccessible(true);

        return $reflection->invoke($recipeImportService, [$caption]);
    }
}
