<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\Ingredient;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Utilities\UnitConverter;
use Illuminate\Http\RedirectResponse;

class IngredientController extends Controller
{
    public function index(): Response
    {
        $ingredients = Ingredient::query()->with('units')->get();

        // Get volume and weight units from UnitConverter
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
                    
                    if ($unitData['is_default'] && !$existingUnit->is_default) {
                        
                        $existingUnit->update([
                            'is_default' => true,
                            'conversion_factor' => 1
                        ]);
                        
                        // Update all other units' conversion factors relative to new default
                        foreach ($existingIngredient->units()->where('id', '!=', $existingUnit->id)->get() as $unit) {
                            $unit->update([
                                'is_default' => false,
                                'conversion_factor' => UnitConverter::determineConversionFactor(
                                    $existingUnit->unit,
                                    $unit->unit,
                                    $existingIngredient->name
                                )
                            ]);
                        }
                    }
                    continue;
                }

                
                if ($unitData['is_default']) {
                    $newDefaultUnit = $existingIngredient->units()->create([
                        'unit' => Str::lower($unitData['unit']),
                        'is_default' => true,
                        'conversion_factor' => 1
                    ]);

                    
                    foreach ($existingIngredient->units()->where('id', '!=', $newDefaultUnit->id)->get() as $unit) {
                        $unit->update([
                            'is_default' => false,
                            'conversion_factor' => UnitConverter::determineConversionFactor(
                                $newDefaultUnit->unit,
                                $unit->unit,
                                $existingIngredient->name
                            )
                        ]);
                    }
                } else {
                    
                    $conversionFactor = 1;
                    if ($defaultUnit) {
                        $conversionFactor = UnitConverter::determineConversionFactor(
                            $defaultUnit->unit,
                            $unitData['unit'],
                            $existingIngredient->name
                        );
                    }

                    $existingIngredient->units()->create([
                        'unit' => Str::lower($unitData['unit']),
                        'is_default' => false,
                        'conversion_factor' => $conversionFactor
                    ]);
                }
            }
        } else {
            $newIngredient = Ingredient::create([
                'name' => Str::lower($validated['name']),
            ]);

            $defaultUnitKey = array_search(true, array_column($validated['units'], 'is_default'));

            foreach ($validated['units'] as $index => $unitData) {
                $newIngredient->units()->create([
                    'unit' => Str::lower($unitData['unit']),
                    'is_default' => $unitData['is_default'],
                    'conversion_factor' => $unitData['is_default'] ? 1 : UnitConverter::determineConversionFactor(
                        $validated['units'][$defaultUnitKey]['unit'],
                        $unitData['unit'],
                        $validated['name']
                    )
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
            
            if ($validated['is_default'] && !$existingUnit->is_default) {
                $existingUnit->update([
                    'is_default' => true,
                    'conversion_factor' => 1
                ]);
                
                // Update all other units
                foreach ($ingredient->units()->where('id', '!=', $existingUnit->id)->get() as $unit) {
                    $unit->update([
                        'is_default' => false,
                        'conversion_factor' => UnitConverter::determineConversionFactor(
                            $existingUnit->unit,
                            $unit->unit,
                            $ingredient->name
                        )
                    ]);
                }
            }
            
            return redirect()->route('ingredients.index')
                ->with('success', 'Unit updated successfully.');
        }

        
        if ($validated['is_default']) {
            $newDefaultUnit = $ingredient->units()->create([
                'unit' => Str::lower($validated['unit']),
                'is_default' => true,
                'conversion_factor' => 1
            ]);

            // Update all other units to be non-default and recalculate conversion factors
            foreach ($ingredient->units()->where('id', '!=', $newDefaultUnit->id)->get() as $unit) {
                $unit->update([
                    'is_default' => false,
                    'conversion_factor' => UnitConverter::determineConversionFactor(
                        $newDefaultUnit->unit,
                        $unit->unit,
                        $ingredient->name
                    )
                ]);
            }
        } else {
            $defaultUnit = $ingredient->units()->where('is_default', true)->first();
            $conversionFactor = 1;
            
            if ($defaultUnit) {
                $conversionFactor = UnitConverter::determineConversionFactor(
                    $defaultUnit->unit,
                    $validated['unit'],
                    $ingredient->name
                );
            }

            $ingredient->units()->create([
                'unit' => Str::lower($validated['unit']),
                'is_default' => false,
                'conversion_factor' => $conversionFactor
            ]);
        }

        return redirect()->route('ingredients.index')
            ->with('success', 'Unit added successfully.');
    }
}
