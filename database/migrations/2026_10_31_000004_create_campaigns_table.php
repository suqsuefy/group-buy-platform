<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('regular_price', 10, 2);
            $table->decimal('group_price', 10, 2);
            $table->unsignedInteger('min_participants')->default(10);
            $table->unsignedInteger('current_participants')->default(0);
            $table->unsignedInteger('target_participants')->default(10);
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->string('city')->nullable();
            $table->enum('status', ['draft', 'active', 'fulfilled', 'cancelled', 'expired'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
