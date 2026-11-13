<?php
namespace Database\Seeders;

use App\Models\DocumentTemplate;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DocumentTemplate::firstOrCreate(['slug' => 'contrato-evento'], [
            'title' => 'Contrato de evento',
            'body' => file_get_contents(__DIR__.'/contrato.txt'),
            'active' => true,
        ]);

        DocumentTemplate::firstOrCreate(['slug' => 'recibo-evento'], [
            'title' => 'Recibo de evento',
            'body' => file_get_contents(__DIR__.'/recibo-evento.txt'),
            'active' => true,
        ]);
    }
}
