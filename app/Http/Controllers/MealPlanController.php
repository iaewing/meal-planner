<?php

namespace App\Http\Controllers;

use App\Models\MealPlan;
use App\Models\Recipe;
use App\Services\MealPlanRandomizerService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MealPlanController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $mealPlans = MealPlan::with(['recipes'])
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(10);

        return Inertia::render('MealPlans/Index', [
            'mealPlans' => $mealPlans
        ]);
    }

    public function create()
    {
        $recipes = Recipe::where('user_id', Auth::id())
            ->select('id', 'name')
            ->get();

        return Inertia::render('MealPlans/Create', [
            'recipes' => $recipes
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'meals' => 'required|array|min:1',
            'meals.*.recipe_id' => 'required|exists:recipes,id',
            'meals.*.planned_date' => 'required|date',
            'meals.*.meal_type' => 'required|in:breakfast,lunch,dinner,snack',
        ]);

        $mealPlan = MealPlan::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
        ]);

        foreach ($validated['meals'] as $meal) {
            $mealPlan->recipes()->attach($meal['recipe_id'], [
                'planned_date' => $meal['planned_date'],
                'meal_type' => $meal['meal_type'],
            ]);
        }

        return redirect()->route('meal-plans.show', $mealPlan)
            ->with('success', 'Meal plan created successfully.');
    }

    public function show(MealPlan $mealPlan)
    {
        $this->authorize('view', $mealPlan);

        $mealPlan->load(['recipes' => function ($query) {
            $query->with(['ingredients', 'steps']);
        }]);

        return Inertia::render('MealPlans/Show', [
            'mealPlan' => $mealPlan
        ]);
    }

    public function edit(MealPlan $mealPlan)
    {
        $this->authorize('update', $mealPlan);

        $recipes = Recipe::where('user_id', Auth::id())
            ->select('id', 'name')
            ->get();

        $mealPlan->load('recipes');

        return Inertia::render('MealPlans/Edit', [
            'mealPlan' => $mealPlan,
            'recipes' => $recipes
        ]);
    }

    public function update(Request $request, MealPlan $mealPlan)
    {
        $this->authorize('update', $mealPlan);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'meals' => 'required|array|min:1',
            'meals.*.recipe_id' => 'required|exists:recipes,id',
            'meals.*.planned_date' => 'required|date',
            'meals.*.meal_type' => 'required|in:breakfast,lunch,dinner,snack',
        ]);

        $mealPlan->update([
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
        ]);

        $mealPlan->recipes()->sync(collect($validated['meals'])->mapWithKeys(function ($meal) {
            return [$meal['recipe_id'] => [
                'planned_date' => $meal['planned_date'],
                'meal_type' => $meal['meal_type'],
            ]];
        }));

        return redirect()->route('meal-plans.show', $mealPlan)
            ->with('success', 'Meal plan updated successfully.');
    }

    public function destroy(MealPlan $mealPlan)
    {
        $this->authorize('delete', $mealPlan);
        
        $mealPlan->delete();

        return redirect()->route('meal-plans.index')
            ->with('success', 'Meal plan deleted successfully.');
    }

    public function groceryList(MealPlan $mealPlan)
    {
        $this->authorize('view', $mealPlan);

        $ingredients = $mealPlan->recipes()
            ->with('ingredients')
            ->get()
            ->pluck('ingredients')
            ->flatten()
            ->groupBy('id')
            ->map(function ($group) {
                $first = $group->first();
                return [
                    'id' => $first->id,
                    'name' => $first->name,
                    'total_quantity' => $group->sum('pivot.quantity'),
                    'unit' => $first->pivot->unit,
                ];
            })
            ->values();

        return Inertia::render('MealPlans/GroceryList', [
            'mealPlan' => $mealPlan,
            'ingredients' => $ingredients
        ]);
    }

    public function randomizeForm()
    {
        return Inertia::render('MealPlans/Randomize');
    }

    public function randomize(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'name' => 'required|string|max:255',
            'meal_types' => 'array',
            'meal_types.*' => 'in:breakfast,lunch,dinner,snack',
        ]);

        $user = Auth::id();

        $mealTypes = $validated['meal_types'] ?? ['breakfast', 'lunch', 'dinner'];
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        
        $randomizer = app(MealPlanRandomizerService::class);
        $randomMeals = $randomizer->generateRandomMealPlan($startDate, $endDate, $user, $mealTypes);
        
        if (empty($randomMeals)) {
            return redirect()->route('meal-plans.create')
                ->with('error', 'You need to add some recipes before creating a random meal plan.');
        }
        
        $mealPlan = MealPlan::create([
            'user_id' => $user,
            'name' => $validated['name'],
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
        
        foreach ($randomMeals as $meal) {
            $mealPlan->recipes()->attach($meal['recipe_id'], [
                'planned_date' => $meal['planned_date'],
                'meal_type' => $meal['meal_type'],
            ]);
        }
        
        return redirect()->route('meal-plans.show', $mealPlan)
            ->with('success', 'Random meal plan created successfully.');
    }
}