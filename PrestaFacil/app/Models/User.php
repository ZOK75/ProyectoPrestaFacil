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
        'conteo_retrasos',
        'es_morosa',
        'morosa_at',
        'morosa_by_user_id',
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
            'conteo_retrasos' => 'integer',
            'es_morosa' => 'boolean',
            'morosa_at' => 'datetime',
            'puntos' => 'integer',
            'google2fa_enabled' => 'boolean',
            'google2fa_secret' => 'encrypted',
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
        $nombre = strtolower(trim($this->rol?->nombre ?? ''));
        return in_array($nombre, ['gerente general', 'gerente_general', 'director general', 'direccion general']);
    }

    public function esAdministrador(): bool
    {
        $nombre = strtolower(trim($this->rol?->nombre ?? ''));
        return in_array($nombre, ['administrador', 'admin']);
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
        $nombre = strtolower(trim($this->rol?->nombre ?? ''));
        return in_array($nombre, ['gerente de sucursal', 'gerente_de_sucursal', 'gerente sucursal', 'gerente_sucursal']);
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

    public function solicitudesCategoriaComoDistribuidor(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\SolicitudCategoria::class, 'distribuidor_id')->orderBy('created_at', 'desc');
    }

    public function solicitudesCategoriaComoCoordinador(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\SolicitudCategoria::class, 'coordinador_id')->orderBy('created_at', 'desc');
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
     * Calcula el monto de crédito ocupado actualmente por los vales de la distribuidora.
     * Al asignar un vale, se descuenta de inmediato el monto prestado.
     * Conforme se pagan las cuotas quincenales (a tiempo, anticipadas o con retraso),
     * la distribuidora recupera el capital proporcional (monto_prestamo / plazo_quincenas)
     * en su línea de crédito disponible.
     */
    public function creditoUtilizado(): float
    {
        if (!$this->esDistribuidor()) {
            return 0.0;
        }

        $prestamos = Prestamo::where('created_by_user_id', $this->id)
            ->whereIn('estado', ['activo', 'pendiente'])
            ->with(['pagos', 'productoVale'])
            ->get();

        $porcentajeComision = floatval($this->obtenerPorcentajeGanancia() ?? 0.0);
        $totalCreditoOcupado = 0.0;

        foreach ($prestamos as $p) {
            $montoPrestamo = floatval($p->monto_prestamo);
            $totalQuincenas = max(1, intval($p->pagos_totales ?: ($p->productoVale?->plazo_quincenas ?: 8)));

            // Préstamo finalizado o pagado por completo: 0 crédito ocupado
            if ($p->estaPagado() || $p->estado === 'finalizado') {
                continue;
            }

            // Préstamo pendiente de entrega en ventanilla: ocupa el 100% de su capital
            if ($p->estado === 'pendiente') {
                $totalCreditoOcupado += $montoPrestamo;
                continue;
            }

            // Para préstamos activos: calcular capital amortizado / recuperado
            $cuotaBruta = floatval($p->cuota_quincenal ?: ($montoPrestamo / $totalQuincenas));
            $comisionQuincenal = (($porcentajeComision / 100) * $montoPrestamo) / $totalQuincenas;
            $cuotaNeta = max(0.01, $cuotaBruta - $comisionQuincenal);

            $totalAbonado = floatval($p->pagos->sum('monto_abonado'));
            $totalNetoExigible = $cuotaNeta * $totalQuincenas;

            if ($totalAbonado >= ($totalNetoExigible - 0.99)) {
                // Préstamo cubierto por completo
                continue;
            }

            // Capital amortizado proporcional a los pagos realizados
            $porcentajeAmortizado = min(1.0, max(0.0, $totalAbonado / $totalNetoExigible));
            $capitalRecuperado = round($porcentajeAmortizado * $montoPrestamo, 2);
            $capitalPendiente = max(0.0, $montoPrestamo - $capitalRecuperado);

            $totalCreditoOcupado += $capitalPendiente;
        }

        return round($totalCreditoOcupado, 2);
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
     * Regla configurable: (Porcentaje configurado del Límite de Crédito) + Monto adicional configurado
     */
    public function montoMaximoPermitidoPorVale(): float
    {
        $limite = floatval($this->limite_credito ?? 20000.00);
        $config = Configuracion::actual();
        $porcentaje = $config->obtenerPorcentajeRegla() / 100.0;
        $tolerancia = $config->obtenerTolerancia();

        return ($limite * $porcentaje) + $tolerancia;
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
     * Calcula la cuota quincenal total bruta a cobrar en el periodo por todos los préstamos activos.
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
     * Calcula la comisión total quincenal que gana la distribuidora por todos sus préstamos activos.
     */
    public function totalComisionQuincenal(): float
    {
        if (!$this->esDistribuidor()) {
            return 0.0;
        }

        $porcentajeComision = $this->obtenerPorcentajeGanancia();
        if ($porcentajeComision <= 0) {
            return 0.0;
        }

        $prestamos = Prestamo::where('created_by_user_id', $this->id)
            ->where('estado', 'activo')
            ->get();

        $totalComision = 0.0;
        foreach ($prestamos as $p) {
            $totalPagos = max(1, intval($p->pagos_totales));
            $comision = (($porcentajeComision / 100.0) * floatval($p->monto_prestamo)) / $totalPagos;
            $totalComision += $comision;
        }

        return $totalComision;
    }

    /**
     * Calcula la cuota quincenal neta a pagar por la distribuidora (Cuota Bruta - Comisión de Distribuidora).
     * Redondeada al piso (pesos enteros).
     */
    public function totalCuotaQuincenalNeta(): float
    {
        if (!$this->esDistribuidor()) {
            return 0.0;
        }

        $bruta = $this->totalCuotaQuincenal();
        $comision = $this->totalComisionQuincenal();

        return floor(max(0.0, $bruta - $comision));
    }

    /**
     * Calcula el monto quincenal exigible de la relación (Cuota Neta + Multas).
     * Redondeado al piso (pesos enteros).
     */
    public function totalQuincenalExigibleRelacion(): float
    {
        return floor($this->totalCuotaQuincenalNeta() + floatval($this->multas ?? 0.0));
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
     * Al registrarse nuevos usuarios, los distribuidores NO pueden crearse manualmente (solo vía solicitud y verificación).
     * Al editar un distribuidor ya existente, se permite conservar su rol.
     */
    public function rolesPermitidos(bool $incluirDistribuidor = false)
    {
        $query = Rol::query();

        if (!$incluirDistribuidor) {
            $query->whereNotIn('nombre', ['Distribuidor', 'Distribuidora', 'distribuidor', 'distribuidora']);
        }

        if ($this->esAdministrador()) {
            $query->whereIn('nombre', ['Gerente General', 'gerente general']);
        } elseif ($this->esGerenteGeneral()) {
            $query->whereNotIn('nombre', ['Gerente General', 'gerente general', 'Administrador', 'administrador']);
        } elseif ($this->esGerenteSucursal()) {
            $query->whereNotIn('nombre', ['Gerente General', 'Gerente de Sucursal', 'Administrador', 'administrador']);
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
     * Usuario que marcó a esta distribuidora como morosa.
     */
    public function morosaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'morosa_by_user_id');
    }

    /**
     * Determina si la distribuidora está bloqueada por morosidad.
     */
    public function esMorosa(): bool
    {
        return (bool)($this->es_morosa ?? false);
    }

    /**
     * Marca a la distribuidora como morosa, desactiva vales pendientes y envía notificaciones.
     */
    public function marcarComoMorosa(User $gerente, ?string $motivo = null): void
    {
        $this->update([
            'es_morosa' => true,
            'morosa_at' => now(),
            'morosa_by_user_id' => $gerente->id,
        ]);

        // Desactivar todos los vales pendientes (que aún no han sido cobrados/activados en caja)
        Prestamo::where('created_by_user_id', $this->id)
            ->where('estado', 'pendiente')
            ->update([
                'estado' => 'desactivado',
                'estado_entrega' => 'cancelado',
                'activo' => false,
                'desactivado_at' => now(),
                'desactivado_by_user_id' => $gerente->id,
            ]);

        // Notificar a la distribuidora
        NotificacionCajero::enviar(
            $this->id,
            'alerta',
            '🚫 Cuenta Declarada en Estado de Morosidad',
            "Tu cuenta ha sido declarada en estado de morosidad por la Gerencia ({$gerente->name})" . ($motivo ? " por motivo: {$motivo}" : "") . ". Se han cancelado todos tus vales pendientes de entrega y la colocación de nuevos vales está suspendida."
        );

        // Notificar al coordinador si tiene asignado
        if ($this->coordinador_id) {
            NotificacionCajero::enviar(
                $this->coordinador_id,
                'alerta',
                'Distribuidora Marcada como Morosa',
                "La distribuidora {$this->name} ha sido declarada en estado de morosidad por la Gerencia ({$gerente->name}). Todos sus vales pendientes fueron cancelados."
            );
        }
    }

    /**
     * Desmarca el estado de morosidad y rehabilita la operación normal.
     */
    public function desmarcarMorosidad(): void
    {
        $this->update([
            'es_morosa' => false,
            'morosa_at' => null,
            'morosa_by_user_id' => null,
            'conteo_retrasos' => 0,
        ]);

        NotificacionCajero::enviar(
            $this->id,
            'informativa',
            'Estado de Morosidad Retirado',
            "La Gerencia ha retirado la restricción de morosidad de tu cuenta. Ya puedes generar y asignar vales con normalidad."
        );

        if ($this->coordinador_id) {
            NotificacionCajero::enviar(
                $this->coordinador_id,
                'informativa',
                'Morosidad Retirada a Distribuidora',
                "Se ha retirado el estado de morosidad a la distribuidora {$this->name}. Ya puede volver a emitir vales."
            );
        }
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
