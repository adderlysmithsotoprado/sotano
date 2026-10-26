<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(
                ['restaurant_table_id', 'status'],
                'orders_table_status_index'
            );

            $table->index(
                ['status', 'paid_at'],
                'orders_status_paid_at_index'
            );
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->index(
                ['order_id', 'status'],
                'order_items_order_status_index'
            );

            $table->index(
                ['order_id', 'command_sent'],
                'order_items_order_command_sent_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_table_status_index');
            $table->dropIndex('orders_status_paid_at_index');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('order_items_order_status_index');
            $table->dropIndex('order_items_order_command_sent_index');
        });
    }
};