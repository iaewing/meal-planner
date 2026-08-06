<?php

namespace Database\Seeders;

use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class MealPlanSeeder extends Seeder
{
    public User $User;

    public function run(): void
    {
        $this->user = User::query()->where('email', 'test@example.com')->first();

        if (! $this->user) {
            return;
        }

        $this->createWeek();
        $this->createWeek(Carbon::today()->subWeek(), Carbon::today()->subWeeks(2));
    }

    private function createWeek($startDate = null, $endDate = null): void {
        if (! $startDate) {
            $startDate = Carbon::today()->startOfWeek();
        }
        if (! $endDate) {
            $endDate = Carbon::today()->endOfWeek();
        }

        $recipesByName = Recipe::query()
            ->where('user_id', $this->user->id)
            ->pluck('id', 'name');

        if ($recipesByName->isEmpty()) {
            return;
        }

        $mealPlan = MealPlan::query()->create(
            [
                'user_id' => $this->user->id,
                'name' => $startDate->toDateString(),
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        );

        $mealPlan->recipes()->detach();

        foreach ($this->meals($startDate) as $meal) {
            $recipeId = $recipesByName->get($meal['recipe']);

            if (! $recipeId) {
                continue;
            }

            $mealPlan->recipes()->attach($recipeId, [
                'planned_date' => $meal['date']->toDateString(),
                'meal_type' => $meal['meal_type'],
            ]);
        }
    }

    /**
     * @return list<array{recipe: string, date: Carbon, meal_type: string}>
     */
    private function meals(Carbon $startDate): array
    {
        $weeklySchedule = [
            ['breakfast' => 'Overnight Oats', 'dinner' => 'Grilled Lemon Chicken'],
            ['breakfast' => 'Scrambled Eggs', 'dinner' => 'Garlic Butter Pasta'],
            ['breakfast' => 'Buttermilk Pancakes', 'dinner' => 'Grilled Lemon Chicken'],
            ['breakfast' => 'Overnight Oats', 'dinner' => 'Garlic Butter Pasta'],
            ['breakfast' => 'Scrambled Eggs', 'dinner' => 'Buttermilk Pancakes'],
            ['breakfast' => 'Buttermilk Pancakes', 'dinner' => 'Garlic Butter Pasta'],
            ['breakfast' => 'Overnight Oats', 'dinner' => 'Grilled Lemon Chicken'],
        ];

        $today = Carbon::today();
        $todayIndex = $startDate->diffInDays($today);

        if ($todayIndex >= 0 && $todayIndex < count($weeklySchedule)) {
            $weeklySchedule[$todayIndex] = [
                'breakfast' => 'Scrambled Eggs',
                'dinner' => 'Buttermilk Pancakes',
            ];
        }

        $meals = [];

        foreach ($weeklySchedule as $dayOffset => $dayMeals) {
            $date = $startDate->copy()->addDays($dayOffset);

            foreach ($dayMeals as $mealType => $recipeName) {
                $meals[] = [
                    'recipe' => $recipeName,
                    'date' => $date,
                    'meal_type' => $mealType,
                ];
            }
        }

        return $meals;
    }
}
