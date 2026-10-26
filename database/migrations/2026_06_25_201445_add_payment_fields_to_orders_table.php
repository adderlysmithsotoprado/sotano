<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {

            $table->enum('payment_method', [
                'efectivo',
                'tarjeta',
                'yape',
                'plin',
                'transferencia',
                'mixto'
            ])->nullable();

            $table->decimal('cash_amount',10,2)->default(0);

            $table->decimal('card_amount',10,2)->default(0);

            $table->decimal('yape_amount',10,2)->default(0);

            $table->decimal('plin_amount',10,2)->default(0);

            $table->decimal('transfer_amount',10,2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {

            $table->dropColumn([
                'payment_method',
                'cash_amount',
                'card_amount',
                'yape_amount',
                'plin_amount',
                'transfer_amount',
            ]);

        });
    }
};
