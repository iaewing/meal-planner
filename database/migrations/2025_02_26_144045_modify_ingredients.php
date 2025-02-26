<?php

use App\Models\Ingredient;
use App\Models\IngredientUnit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $ingredients = Ingredient::all();

        foreach ($ingredients as $ingredient) {
            if (empty($ingredient->unit)) {
                continue;
            }

            $unit = IngredientUnit::create([
                'ingredient_id' => $ingredient->id,
                'unit' => $ingredient->unit,
                'is_default' => true,
                'conversion_factor' => 1.0  // Base unit has conversion factor of 1
            ]);

            // Update all recipe_ingredients that use this ingredient
            // Using the DB facade to work directly with the pivot table
            DB::table('recipe_ingredients')
                ->where('ingredient_id', $ingredient->id)
                ->update(['ingredient_unit_id' => $unit->id]);
        }
        // Optional: If you want to remove the unit column from ingredients table
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn('unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is hard to reverse precisely, but we can clear out the unit IDs
        DB::table('recipe_ingredients')->update(['ingredient_unit_id' => null]);
        IngredientUnit::query()->delete();

        Schema::table('ingredients', function (Blueprint $table) {
            $table->string('unit')->nullable();
        });
    }
};