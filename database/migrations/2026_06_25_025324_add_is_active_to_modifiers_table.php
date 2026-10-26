<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modifiers', function (Blueprint $table) {
            if (!Schema::hasColumn('modifiers', 'name')) {
                $table->string('name')->nullable();
            }

            if (!Schema::hasColumn('modifiers', 'extra_price')) {
                $table->decimal('extra_price', 10, 2)->default(0);
            }

            if (!Schema::hasColumn('modifiers', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('modifiers', function (Blueprint $table) {
            if (Schema::hasColumn('modifiers', 'is_active')) {
                $table->dropColumn('is_active');
            }

            if (Schema::hasColumn('modifiers', 'extra_price')) {
                $table->dropColumn('extra_price');
            }

            if (Schema::hasColumn('modifiers', 'name')) {
                $table->dropColumn('name');
            }
        });
    }
};
