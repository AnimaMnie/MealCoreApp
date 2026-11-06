<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('meal_date');
            $table->string('meal_type', 20); // breakfast | lunch | dinner | snack | supper
            $table->string('name', 120)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'meal_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meals');
    }
};
