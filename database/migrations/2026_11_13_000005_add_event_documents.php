<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shake_events', function (Blueprint $table) {
            $table->unsignedBigInteger('document_total')->nullable();
            $table->text('document_data')->nullable();
        });
        Schema::create('event_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shake_event_id')->constrained();
            $table->uuid('request_key')->unique();
            $table->string('kind', 20);
            $table->string('title', 120);
            $table->longText('body');
            $table->string('receipt_number', 500)->nullable();
            $table->unsignedBigInteger('received_amount')->nullable();
            $table->date('received_on')->nullable();
            $table->string('payment_method', 30)->nullable();
            $table->timestamp('generated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_documents');
        Schema::table('shake_events', fn (Blueprint $table) => $table->dropColumn(['document_total', 'document_data']));
    }
};
