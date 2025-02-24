<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ingredient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'unit',
    ];

    public function recipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class, 'recipe_ingredients')
            ->withPivot('quantity', 'unit', 'notes')
            ->withTimestamps();
    }

    public function nutrition(): HasOne
    {
        return $this->hasOne(IngredientNutrition::class);
    }

    public function getNutritionValues(): array
    {
        if (!$this->nutrition) {
            return [];
        }

        return [
            'calories' => $this->nutrition->calories,
            'fat' => $this->nutrition->fat,
            'saturated_fat' => $this->nutrition->saturated_fat,
            'cholesterol' => $this->nutrition->cholesterol,
            'sodium' => $this->nutrition->sodium,
            'carbohydrates' => $this->nutrition->carbohydrates,
            'fiber' => $this->nutrition->fiber,
            'sugar' => $this->nutrition->sugar,
            'protein' => $this->nutrition->protein,
        ];
    }
}