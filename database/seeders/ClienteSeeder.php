<?php

namespace Database\Seeders;

use App\Models\Cliente;
use Illuminate\Database\Seeder;

class ClienteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Cliente::query()->updateOrCreate(
            ['rut' => '76.123.456-0'],
            [
                'razon_social' => 'Comercial Andina SpA',
                'email' => 'contacto@andina.test',
                'telefono' => '+56911111111',
            ],
        );

        Cliente::query()->updateOrCreate(
            ['rut' => '77.890.123-4'],
            [
                'razon_social' => 'Distribuidora del Sur Ltda',
                'email' => 'contacto@delsur.test',
                'telefono' => '+56922222222',
            ],
        );
    }
}
