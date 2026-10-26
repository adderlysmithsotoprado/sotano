<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'status')) {
                $table->enum('status', [
                    'pendiente',
                    'preparando',
                    'listo',
                    'entregado'
                ])->default('pendiente');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
