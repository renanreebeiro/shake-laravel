<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shake_events', function (Blueprint $table) {
            $table->string('maps_place_id')->nullable();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('shake_events', function (Blueprint $table) {
            $table->dropColumn('maps_place_id');
            $table->dropSoftDeletes();
        });
    }
};
