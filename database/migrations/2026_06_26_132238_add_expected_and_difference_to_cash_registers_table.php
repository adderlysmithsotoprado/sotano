<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            if (!Schema::hasColumn('cash_registers', 'expected_amount')) {
                $table->decimal('expected_amount', 10, 2)->nullable();
            }

            if (!Schema::hasColumn('cash_registers', 'difference_amount')) {
                $table->decimal('difference_amount', 10, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            if (Schema::hasColumn('cash_registers', 'difference_amount')) {
                $table->dropColumn('difference_amount');
            }

            if (Schema::hasColumn('cash_registers', 'expected_amount')) {
                $table->dropColumn('expected_amount');
            }
        });
    }
};
