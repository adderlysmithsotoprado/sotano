<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->unsignedInteger('minimum_quantity')
                ->nullable()
                ->after('product_id');

            $table->decimal('promotional_unit_price', 10, 2)
                ->nullable()
                ->after('minimum_quantity');

            $table->decimal('discount_percentage', 5, 2)
                ->nullable()
                ->after('promotional_unit_price');

            $table->decimal('discount_amount', 10, 2)
                ->nullable()
                ->after('discount_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn([
                'minimum_quantity',
                'promotional_unit_price',
                'discount_percentage',
                'discount_amount',
            ]);
        });
    }
};
