<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\Ingredient;
use App\Models\IngredientUnit;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Utilities\UnitConverter;
use Illuminate\Http\RedirectResponse;

class IngredientController extends Controller
{
    public function index(): Response
    {
        $ingredients = Ingredient::query()->with('units')->get();

        $volumeUnits = array_keys(UnitConverter::VOLUME_CONVERSIONS);
        $weightUnits = array_keys(UnitConverter::WEIGHT_CONVERSIONS);

        return Inertia::render('Ingredients/Index', [
            'ingredients' => $ingredients,
            'volumeUnits' => $volumeUnits,
            'weightUnits' => $weightUnits
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'units' => 'required|array|min:1',
            'units.*.unit' => 'required|string|max:50',
            'units.*.is_default' => 'required|boolean',
        ]);

        $existingIngredient = Ingredient::query()->where('name', Str::lower($validated['name']))->first();

        if ($existingIngredient) {
            $defaultUnit = $existingIngredient->units()->where('is_default', true)->first();

            foreach ($validated['units'] as $unitData) {
                $existingUnit = $existingIngredient->units()
                    ->where('unit', Str::lower($unitData['unit']))
                    ->first();

                if ($existingUnit) {
                    if ($unitData['is_default'] && ! $existingUnit->is_default) {
                        $existingUnit->update([
                            'is_default' => true,
                            'conversion_factor' => 1,
                        ]);

                        $this->recalculateConversionFactors($existingIngredient, $existingUnit);
                    }

                    continue;
                }

                if ($unitData['is_default']) {
                    $newDefaultUnit = $existingIngredient->units()->create([
                        'unit' => Str::lower($unitData['unit']),
                        'is_default' => true,
                        'conversion_factor' => 1,
                    ]);

                    $this->recalculateConversionFactors($existingIngredient, $newDefaultUnit);
                } else {
                    $existingIngredient->units()->create([
                        'unit' => Str::lower($unitData['unit']),
                        'is_default' => false,
                        'conversion_factor' => $defaultUnit
                            ? UnitConverter::conversionFactorOrNull(
                                $defaultUnit->unit,
                                $unitData['unit'],
                                $existingIngredient->name
                            )
                            : null,
                    ]);
                }
            }
        } else {
            $newIngredient = Ingredient::create([
                'name' => Str::lower($validated['name']),
            ]);

            $defaultUnitKey = array_search(true, array_column($validated['units'], 'is_default'));

            foreach ($validated['units'] as $unitData) {
                $newIngredient->units()->create([
                    'unit' => Str::lower($unitData['unit']),
                    'is_default' => $unitData['is_default'],
                    'conversion_factor' => $unitData['is_default']
                        ? 1
                        : UnitConverter::conversionFactorOrNull(
                            $validated['units'][$defaultUnitKey]['unit'],
                            $unitData['unit'],
                            $validated['name']
                        ),
                ]);
            }
        }

        return redirect()
            ->route('ingredients.index')
            ->with('success', 'Ingredient created successfully.');
    }

    public function addUnit(Request $request, Ingredient $ingredient): RedirectResponse
    {
        $validated = $request->validate([
            'unit' => 'required|string|max:50',
            'is_default' => 'required|boolean',
        ]);

        $existingUnit = $ingredient->units()->where('unit', Str::lower($validated['unit']))->first();
        if ($existingUnit) {
            if ($validated['is_default'] && ! $existingUnit->is_default) {
                $existingUnit->update([
                    'is_default' => true,
                    'conversion_factor' => 1,
                ]);

                $this->recalculateConversionFactors($ingredient, $existingUnit);
            }

            return redirect()->route('ingredients.index')
                ->with('success', 'Unit updated successfully.');
        }

        if ($validated['is_default']) {
            $newDefaultUnit = $ingredient->units()->create([
                'unit' => Str::lower($validated['unit']),
                'is_default' => true,
                'conversion_factor' => 1,
            ]);

            $this->recalculateConversionFactors($ingredient, $newDefaultUnit);
        } else {
            $defaultUnit = $ingredient->units()->where('is_default', true)->first();

            $ingredient->units()->create([
                'unit' => Str::lower($validated['unit']),
                'is_default' => false,
                'conversion_factor' => $defaultUnit
                    ? UnitConverter::conversionFactorOrNull(
                        $defaultUnit->unit,
                        $validated['unit'],
                        $ingredient->name
                    )
                    : null,
            ]);
        }

        return redirect()->route('ingredients.index')
            ->with('success', 'Unit added successfully.');
    }

    private function recalculateConversionFactors(Ingredient $ingredient, IngredientUnit $defaultUnit): void
    {
        foreach ($ingredient->units()->where('id', '!=', $defaultUnit->id)->get() as $unit) {
            $unit->update([
                'is_default' => false,
                'conversion_factor' => UnitConverter::conversionFactorOrNull(
                    $defaultUnit->unit,
                    $unit->unit,
                    $ingredient->name
                ),
            ]);
        }
    }
}
