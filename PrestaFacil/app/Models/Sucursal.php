<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sucursal extends Model
{
    use HasUuids;

    protected $table = 'sucursales';

    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'sucursal_id');
    }
}