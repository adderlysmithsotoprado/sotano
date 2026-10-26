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
        if (!Schema::hasColumn('order_items', 'discount')) {
            $table->decimal('discount', 10, 2)->default(0);
        }

        if (!Schema::hasColumn('order_items', 'promotion_name')) {
            $table->string('promotion_name')->nullable();
        }
    });
    }

public function down(): void
    {
    Schema::table('order_items', function (Blueprint $table) {
        if (Schema::hasColumn('order_items', 'discount')) {
            $table->dropColumn('discount');
        }

        if (Schema::hasColumn('order_items', 'promotion_name')) {
            $table->dropColumn('promotion_name');
        }
    });
    }

};
