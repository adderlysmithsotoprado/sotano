<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('order_payments', 'order_id')) {
                $table->foreignId('order_id')
                    ->after('id')
                    ->constrained('orders')
                    ->cascadeOnDelete();
            }

            if (!Schema::hasColumn('order_payments', 'method')) {
                $table->string('method')->after('order_id');
            }

            if (!Schema::hasColumn('order_payments', 'amount')) {
                $table->decimal('amount', 10, 2)->default(0)->after('method');
            }

            if (!Schema::hasColumn('order_payments', 'operation_number')) {
                $table->string('operation_number')->nullable()->after('amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            if (Schema::hasColumn('order_payments', 'operation_number')) {
                $table->dropColumn('operation_number');
            }

            if (Schema::hasColumn('order_payments', 'amount')) {
                $table->dropColumn('amount');
            }

            if (Schema::hasColumn('order_payments', 'method')) {
                $table->dropColumn('method');
            }

            if (Schema::hasColumn('order_payments', 'order_id')) {
                $table->dropConstrainedForeignId('order_id');
            }
        });
    }
};
