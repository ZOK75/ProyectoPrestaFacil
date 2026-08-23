<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prestamo extends Model
{
    use HasUuids;
    use HasFactory;

    protected $table = 'prestamos';

    protected $fillable = [
        'referencia',
        'cliente_id',
        'producto_vale_id',
        'tipo',
        'monto_prestamo',
        'cuota_quincenal',
        'pagos_totales',
        'pagos_realizados',
        'monto_total_pagar',
        'adeudo_pendiente',
        'pagos_recibidos',
        'multas',
        'estado',
        'estado_entrega',
        'entregado_por_user_id',
        'entregado_at',
        'numero_transferencia',
        'monto_depositado',
        'sucursal_entrega_id',
        'limite_credito_anterior',
        'activo',
        'created_by_user_id',
        'desactivado_at',
        'desactivado_by_user_id',
    ];

    protected $casts = [
        'monto_prestamo' => 'decimal:2',
        'cuota_quincenal' => 'decimal:2',
        'monto_total_pagar' => 'decimal:2',
        'adeudo_pendiente' => 'decimal:2',
        'pagos_recibidos' => 'decimal:2',
        'multas' => 'decimal:2',
        'pagos_totales' => 'integer',
        'pagos_realizados' => 'integer',
        'activo' => 'boolean',
        'desactivado_at' => 'datetime',
        'entregado_at' => 'datetime',
        'monto_depositado' => 'decimal:2',
        'limite_credito_anterior' => 'decimal:2',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function productoVale(): BelongsTo
    {
        return $this->belongsTo(ProductoVale::class, 'producto_vale_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(PagoPrestamo::class, 'prestamo_id')->orderBy('created_at', 'desc');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function desactivadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'desactivado_by_user_id');
    }

    public function entregadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entregado_por_user_id');
    }

    public function sucursalEntrega(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_entrega_id');
    }

    public function esPrevale(): bool
    {
        return $this->tipo === 'prevale';
    }

    public function esPendiente(): bool
    {
        return $this->estado === 'pendiente' || ($this->estado_entrega === 'pendiente' && $this->estado !== 'desactivado');
    }

    public function esActivo(): bool
    {
        return $this->estado === 'activo';
    }

    public function puedeDesactivarsePorDistribuidor(): bool
    {
        return $this->esPendiente() && !$this->estaCancelado();
    }

    public function estaPagado(): bool
    {
        return $this->estado === 'finalizado' || $this->adeudo_pendiente <= 0;
    }

    public function estaEntregado(): bool
    {
        return $this->estado_entrega === 'entregado';
    }

    public function estaCancelado(): bool
    {
        return $this->estado_entrega === 'cancelado' || $this->estado === 'desactivado';
    }

    public function estaPendienteEntrega(): bool
    {
        return $this->estado_entrega === 'pendiente';
    }

    public function multaConfigurada(): float
    {
        $multa = floatval($this->productoVale?->multa ?? 0.0);
        if ($multa <= 0) {
            $config = Configuracion::actual();
            $multaConfig = floatval($config->multa_adeudo ?? 0.0);
            return $multaConfig > 0 ? $multaConfig : 150.00;
        }
        return $multa;
    }

    public function totalAdeudoConMultas(): float
    {
        return floatval($this->adeudo_pendiente) + floatval($this->multas ?? 0.0);
    }

    public function cuotaExigibleQuincenal(): float
    {
        return floatval($this->cuota_quincenal) + floatval($this->multas ?? 0.0);
    }
}
