<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;


// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Prestamo;
use App\Models\Configuracion;
use App\Models\SolicitudCliente;
use App\Models\RelacionCobranza;
use App\Models\NotificacionCajero;

class User extends Authenticatable
{
    use HasUuids;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'rol_id',
        'sucursal_id',
        'coordinador_id',
        'categoria_distribuidor',
        'limite_credito',
        'limite_credito_anterior',
        'multas',
        'referencia_pago_distribuidor',
        'puntos',
        'activo',
        'desactivado_at',
        'desactivado_by_user_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'desactivado_at' => 'datetime',
            'limite_credito' => 'decimal:2',
            'limite_credito_anterior' => 'decimal:2',
            'multas' => 'decimal:2',
            'puntos' => 'integer',
        ];
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function hasRole(string $rolName): bool
    {
        return $this->rol && strtolower($this->rol->nombre) == strtolower($rolName);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    /**
     * Usuario que desactivó esta cuenta.
     */
    public function desactivadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'desactivado_by_user_id');
    }

    /**
     * Préstamos otorgados / colocados por este usuario.
     */
    public function prestamos(): HasMany
    {
        return $this->hasMany(Prestamo::class, 'created_by_user_id');
    }

    /**
     * Solicitudes de clientes registradas por este usuario.
     */
    public function solicitudesClientes(): HasMany
    {
        return $this->hasMany(SolicitudCliente::class, 'distribuidor_id');
    }

    /**
     * Relaciones de cobranza de este distribuidor.
     */
    public function relacionesCobranza(): HasMany
    {
        return $this->hasMany(RelacionCobranza::class, 'distribuidora_id');
    }

    /**
     * Notificaciones recibidas por este usuario.
     */
    public function notificaciones(): HasMany
    {
        return $this->hasMany(NotificacionCajero::class, 'user_id');
    }

    // ──────────────────────────────────────────
    // Helpers de Rol
    // ──────────────────────────────────────────

    public function esGerenteGeneral(): bool
    {
        return strtolower($this->rol?->nombre ?? '') === 'gerente general';
    }

    public function esAdministrador(): bool
    {
        return strtolower($this->rol?->nombre ?? '') === 'administrador';
    }

    public function esAdminGeneralOAdmin(): bool
    {
        return $this->esGerenteGeneral() || $this->esAdministrador();
    }

    public function puedeModificar(): bool
    {
        // El rol de Administrador es estrictamente de solo lectura / auditoría
        if ($this->esAdministrador()) {
            return false;
        }

        return true;
    }

    public function esGerenteSucursal(): bool
    {
        return strtolower($this->rol?->nombre ?? '') === 'gerente de sucursal';
    }

    public function esDistribuidor(): bool
    {
        $nombre = strtolower($this->rol?->nombre ?? '');
        return in_array($nombre, ['distribuidor', 'distribuidora']);
    }



    public function esVerificador(): bool
    {
        return strtolower($this->rol?->nombre ?? '') === 'verificador';
    }

    public function solicitudesCreditoComoDistribuidor(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SolicitudCredito::class, 'distribuidor_id')->orderBy('created_at', 'desc');
    }

    public function solicitudesCreditoComoCoordinador(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SolicitudCredito::class, 'coordinador_id')->orderBy('created_at', 'desc');
    }

    public function esCajero(): bool
    {
        $nombre = strtolower($this->rol?->nombre ?? '');
        return in_array($nombre, ['cajero', 'cajera', 'caja']);
    }

    public function esCoordinador(): bool
    {
        $nombre = strtolower($this->rol?->nombre ?? '');
        return in_array($nombre, ['coordinador', 'coordinadora']);
    }

    /**
     * Determina si el usuario puede autorizar solicitudes operativas.
     */
    public function puedeAutorizar(?string $tipo = null, ?string $sucursalId = null): bool
    {
        if ($this->esAdministrador() || $this->esGerenteGeneral() || $this->esGerenteSucursal()) {
            return false;
        }

        if ($this->esCoordinador()) {
            if ($sucursalId && $this->sucursal_id) {
                return $this->sucursal_id == $sucursalId;
            }
            return true;
        }

        return false;
    }

    /**
     * Conteo de solicitudes pendientes para vista de gerentes.
     */
    public function conteoSolicitudesPendientes(): int
    {
        if ($this->esGerenteSucursal()) {
            return 0;
        }

        return SolicitudCliente::where('estado', 'pendiente')->count();
    }

    /**
     * Conteo de notificaciones sin leer de este usuario.
     */
    public function conteoNotificacionesSinLeer(): int
    {
        return NotificacionCajero::where('user_id', $this->id)
            ->where('leida', false)
            ->count();
    }

    /**
     * Obtiene el porcentaje de ganancia según su categoría de distribuidor.
     */
    public function obtenerPorcentajeGanancia(): float
    {
        if (!$this->esDistribuidor()) {
            return 0.0;
        }

        $config = Configuracion::actual();
        return $config->obtenerComision($this->categoria_distribuidor);
    }

    /**
     * Calcula el monto de crédito de vales en estado 'activo' que tiene ocupados el distribuidor.
     * El límite de crédito no se ve afectado hasta que el vale es entregado y activado por el cajero.
     */
    public function creditoUtilizado(): float
    {
        if (!$this->esDistribuidor()) {
            return 0.0;
        }

        return floatval(Prestamo::where('created_by_user_id', $this->id)
            ->where('estado', 'activo')
            ->sum('monto_prestamo'));
    }

    /**
     * Calcula el crédito disponible actual del distribuidor.
     */
    public function creditoDisponible(): float
    {
        $limite = floatval($this->limite_credito ?? 20000.00);
        return max(0.0, $limite - $this->creditoUtilizado());
    }

    /**
     * Calcula el valor máximo que puede tener UN SOLO VALE otorgado por este distribuidor:
     * Regla: (50% del Límite de Crédito Total) + $500.00
     */
    public function montoMaximoPermitidoPorVale(): float
    {
        $limite = floatval($this->limite_credito ?? 20000.00);
        return ($limite * 0.50) + 500.00;
    }

    /**
     * Calcula los puntos acumulados por el distribuidor según el total de productos otorgados activos.
     * Fórmula: floor(Total en productos / Monto base) * Puntos base
     */
    public function puntosAcumulados(): int
    {
        if (!$this->esDistribuidor()) {
            return 0;
        }

        $config = Configuracion::actual();
        $totalProductos = floatval(Prestamo::where('created_by_user_id', $this->id)
            ->where('estado', 'activo')
            ->sum('monto_prestamo'));

        return $config->calcularPuntosPorMonto($totalProductos);
    }

    /**
     * Devuelve el equivalente en dinero de los puntos acumulados.
     */
    public function valorPuntosEnDinero(): float
    {
        $config = Configuracion::actual();
        $valorPorPunto = floatval($config->valor_punto ?? 2.00);

        return $this->puntosAcumulados() * $valorPorPunto;
    }

    /**
     * Devuelve la referencia de pago bancaria única del distribuidor.
     */
    public function referenciaPago(): string
    {
        if (!empty($this->referencia_pago_distribuidor)) {
            return $this->referencia_pago_distribuidor;
        }

        return 'REF-DIST-' . str_pad((string)$this->id, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Calcula el adeudo pendiente total acumulado de todos los préstamos activos de la distribuidora.
     */
    public function totalAdeudoPrestamos(): float
    {
        if (!$this->esDistribuidor()) {
            return 0.0;
        }

        return floatval(Prestamo::where('created_by_user_id', $this->id)
            ->where('estado', 'activo')
            ->sum('adeudo_pendiente'));
    }

    /**
     * Calcula la cuota quincenal total a cobrar en el periodo por todos los préstamos activos.
     */
    public function totalCuotaQuincenal(): float
    {
        if (!$this->esDistribuidor()) {
            return 0.0;
        }

        return floatval(Prestamo::where('created_by_user_id', $this->id)
            ->where('estado', 'activo')
            ->sum('cuota_quincenal'));
    }

    /**
     * Calcula el saldo total a pagar por la distribuidora (Adeudo de Préstamos + Multas acumuladas).
     */
    public function totalAdeudoGlobal(): float
    {
        return $this->totalAdeudoPrestamos() + floatval($this->multas ?? 0.0);
    }

    /**
     * Devuelve los roles que este usuario tiene permiso de asignar.
     * Los distribuidores NO pueden crearse manualmente por ningún gerente (solo vía solicitud y verificación).
     */
    public function rolesPermitidos()
    {
        $query = Rol::query()->whereNotIn('nombre', ['Distribuidor', 'Distribuidora', 'distribuidor', 'distribuidora']);

        if ($this->esGerenteGeneral()) {
            $query->where('nombre', '!=', 'Gerente General');
        } elseif ($this->esGerenteSucursal()) {
            $query->whereNotIn('nombre', ['Gerente General', 'Gerente de Sucursal', 'Administrador']);
        } else {
            return Rol::where('id', 0)->get();
        }

        return $query->orderBy('nombre')->get();
    }

    /**
     * Devuelve las sucursales a las que este usuario puede asignar empleados.
     */
    public function sucursalesPermitidas()
    {
        if ($this->esGerenteGeneral()) {
            return Sucursal::where('activo', true)->orderBy('nombre')->get();
        }

        if ($this->esGerenteSucursal() && $this->sucursal_id) {
            return Sucursal::where('id', $this->sucursal_id)->get();
        }

        return Sucursal::where('id', 0)->get();
    }

    /**
     * Determina si la distribuidora está bloqueada por morosidad.
     * TODO: Implementar lógica real con tabla de strikes
     */
    public function esMorosa(): bool
    {
        return false;
    }

    /**
     * Solicitudes de distribuidores creadas por este coordinador.
     */
    public function solicitudesDistribuidoresCreadas(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SolicitudDistribuidor::class, 'coordinador_id')->orderBy('created_at', 'desc');
    }

    /**
     * Coordinador asignado a esta distribuidora.
     */
    public function coordinador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinador_id');
    }

    /**
     * Distribuidoras asignadas a este coordinador.
     */
    public function distribuidoresCoordinados(): HasMany
    {
        return $this->hasMany(User::class, 'coordinador_id');
    }

    /**
     * Solicitudes de transferencia emitidas por este coordinador.
     */
    public function solicitudesTransferenciaEmitidas(): HasMany
    {
        return $this->hasMany(SolicitudTransferencia::class, 'coordinador_emisor_id')->orderBy('created_at', 'desc');
    }

    /**
     * Solicitudes de transferencia recibidas por este coordinador.
     */
    public function solicitudesTransferenciaRecibidas(): HasMany
    {
        return $this->hasMany(SolicitudTransferencia::class, 'coordinador_receptor_id')->orderBy('created_at', 'desc');
    }

    /**
     * Obtiene las distribuidoras bajo la supervisión de este coordinador.
     */
    public function misDistribuidorasQuery()
    {
        return User::whereHas('rol', function($q) {
                $q->whereIn('nombre', ['Distribuidor', 'distribuidor', 'Distribuidora', 'distribuidora']);
            })
            ->where('activo', true)
            ->where(function($q) {
                $q->where('coordinador_id', $this->id)
                  ->orWhere(function($sub) {
                      $sub->whereNull('coordinador_id')
                          ->where('sucursal_id', $this->sucursal_id);
                  });
            });
    }
}
