<?php

use App\Models\Ingredient;
use App\Services\IngredientNormalizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->string('normalized_name')->nullable()->after('name');
        });

        $normalizer = app(IngredientNormalizer::class);

        Ingredient::query()->each(function (Ingredient $ingredient) use ($normalizer) {
            $ingredient->update([
                'normalized_name' => $normalizer->normalize($ingredient->name),
            ]);
        });

        $duplicateGroups = Ingredient::query()
            ->select('normalized_name', DB::raw('MIN(id) as canonical_id'))
            ->groupBy('normalized_name')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            $canonical = Ingredient::query()->find($group->canonical_id);
            $duplicates = Ingredient::query()
                ->where('normalized_name', $group->normalized_name)
                ->where('id', '!=', $group->canonical_id)
                ->get();

            foreach ($duplicates as $duplicate) {
                $pivots = DB::table('recipe_ingredients')
                    ->where('ingredient_id', $duplicate->id)
                    ->get();

                foreach ($pivots as $pivot) {
                    $existingPivot = DB::table('recipe_ingredients')
                        ->where('recipe_id', $pivot->recipe_id)
                        ->where('ingredient_id', $canonical->id)
                        ->first();

                    if ($existingPivot) {
                        DB::table('recipe_ingredients')->where('id', $pivot->id)->delete();
                    } else {
                        DB::table('recipe_ingredients')
                            ->where('id', $pivot->id)
                            ->update(['ingredient_id' => $canonical->id]);
                    }
                }

                DB::table('ingredient_units')
                    ->where('ingredient_id', $duplicate->id)
                    ->delete();

                DB::table('ingredient_nutrition')
                    ->where('ingredient_id', $duplicate->id)
                    ->delete();

                $duplicate->delete();
            }
        }

        Schema::table('ingredients', function (Blueprint $table) {
            $table->string('normalized_name')->nullable(false)->change();
            $table->unique('normalized_name');
        });
    }

    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropUnique(['normalized_name']);
            $table->dropColumn('normalized_name');
        });
    }
};
