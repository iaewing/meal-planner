<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateRecipeRequest;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Services\RecipeImportService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RecipeController extends Controller
{
    public function index()
    {
        $recipes = Recipe::with(['ingredients', 'steps'])
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(12);

        return Inertia::render('Recipes/Index', [
            'recipes' => $recipes
        ]);
    }

    public function create()
    {
        $ingredients = Ingredient::query()
            ->get();

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
        $this->authorize('view', $recipe);

        $recipe->load(['ingredients', 'steps']);

        return Inertia::render('Recipes/Show', [
            'recipe' => $recipe
        ]);
    }

    public function edit(Recipe $recipe)
    {
        $this->authorize('update', $recipe);

        $recipe->load(['ingredients', 'steps']);

        return Inertia::render('Recipes/Edit', [
            'recipe' => $recipe
        ]);
    }

    public function update(Request $request, Recipe $recipe)
    {
        $this->authorize('update', $recipe);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'source_url' => 'nullable|url',
            'image' => 'nullable|image|max:2048',
            'ingredients' => 'required|array|min:1',
            'ingredients.*.ingredient_id' => 'required|exists:ingredients,id',
            'ingredients.*.quantity' => 'required|numeric',
            'ingredients.*.unit' => 'nullable|string',
            'ingredients.*.notes' => 'nullable|string',
            'steps' => 'required|array|min:1',
            'steps.*.instruction' => 'required|string',
            'steps.*.order' => 'required|integer|min:1',
        ]);

        $recipe->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'source_url' => $validated['source_url'],
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('recipe-images', 'public');
            $recipe->update(['image_path' => $path]);
        }

        // Sync ingredients
        $recipe->ingredients()->sync(collect($validated['ingredients'])->mapWithKeys(function ($ingredient) {
            return [$ingredient['ingredient_id'] => [
                'quantity' => $ingredient['quantity'],
                'unit' => $ingredient['unit'],
                'notes' => $ingredient['notes'],
            ]];
        }));

        // Update steps
        $recipe->steps()->delete();
        foreach ($validated['steps'] as $step) {
            $recipe->steps()->create([
                'instruction' => $step['instruction'],
                'order' => $step['order'],
            ]);
        }

        return redirect()->route('recipes.show', $recipe)
            ->with('success', 'Recipe updated successfully.');
    }

    public function destroy(Recipe $recipe)
    {
        $this->authorize('delete', $recipe);

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
        $validated = $request->validate([
            'url' => 'required|url'
        ]);

        try {
            $recipe = app(RecipeImportService::class)->importFromUrl(
                $validated['url'],
                auth()->id()
            );

            return redirect()->route('recipes.edit', $recipe)
                ->with('success', 'Recipe imported successfully. Please review and adjust as needed.');
        } catch (\Exception $e) {
            return back()->withErrors(['url' => 'Unable to import recipe from this URL.']);
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

    private function authorize(string $string, Recipe $recipe)
    {
        return true;
    }
}