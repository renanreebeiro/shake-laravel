<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('event_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shake_event_id')->constrained()->cascadeOnDelete();
            $table->string('action', 32);
            $table->string('source', 16);
            $table->string('actor_email')->nullable();
            $table->string('actor_name')->nullable();
            $table->string('previous_status', 16)->nullable();
            $table->string('new_status', 16)->nullable();
            $table->timestamp('occurred_at');
            $table->index(['shake_event_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_activities');
    }
};
