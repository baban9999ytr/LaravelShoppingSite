<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shopping_orders', function (Blueprint $table) {
            $table->boolean('did_arrive')->default(false)->after('estimated_arrival_at');
        });
    }

    public function down(): void
    {
        Schema::table('shopping_orders', function (Blueprint $table) {
            $table->dropColumn('did_arrive');
        });
    }
};