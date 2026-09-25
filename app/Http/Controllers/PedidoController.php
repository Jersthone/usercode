<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PedidoService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PedidoController extends Controller
{
    protected PedidoService $pedidoService;

    public function __construct(PedidoService $pedidoService)
    {
        $this->pedidoService = $pedidoService;
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'sucursal_id' => ['required', 'integer'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'integer', 'distinct'],
            'detalles.*.cantidad' => ['required', 'integer', 'min:1'],
        ], [
            'sucursal_id.required' => 'La sucursal es obligatoria.',
            'sucursal_id.integer' => 'La sucursal debe ser un número.',
            'detalles.required' => 'El pedido debe incluir productos.',
            'detalles.array' => 'El detalle del pedido no es válido.',
            'detalles.min' => 'El pedido debe incluir al menos un producto.',
            'detalles.*.producto_id.required' => 'Cada línea debe indicar el producto.',
            'detalles.*.producto_id.integer' => 'El producto debe ser un número.',
            'detalles.*.producto_id.distinct' => 'Hay un producto repetido en el pedido.',
            'detalles.*.cantidad.required' => 'Cada línea debe indicar la cantidad.',
            'detalles.*.cantidad.integer' => 'La cantidad debe ser un número entero.',
            'detalles.*.cantidad.min' => 'La cantidad debe ser mayor a cero.',
        ]);

        // Sin login en el API, el pendiente queda a nombre del usuario autenticado o del primero existente.
        $usuario = $request->user() ?? User::query()->orderBy('id')->first();

        if (! $usuario) {
            return response()->json([
                'status' => 'error',
                'message' => 'No hay un usuario para registrar quién crea el pedido.',
            ], 500);
        }

        try {
            $pedido = $this->pedidoService->crear($datos, $usuario->id);

            return response()->json([
                'status' => 'success',
                'message' => 'El pedido se creó correctamente.',
                'data' => $pedido,
            ], 201);
        } catch (Exception $e) {
            $codigo = (int) $e->getCode();

            if ($codigo < 400 || $codigo > 599) {
                throw $e;
            }

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $codigo);
        }
    }
}
