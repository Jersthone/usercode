<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialEstadoPedido extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'historial_estado_pedidos';

    protected $fillable = [
        'pedido_id',
        'estado_id',
        'user_id',
        'observacion',
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    public function estado()
    {
        return $this->belongsTo(Estado::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
