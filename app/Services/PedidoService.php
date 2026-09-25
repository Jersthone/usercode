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
     * Actualiza el estado actual y agrega una fila de historial con el usuario y la fecha.
     *
     * @param  array<string, mixed>  $datos
     *
     * @throws Exception
     */
    public function cambiarEstado(int $pedidoId, array $datos, int $userId): Pedido
    {
        return DB::transaction(function () use ($pedidoId, $datos, $userId) {
            $pedido = Pedido::query()->with('estado')->lockForUpdate()->find($pedidoId);

            if (! $pedido) {
                throw new Exception('El pedido indicado no existe.', 404);
            }

            $estadoNuevo = Estado::query()->find($datos['estado_id']);

            if (! $estadoNuevo) {
                throw new Exception('El estado indicado no existe.', 404);
            }

            $estadoActual = $pedido->estado->nombre;

            if ($estadoActual === $estadoNuevo->nombre) {
                throw new Exception('El pedido ya está en estado '.$estadoActual.'.', 409);
            }

            if (! $this->transicionPermitida($estadoActual, $estadoNuevo->nombre)) {
                throw new Exception('No se puede pasar el pedido de '.$estadoActual.' a '.$estadoNuevo->nombre.'.', 409);
            }

            if ($estadoNuevo->nombre === 'anulado') {
                $this->devolverStock($pedido);
            }

            $pedido->update([
                'estado_id' => $estadoNuevo->id,
            ]);

            HistorialEstadoPedido::query()->create([
                'pedido_id' => $pedido->id,
                'estado_id' => $estadoNuevo->id,
                'user_id' => $userId,
                'observacion' => filled($datos['observacion'] ?? null) ? $datos['observacion'] : null,
            ]);

            return $pedido->fresh()->load([
                'estado',
                'historial.user',
                'historial.estado',
            ]);
        });
    }

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

    private function transicionPermitida(string $actual, string $nuevo): bool
    {
        $permitidas = [
            'pendiente' => ['pagado', 'anulado'],
            'pagado' => ['despachado', 'anulado'],
            'despachado' => ['entregado', 'anulado'],
            'entregado' => [],
            'anulado' => [],
        ];

        return in_array($nuevo, $permitidas[$actual] ?? [], true);
    }

    private function devolverStock(Pedido $pedido): void
    {
        $detalles = $pedido->detalles()->orderBy('producto_id')->get();

        $productos = Producto::query()
            ->whereIn('id', $detalles->pluck('producto_id'))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($detalles as $detalle) {
            $producto = $productos->get($detalle->producto_id);

            if (! $producto) {
                throw new Exception('No existe el producto con id '.$detalle->producto_id.'.', 404);
            }

            $producto->increment('stock', $detalle->cantidad);
        }
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
