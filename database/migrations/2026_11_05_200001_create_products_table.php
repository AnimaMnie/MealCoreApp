<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->index();
            $table->string('category', 40)->default('Inne');
            $table->decimal('kcal_100g', 6, 2);
            $table->decimal('protein_100g', 6, 2)->default(0);
            $table->decimal('carbs_100g', 6, 2)->default(0);
            $table->decimal('fat_100g', 6, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
