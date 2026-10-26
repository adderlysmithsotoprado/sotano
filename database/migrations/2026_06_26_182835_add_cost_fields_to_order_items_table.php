<?php

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
        Schema::table('order_items', function (Blueprint $table) {
        if (!Schema::hasColumn('order_items', 'unit_cost')) {
        $table->decimal('unit_cost', 10, 2)->default(0);
        }

        if (!Schema::hasColumn('order_items', 'profit')) {
        $table->decimal('profit', 10, 2)->default(0);
        }
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            //
        });
    }
};
