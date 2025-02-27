<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\Ingredient;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\IngredientUnit;
use App\Utilities\UnitConverter;
use Illuminate\Http\RedirectResponse;

class IngredientController extends Controller
{
    public function index(): Response
    {
        $ingredients = Ingredient::query()
            ->get();

        return Inertia::render('Ingredients/Index', [
            'ingredients' => $ingredients
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
        ]);

        $existingIngredient = Ingredient::query()->where('name', $validated['name'])->first();

        if ($existingIngredient) {
            $defaultUnit = IngredientUnit::query()
                ->where('ingredient_id', $existingIngredient->id)
                ->where('is_default', true)
                ->first();

            $conversionFactor = 1;
            
            if ($defaultUnit) {
                $conversionFactor = UnitConverter::determineConversionFactor(
                    $defaultUnit->unit,
                    $validated['unit'],
                    $validated['name']
                );
            }

            IngredientUnit::query()->create([
                'ingredient_id' => $existingIngredient->id,
                'unit' => Str::lower($validated['unit']),
                'is_default' => $defaultUnit ? false : true,
                'conversion_factor' => $conversionFactor
            ]);

            return redirect()
                ->route('ingredients.index')
                ->with('success', 'Ingredient created successfully.');
        }

        $newIngredient = Ingredient::query()->create([
            'name' => Str::lower($validated['name']),
        ]);

        IngredientUnit::query()->create([
            'ingredient_id' => $newIngredient->id,
            'unit' => Str::lower($validated['unit']),
            'is_default' => true,
            'conversion_factor' => 1
        ]);

        return redirect()
            ->route('ingredients.index')
            ->with('success', 'Ingredient created successfully.');
    }
}
