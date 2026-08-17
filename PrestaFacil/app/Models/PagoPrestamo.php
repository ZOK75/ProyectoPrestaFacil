<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoPrestamo extends Model
{
    use HasUuids;
    use HasFactory;

    protected $table = 'pago_prestamos';

    protected $fillable = [
        'prestamo_id',
        'folio_pago',
        'numero_quincena',
        'monto_abonado',
        'monto_multa',
        'metodo_pago',
        'observaciones',
        'registrado_por_user_id',
    ];

    protected $casts = [
        'monto_abonado' => 'decimal:2',
        'monto_multa' => 'decimal:2',
        'numero_quincena' => 'integer',
    ];

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class, 'prestamo_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_user_id');
    }
}
