<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_item_modifiers', function (Blueprint $table) {
            if (!Schema::hasColumn('order_item_modifiers', 'order_item_id')) {
                $table->foreignId('order_item_id')
                    ->after('id')
                    ->constrained('order_items')
                    ->cascadeOnDelete();
            }

            if (!Schema::hasColumn('order_item_modifiers', 'modifier_id')) {
                $table->foreignId('modifier_id')
                    ->after('order_item_id')
                    ->constrained('modifiers')
                    ->cascadeOnDelete();
            }

            if (!Schema::hasColumn('order_item_modifiers', 'modifier_name')) {
                $table->string('modifier_name')
                    ->after('modifier_id');
            }

            if (!Schema::hasColumn('order_item_modifiers', 'extra_price')) {
                $table->decimal('extra_price', 10, 2)
                    ->default(0)
                    ->after('modifier_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_item_modifiers', function (Blueprint $table) {
            if (Schema::hasColumn('order_item_modifiers', 'extra_price')) {
                $table->dropColumn('extra_price');
            }

            if (Schema::hasColumn('order_item_modifiers', 'modifier_name')) {
                $table->dropColumn('modifier_name');
            }

            if (Schema::hasColumn('order_item_modifiers', 'modifier_id')) {
                $table->dropConstrainedForeignId('modifier_id');
            }

            if (Schema::hasColumn('order_item_modifiers', 'order_item_id')) {
                $table->dropConstrainedForeignId('order_item_id');
            }
        });
    }
};
