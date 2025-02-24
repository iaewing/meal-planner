<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngredientNutrition extends Model
{
    protected $table = 'ingredient_nutrition';

    protected $fillable = [
        'ingredient_id',
        'serving_size',
        'serving_unit',
        'calories',
        'fat',
        'saturated_fat',
        'cholesterol',
        'sodium',
        'carbohydrates',
        'fiber',
        'sugar',
        'protein',
    ];

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}