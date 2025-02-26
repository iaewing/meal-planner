<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Services\RecipeScalingService;
use App\Services\NutritionCalculationService;

class Recipe extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'source_url',
        'image_path',
        'servings'
    ];

    protected $casts = [
        'nutrition' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'recipe_ingredients')
            ->withPivot('quantity', 'unit', 'notes')
            ->withTimestamps();
    }

    public function steps(): HasMany
    {
        return $this->hasMany(RecipeStep::class)->orderBy('order');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(IngredientUnit::class, 'ingredient_unit_id');
    }

    public function mealPlans(): BelongsToMany
    {
        return $this->belongsToMany(MealPlan::class, 'meal_plan_recipes')
            ->withPivot('planned_date', 'meal_type')
            ->withTimestamps();
    }

    public function scaleServings(int $targetServings)
    {
        if (!$this->servings) {
            return $this;
        }

        $scaleFactor = $targetServings / $this->servings;
        $scalingService = app(RecipeScalingService::class);

        $scaledIngredients = $this->ingredients->map(function ($ingredient) use ($scaleFactor, $scalingService) {
            $scaled = $scalingService->scaleRecipe([
                'quantity' => $ingredient->pivot->quantity,
                'unit' => $ingredient->pivot->unit,
                'name' => $ingredient->name,
                'notes' => $ingredient->pivot->notes,
            ], $scaleFactor);

            return [
                'id' => $ingredient->id,
                'name' => $ingredient->name,
                'quantity' => $scaled[0]['quantity'],
                'unit' => $scaled[0]['unit'],
                'notes' => $scaled[0]['notes'],
            ];
        });

        // Scale nutrition information
        if ($this->nutrition) {
            $this->nutrition = collect($this->nutrition)->map(function ($value) use ($scaleFactor) {
                if (is_numeric($value)) {
                    return round($value * $scaleFactor, 1);
                }
                return $value;
            })->all();
        }

        $this->servings = $targetServings;

        return $this;
    }

    public function getNutritionAttribute($value)
    {
        $nutrition = json_decode($value, true) ?? [];
        
        // Ensure all expected nutrition fields are present
        return array_merge([
            'calories' => null,
            'fat' => null,
            'saturated_fat' => null,
            'cholesterol' => null,
            'sodium' => null,
            'carbohydrates' => null,
            'fiber' => null,
            'sugar' => null,
            'protein' => null,
        ], $nutrition);
    }

    public function calculateNutrition(): array
    {
        return app(NutritionCalculationService::class)->calculateRecipeNutrition($this);
    }

    public function updateNutrition(): void
    {
        $this->nutrition = $this->calculateNutrition();
        $this->save();
    }

    protected static function booted()
    {
        static::saved(function ($recipe) {
            if ($recipe->wasChanged(['servings']) || !$recipe->nutrition) {
                $recipe->updateNutrition();
            }
        });
    }
}