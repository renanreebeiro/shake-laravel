<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('event_coverages', function (Blueprint $table) {
            $table->foreignId('shake_event_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('type', 12)->default('pending');
            $table->json('contacts');
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->date('deadline')->nullable();
            $table->string('parent_id')->nullable();
            $table->json('folders')->nullable();
            $table->string('folder_name')->nullable();
            $table->json('grants')->nullable();
            $table->boolean('pending')->default(true)->index();
            $table->string('failure_stage')->nullable();
            $table->string('failure_message')->nullable();
            $table->timestamps();
        });
        Schema::create('event_coverage_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shake_event_id')->constrained()->cascadeOnDelete();
            $table->string('fingerprint', 64);
            $table->string('professional');
            $table->string('channel', 12);
            $table->string('status', 20);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['shake_event_id', 'fingerprint']);
        });
        Schema::create('coverage_drive_connections', function (Blueprint $table) {
            $table->id();
            $table->text('refresh_token');
            $table->string('parent_id')->nullable();
            $table->string('parent_name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coverage_drive_connections');
        Schema::dropIfExists('event_coverage_deliveries');
        Schema::dropIfExists('event_coverages');
    }
};
