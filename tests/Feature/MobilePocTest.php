<?php

use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('requires authentication', function () {
    $this->get('/mobile')
        ->assertRedirect('/login');
});

it('renders the mobile poc entry point with hosted data', function () {
    $user = User::factory()->create();
    Recipe::factory()->count(2)->create(['user_id' => $user->id]);
    $mealPlan = MealPlan::create([
        'user_id' => $user->id,
        'name' => 'POC Meal Plan',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDay()->toDateString(),
    ]);

    $this->actingAs($user)
        ->get('/mobile')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Mobile/Poc')
            ->where('stats.recipes', 2)
            ->where('stats.mealPlans', 1)
            ->where('groceryMealPlan.id', $mealPlan->id)
        );
});
