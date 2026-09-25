<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Sucursal;
use Illuminate\Database\Seeder;

class SucursalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $andina = Cliente::query()->where('rut', '76.123.456-0')->firstOrFail();
        $delSur = Cliente::query()->where('rut', '77.890.123-4')->firstOrFail();

        Sucursal::query()->updateOrCreate(
            ['cliente_id' => $andina->id, 'nombre' => 'Casa Matriz'],
            [
                'direccion' => 'Av. Providencia 1234',
                'ciudad' => 'Santiago',
                'telefono' => '+56223456789',
            ],
        );

        Sucursal::query()->updateOrCreate(
            ['cliente_id' => $andina->id, 'nombre' => 'Bodega Valparaíso'],
            [
                'direccion' => 'Av. Argentina 500',
                'ciudad' => 'Valparaíso',
                'telefono' => '+56322345678',
            ],
        );

        Sucursal::query()->updateOrCreate(
            ['cliente_id' => $delSur->id, 'nombre' => 'Sucursal Concepción'],
            [
                'direccion' => 'Barros Arana 800',
                'ciudad' => 'Concepción',
                'telefono' => '+56412345678',
            ],
        );
    }
}
