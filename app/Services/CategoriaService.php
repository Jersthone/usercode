<?php

namespace App\Services;

use App\Models\Categoria;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CategoriaService
{
    /**
     * Lista categorías paginadas, filtrando por nombre si se indica.
     */
    public function listar(?string $nombre, int $porPagina): LengthAwarePaginator
    {
        return Categoria::query()
            ->when($nombre, function ($query, string $nombre) {
                $query->where('nombre', 'ilike', '%'.$this->escaparLike($nombre).'%');
            })
            ->orderBy('nombre')
            ->paginate($porPagina)
            ->withQueryString();
    }

    /**
     * Guarda una nueva categoría verificando que el nombre no exista.
     *
     * @param array $datos
     * @return Categoria
     * @throws Exception
     */
    public function crearCategoria(array $datos): Categoria
    {
        // 1. Validar que no exista una categoría con el mismo nombre
        $existe = Categoria::where('nombre', $datos['nombre'])->exists();

        if ($existe) {
            throw new Exception('Ya existe una categoría con el nombre "'.$datos['nombre'].'".', 400);
        }

        // 2. Guardar y retornar el nuevo registro
        return Categoria::create([
            'nombre' => $datos['nombre'],
        ]);
    }

    private function escaparLike(string $valor): string
    {
        return addcslashes($valor, '%_\\');
    }
}
