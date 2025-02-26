<?php

// Create the ingredient_units table
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, create the ingredient_units table
        Schema::create('ingredient_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->onDelete('cascade');
            $table->string('unit');
            $table->boolean('is_default')->default(false);
            $table->decimal('conversion_factor', 10, 4);
            $table->timestamps();
            $table->unique(['ingredient_id', 'unit']);
        });

        // Then, update the recipe_ingredients table
        Schema::table('recipe_ingredients', function (Blueprint $table) {
            $table->foreignId('ingredient_unit_id')->nullable()->constrained()->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First remove the foreign key from recipe_ingredients
        Schema::table('recipe_ingredients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ingredient_unit_id');
        });

        // Then drop the ingredient_units table
        Schema::dropIfExists('ingredient_units');
    }
};