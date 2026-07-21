<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sucursal extends Model
{
    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'activo'
    ];

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }
}