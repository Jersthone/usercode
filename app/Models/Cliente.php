<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'razon_social',
        'rut',
        'email',
        'telefono',
    ];

    public function sucursales()
    {
        return $this->hasMany(Sucursal::class);
    }
}
