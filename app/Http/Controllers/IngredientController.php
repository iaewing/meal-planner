<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use Inertia\Inertia;

class IngredientController extends Controller
{
    public function index()
    {
        $ingredients = Ingredient::query()
            ->get();

        return Inertia::render('Ingredients/Index', [
            'ingredients' => $ingredients
        ]);
    }
}
