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
use Illuminate\Support\Str;

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
        $cajera = $this->cajera();
        $referencia = $request->input('referencia');
        $prestamo = null;

        if ($referencia) {
            $query = Prestamo::with(['cliente', 'productoVale', 'createdBy'])->where('referencia', $referencia);

            if ($cajera->sucursal_id) {
                $query->whereHas('createdBy', fn($q) => $q->where('sucursal_id', $cajera->sucursal_id));
            }

            $prestamo = $query->first();
            
            if (!$prestamo) {
                return back()->with('error', 'Referencia no encontrada o no pertenece a tu sucursal.');
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

            // 3. Notificar a Gerentes
            $gerentes = \App\Models\User::whereHas('rol', function ($q) {
                $q->where('nombre', 'like', '%gerente%');
            })->get();

            foreach ($gerentes as $gerente) {
                NotificacionCajero::enviar(
                    $gerente->id,
                    'prestamo_cobrado',
                    'Préstamo Cobrado en Ventanilla',
                    "El cliente {$prestamo->cliente->nombre} cobró el prevale {$prestamo->referencia} por $" . number_format($prestamo->monto_prestamo, 2) . " en la sucursal {$nombreSucursal}.",
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

            // 3. Notificar a Gerentes
            $gerentes = \App\Models\User::whereHas('rol', function ($q) {
                $q->where('nombre', 'like', '%gerente%');
            })->get();

            foreach ($gerentes as $gerente) {
                NotificacionCajero::enviar(
                    $gerente->id,
                    'prestamo_cobrado',
                    'Vale Cobrado en Ventanilla',
                    "El cliente {$prestamo->cliente->nombre} cobró el vale {$prestamo->referencia} por $" . number_format($prestamo->monto_prestamo, 2) . " en la sucursal {$nombreSucursal}.",
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
        $cajera = $this->cajera();

        $query = User::whereHas('rol', function($q) {
                $q->whereIn('nombre', ['Distribuidor', 'Distribuidora', 'distribuidor', 'distribuidora']);
            })
            ->where('activo', true);

        if ($cajera->sucursal_id) {
            $query->where('sucursal_id', $cajera->sucursal_id);
        }

        $query->with(['sucursal', 'prestamos' => function($qp) {
            $qp->where('estado', 'activo')
               ->where(function($q) {
                   $q->whereNull('estado_entrega')
                     ->orWhere('estado_entrega', '!=', 'pendiente');
               })
               ->with(['cliente', 'productoVale']);
        }]);
            
        if ($request->filled('buscar')) {
            $busqueda = $request->buscar;
            $query->where(function($q) use ($busqueda) {
                $q->where('name', 'like', "%{$busqueda}%")
                  ->orWhere('email', 'like', "%{$busqueda}%")
                  ->orWhere('referencia_pago_distribuidor', 'like', "%{$busqueda}%")
                  ->orWhere('id', 'like', "%{$busqueda}%")
                  ->orWhereHas('prestamos', function($qp) use ($busqueda) {
                      $qp->where('referencia', 'like', "%{$busqueda}%")
                        ->orWhereHas('cliente', fn($qc) => $qc->where('nombre', 'like', "%{$busqueda}%")->orWhere('apellido_paterno', 'like', "%{$busqueda}%"));
                  });
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

        if (floatval($request->monto_abonado) >= 1000000) {
            return back()->with('error', 'Límite de un solo abono debe ser menor a 1 millón.')->withInput();
        }

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

            // 1. Amortizar multas de los vales individuales
            $prestamosConMultas = Prestamo::where('created_by_user_id', $distribuidora->id)
                ->where('estado', 'activo')
                ->where('multas', '>', 0)
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($prestamosConMultas as $pm) {
                if ($montoRestante <= 0) break;
                $abonoM = min($montoRestante, floatval($pm->multas));
                $pm->decrement('multas', $abonoM);
                $montoRestante -= $abonoM;

                if ($distribuidora->multas > 0) {
                    $distribuidora->decrement('multas', min($abonoM, floatval($distribuidora->multas)));
                }
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
                    'folio_pago' => 'PAG-DIST-' . strtoupper(uniqid()),
                    'numero_quincena' => $prestamo->pagos_realizados + 1,
                    'monto_abonado' => $pagoPrestamo,
                    'metodo_pago' => $request->metodo_pago,
                    'observaciones' => "Abono general distribuidora. " . ($request->observaciones ?? ''),
                    'registrado_por_user_id' => $cajera->id,
                ]);

                $prestamo->increment('pagos_recibidos', $pagoPrestamo);
                $prestamo->decrement('adeudo_pendiente', $pagoPrestamo);
                $prestamo->increment('pagos_realizados');

                if ($prestamo->adeudo_pendiente <= 0 && ($prestamo->multas ?? 0) <= 0) {
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
     * MÓDULO 3: ABONOS POR VALE INDIVIDUAL
     */
    public function registrarAbono(RegistrarAbonoRequest $request, Prestamo $prestamo)
    {
        if (floatval($request->monto_abonado) >= 1000000) {
            return back()->with('error', 'Límite de un solo abono debe ser menor a 1 millón.')->withInput();
        }

        $distribuidora = $prestamo->createdBy;
        $corteService = app(\App\Services\CorteCobranzaService::class);
        $filasRelacion = $distribuidora ? $corteService->generarFilasRelacionCobranza($distribuidora) : [];
        $filasPrestamo = array_values(array_filter($filasRelacion, fn($f) => $f['prestamo_id'] == $prestamo->id));
        $comisionVale = 0;
        $multaPrestamo = 0;
        foreach ($filasPrestamo as $fp) {
            $comisionVale += floatval($fp['comision']);
            $multaPrestamo += floatval($fp['recargos']);
        }
        $numCortesAtrasados = max(0, count($filasPrestamo) - 1);
        $comisionPorQuincena = count($filasPrestamo) > 0 ? ($comisionVale / count($filasPrestamo)) : $prestamo->comisionDistribuidorPorQuincena();
        $comisionesPerdidas = ($numCortesAtrasados > 0 && $multaPrestamo > 0) ? ($numCortesAtrasados * $comisionPorQuincena) : 0.0;

        $totalMaximoPermitido = floatval($prestamo->adeudo_pendiente) + max(floatval($prestamo->multas ?? 0), $multaPrestamo) + $comisionesPerdidas;
        if (floatval($request->monto_abonado) > ($totalMaximoPermitido + 0.01)) {
            return back()->with('error', "El abono ingresado (\$" . number_format($request->monto_abonado, 2) . ") excede el adeudo total pendiente del vale (\$" . number_format($totalMaximoPermitido, 2) . ").")->withInput();
        }

        $cajera = $this->cajera();
        $ahora = now();

        DB::transaction(function () use ($prestamo, $request, $cajera, $ahora, $distribuidora) {
            $montoRestante = floatval($request->monto_abonado);
            $abonoMultas = 0.0;

            // 1. Amortizar multas del vale individual primero
            if ($prestamo->multas > 0) {
                $abonoMultas = min($montoRestante, floatval($prestamo->multas));
                $prestamo->decrement('multas', $abonoMultas);
                $montoRestante -= $abonoMultas;

                // Actualizar multas en distribuidora
                if ($distribuidora && $distribuidora->multas > 0) {
                    $distribuidora->decrement('multas', min($abonoMultas, floatval($distribuidora->multas)));
                }
            }

            // 2. Amortizar capital / adeudo pendiente del préstamo
            if ($montoRestante > 0) {
                $pagoCapital = min($montoRestante, floatval($prestamo->adeudo_pendiente));
                $prestamo->increment('pagos_recibidos', $pagoCapital);
                $prestamo->decrement('adeudo_pendiente', $pagoCapital);
                $montoRestante -= $pagoCapital;
            }

            $fechaInicioPrestamo = $prestamo->entregado_at ?? $prestamo->created_at;
            $cortesPosteriores = 0;
            if ($distribuidora && $fechaInicioPrestamo) {
                $cortesPosteriores = \App\Models\RelacionCobranza::where('distribuidora_id', $distribuidora->id)
                    ->whereNotNull('fecha_corte')
                    ->where('fecha_corte', '>=', $fechaInicioPrestamo)
                    ->count();
            }
            $totalQuincenasPrestamo = intval($prestamo->pagos_totales ?: ($prestamo->productoVale?->plazo_quincenas ?: 8));
            $quincenaActual = min($totalQuincenasPrestamo, max(1, $cortesPosteriores));

            $pago = PagoPrestamo::create([
                'prestamo_id' => $prestamo->id,
                'folio_pago' => 'PAG-' . strtoupper(uniqid()),
                'numero_quincena' => $quincenaActual,
                'monto_abonado' => $request->monto_abonado,
                'metodo_pago' => $request->metodo_pago,
                'observaciones' => $request->observaciones,
                'registrado_por_user_id' => $cajera->id,
            ]);

            $prestamo->increment('pagos_realizados');

            if ($prestamo->adeudo_pendiente <= 0 && ($prestamo->multas ?? 0) <= 0) {
                $prestamo->update(['estado' => 'finalizado']);
                AuditService::registrar('VALE_FINALIZADO', "Vale {$prestamo->referencia} ha sido liquidado en su totalidad y marcado como FINALIZADO", [
                    'prestamo_id' => $prestamo->id,
                    'cliente_id' => $prestamo->cliente_id,
                    'distribuidora_id' => $prestamo->created_by_user_id,
                ]);
            }

            if ($distribuidora) {
                $corteService = app(\App\Services\CorteCobranzaService::class);
                $corteService->actualizarRelacionPorAbono($distribuidora, floatval($request->monto_abonado));
            }

            AuditService::registrar('REGISTRO_ABONO', "Abono individual de \${$request->monto_abonado} a Vale {$prestamo->referencia} (Multas cubiertas: \${$abonoMultas})", [
                'entidad_tipo' => 'pago_prestamos',
                'entidad_id' => $pago->id,
                'prestamo_id' => $prestamo->id,
            ]);
        });

        return back()->with('success', "Abono de $" . number_format($request->monto_abonado, 2) . " aplicado exitosamente al vale {$prestamo->referencia}.");
    }

    /**
     * MÓDULO 4: CONCILIACIONES - INDEX
     */
    public function indexConciliaciones(Request $request)
    {
        $cajera = $this->cajera();

        $query = Conciliacion::with(['solicitante', 'distribuidora', 'prestamo.cliente', 'conciliadoPor', 'autorizador'])
            ->orderBy('created_at', 'desc');

        if ($cajera->sucursal_id) {
            $query->whereHas('solicitante', fn($q) => $q->where('sucursal_id', $cajera->sucursal_id));
        }

        if ($request->filled('buscar')) {
            $b = $request->buscar;
            $query->where(function($q) use ($b) {
                $q->where('referencia_original', 'like', "%{$b}%")
                  ->orWhere('referencia_conciliacion', 'like', "%{$b}%")
                  ->orWhere('motivo', 'like', "%{$b}%")
                  ->orWhereHas('distribuidora', fn($qd) => $qd->where('name', 'like', "%{$b}%"));
            });
        }

        $conciliaciones = $query->paginate(15, ['*'], 'conciliaciones_page')->withQueryString();

        // Pagos registrados recientes para consulta directa del cajero
        $pagosQuery = PagoPrestamo::with(['prestamo.cliente', 'prestamo.createdBy'])
            ->orderBy('created_at', 'desc');
            
        if ($cajera->sucursal_id) {
            $pagosQuery->whereHas('prestamo.createdBy', fn($q) => $q->where('sucursal_id', $cajera->sucursal_id));
        }

        if ($request->filled('buscar_pago')) {
            $bp = $request->buscar_pago;
            $pagosQuery->where(function($q) use ($bp) {
                $q->where('folio_pago', 'like', "%{$bp}%")
                  ->orWhereHas('prestamo', fn($qp) => $qp->where('referencia', 'like', "%{$bp}%"))
                  ->orWhereHas('prestamo.cliente', fn($qc) => $qc->where('nombre', 'like', "%{$bp}%"));
            });
        }

        $pagosRecientes = $pagosQuery->paginate(15, ['*'], 'pagos_page')->withQueryString();

        $prestamosQuery = Prestamo::where('estado', 'activo')->with(['cliente', 'createdBy']);
        $distQuery = User::whereHas('rol', fn($q) => $q->whereIn('nombre', ['Distribuidor', 'Distribuidora', 'distribuidor', 'distribuidora']))->where('activo', true);

        if ($cajera->sucursal_id) {
            $prestamosQuery->whereHas('createdBy', fn($q) => $q->where('sucursal_id', $cajera->sucursal_id));
            $distQuery->where('sucursal_id', $cajera->sucursal_id);
        }

        $prestamosActivos = $prestamosQuery->get();
        $distribuidoras = $distQuery->get();

        return view('cajero.conciliaciones.index', compact('conciliaciones', 'pagosRecientes', 'prestamosActivos', 'distribuidoras'));
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
            'monto_original' => 'nullable|numeric|min:0',
            'monto_corregido' => 'required|numeric|min:0.01',
            'referencia_conciliacion' => 'nullable|string|max:100',
            'referencia_original' => 'nullable|string|max:100',
            'fecha_pago' => 'nullable|date',
            'evidencia' => 'nullable|file|max:5120',
        ]);

        // Procesar préstamos asignados si se enviaron múltiples
        $prestamosAsignados = [];
        if ($request->filled('prestamos_asignados')) {
            $rawAsignados = is_array($request->prestamos_asignados) ? $request->prestamos_asignados : json_decode($request->prestamos_asignados, true);
            if (is_array($rawAsignados)) {
                foreach ($rawAsignados as $asig) {
                    if (!empty($asig['prestamo_id']) || !empty($asig['folio'])) {
                        $p = !empty($asig['prestamo_id']) ? Prestamo::find($asig['prestamo_id']) : Prestamo::where('referencia', $asig['folio'])->first();
                        if ($p) {
                            $prestamosAsignados[] = [
                                'prestamo_id' => $p->id,
                                'folio' => $p->referencia,
                                'cliente' => $p->cliente?->nombre ?? 'Cliente',
                                'monto' => floatval($asig['monto'] ?? $request->monto_corregido),
                            ];
                        }
                    }
                }
            }
        }

        // Si no se especificó array pero sí prestamo_id directo
        if (empty($prestamosAsignados) && $request->filled('prestamo_id')) {
            $p = Prestamo::find($request->prestamo_id);
            if ($p) {
                $prestamosAsignados[] = [
                    'prestamo_id' => $p->id,
                    'folio' => $p->referencia,
                    'cliente' => $p->cliente?->nombre ?? 'Cliente',
                    'monto' => floatval($request->monto_corregido),
                ];
            }
        }

        // Verificar si ya existe una conciliación pendiente para esta referencia o pago
        $existente = null;
        if ($request->filled('prestamo_id') || $request->filled('pago_prestamo_id') || $request->filled('referencia_conciliacion')) {
            $existente = Conciliacion::where(function($q) use ($request) {
                if ($request->filled('prestamo_id')) $q->orWhere('prestamo_id', $request->prestamo_id);
                if ($request->filled('pago_prestamo_id')) $q->orWhere('pago_prestamo_id', $request->pago_prestamo_id);
                if ($request->filled('referencia_conciliacion')) $q->orWhere('referencia_conciliacion', $request->referencia_conciliacion);
            })->whereIn('estado', ['pendiente_coordinador', 'pendiente_gerencia', 'pendiente'])->first();
        }

        if ($existente) {
            return back()->with('error', 'Ya existe una solicitud de conciliación manual pendiente para esta referencia o pago.')->withInput();
        }

        $cajera = $this->cajera();
        
        $path = null;
        if ($request->hasFile('evidencia')) {
            $path = $request->file('evidencia')->store('evidencias', 'public');
        }

        DB::transaction(function () use ($request, $cajera, $path, $prestamosAsignados) {
            $primerPrestamo = !empty($prestamosAsignados) ? Prestamo::find($prestamosAsignados[0]['prestamo_id']) : null;
            $distribuidoraId = $request->distribuidora_id ?: ($primerPrestamo ? $primerPrestamo->created_by_user_id : null);
            $distribuidora = $distribuidoraId ? User::find($distribuidoraId) : null;
            $prestamo = $request->prestamo_id ? Prestamo::find($request->prestamo_id) : $primerPrestamo;

            $refOriginal = $request->referencia_original;
            if (empty($refOriginal) || strtoupper(trim($refOriginal)) === 'N/A') {
                $refOriginal = $prestamo?->referencia ?? ($distribuidora ? $distribuidora->referenciaPago() : 'REF-ERR-' . strtoupper(Str::random(6)));
            }

            $refConciliacion = $request->referencia_conciliacion;
            if (empty($refConciliacion) || strtoupper(trim($refConciliacion)) === 'N/A') {
                $refConciliacion = $distribuidora ? $distribuidora->referenciaPago() : ($prestamo?->referencia ?? 'REF-CONC-' . strtoupper(Str::random(6)));
            }

            $montoOriginal = $request->filled('monto_original') ? floatval($request->monto_original) : floatval($request->monto_corregido);

            $conciliacion = Conciliacion::create([
                'prestamo_id' => $prestamo?->id ?: null,
                'pago_prestamo_id' => $request->pago_prestamo_id ?: null,
                'prestamos_asignados' => !empty($prestamosAsignados) ? $prestamosAsignados : null,
                'distribuidora_id' => $distribuidoraId,
                'referencia_original' => $refOriginal,
                'referencia_conciliacion' => $refConciliacion,
                'fecha_pago' => $request->fecha_pago ?: now()->toDateString(),
                'metodo_pago' => $request->metodo_pago ?? 'transferencia',
                'monto_original' => $montoOriginal,
                'monto_corregido' => $request->monto_corregido,
                'motivo' => $request->motivo,
                'evidencia_path' => $path,
                'solicitante_id' => $cajera->id,
                'estado' => 'pendiente_coordinador',
            ]);

            // Log de Auditoría 1: Creación de la Solicitud de Conciliación
            AuditService::registrar(
                'CONCILIACION_SOLICITADA',
                "Cajera {$cajera->name} solicitó conciliación manual por $" . number_format($request->monto_corregido, 2) . " (Ref: {$refConciliacion})",
                [
                    'entidad_tipo' => 'conciliaciones',
                    'entidad_id' => $conciliacion->id,
                    'solicitante_id' => $cajera->id,
                    'sucursal_id' => $cajera->sucursal_id,
                    'antes' => [
                        'referencia_original' => $refOriginal,
                        'monto_original' => $montoOriginal,
                    ],
                    'despues' => [
                        'referencia_conciliacion' => $refConciliacion,
                        'monto_corregido' => $request->monto_corregido,
                        'prestamos_asignados' => $prestamosAsignados,
                        'motivo' => $request->motivo,
                        'fecha_pago' => $request->fecha_pago ?: now()->toDateString(),
                        'estado' => 'pendiente_coordinador',
                    ],
                ]
            );

            // Crear solicitud de autorización para seguimiento
            SolicitudAutorizacion::create([
                'tipo' => 'conciliacion_manual',
                'solicitante_id' => $cajera->id,
                'sucursal_id' => $cajera->sucursal_id,
                'entidad_tipo' => 'conciliaciones',
                'entidad_id' => $conciliacion->id,
                'datos_originales' => [
                    'monto_original' => $montoOriginal,
                    'referencia_original' => $refOriginal,
                ],
                'datos_propuestos' => [
                    'monto_corregido' => $request->monto_corregido,
                    'referencia_conciliacion' => $refConciliacion,
                    'fecha_pago' => $request->fecha_pago ?: now()->toDateString(),
                    'prestamos_asignados' => $prestamosAsignados,
                ],
                'motivo' => $request->motivo,
                'evidencia_path' => $path,
            ]);

            // 1. Notificar a Gerente General
            $gerentesGenerales = User::whereHas('rol', fn($q) => $q->whereIn('nombre', ['Gerente General', 'gerente general', 'GerenteGeneral', 'gerente_general']))
                ->where('activo', true)
                ->get();

            foreach ($gerentesGenerales as $gg) {
                NotificacionCajero::enviar(
                    $gg->id,
                    'conciliacion_solicitada_gerente',
                    'Solicitud de Conciliación de Pago',
                    "La cajera {$cajera->name} solicita la revisión y dictamen de una conciliación de pago por $" . number_format($request->monto_corregido, 2) . ($distribuidora ? " para la distribuidora {$distribuidora->name}." : "."),
                    [
                        'conciliacion_id' => $conciliacion->id,
                        'referencia' => $refConciliacion,
                        'url' => route('gerente.conciliaciones.show', $conciliacion, false),
                    ]
                );
            }

            // 2. Notificar a Gerente de Sucursal
            $sucursalTargetId = $cajera->sucursal_id ?? $distribuidora?->sucursal_id;
            if ($sucursalTargetId) {
                $gerentesSucursal = User::whereHas('rol', fn($q) => $q->whereIn('nombre', ['Gerente de Sucursal', 'gerente de sucursal', 'Gerente Sucursal', 'gerente_de_sucursal']))
                    ->where('sucursal_id', $sucursalTargetId)
                    ->where('activo', true)
                    ->get();

                foreach ($gerentesSucursal as $gs) {
                    NotificacionCajero::enviar(
                        $gs->id,
                        'conciliacion_solicitada_gerente',
                        'Solicitud de Conciliación de Pago',
                        "La cajera {$cajera->name} solicita la revisión y dictamen de una conciliación de pago por $" . number_format($request->monto_corregido, 2) . ($distribuidora ? " para la distribuidora {$distribuidora->name}." : "."),
                        [
                            'conciliacion_id' => $conciliacion->id,
                            'referencia' => $refConciliacion,
                            'url' => route('gerente.conciliaciones.show', $conciliacion, false),
                        ]
                    );
                }
            }

            // 3. Notificar a los coordinadores de la sucursal del cajero
            $coordinadores = User::whereHas('rol', fn($q) => $q->whereIn('nombre', ['Coordinador', 'coordinador']))
                ->where('sucursal_id', $cajera->sucursal_id)
                ->where('activo', true)
                ->get();

            foreach ($coordinadores as $coord) {
                NotificacionCajero::enviar(
                    $coord->id,
                    'conciliacion_solicitada_coordinador',
                    'Solicitud de Conciliación Manual',
                    "La cajera {$cajera->name} solicita la revisión de una conciliación manual por $" . number_format($request->monto_corregido, 2) . ".",
                    [
                        'conciliacion_id' => $conciliacion->id,
                        'referencia' => $refConciliacion,
                    ]
                );
            }
        });

        return back()->with('success', 'Solicitud de conciliación registrada y notificada a Gerencia y Coordinación.');
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
        $cajera = $this->cajera();

        $distribuidora = null;
        if ($request->filled('distribuidora_id')) {
            $distribuidora = User::find($request->distribuidora_id);
        }
        
        // Obtener distribuidores de la misma sucursal para el select
        $distQuery = User::whereHas('rol', function($q) {
            $q->whereIn('nombre', ['Distribuidor', 'Distribuidora', 'distribuidor', 'distribuidora']);
        })->where('activo', true);

        if ($cajera->sucursal_id) {
            $distQuery->where('sucursal_id', $cajera->sucursal_id);
        }

        $distribuidoras = $distQuery->get();

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

            NotificacionCajero::enviar(
                $distribuidora->id,
                'canje_puntos',
                'Canje de Puntos Realizado',
                "Se han canjeado {$puntosACanjear} puntos de tu cuenta por un equivalente de $" . number_format($equivalenteEnDinero, 2) . " en ventanilla de caja."
            );
        });

        return back()->with('success', "Puntos cobrados, realiza la transferencia correspondiente");
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
