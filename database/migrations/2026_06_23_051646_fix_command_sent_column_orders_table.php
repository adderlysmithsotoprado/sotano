<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('orders', 'command_sent')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->boolean('command_sent')->default(false)->after('ready');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'command_sent')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('command_sent');
            });
        }
    }
};
