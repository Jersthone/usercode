<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estado extends Model
{
    public $timestamps = false;

    protected $table = 'estados';

    protected $fillable = [
        'nombre',
    ];

    public function pedidos()
    {
        return $this->hasMany(Pedido::class);
    }

    public function historial()
    {
        return $this->hasMany(HistorialEstadoPedido::class);
    }
}
