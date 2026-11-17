<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Zapisane podsumowania dni - wypełniane asynchronicznie przez kolejkę
 * (event MealLogChanged -> listener UpdateDailySummary). Służą do statystyk tygodnia/miesiąca.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('meals_count')->default(0);
            $table->decimal('kcal', 8, 1)->default(0);
            $table->decimal('protein', 7, 1)->default(0);
            $table->decimal('carbs', 7, 1)->default(0);
            $table->decimal('fat', 7, 1)->default(0);
            $table->unsignedInteger('calorie_goal');
            $table->boolean('goal_exceeded')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_summaries');
    }
};
