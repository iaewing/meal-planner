<?php

namespace App\Console\Commands;

use App\Models\Ingredient;
use App\Services\CanadianNutrientService;
use Illuminate\Console\Command;

class UpdateIngredientNutrition extends Command
{
    protected $signature = 'ingredients:update-nutrition {--force : Force update even if nutrition exists}';
    protected $description = 'Update ingredient nutrition information from Canadian Nutrient File';

    public function handle(CanadianNutrientService $nutritionService)
    {
        $query = Ingredient::query();
        
        if (!$this->option('force')) {
            $query->whereDoesntHave('nutrition');
        }

        $ingredients = $query->get();
        $bar = $this->output->createProgressBar($ingredients->count());

        foreach ($ingredients as $ingredient) {
            $this->info("\nProcessing: {$ingredient->name}");
            
            try {
                $foodId = $nutritionService->findBestMatch($ingredient->name);
                if ($foodId) {
                    $nutritionService->updateIngredientNutrition($ingredient, $foodId);
                    $this->info("✓ Updated nutrition data");
                } else {
                    $this->warn("✗ No match found in CNF database");
                }
            } catch (\Exception $e) {
                $this->error("✗ Error: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Nutrition update complete!');
    }
} 