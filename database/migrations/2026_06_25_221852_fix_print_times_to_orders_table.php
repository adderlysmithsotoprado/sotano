<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'paid_at')) {
                $table->timestamp('paid_at')->nullable();
            }

            if (!Schema::hasColumn('orders', 'printed_at')) {
                $table->timestamp('printed_at')->nullable();
            }

            if (!Schema::hasColumn('orders', 'ticket_printed_at')) {
                $table->timestamp('ticket_printed_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'ticket_printed_at')) {
                $table->dropColumn('ticket_printed_at');
            }

            if (Schema::hasColumn('orders', 'printed_at')) {
                $table->dropColumn('printed_at');
            }

            if (Schema::hasColumn('orders', 'paid_at')) {
                $table->dropColumn('paid_at');
            }
        });
    }
};
