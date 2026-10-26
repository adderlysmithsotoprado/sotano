<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('command_batch_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('command_batch_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('order_item_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('product_name');
            $table->unsignedInteger('quantity');
            $table->text('notes')->nullable();
            $table->json('modifiers')->nullable();

            $table->string('status', 30)->default('pendiente');
            $table->timestamp('delivered_at')->nullable();

            $table->timestamps();

            $table->index(['command_batch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('command_batch_items');
    }
};
