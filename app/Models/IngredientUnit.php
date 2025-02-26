<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngredientUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'ingredient_id',
        'unit',
        'is_default',
        'conversion_factor',
    ];

    /**
     * Get the ingredient that this unit belongs to
     */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}