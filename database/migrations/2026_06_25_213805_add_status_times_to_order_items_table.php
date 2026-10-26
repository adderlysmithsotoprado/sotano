<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'preparing_at')) {
                $table->timestamp('preparing_at')->nullable();
            }

            if (!Schema::hasColumn('order_items', 'ready_at')) {
                $table->timestamp('ready_at')->nullable();
            }

            if (!Schema::hasColumn('order_items', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'delivered_at')) {
                $table->dropColumn('delivered_at');
            }

            if (Schema::hasColumn('order_items', 'ready_at')) {
                $table->dropColumn('ready_at');
            }

            if (Schema::hasColumn('order_items', 'preparing_at')) {
                $table->dropColumn('preparing_at');
            }
        });
    }
};
