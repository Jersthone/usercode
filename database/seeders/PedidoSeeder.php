<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\DetallePedido;
use App\Models\Estado;
use App\Models\HistorialEstadoPedido;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Database\Seeder;

class PedidoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sucursal = Sucursal::query()
            ->where('nombre', 'Casa Matriz')
            ->whereHas('cliente', function ($query) {
                $query->where('rut', '76.123.456-0');
            })
            ->firstOrFail();

        if (Pedido::query()->where('sucursal_id', $sucursal->id)->exists()) {
            return;
        }

        $usuario = User::query()->where('email', 'test@example.com')->firstOrFail();
        $pendiente = Estado::query()->where('nombre', 'pendiente')->firstOrFail();
        $pagado = Estado::query()->where('nombre', 'pagado')->firstOrFail();
        $creadoEn = now()->subHours(2);
        $pagadoEn = now()->subHour();

        $pedido = new Pedido([
            'sucursal_id' => $sucursal->id,
            'estado_id' => $pagado->id,
            'total' => 0,
        ]);
        $pedido->created_at = $creadoEn;
        $pedido->updated_at = $creadoEn;
        $pedido->save();

        foreach ($this->lineas() as $linea) {
            DetallePedido::query()->create([
                'pedido_id' => $pedido->id,
                'producto_id' => $linea['producto']->id,
                'cantidad' => $linea['cantidad'],
                'precio_unitario' => $linea['producto']->precio,
            ]);
        }

        $pedido->timestamps = false;
        $pedido->total = DetallePedido::query()->where('pedido_id', $pedido->id)->sum('subtotal');
        $pedido->updated_at = $pagadoEn;
        $pedido->save();

        $apertura = new HistorialEstadoPedido([
            'pedido_id' => $pedido->id,
            'estado_id' => $pendiente->id,
            'user_id' => $usuario->id,
            'observacion' => 'Pedido creado',
        ]);
        $apertura->created_at = $creadoEn;
        $apertura->save();

        $pago = new HistorialEstadoPedido([
            'pedido_id' => $pedido->id,
            'estado_id' => $pagado->id,
            'user_id' => $usuario->id,
            'observacion' => 'Pago confirmado',
        ]);
        $pago->created_at = $pagadoEn;
        $pago->save();
    }

    /**
     * @return list<array{producto: Producto, cantidad: int}>
     */
    private function lineas(): array
    {
        if (Producto::query()->count() >= 2) {
            $existentes = Producto::query()->orderBy('id')->limit(2)->get();

            return [
                ['producto' => $existentes[0], 'cantidad' => 24],
                ['producto' => $existentes[1], 'cantidad' => 10],
            ];
        }

        $bebidas = Categoria::query()->firstOrCreate(['nombre' => 'Bebidas']);
        $abarrotes = Categoria::query()->firstOrCreate(['nombre' => 'Abarrotes']);

        $agua = Producto::query()->firstOrCreate(
            ['nombre' => 'Agua mineral 500 cc'],
            ['precio' => 890, 'categoria_id' => $bebidas->id],
        );

        $aceite = Producto::query()->firstOrCreate(
            ['nombre' => 'Aceite vegetal 1 L'],
            ['precio' => 2490, 'categoria_id' => $abarrotes->id],
        );

        return [
            ['producto' => $agua, 'cantidad' => 24],
            ['producto' => $aceite, 'cantidad' => 10],
        ];
    }
}
