<?php

namespace App\Services;

use App\Models\DetallePedido;
use App\Models\Estado;
use App\Models\HistorialEstadoPedido;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Sucursal;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PedidoService
{
    /**
     * Crea un pedido en pendiente, congela el precio de cada línea y descuenta el stock.
     * Si algún producto no alcanza, la transacción no guarda nada.
     *
     * @param  array{sucursal_id: int, detalles: list<array{producto_id: int, cantidad: int}>}  $datos
     *
     * @throws Exception
     */
    public function crear(array $datos, int $userId): Pedido
    {
        return DB::transaction(function () use ($datos, $userId) {
            $sucursal = Sucursal::query()->find($datos['sucursal_id']);

            if (! $sucursal) {
                throw new Exception('La sucursal indicada no existe.', 404);
            }

            $lineas = $this->lineasOrdenadas($datos['detalles']);

            $productos = Producto::query()
                ->whereIn('id', $lineas->pluck('producto_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $idsFaltantes = $lineas->pluck('producto_id')->diff($productos->keys());

            if ($idsFaltantes->isNotEmpty()) {
                throw new Exception('No existe el producto con id '.$idsFaltantes->implode(', ').'.', 404);
            }

            $sinStock = [];

            foreach ($lineas as $linea) {
                $producto = $productos->get($linea['producto_id']);

                if ($producto->stock < $linea['cantidad']) {
                    $sinStock[] = $producto->nombre.' (disponible: '.$producto->stock.', solicitado: '.$linea['cantidad'].')';
                }
            }

            if ($sinStock !== []) {
                throw new Exception('Stock insuficiente para: '.implode('; ', $sinStock).'.', 409);
            }

            $pendiente = Estado::query()->where('nombre', 'pendiente')->first();

            if (! $pendiente) {
                throw new Exception('No está configurado el estado pendiente.', 500);
            }

            $pedido = Pedido::query()->create([
                'sucursal_id' => $sucursal->id,
                'estado_id' => $pendiente->id,
                'total' => 0,
            ]);

            foreach ($lineas as $linea) {
                $producto = $productos->get($linea['producto_id']);

                DetallePedido::query()->create([
                    'pedido_id' => $pedido->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $linea['cantidad'],
                    'precio_unitario' => $producto->precio,
                ]);

                $producto->decrement('stock', $linea['cantidad']);
            }

            $pedido->update([
                'total' => DetallePedido::query()->where('pedido_id', $pedido->id)->sum('subtotal'),
            ]);

            HistorialEstadoPedido::query()->create([
                'pedido_id' => $pedido->id,
                'estado_id' => $pendiente->id,
                'user_id' => $userId,
                'observacion' => 'Pedido creado',
            ]);

            return $pedido->fresh()->load([
                'estado',
                'sucursal',
                'detalles.producto',
                'historial.user',
                'historial.estado',
            ]);
        });
    }

    /**
     * @param  list<array{producto_id: int, cantidad: int}>  $detalles
     * @return Collection<int, array{producto_id: int, cantidad: int}>
     */
    private function lineasOrdenadas(array $detalles): Collection
    {
        return collect($detalles)
            ->map(fn (array $linea) => [
                'producto_id' => (int) $linea['producto_id'],
                'cantidad' => (int) $linea['cantidad'],
            ])
            ->sortBy('producto_id')
            ->values();
    }
}
