<?php

namespace App\Http\Controllers;

use App\Http\Requests\CanjePuntosRequest;
use App\Http\Requests\EntregarValeRequest;
use App\Http\Requests\RegistrarAbonoRequest;
use App\Http\Requests\SolicitarConciliacionRequest;
use App\Models\CanjePuntos;
use App\Models\Conciliacion;
use App\Models\Configuracion;
use App\Models\NotificacionCajero;
use App\Models\PagoPrestamo;
use App\Models\Prestamo;
use App\Models\SolicitudAutorizacion;
use App\Models\User;
use App\Services\AuditService;
use App\Services\NotificacionService;
use App\Services\ValidacionValeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CajeroController extends Controller
{
    protected $validacionService;

    public function __construct(ValidacionValeService $validacionService)
    {
        $this->validacionService = $validacionService;
    }

    private function cajera(): User
    {
        return Auth::user()->load('sucursal');
    }

    /**
     * DASHBOARD
     */
    public function dashboard(Request $request)
    {
        $cajera = $this->cajera();
        $sucursalId = $cajera->sucursal_id;

        // Estadísticas del día en la sucursal de la cajera
        $valesEntregadosHoy = Prestamo::where('estado_entrega', 'entregado')
            ->where('sucursal_entrega_id', $sucursalId)
            ->whereDate('entregado_at', Carbon::today())
            ->count();

        $abonosRecibidosHoy = PagoPrestamo::whereHas('prestamo', function($q) use ($sucursalId) {
            // Asumiendo que el abono se registra en la sucursal de la cajera
            // Por ahora contamos los registrados por esta cajera hoy
        })->where('registrado_por_user_id', $cajera->id)
          ->whereDate('created_at', Carbon::today())
          ->sum('monto_abonado');

        $canjesHoy = CanjePuntos::where('cajera_id', $cajera->id)
            ->whereDate('created_at', Carbon::today())
            ->count();

        return view('cajero.dashboard', compact('cajera', 'valesEntregadosHoy', 'abonosRecibidosHoy', 'canjesHoy'));
    }

    /**
     * BUSCAR FOLIO
     */
    public function buscarFolio(Request $request)
    {
        $referencia = $request->input('referencia');
        $prestamo = null;

        if ($referencia) {
            $prestamo = Prestamo::with(['cliente', 'productoVale', 'createdBy'])->where('referencia', $referencia)->first();
            
            if (!$prestamo) {
                return back()->with('error', 'Referencia no encontrada.');
            }
        }

        return view('cajero.buscar-folio', compact('prestamo', 'referencia'));
    }

    /**
     * MÓDULO 1: PREVALE - VERIFICAR
     */
    public function verificarDatosPrevale(Prestamo $prestamo)
    {
        $cajera = $this->cajera();
        $erroresNegocio = $this->validacionService->validarEntregaPrevale($prestamo, $prestamo->createdBy);

        return view('cajero.prevale.verificar', compact('prestamo', 'erroresNegocio', 'cajera'));
    }

    /**
     * MÓDULO 1: PREVALE - ENTREGAR
     */
    public function entregarPrevale(EntregarValeRequest $request, Prestamo $prestamo)
    {
        $cajera = $this->cajera();
        
        // Revalidar por seguridad
        $erroresNegocio = $this->validacionService->validarEntregaPrevale($prestamo, $prestamo->createdBy);
        if (!empty($erroresNegocio)) {
            return back()->with('error', 'No se puede entregar: ' . implode(" ", $erroresNegocio));
        }

        DB::transaction(function () use ($prestamo, $request, $cajera) {
            $prestamo->update([
                'estado' => 'activo',
                'estado_entrega' => 'entregado',
                'entregado_por_user_id' => $cajera->id,
                'entregado_at' => now(),
                'numero_transferencia' => $request->numero_transferencia,
                'monto_depositado' => $request->monto_depositado,
                'sucursal_entrega_id' => $cajera->sucursal_id,
            ]);

            $prestamo->loadMissing(['cliente', 'createdBy.coordinador', 'createdBy.sucursal']);
            $nombreSucursal = $cajera->sucursal?->nombre ?? 'Sucursal';

            // 1. Notificar a la Distribuidora
            if ($prestamo->created_by_user_id) {
                NotificacionCajero::enviar(
                    $prestamo->created_by_user_id,
                    'prestamo_cobrado',
                    '¡Préstamo Cobrado en Ventanilla!',
                    "El cliente {$prestamo->cliente->nombre} cobró exitosamente el prevale con Referencia {$prestamo->referencia} por un monto de $" . number_format($prestamo->monto_prestamo, 2) . " con el cajero {$cajera->name} en {$nombreSucursal}. El préstamo ahora está activo.",
                    [
                        'url' => route('prestamos.show', $prestamo),
                        'entidad_tipo' => 'prestamos',
                        'entidad_id' => $prestamo->id,
                    ]
                );
            }

            // 2. Notificar al Coordinador de la distribuidora
            if ($prestamo->createdBy && $prestamo->createdBy->coordinador_id) {
                NotificacionCajero::enviar(
                    $prestamo->createdBy->coordinador_id,
                    'prestamo_cobrado',
                    'Préstamo Cobrado por Cliente',
                    "El cliente {$prestamo->cliente->nombre} (Distribuidora: {$prestamo->createdBy->name}) cobró el prevale {$prestamo->referencia} por $" . number_format($prestamo->monto_prestamo, 2) . " en la sucursal {$nombreSucursal}.",
                    [
                        'url' => route('prestamos.show', $prestamo),
                        'entidad_tipo' => 'prestamos',
                        'entidad_id' => $prestamo->id,
                    ]
                );
            }

            AuditService::registrar('ENTREGA_PREVALE', "Prevale {$prestamo->referencia} entregado/cobrado", [
                'entidad_tipo' => 'prestamos',
                'entidad_id' => $prestamo->id,
                'despues' => $prestamo->toArray(),
            ]);
        });

        return redirect()->route('cajero.dashboard')->with('success', 'Prevale entregado y activado con éxito.');
    }

    /**
     * MÓDULO 1: SOLICITAR MODIFICACIÓN DE DATOS (CUANDO NO COINCIDEN)
     */
    public function solicitarModificacionDatos(Request $request, Prestamo $prestamo)
    {
        // En un MVP, renderizamos un formulario para modificar. 
        // Si es un POST, guardamos la solicitud.
        if ($request->isMethod('post')) {
            $request->validate([
                'motivo' => 'required|string|max:500',
                'nombre' => 'required|string',
                // Más validaciones según los campos...
            ]);

            $cajera = $this->cajera();
            
            DB::transaction(function () use ($request, $prestamo, $cajera) {
                $solicitud = SolicitudAutorizacion::create([
                    'tipo' => 'modificacion_datos',
                    'solicitante_id' => $cajera->id,
                    'sucursal_id' => $cajera->sucursal_id,
                    'entidad_tipo' => 'clientes',
                    'entidad_id' => $prestamo->cliente_id,
                    'datos_originales' => $prestamo->cliente->toArray(),
                    'datos_propuestos' => $request->except(['_token', 'motivo']),
                    'motivo' => $request->motivo,
                ]);

                // Notificar autorizadores
                NotificacionService::notificarAutorizadores(
                    $cajera->sucursal_id,
                    'NUEVA_SOLICITUD',
                    'Modificación de datos requerida',
                    "La cajera {$cajera->name} solicita modificar datos del cliente {$prestamo->cliente->nombre}",
                    ['entidad_tipo' => 'solicitudes_autorizacion', 'entidad_id' => $solicitud->id]
                );

                AuditService::registrar('SOLICITUD_MODIFICACION', "Solicitud para cliente {$prestamo->cliente->nombre}", [
                    'entidad_tipo' => 'solicitudes_autorizacion',
                    'entidad_id' => $solicitud->id,
                ]);
            });

            return redirect()->route('cajero.buscar-folio', ['referencia' => $prestamo->referencia])
                ->with('success', 'Solicitud de modificación enviada a Coordinación.');
        }

        return view('cajero.solicitar-modificacion', compact('prestamo'));
    }

    /**
     * MÓDULO 2: VALE DIGITAL - VERIFICAR
     */
    public function verificarDatosVale(Prestamo $prestamo)
    {
        $cajera = $this->cajera();
        $erroresNegocio = $this->validacionService->validarEntregaVale($prestamo, $prestamo->createdBy);

        return view('cajero.vale.verificar', compact('prestamo', 'erroresNegocio', 'cajera'));
    }

    /**
     * MÓDULO 2: VALE DIGITAL - ENTREGAR
     */
    public function entregarVale(EntregarValeRequest $request, Prestamo $prestamo)
    {
        $cajera = $this->cajera();
        
        $erroresNegocio = $this->validacionService->validarEntregaVale($prestamo, $prestamo->createdBy);
        if (!empty($erroresNegocio)) {
            return back()->with('error', 'No se puede entregar: ' . implode(" ", $erroresNegocio));
        }

        DB::transaction(function () use ($prestamo, $request, $cajera) {
            $prestamo->update([
                'estado' => 'activo',
                'estado_entrega' => 'entregado',
                'entregado_por_user_id' => $cajera->id,
                'entregado_at' => now(),
                'numero_transferencia' => $request->numero_transferencia,
                'monto_depositado' => $request->monto_depositado,
                'sucursal_entrega_id' => $cajera->sucursal_id,
            ]);

            $prestamo->loadMissing(['cliente', 'createdBy.coordinador', 'createdBy.sucursal']);
            $nombreSucursal = $cajera->sucursal?->nombre ?? 'Sucursal';

            // 1. Notificar a la Distribuidora
            if ($prestamo->created_by_user_id) {
                NotificacionCajero::enviar(
                    $prestamo->created_by_user_id,
                    'prestamo_cobrado',
                    '¡Vale Cobrado en Ventanilla!',
                    "El cliente {$prestamo->cliente->nombre} cobró exitosamente el vale con Referencia {$prestamo->referencia} por un monto de $" . number_format($prestamo->monto_prestamo, 2) . " con el cajero {$cajera->name} en {$nombreSucursal}. El préstamo ahora está activo.",
                    [
                        'url' => route('prestamos.show', $prestamo),
                        'entidad_tipo' => 'prestamos',
                        'entidad_id' => $prestamo->id,
                    ]
                );
            }

            // 2. Notificar al Coordinador de la distribuidora
            if ($prestamo->createdBy && $prestamo->createdBy->coordinador_id) {
                NotificacionCajero::enviar(
                    $prestamo->createdBy->coordinador_id,
                    'prestamo_cobrado',
                    'Vale Cobrado por Cliente',
                    "El cliente {$prestamo->cliente->nombre} (Distribuidora: {$prestamo->createdBy->name}) cobró el vale {$prestamo->referencia} por $" . number_format($prestamo->monto_prestamo, 2) . " en la sucursal {$nombreSucursal}.",
                    [
                        'url' => route('prestamos.show', $prestamo),
                        'entidad_tipo' => 'prestamos',
                        'entidad_id' => $prestamo->id,
                    ]
                );
            }

            AuditService::registrar('ENTREGA_VALE_DIGITAL', "Vale {$prestamo->referencia} entregado/cobrado", [
                'entidad_tipo' => 'prestamos',
                'entidad_id' => $prestamo->id,
                'despues' => $prestamo->toArray(),
            ]);
        });

        return redirect()->route('cajero.dashboard')->with('success', 'Vale digital entregado y activado con éxito.');
    }

    /**
     * MÓDULO 3: ABONOS POR DISTRIBUIDORA - INDEX
     */
    public function indexAbonos(Request $request)
    {
        $query = User::whereHas('rol', function($q) {
                $q->whereIn('nombre', ['Distribuidor', 'Distribuidora', 'distribuidor', 'distribuidora']);
            })
            ->where('activo', true)
            ->with(['sucursal', 'prestamos' => function($qp) {
                $qp->where('estado', 'activo');
            }]);
            
        if ($request->filled('buscar')) {
            $busqueda = $request->buscar;
            $query->where(function($q) use ($busqueda) {
                $q->where('name', 'like', "%{$busqueda}%")
                  ->orWhere('email', 'like', "%{$busqueda}%")
                  ->orWhere('referencia_pago_distribuidor', 'like', "%{$busqueda}%")
                  ->orWhere('id', 'like', "%{$busqueda}%");
            });
        }
        
        $distribuidoras = $query->paginate(15)->withQueryString();
        
        return view('cajero.abonos.index', compact('distribuidoras'));
    }

    /**
     * MÓDULO 3: ABONOS POR DISTRIBUIDORA - REGISTRAR
     */
    public function registrarAbonoDistribuidora(Request $request, User $distribuidora)
    {
        $request->validate([
            'monto_abonado' => 'required|numeric|min:0.01',
            'referencia_pago' => 'required|string',
            'metodo_pago' => 'required|in:efectivo,transferencia,tarjeta',
            'observaciones' => 'nullable|string|max:500',
        ]);

        // Validar que la referencia ingresada coincida con la referencia oficial de la distribuidora
        $refIngresada = strtoupper(trim($request->referencia_pago));
        $refOficial = strtoupper(trim($distribuidora->referenciaPago()));
        if ($refIngresada !== $refOficial) {
            return back()->withErrors([
                'referencia_pago' => "La referencia ingresada '{$request->referencia_pago}' no coincide con la referencia bancaria oficial ({$distribuidora->referenciaPago()}). Verifica la referencia o solicita una Conciliación."
            ])->withInput();
        }

        $cajera = $this->cajera();
        $ahora = now();

        DB::transaction(function () use ($distribuidora, $request, $cajera, $ahora) {
            $montoRestante = floatval($request->monto_abonado);

            // 1. Amortizar multas de la distribuidora si las tiene
            if ($distribuidora->multas > 0) {
                $abonoMultas = min($montoRestante, floatval($distribuidora->multas));
                $distribuidora->decrement('multas', $abonoMultas);
                $montoRestante -= $abonoMultas;
            }

            // 2. Distribuir a los préstamos activos con adeudo pendiente
            $prestamos = Prestamo::where('created_by_user_id', $distribuidora->id)
                ->where('estado', 'activo')
                ->where('adeudo_pendiente', '>', 0)
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($prestamos as $prestamo) {
                if ($montoRestante <= 0) {
                    break;
                }

                $pagoPrestamo = min($montoRestante, floatval($prestamo->adeudo_pendiente));

                $pago = PagoPrestamo::create([
                    'prestamo_id' => $prestamo->id,
                    'folio_pago' => 'PAG-' . strtoupper(uniqid()),
                    'numero_quincena' => $prestamo->pagos_realizados + 1,
                    'monto_abonado' => $pagoPrestamo,
                    'metodo_pago' => $request->metodo_pago,
                    'observaciones' => ($request->observaciones ? $request->observaciones . ' | ' : '') . "Abono distribuidora (Ref: {$request->referencia_pago})",
                    'registrado_por_user_id' => $cajera->id,
                ]);

                $prestamo->increment('pagos_recibidos', $pagoPrestamo);
                $prestamo->decrement('adeudo_pendiente', $pagoPrestamo);
                $prestamo->increment('pagos_realizados');

                if ($prestamo->adeudo_pendiente <= 0) {
                    $prestamo->update(['estado' => 'finalizado']);
                }

                $montoRestante -= $pagoPrestamo;
            }

            // 3. Actualizar la relación de cobranza con este abono y evaluar reglas
            $corteService = app(\App\Services\CorteCobranzaService::class);
            $corteService->actualizarRelacionPorAbono($distribuidora, floatval($request->monto_abonado));

            // 4. Notificar a la distribuidora
            NotificacionCajero::enviar(
                $distribuidora->id,
                'abono_registrado',
                'Abono Recibido en Caja',
                "Se ha registrado un abono de $" . number_format($request->monto_abonado, 2) . " a tu cuenta (Ref: {$request->referencia_pago}) por el cajero {$cajera->name}.",
                [
                    'monto' => $request->monto_abonado,
                    'metodo_pago' => $request->metodo_pago,
                ]
            );

            AuditService::registrar('ABONO_DISTRIBUIDORA', "Abono de \${$request->monto_abonado} para {$distribuidora->name} (Ref: {$request->referencia_pago})", [
                'distribuidora_id' => $distribuidora->id,
                'monto' => $request->monto_abonado,
            ]);
        });

        return back()->with('success', "Abono de $" . number_format($request->monto_abonado, 2) . " registrado correctamente para {$distribuidora->name}.");
    }

    /**
     * MÓDULO 3: ABONOS POR PRÉSTAMO INDIVIDUAL (FALLBACK)
     */
    public function registrarAbono(RegistrarAbonoRequest $request, Prestamo $prestamo)
    {
        $cajera = $this->cajera();
        $ahora = now();
        $distribuidora = $prestamo->createdBy;

        DB::transaction(function () use ($prestamo, $request, $cajera, $ahora, $distribuidora) {
            $pago = PagoPrestamo::create([
                'prestamo_id' => $prestamo->id,
                'folio_pago' => 'PAG-' . strtoupper(uniqid()),
                'numero_quincena' => $prestamo->pagos_realizados + 1,
                'monto_abonado' => $request->monto_abonado,
                'metodo_pago' => $request->metodo_pago,
                'observaciones' => $request->observaciones,
                'registrado_por_user_id' => $cajera->id,
            ]);

            $prestamo->increment('pagos_recibidos', $request->monto_abonado);
            $prestamo->decrement('adeudo_pendiente', $request->monto_abonado);
            $prestamo->increment('pagos_realizados');

            if ($prestamo->adeudo_pendiente <= 0) {
                $prestamo->update(['estado' => 'finalizado']);
            }

            if ($distribuidora) {
                $corteService = app(\App\Services\CorteCobranzaService::class);
                $corteService->actualizarRelacionPorAbono($distribuidora, floatval($request->monto_abonado));
            }

            AuditService::registrar('REGISTRO_ABONO', "Abono de \${$request->monto_abonado} a {$prestamo->referencia}", [
                'entidad_tipo' => 'pago_prestamos',
                'entidad_id' => $pago->id,
            ]);
        });

        return back()->with('success', 'Abono registrado correctamente.');
    }

    /**
     * MÓDULO 4: CONCILIACIONES - INDEX
     */
    public function indexConciliaciones(Request $request)
    {
        $cajera = $this->cajera();
        
        $query = Conciliacion::with(['prestamo.cliente', 'distribuidora', 'autorizador', 'conciliadoPor'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('buscar')) {
            $b = $request->buscar;
            $query->where(function($q) use ($b) {
                $q->where('referencia_original', 'like', "%{$b}%")
                  ->orWhere('referencia_conciliacion', 'like', "%{$b}%")
                  ->orWhere('motivo', 'like', "%{$b}%")
                  ->orWhereHas('distribuidora', fn($qd) => $qd->where('name', 'like', "%{$b}%"));
            });
        }

        $conciliaciones = $query->paginate(15)->withQueryString();

        $prestamosActivos = Prestamo::where('estado', 'activo')->with('cliente')->get();
        $distribuidoras = User::whereHas('rol', fn($q) => $q->whereIn('nombre', ['Distribuidor', 'Distribuidora', 'distribuidor', 'distribuidora']))->where('activo', true)->get();

        return view('cajero.conciliaciones.index', compact('conciliaciones', 'prestamosActivos', 'distribuidoras'));
    }

    /**
     * Búsqueda dinámica de pagos para conciliación por monto o fecha.
     */
    public function buscarPagosParaConciliacion(Request $request)
    {
        $query = PagoPrestamo::with(['prestamo.cliente', 'prestamo.createdBy']);

        if ($request->filled('monto')) {
            $query->where('monto_abonado', floatval($request->monto));
        }

        if ($request->filled('fecha')) {
            $query->whereDate('created_at', $request->fecha);
        }

        if ($request->filled('folio')) {
            $query->where('folio_pago', 'like', "%{$request->folio}%");
        }

        $pagos = $query->orderBy('created_at', 'desc')->take(20)->get();

        return response()->json($pagos);
    }

    /**
     * MÓDULO 4: CONCILIACIONES - SOLICITAR
     */
    public function solicitarConciliacion(Request $request)
    {
        $request->validate([
            'motivo' => 'required|string|max:500',
            'monto_original' => 'required|numeric|min:0.01',
            'monto_corregido' => 'required|numeric|min:0.01',
            'referencia_conciliacion' => 'nullable|string|max:100',
            'referencia_original' => 'nullable|string|max:100',
            'fecha_pago' => 'nullable|date',
            'evidencia' => 'nullable|file|max:5120',
        ]);

        $cajera = $this->cajera();
        
        $path = null;
        if ($request->hasFile('evidencia')) {
            $path = $request->file('evidencia')->store('evidencias', 'public');
        }

        DB::transaction(function () use ($request, $cajera, $path) {
            $conciliacion = Conciliacion::create([
                'prestamo_id' => $request->prestamo_id ?: null,
                'pago_prestamo_id' => $request->pago_prestamo_id ?: null,
                'distribuidora_id' => $request->distribuidora_id ?: null,
                'referencia_original' => $request->referencia_original,
                'referencia_conciliacion' => $request->referencia_conciliacion,
                'fecha_pago' => $request->fecha_pago,
                'metodo_pago' => $request->metodo_pago ?? 'transferencia',
                'monto_original' => $request->monto_original,
                'monto_corregido' => $request->monto_corregido,
                'motivo' => $request->motivo,
                'evidencia_path' => $path,
                'solicitante_id' => $cajera->id,
                'estado' => 'pendiente',
            ]);

            $solicitud = SolicitudAutorizacion::create([
                'tipo' => 'conciliacion_manual',
                'solicitante_id' => $cajera->id,
                'sucursal_id' => $cajera->sucursal_id,
                'entidad_tipo' => 'conciliaciones',
                'entidad_id' => $conciliacion->id,
                'datos_originales' => [
                    'monto_original' => $request->monto_original,
                    'referencia_original' => $request->referencia_original,
                ],
                'datos_propuestos' => [
                    'monto_corregido' => $request->monto_corregido,
                    'referencia_conciliacion' => $request->referencia_conciliacion,
                    'fecha_pago' => $request->fecha_pago,
                ],
                'motivo' => $request->motivo,
                'evidencia_path' => $path,
            ]);

            NotificacionService::notificarAutorizadores(
                $cajera->sucursal_id,
                'NUEVA_CONCILIACION',
                'Solicitud de Conciliación Manual',
                "La cajera {$cajera->name} solicita una corrección/conciliación de pago (Ref: {$request->referencia_conciliacion})",
                ['entidad_tipo' => 'solicitudes_autorizacion', 'entidad_id' => $solicitud->id]
            );

            AuditService::registrar('SOLICITUD_CONCILIACION', "Conciliación por \${$request->monto_corregido} con referencia {$request->referencia_conciliacion}");
        });

        return back()->with('success', 'Solicitud de conciliación enviada a Coordinación.');
    }
    
    public function mostrarConciliacion(Conciliacion $conciliacion)
    {
        $conciliacion->load(['prestamo.cliente', 'distribuidora', 'autorizador', 'conciliadoPor']);
        return view('cajero.conciliaciones.show', compact('conciliacion'));
    }

    /**
     * MÓDULO 5: CANJE DE PUNTOS - INDEX
     */
    public function indexCanje(Request $request)
    {
        $distribuidora = null;
        if ($request->filled('distribuidora_id')) {
            $distribuidora = User::find($request->distribuidora_id);
        }
        
        // Obtener distribuidores para el select
        $distribuidoras = User::whereHas('rol', function($q) {
            $q->whereIn('nombre', ['Distribuidor', 'Distribuidora']);
        })->where('activo', true)->get();

        $config = Configuracion::actual();

        return view('cajero.canje-puntos.index', compact('distribuidora', 'distribuidoras', 'config'));
    }

    /**
     * MÓDULO 5: CANJE DE PUNTOS - REALIZAR
     */
    public function realizarCanje(CanjePuntosRequest $request)
    {
        $cajera = $this->cajera();
        $distribuidora = User::findOrFail($request->distribuidora_id);
        $puntosACanjear = (int) $request->puntos_canjear;

        if ($distribuidora->puntos < $puntosACanjear) {
            return back()->with('error', 'La distribuidora no tiene suficientes puntos.');
        }

        $config = Configuracion::actual();
        $equivalenteEnDinero = $puntosACanjear * $config->obtenerValorPunto();

        DB::transaction(function () use ($distribuidora, $puntosACanjear, $equivalenteEnDinero, $cajera, $config) {
            $distribuidora->decrement('puntos', $puntosACanjear);

            $canje = CanjePuntos::create([
                'distribuidora_id' => $distribuidora->id,
                'puntos_canjeados' => $puntosACanjear,
                'valor_punto' => $config->obtenerValorPunto(),
                'equivalente_dinero' => $equivalenteEnDinero,
                'cajera_id' => $cajera->id,
                'sucursal_id' => $cajera->sucursal_id,
            ]);

            AuditService::registrar('CANJE_PUNTOS', "Canje de {$puntosACanjear} pts por \${$equivalenteEnDinero} para {$distribuidora->name}", [
                'entidad_tipo' => 'canjes_puntos',
                'entidad_id' => $canje->id,
            ]);
        });

        return back()->with('success', "Canje exitoso. Entregar a distribuidora: $" . number_format($equivalenteEnDinero, 2));
    }

    /**
     * NOTIFICACIONES
     */
    public function notificaciones(Request $request)
    {
        $cajera = $this->cajera();
        $notificaciones = $cajera->notificaciones()->paginate(15);
        
        return view('cajero.notificaciones', compact('notificaciones'));
    }

    public function marcarNotificacionLeida($id)
    {
        $notificacion = NotificacionCajero::where('user_id', Auth::id())->findOrFail($id);
        $notificacion->marcarLeida();
        
        return back();
    }
}
