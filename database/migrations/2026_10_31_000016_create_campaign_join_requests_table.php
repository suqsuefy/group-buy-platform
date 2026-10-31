<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaign_join_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('idempotency_key', 100);
            $table->unsignedInteger('quantity');
            $table->timestamps();
            $table->unique(['campaign_id', 'user_id', 'idempotency_key'], 'campaign_join_request_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_join_requests');
    }
};
