<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Recipe;
use App\Models\MealPlan;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    public function index()
    {
        try {
            $recentRecipes = Recipe::where('user_id', auth()->id())
                ->latest()
                ->take(4)
                ->get();

            $activeMealPlan = MealPlan::where('user_id', auth()->id())
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->with(['recipes' => function ($query) {
                    $query->whereDate('meal_plan_recipes.planned_date', today());
                }])
                ->first();

            return Inertia::render('Home', [
                'recentRecipes' => $recentRecipes,
                'activeMealPlan' => $activeMealPlan,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in HomeController@index: ' . $e->getMessage());
            throw $e;
        }
    }
} 