<?php

use App\Http\Controllers\PedidoController;
use Illuminate\Support\Facades\Route;

Route::post('/pedidos', [PedidoController::class, 'store']);
