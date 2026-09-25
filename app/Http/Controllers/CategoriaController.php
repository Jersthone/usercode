<?php

namespace App\Http\Controllers;

use App\Services\CategoriaService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    protected CategoriaService $categoriaService;

    public function __construct(CategoriaService $categoriaService)
    {
        $this->categoriaService = $categoriaService;
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => 'sometimes|nullable|string|max:255',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $categorias = $this->categoriaService->listar(
            isset($validated['nombre']) ? trim($validated['nombre']) : null,
            (int) ($validated['per_page'] ?? 5),
        );

        return response()->json($categorias->items());
    }

    public function store(Request $request): JsonResponse
    {
        // Validar formato de entrada
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        try {
            // Llamar al servicio
            $categoria = $this->categoriaService->crearCategoria($request->only('nombre'));

            return response()->json([
                'status'  => 'success',
                'message' => 'La categoría "'.$categoria->nombre.'" se creó correctamente.',
                'data'    => $categoria,
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 400);
        }
    }
}
