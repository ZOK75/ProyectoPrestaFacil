<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SolicitudCliente extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_clientes';

    protected $fillable = [
        'tipo',
        'estado',
        'cliente_id',
        'distribuidor_id',
        'sucursal_id',
        'datos_originales',
        'datos_solicitados',
        'motivo',
        'pdf_ine_nuevo',
        'pdf_comprobante_nuevo',
        'aprobado_por_user_id',
        'rechazado_por_user_id',
        'observaciones_resolucion',
        'resolved_at',
    ];

    protected $casts = [
        'datos_originales' => 'array',
        'datos_solicitados' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function distribuidor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'distribuidor_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por_user_id');
    }

    public function rechazadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rechazado_por_user_id');
    }

    public function esPendiente(): bool
    {
        return $this->estado === 'pendiente';
    }

    public function esActualizacion(): bool
    {
        return $this->tipo === 'actualizacion';
    }

    public function esDesactivacion(): bool
    {
        return $this->tipo === 'desactivacion';
    }

    /**
     * Aplica la aprobación de la solicitud sobre el Cliente.
     */
    public function aplicarAprobacion(User $gerente, ?string $observaciones = null): bool
    {
        if (!$this->esPendiente()) {
            return false;
        }

        $cliente = $this->cliente;
        if (!$cliente) {
            return false;
        }

        if ($this->esDesactivacion()) {
            $cliente->update([
                'activo' => false,
                'desactivado_at' => now(),
                'desactivado_by_user_id' => $gerente->id,
            ]);
        } elseif ($this->esActualizacion() && is_array($this->datos_solicitados)) {
            $datos = $this->datos_solicitados;

            // Reemplazar archivos PDF definitivos si venían en la solicitud
            if (!empty($this->pdf_ine_nuevo)) {
                if ($cliente->path_ine_pdf && Storage::disk('public')->exists($cliente->path_ine_pdf)) {
                    Storage::disk('public')->delete($cliente->path_ine_pdf);
                }
                $datos['path_ine_pdf'] = $this->pdf_ine_nuevo;
            }

            if (!empty($this->pdf_comprobante_nuevo)) {
                if ($cliente->path_comprobante_pdf && Storage::disk('public')->exists($cliente->path_comprobante_pdf)) {
                    Storage::disk('public')->delete($cliente->path_comprobante_pdf);
                }
                $datos['path_comprobante_pdf'] = $this->pdf_comprobante_nuevo;
            }

            $cliente->update($datos);
        }

        $this->update([
            'estado' => 'aprobada',
            'aprobado_por_user_id' => $gerente->id,
            'observaciones_resolucion' => $observaciones,
            'resolved_at' => now(),
        ]);

        return true;
    }

    /**
     * Aplica el rechazo de la solicitud.
     */
    public function aplicarRechazo(User $gerente, ?string $observaciones = null): bool
    {
        if (!$this->esPendiente()) {
            return false;
        }

        // Si había archivos temporales subidos para la actualización, podemos eliminarlos
        if (!empty($this->pdf_ine_nuevo) && Storage::disk('public')->exists($this->pdf_ine_nuevo)) {
            Storage::disk('public')->delete($this->pdf_ine_nuevo);
        }
        if (!empty($this->pdf_comprobante_nuevo) && Storage::disk('public')->exists($this->pdf_comprobante_nuevo)) {
            Storage::disk('public')->delete($this->pdf_comprobante_nuevo);
        }

        $this->update([
            'estado' => 'rechazada',
            'rechazado_por_user_id' => $gerente->id,
            'observaciones_resolucion' => $observaciones,
            'resolved_at' => now(),
        ]);

        return true;
    }
}
