<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateRecipeRequest;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Services\RecipeImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class RecipeController extends Controller
{
    public function index(Request $request)
    {
        $query = Recipe::query()
            ->where('user_id', auth()->id());
            
        if ($request->has('search') && $request->input('search')) {
            $query->where('name', 'ilike', '%' . $request->input('search') . '%');
        }
        
        $recipes = $query->with(['ingredients', 'steps'])
            ->latest()
            ->paginate(12)
            ->appends($request->only('search'));

        return Inertia::render('Recipes/Index', [
            'recipes' => $recipes,
            'filters' => [
                'search' => $request->input('search')
            ]
        ]);
    }

    public function create()
    {
        $ingredients = Ingredient::query()->with('units')->get();

        return Inertia::render('Recipes/Create', [
            'ingredientsData' => $ingredients
        ]);
    }

    public function store(CreateRecipeRequest $request)
    {
        $recipe = Recipe::create([
            'user_id' => auth()->id(),
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'source_url' => $request->input('source_url'),
            'servings' => $request->input('servings'),
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('recipe-images', 'public');
            $recipe->update(['image_path' => $path]);
        }

        // Attach ingredients
        foreach ($request->input('ingredients') as $ingredient) {
            $recipe->ingredients()->attach($ingredient['ingredient_id'], [
                'quantity' => $ingredient['quantity'],
                'unit' => $ingredient['unit'],
                'notes' => $ingredient['notes'] ?? '',
            ]);
        }
        // Create steps
        foreach ($request->input('steps') as $index => $step) {
            $recipe->steps()->create([
                'instruction' => $step,
                'order' => $index,
            ]);
        }

        return redirect()->route('recipes.show', $recipe)
            ->with('success', 'Recipe created successfully.');
    }

    public function show(Recipe $recipe)
    {
        //TODO: We need to be able to share recipes between accounts
        Gate::authorize('view', $recipe);

        $recipe->load(['ingredients.units', 'steps']);

        $recipe->setRelation('ingredients', $recipe->ingredients->map(function ($ingredient) {
            $pivotData = $ingredient->pivot;
            
            return [
                'id' => $ingredient->id,
                'name' => $ingredient->name,
                'quantity' => $pivotData->quantity,
                'unit' => $pivotData->unit,
                'notes' => $pivotData->notes,
                'available_units' => $ingredient->units->map(function ($unit) {
                    return [
                        'unit' => $unit->unit,
                        'is_default' => $unit->is_default,
                        'conversion_factor' => $unit->conversion_factor
                    ];
                })
            ];
        }));

        return Inertia::render('Recipes/Show', [
            'recipe' => $recipe
        ]);
    }

    public function edit(Recipe $recipe)
    {
        Gate::authorize('update', $recipe);

        $recipe->load(['ingredients', 'steps']);
        $ingredients = Ingredient::query()->with('units')->get();

        return Inertia::render('Recipes/Edit', [
            'recipe' => $recipe,
            'ingredientsData' => $ingredients
        ]);
    }

    public function update(Request $request, Recipe $recipe)
    {
        Gate::authorize('update', $recipe);

        $validationRules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'source_url' => 'nullable|url',
            'image' => 'nullable|image|max:2048',
        ];
        
        // Only require ingredients and steps if they're provided
        if ($request->has('ingredients')) {
            $validationRules['ingredients'] = 'array|min:1';
            $validationRules['ingredients.*.ingredient_id'] = 'required|exists:ingredients,id';
            $validationRules['ingredients.*.quantity'] = 'required|numeric';
            $validationRules['ingredients.*.unit'] = 'nullable|string';
            $validationRules['ingredients.*.notes'] = 'nullable|string';
        }
        
        if ($request->has('steps')) {
            $validationRules['steps'] = 'array|min:1';
            $validationRules['steps.*.instruction'] = 'required|string';
            $validationRules['steps.*.order'] = 'required|integer|min:1';
        }

        $validated = $request->validate($validationRules);

        $recipe->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'source_url' => $validated['source_url'] ?? null,
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('recipe-images', 'public');
            $recipe->update(['image_path' => $path]);
        }

        // Only update ingredients if they're provided
        if ($request->has('ingredients')) {
            // Sync ingredients
            $recipe->ingredients()->sync(collect($validated['ingredients'])->mapWithKeys(function ($ingredient) {
                return [$ingredient['ingredient_id'] => [
                    'quantity' => $ingredient['quantity'],
                    'unit' => $ingredient['unit'],
                    'notes' => $ingredient['notes'],
                ]];
            }));
        }

        // Only update steps if they're provided
        if ($request->has('steps')) {
            // Update steps
            $recipe->steps()->delete();
            foreach ($validated['steps'] as $step) {
                $recipe->steps()->create([
                    'instruction' => $step['instruction'],
                    'order' => $step['order'],
                ]);
            }
        }

        return redirect()->route('recipes.show', $recipe)
            ->with('success', 'Recipe updated successfully.');
    }

    public function destroy(Recipe $recipe)
    {
        Gate::authorize('delete', $recipe);

        $recipe->delete();

        return redirect()->route('recipes.index')
            ->with('success', 'Recipe deleted successfully.');
    }

    public function importForm()
    {
        return Inertia::render('Recipes/Import');
    }

    public function importUrl(Request $request)
    {
        $request->validate([
            'url' => 'required|url'
        ]);

        try {
            $recipeImportService = new RecipeImportService();
            $recipe = $recipeImportService->importFromUrl($request->input('url'), auth()->id());
            
            return response()->json([
                'success' => true,
                'message' => 'Recipe imported successfully',
                'recipe' => [
                    'id' => $recipe->id,
                    'name' => $recipe->name,
                    'ingredients_count' => $recipe->ingredients->count(),
                    'steps_count' => $recipe->steps->count(),
                    'url' => route('recipes.show', $recipe->id)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Recipe import failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to import recipe: ' . $e->getMessage()
            ], 422);
        }
    }

    public function importImage(Request $request)
    {
        $validated = $request->validate([
            'image' => 'required|image|max:5120' // 5MB max
        ]);

        try {
            $path = $request->file('image')->store('recipe-imports', 'public');
            $fullPath = storage_path('app/public/' . $path);

            $recipe = app(RecipeImportService::class)->importFromImage(
                $fullPath,
                auth()->id()
            );

            return redirect()->route('recipes.edit', $recipe)
                ->with('success', 'Recipe imported successfully. Please review and adjust as needed.');
        } catch (\Exception $e) {
            return back()->withErrors(['image' => 'Unable to process recipe from this image.']);
        }
    }
}