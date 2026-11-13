<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->json('area_access')->nullable());
        $this->updateTitles([
            'contrato-evento' => ['Contrato de prestação de serviços', 'Contrato de evento'],
            'recibo-evento' => ['Recibo de pagamento — evento', 'Recibo de evento'],
        ]);
    }

    public function down(): void
    {
        $this->updateTitles([
            'contrato-evento' => ['Contrato de evento', 'Contrato de prestação de serviços'],
            'recibo-evento' => ['Recibo de evento', 'Recibo de pagamento — evento'],
        ]);
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('area_access'));
    }

    private function updateTitles(array $titles): void
    {
        foreach ($titles as $slug => [$before, $after]) {
            DB::table('document_templates')->where('slug', $slug)->where('title', $before)->update(['title' => $after]);
        }
    }
};
