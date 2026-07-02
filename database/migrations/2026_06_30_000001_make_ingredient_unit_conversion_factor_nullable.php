<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingredient_units', function (Blueprint $table) {
            $table->decimal('conversion_factor', 10, 4)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ingredient_units', function (Blueprint $table) {
            $table->decimal('conversion_factor', 10, 4)->nullable(false)->change();
        });
    }
};
