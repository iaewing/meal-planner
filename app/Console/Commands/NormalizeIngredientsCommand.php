<?php

namespace App\Console\Commands;

use App\Models\Ingredient;
use App\Services\IngredientNormalizationService;
use Illuminate\Console\Command;

class NormalizeIngredientsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ingredients:normalize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Normalize existing ingredient names by removing quantities, units, and other junk data.';

    public function __construct(protected IngredientNormalizationService $normalizationService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting ingredient normalization...');

        $ingredients = Ingredient::all();

        foreach ($ingredients as $ingredient) {
            $normalizedName = $this->normalizationService->normalize($ingredient->name);

            if (empty($normalizedName)) {
                $this->warn("Could not normalize '{$ingredient->name}', skipping.");
                continue;
            }

            $existingIngredient = Ingredient::query()->where('name', $normalizedName)->where('id', '!=', $ingredient->id)->first();

            if ($existingIngredient) {
                $this->line("Found duplicate: '$ingredient->name' -> '$normalizedName'. Merging with existing ID: $existingIngredient->id");

                // Get all recipe associations for the duplicate ingredient
                $recipes = $ingredient->recipes()->get();

                foreach ($recipes as $recipe) {
                    // Attach the recipe to the existing ingredient, preserving pivot data
                    $pivotData = $recipe->pivot;
                    $existingIngredient->recipes()->attach($recipe->id, [
                        'quantity' => $pivotData->quantity,
                        'unit' => $pivotData->unit,
                        'notes' => $pivotData->notes,
                    ]);
                }

                $ingredient->recipes()->detach();
                $ingredient->delete();
                $this->info("Merged and deleted duplicate ingredient '{$ingredient->name}'.");
            } else {
                if ($ingredient->name !== $normalizedName) {
                    $this->line("Renaming: '$ingredient->name' -> '$normalizedName'");
                    $ingredient->name = $normalizedName;
                    $ingredient->save();
                }
            }
        }

        $this->info('Ingredient normalization complete.');
    }
}

