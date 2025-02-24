<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\MealPlanController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::middleware(['auth'])->group(function () {
    // Home route
    Route::get('/', [HomeController::class, 'index'])->name('home');

    // Recipe routes
    Route::get('recipes/import', [RecipeController::class, 'importForm'])
        ->name('recipes.import');
    Route::post('recipes/import/url', [RecipeController::class, 'importUrl'])
        ->name('recipes.import.url');
    Route::post('recipes/import/image', [RecipeController::class, 'importImage'])
        ->name('recipes.import.image');
    Route::resource('recipes', RecipeController::class);  // Move this AFTER the specific routes

    // Meal plan routes
    Route::resource('meal-plans', MealPlanController::class);
    Route::get('grocery-list/{mealPlan}', [MealPlanController::class, 'groceryList'])
        ->name('meal-plans.grocery-list');
});

require __DIR__.'/auth.php'; 