<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shake_events', fn (Blueprint $table) => $table->date('payment_due_on')->nullable());
        Schema::create('event_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shake_event_id')->constrained();
            $table->uuid('request_key')->unique();
            $table->unsignedBigInteger('amount');
            $table->date('received_on')->index();
            $table->string('method', 30);
        });
        Schema::table('event_documents', fn (Blueprint $table) => $table->foreignId('event_payment_id')->nullable()->unique()->constrained());
        DB::table('event_documents')->whereNotNull('received_amount')->whereNotNull('received_on')->orderBy('id')->chunkById(100, function ($documents) {
            foreach ($documents as $document) {
                $payment = DB::table('event_payments')->insertGetId([
                    'shake_event_id' => $document->shake_event_id, 'request_key' => $document->request_key,
                    'amount' => $document->received_amount, 'received_on' => $document->received_on, 'method' => $document->payment_method,
                ]);
                DB::table('event_documents')->where('id', $document->id)->update(['event_payment_id' => $payment]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('event_documents', function (Blueprint $table) {
            $table->dropForeign(['event_payment_id']);
            $table->dropUnique(['event_payment_id']);
            $table->dropColumn('event_payment_id');
        });
        Schema::dropIfExists('event_payments');
        Schema::table('shake_events', fn (Blueprint $table) => $table->dropColumn('payment_due_on'));
    }
};
