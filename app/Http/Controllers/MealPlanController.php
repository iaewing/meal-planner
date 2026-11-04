<?php

namespace App\Http\Controllers;

use App\Models\MealPlan;
use App\Models\Recipe;
use App\Services\GroceryListService;
use App\Services\MealPlanRandomizerService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MealPlanController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $startOfWeek = Carbon::now()->startOfWeek()->toDateString();

        $mealPlans = MealPlan::with(['recipes'])
            ->where('user_id', Auth::id())
            ->whereDate('start_date', '>=', $startOfWeek)
            ->latest()
            ->paginate(10, ['*'], 'page')
            ->withQueryString();

        $previousMealPlans = MealPlan::with(['recipes'])
            ->where('user_id', Auth::id())
            ->whereDate('start_date', '<', $startOfWeek)
            ->latest()
            ->paginate(10, ['*'], 'previous_page')
            ->withQueryString();

        return Inertia::render('MealPlans/Index', [
            'mealPlans' => $mealPlans,
            'previousMealPlans' => $previousMealPlans
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
            'meals.*.recipe_id' => [
                'required',
                Rule::exists('recipes', 'id')->where('user_id', Auth::id()),
            ],
            'meals.*.planned_date' => 'required|date|after_or_equal:start_date|before_or_equal:end_date',
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
            'meals.*.recipe_id' => [
                'required',
                Rule::exists('recipes', 'id')->where('user_id', Auth::id()),
            ],
            'meals.*.planned_date' => 'required|date|after_or_equal:start_date|before_or_equal:end_date',
            'meals.*.meal_type' => 'required|in:breakfast,lunch,dinner,snack',
        ]);

        $mealPlan->update([
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
        ]);

        $mealPlan->recipes()->detach();

        foreach ($validated['meals'] as $meal) {
            $mealPlan->recipes()->attach($meal['recipe_id'], [
                'planned_date' => $meal['planned_date'],
                'meal_type' => $meal['meal_type'],
            ]);
        }

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

    public function groceryList(MealPlan $mealPlan, GroceryListService $groceryListService)
    {
        $this->authorize('view', $mealPlan);

        return Inertia::render('MealPlans/GroceryList', [
            'mealPlan' => $mealPlan,
            'ingredients' => $groceryListService->ingredientsFor($mealPlan),
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
