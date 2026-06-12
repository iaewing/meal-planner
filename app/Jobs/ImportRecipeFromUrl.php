<?php

namespace App\Jobs;

use App\Services\RecipeImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportRecipeFromUrl implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    public function __construct(
        public readonly string $url,
        public readonly int $userId,
    ) {
        //
    }

    public function handle(RecipeImportService $recipeImportService): void
    {
        $recipe = $recipeImportService->importFromUrl($this->url, $this->userId);

        Log::info('Recipe URL import completed', [
            'recipe_id' => $recipe->id,
            'url' => $this->url,
            'user_id' => $this->userId,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Recipe URL import job failed', [
            'url' => $this->url,
            'user_id' => $this->userId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
