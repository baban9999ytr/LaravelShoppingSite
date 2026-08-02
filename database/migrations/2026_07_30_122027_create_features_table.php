<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('feature_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feature_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->timestamps();
        });

        Schema::create('feature_value_product', function (Blueprint $table) {
            $table->foreignId('feature_value_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['feature_value_id', 'product_id']);
        });

        Schema::create('feature_value_product_variant', function (Blueprint $table) {
            $table->foreignId('feature_value_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->primary(['feature_value_id', 'product_variant_id'], 'fv_pv_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_value_product_variant');
        Schema::dropIfExists('feature_value_product');
        Schema::dropIfExists('feature_values');
        Schema::dropIfExists('features');
    }
};