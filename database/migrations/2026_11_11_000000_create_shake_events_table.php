<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shake_events', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->date('event_date')->index();
            $table->time('starts_at');
            $table->time('ends_at')->nullable();
            $table->string('address', 250);
            $table->unsignedInteger('quantity')->nullable();
            $table->string('quantity_unit', 40)->nullable();
            $table->string('mascot', 12);
            $table->string('status', 16);
            $table->string('responsible', 120)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shake_events');
    }
};
