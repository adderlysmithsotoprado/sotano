<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('command_batches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('area', 30);
            $table->string('printer_name')->nullable();

            $table->string('status', 30)->default('pendiente');

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('printed_at')->nullable();

            $table->text('print_error')->nullable();

            $table->timestamps();

            $table->index(['order_id', 'area']);
            $table->index(['status', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('command_batches');
    }
};
