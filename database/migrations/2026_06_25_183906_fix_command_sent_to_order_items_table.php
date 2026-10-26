<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'command_sent')) {
                $table->boolean('command_sent')->default(false);
            }

            if (!Schema::hasColumn('order_items', 'printed_at')) {
                $table->timestamp('printed_at')->nullable();
            }

            if (!Schema::hasColumn('order_items', 'status')) {
                $table->string('status')->default('pendiente');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('order_items', 'printed_at')) {
                $table->dropColumn('printed_at');
            }

            if (Schema::hasColumn('order_items', 'command_sent')) {
                $table->dropColumn('command_sent');
            }
        });
    }
};
