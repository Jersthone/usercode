<?php

namespace Database\Seeders;

use App\Models\Estado;
use Illuminate\Database\Seeder;

class EstadoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['pendiente', 'pagado', 'despachado', 'entregado', 'anulado'] as $nombre) {
            Estado::query()->firstOrCreate(['nombre' => $nombre]);
        }
    }
}
