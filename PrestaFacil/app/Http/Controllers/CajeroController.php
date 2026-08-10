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
                'estado_entrega' => 'entregado',
                'entregado_por_user_id' => $cajera->id,
                'entregado_at' => now(),
                'numero_transferencia' => $request->numero_transferencia,
                'monto_depositado' => $request->monto_depositado,
                'sucursal_entrega_id' => $cajera->sucursal_id,
            ]);

            AuditService::registrar('ENTREGA_PREVALE', "Prevale {$prestamo->referencia} entregado", [
                'entidad_tipo' => 'prestamos',
                'entidad_id' => $prestamo->id,
                'despues' => $prestamo->toArray(),
            ]);
        });

        return redirect()->route('cajero.dashboard')->with('success', 'Prevale entregado con éxito.');
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
                'estado_entrega' => 'entregado',
                'entregado_por_user_id' => $cajera->id,
                'entregado_at' => now(),
                'numero_transferencia' => $request->numero_transferencia,
                'monto_depositado' => $request->monto_depositado,
                'sucursal_entrega_id' => $cajera->sucursal_id,
            ]);

            AuditService::registrar('ENTREGA_VALE_DIGITAL', "Vale {$prestamo->referencia} entregado", [
                'entidad_tipo' => 'prestamos',
                'entidad_id' => $prestamo->id,
                'despues' => $prestamo->toArray(),
            ]);
        });

        return redirect()->route('cajero.dashboard')->with('success', 'Vale digital entregado con éxito.');
    }

    /**
     * MÓDULO 3: ABONOS - INDEX
     */
    public function indexAbonos(Request $request)
    {
        $query = Prestamo::with('cliente')
            ->where('estado', 'activo')
            ->where('estado_entrega', 'entregado');
            
        if ($request->filled('buscar')) {
            $busqueda = $request->buscar;
            $query->where(function($q) use ($busqueda) {
                $q->where('referencia', 'like', "%{$busqueda}%")
                  ->orWhereHas('cliente', function($qc) use ($busqueda) {
                      $qc->where('nombre', 'like', "%{$busqueda}%");
                  });
            });
        }
        
        $prestamos = $query->paginate(20);
        
        return view('cajero.abonos.index', compact('prestamos'));
    }

    /**
     * MÓDULO 3: ABONOS - REGISTRAR
     */
    public function registrarAbono(RegistrarAbonoRequest $request, Prestamo $prestamo)
    {
        $cajera = $this->cajera();
        $ahora = now();
        $distribuidora = $prestamo->createdBy;

        DB::transaction(function () use ($prestamo, $request, $cajera, $ahora, $distribuidora) {
            $tipoPago = $this->validacionService->determinarTipoPago($ahora);
            
            // Si es tardío y la distribuidora cae en morosidad
            if ($tipoPago === 'tardio' && $this->validacionService->esMorosa($distribuidora)) {
                $puntosPerdidos = $this->validacionService->aplicarPenalizacionMorosidad($distribuidora);
                if ($puntosPerdidos > 0) {
                    $distribuidora->decrement('puntos', $puntosPerdidos);
                    AuditService::registrar('PENALIZACION_PUNTOS', "Se restaron {$puntosPerdidos} puntos por morosidad a {$distribuidora->name}");
                }
            }

            // Si es anticipado
            if ($tipoPago === 'anticipado') {
                $puntosGanados = $this->validacionService->calcularPuntosAbono('anticipado');
                $distribuidora->increment('puntos', $puntosGanados);
            }

            $pago = PagoPrestamo::create([
                'prestamo_id' => $prestamo->id,
                'folio_pago' => 'PAG-' . strtoupper(uniqid()),
                'numero_quincena' => $prestamo->pagos_realizados + 1,
                'monto_abonado' => $request->monto_abonado,
                'metodo_pago' => $request->metodo_pago,
                'observaciones' => $request->observaciones . " [Tipo: $tipoPago]",
                'registrado_por_user_id' => $cajera->id,
            ]);

            $prestamo->increment('pagos_recibidos', $request->monto_abonado);
            $prestamo->decrement('adeudo_pendiente', $request->monto_abonado);
            $prestamo->increment('pagos_realizados');

            if ($prestamo->adeudo_pendiente <= 0) {
                $prestamo->update(['estado' => 'finalizado']);
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
        
        $conciliaciones = Conciliacion::with(['prestamo.cliente', 'autorizador'])
            ->where('solicitante_id', $cajera->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Para el modal de nueva conciliación
        $prestamosActivos = Prestamo::where('estado', 'activo')->get();

        return view('cajero.conciliaciones.index', compact('conciliaciones', 'prestamosActivos'));
    }

    /**
     * MÓDULO 4: CONCILIACIONES - SOLICITAR
     */
    public function solicitarConciliacion(SolicitarConciliacionRequest $request)
    {
        $cajera = $this->cajera();
        
        $path = null;
        if ($request->hasFile('evidencia')) {
            $path = $request->file('evidencia')->store('evidencias', 'public');
        }

        DB::transaction(function () use ($request, $cajera, $path) {
            $conciliacion = Conciliacion::create([
                'prestamo_id' => $request->prestamo_id,
                'monto_original' => $request->monto_original,
                'monto_corregido' => $request->monto_corregido,
                'motivo' => $request->motivo,
                'evidencia_path' => $path,
                'solicitante_id' => $cajera->id,
            ]);

            $solicitud = SolicitudAutorizacion::create([
                'tipo' => 'conciliacion_manual',
                'solicitante_id' => $cajera->id,
                'sucursal_id' => $cajera->sucursal_id,
                'entidad_tipo' => 'conciliaciones',
                'entidad_id' => $conciliacion->id,
                'datos_originales' => ['monto_original' => $request->monto_original],
                'datos_propuestos' => ['monto_corregido' => $request->monto_corregido],
                'motivo' => $request->motivo,
                'evidencia_path' => $path,
            ]);

            NotificacionService::notificarAutorizadores(
                $cajera->sucursal_id,
                'NUEVA_CONCILIACION',
                'Solicitud de Conciliación Manual',
                "La cajera {$cajera->name} solicita una corrección de abono",
                ['entidad_tipo' => 'solicitudes_autorizacion', 'entidad_id' => $solicitud->id]
            );

            AuditService::registrar('SOLICITUD_CONCILIACION', "Conciliación para préstamo ID {$request->prestamo_id}");
        });

        return back()->with('success', 'Solicitud de conciliación enviada a Coordinación.');
    }
    
    public function mostrarConciliacion(Conciliacion $conciliacion)
    {
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
