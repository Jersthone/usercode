<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $table = 'pedidos';

    protected $fillable = [
        'sucursal_id',
        'estado_id',
        'total',
    ];

    protected $casts = [
        'total' => 'decimal:2',
    ];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function estado()
    {
        return $this->belongsTo(Estado::class);
    }

    public function detalles()
    {
        return $this->hasMany(DetallePedido::class);
    }

    public function historial()
    {
        return $this->hasMany(HistorialEstadoPedido::class)->orderBy('created_at')->orderBy('id');
    }
}
