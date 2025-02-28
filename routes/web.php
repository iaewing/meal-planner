<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\MealPlanController;
use App\Http\Controllers\RecipeController;
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

    Route::resource('recipes', RecipeController::class)->names([
        'index' => 'recipes.index',
        'create' => 'recipes.create',
        'store' => 'recipes.store',
        'show' => 'recipes.show',
        'edit' => 'recipes.edit',
        'update' => 'recipes.update',
        'destroy' => 'recipes.destroy',
        'import-url' => 'recipes.import-url',
        'import-form' => 'recipes.import-form',
        'import-image' => 'recipes.import-image',
    ]);

    Route::resource('meal-plans', MealPlanController::class)->names([
        'index' => 'meal-plans.index',
        'create' => 'meal-plans.create',
        'store' => 'meal-plans.store',
        'show' => 'meal-plans.show',
        'edit' => 'meal-plans.edit',
        'update' => 'meal-plans.update',
        'destroy' => 'meal-plans.destroy',
        'grocery-list' => 'meal-plans.grocery-list',
    ]);

    Route::resource('ingredients', IngredientController::class)->names([
        'index' => 'ingredients.index',
        'store' => 'ingredients.store',
    ]);
    Route::post('/ingredients/{ingredient}/add-unit', [IngredientController::class, 'addUnit'])
        ->name('ingredients.add-unit');

    Route::get('grocery-list/{mealPlan}', [MealPlanController::class, 'groceryList'])
        ->name('meal-plans.grocery-list');
});

require __DIR__ . '/auth.php';