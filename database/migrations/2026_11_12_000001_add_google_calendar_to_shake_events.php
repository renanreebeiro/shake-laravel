<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shake_events', function (Blueprint $table) {
            $table->boolean('all_day')->default(false);
            $table->date('ends_on')->nullable();
            $table->string('google_event_id', 255)->nullable()->unique();
            $table->string('google_calendar_key', 64)->nullable();
            $table->string('google_etag')->nullable();
            $table->string('google_synced_hash', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('shake_events', function (Blueprint $table) {
            $table->dropUnique(['google_event_id']);
            $table->dropColumn(['all_day', 'ends_on', 'google_event_id', 'google_calendar_key', 'google_etag', 'google_synced_hash']);
        });
    }
};
