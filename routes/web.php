<?php

use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\PedidoController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::post('/categorias', [CategoriaController::class, 'store']);
Route::get('/categorias', [CategoriaController::class, 'index']);
Route::post('/pedidos', [PedidoController::class, 'store']);
Route::patch('/pedidos/{pedido}/estado', [PedidoController::class, 'cambiarEstado']);