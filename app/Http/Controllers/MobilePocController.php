<?php

namespace App\Http\Controllers;

use App\Models\MealPlan;
use App\Models\Recipe;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MobilePocController extends Controller
{
    public function __invoke(): Response
    {
        $userId = Auth::id();

        $activeMealPlan = MealPlan::query()
            ->where('user_id', $userId)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->latest()
            ->first(['id', 'name', 'start_date', 'end_date']);

        $latestMealPlan = MealPlan::query()
            ->where('user_id', $userId)
            ->latest()
            ->first(['id', 'name', 'start_date', 'end_date']);

        return Inertia::render('Mobile/Poc', [
            'stats' => [
                'recipes' => Recipe::where('user_id', $userId)->count(),
                'mealPlans' => MealPlan::where('user_id', $userId)->count(),
            ],
            'groceryMealPlan' => $activeMealPlan ?? $latestMealPlan,
        ]);
    }
}
