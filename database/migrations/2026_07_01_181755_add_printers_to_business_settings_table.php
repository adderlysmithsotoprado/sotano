<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
        if (!Schema::hasColumn('business_settings', 'kitchen_printer')) {
        $table->string('kitchen_printer')->nullable();
        }

        if (!Schema::hasColumn('business_settings', 'bar_printer')) {
        $table->string('bar_printer')->nullable();
        }

        if (!Schema::hasColumn('business_settings', 'ticket_printer')) {
        $table->string('ticket_printer')->nullable();
        }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            //
        });
    }
};
