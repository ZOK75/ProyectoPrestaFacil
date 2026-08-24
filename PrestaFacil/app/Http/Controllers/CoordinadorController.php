<?php

namespace App\Http\Controllers;

use App\Models\NotificacionCajero;
use App\Models\Prestamo;
use App\Models\Rol;
use App\Models\SolicitudCredito;
use App\Models\SolicitudDistribuidor;
use App\Models\SolicitudTransferencia;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CoordinadorController extends Controller
{
    /**
     * Dashboard del Coordinador
     */
    public function dashboard()
    {
        $user = Auth::user();
        
        // Distribuidoras asignadas a este coordinador
        $distribuidores = $user->misDistribuidorasQuery()
            ->with(['sucursal', 'prestamos' => fn($q) => $q->where('estado', 'activo')])
            ->orderBy('name')
            ->get();

        // Obtener historial de solicitudes de incremento de crédito
        $solicitudesCredito = SolicitudCredito::where('coordinador_id', $user->id)
            ->with(['distribuidor', 'gerente'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Solicitudes de transferencia de distribuidora emitidas
        $transferenciasEmitidas = SolicitudTransferencia::where('coordinador_emisor_id', $user->id)
            ->with(['distribuidor', 'coordinadorReceptor', 'sucursalDestino', 'gerente'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Solicitudes de transferencia recibidas
        $transferenciasRecibidas = SolicitudTransferencia::where('coordinador_receptor_id', $user->id)
            ->with(['distribuidor', 'coordinadorEmisor', 'sucursalOrigen'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Otros coordinadores activos en el sistema para transferencias
        $coordinadoresDestino = User::whereHas('rol', fn($q) => $q->whereIn('nombre', ['Coordinador', 'coordinador', 'Coordinadora']))
            ->where('activo', true)
            ->where('id', '!=', $user->id)
            ->with('sucursal')
            ->orderBy('name')
            ->get();

        // Métricas de Cartera y Supervisión para Tablet/Desktop
        $totalPrestamosActivos = 0;
        $totalAdeudoCartera = 0.0;
        $totalCreditoColocado = 0.0;

        foreach ($distribuidores as $dist) {
            $totalPrestamosActivos += $dist->prestamos->count();
            $totalAdeudoCartera += $dist->prestamos->sum('adeudo_pendiente');
            $totalCreditoColocado += $dist->prestamos->sum('monto_prestamo');
        }

        $stats = [
            'total_distribuidores' => $distribuidores->count(),
            'prestamos_activos' => $totalPrestamosActivos,
            'adeudo_cartera' => $totalAdeudoCartera,
            'credito_colocado' => $totalCreditoColocado,
            'transferencias_pendientes' => $transferenciasRecibidas->where('estado', 'pendiente_coordinador')->count(),
        ];

        // Solicitudes de Traspaso de Clientes pendientes de dictamen por el Coordinador (Paso 2)
        $traspasosClientesPendientes = \App\Models\SolicitudTraspasoCliente::where('estado', 'pendiente_coordinador')
            ->where(function($q) use ($user) {
                $q->where('coordinador_id', $user->id)
                  ->orWhereNull('coordinador_id');
            })
            ->with(['cliente', 'distribuidorEmisor', 'distribuidorReceptor'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Conciliaciones manuales de cajeros de esta sucursal pendientes de pre-aprobación del coordinador
        $conciliacionesPendientes = \App\Models\Conciliacion::where('estado', 'pendiente_coordinador')
            ->whereHas('solicitante', fn($q) => $q->where('sucursal_id', $user->sucursal_id))
            ->with(['solicitante', 'distribuidora', 'prestamo.cliente'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('coordinador.dashboard', compact(
            'user',
            'solicitudesCredito',
            'transferenciasEmitidas',
            'transferenciasRecibidas',
            'coordinadoresDestino',
            'distribuidores',
            'traspasosClientesPendientes',
            'conciliacionesPendientes',
            'stats'
        ));
    }

    /**
     * Módulo de Préstamos para el Coordinador:
     * Visualización de distribuidoras con clientes y préstamos activos.
     */
    public function prestamos(Request $request)
    {
        $user = Auth::user();

        // Distribuidoras a cargo
        $distribuidoresIds = $user->misDistribuidorasQuery()->pluck('id');

        $query = Prestamo::with(['cliente', 'productoVale', 'createdBy.sucursal'])
            ->where('estado', 'activo')
            ->whereIn('created_by_user_id', $distribuidoresIds);

        // Filtro por Distribuidora
        if ($request->filled('distribuidor_id')) {
            $query->where('created_by_user_id', $request->input('distribuidor_id'));
        }

        // Búsqueda por folio o cliente
        if ($request->filled('buscar')) {
            $buscar = $request->input('buscar');
            $query->where(function ($q) use ($buscar) {
                $q->where('referencia', 'like', "%{$buscar}%")
                  ->orWhereHas('cliente', function ($qc) use ($buscar) {
                      $qc->where('nombre', 'like', "%{$buscar}%")
                         ->orWhere('curp', 'like', "%{$buscar}%");
                  });
            });
        }

        $prestamos = $query->orderBy('created_at', 'desc')->paginate(12)->withQueryString();

        $distribuidoresFiltro = $user->misDistribuidorasQuery()->orderBy('name')->get();

        // Métricas rápidas de los préstamos activos bajo su coordinación
        $statsPrestamos = [
            'total_activos' => Prestamo::where('estado', 'activo')->whereIn('created_by_user_id', $distribuidoresIds)->count(),
            'adeudo_total' => Prestamo::where('estado', 'activo')->whereIn('created_by_user_id', $distribuidoresIds)->sum('adeudo_pendiente'),
            'capital_colocado' => Prestamo::where('estado', 'activo')->whereIn('created_by_user_id', $distribuidoresIds)->sum('monto_prestamo'),
            'distribuidores_con_cartera' => Prestamo::where('estado', 'activo')->whereIn('created_by_user_id', $distribuidoresIds)->distinct('created_by_user_id')->count('created_by_user_id'),
        ];

        return view('coordinador.prestamos', compact('prestamos', 'distribuidoresFiltro', 'statsPrestamos'));
    }

    /**
     * Listado de solicitudes de registro de distribuidora.
     */
    public function index()
    {
        $user = Auth::user();

        $solicitudes = SolicitudDistribuidor::where('sucursal_id', $user->sucursal_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('coordinador.solicitudes.index', compact('solicitudes'));
    }

    /**
     * Formulario para crear una nueva solicitud de distribuidor interna.
     */
    public function create()
    {
        return view('coordinador.solicitudes.create');
    }

    /**
     * Guardar una nueva solicitud de distribuidor interna.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombres' => 'required|string|min:2|max:255',
            'apellidos' => 'required|string|min:2|max:255',
            'telefono' => 'required|string|regex:/^[0-9]{10}$/',
            'fecha_nacimiento' => 'required|date|before:today',
            'curp' => 'required|string|size:18|regex:/^[A-Z]{4}[0-9]{6}[H,M][A-Z]{5}[0-9,A-Z][0-9]$/i',
            'rfc' => 'required|string|min:12|max:13|regex:/^[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}$/i',
            'lugar_nacimiento' => 'nullable|string|max:255',
            'calle' => 'required|string|min:3|max:255',
            'colonia' => 'required|string|min:2|max:255',
            'codigo_postal' => 'required|string|regex:/^[0-9]{5}$/',
            'ciudad' => 'required|string|min:2|max:255',
            'estado_republica' => 'required|string|min:2|max:255',
            'datos_familiares' => 'nullable|array',
            'datos_familiares.*.nombre' => 'required_with:datos_familiares|string|max:255',
            'datos_familiares.*.parentesco' => 'required_with:datos_familiares|string|max:100',
            'datos_familiares.*.contacto' => 'nullable|string|max:100',
            'datos_vehiculos' => 'nullable|string|max:500',
            'datos_casa' => 'required|string|min:5|max:1000',
            'referencias_laborales' => 'nullable|string|max:1000',
        ], [
            'nombres.required' => 'El nombre o nombres de la distribuidora son obligatorios.',
            'nombres.min' => 'El campo nombres debe tener al menos :min caracteres.',
            'nombres.max' => 'El campo nombres no puede superar los :max caracteres.',
            'apellidos.required' => 'Los apellidos de la distribuidora son obligatorios.',
            'apellidos.min' => 'El campo apellidos debe tener al menos :min caracteres.',
            'apellidos.max' => 'El campo apellidos no puede superar los :max caracteres.',
            'telefono.required' => 'El número de teléfono celular es obligatorio.',
            'telefono.regex' => 'El número de teléfono debe contener exactamente 10 dígitos numéricos.',
            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'fecha_nacimiento.date' => 'La fecha de nacimiento no corresponde a una fecha válida.',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a la fecha de hoy.',
            'curp.required' => 'La clave CURP es obligatoria.',
            'curp.size' => 'La CURP debe tener exactamente 18 caracteres.',
            'curp.regex' => 'El formato de la CURP es inválido (ejemplo: ABCD000000HDFRRN01).',
            'rfc.required' => 'El RFC es obligatorio.',
            'rfc.min' => 'El RFC debe contener al menos 12 caracteres.',
            'rfc.max' => 'El RFC no debe superar los 13 caracteres.',
            'rfc.regex' => 'El formato del RFC es inválido (ejemplo: ABCD000000XXX).',
            'calle.required' => 'La calle y número de domicilio son obligatorios.',
            'calle.min' => 'La calle debe tener al menos :min caracteres.',
            'colonia.required' => 'La colonia es obligatoria.',
            'colonia.min' => 'La colonia debe tener al menos :min caracteres.',
            'codigo_postal.required' => 'El código postal es obligatorio.',
            'codigo_postal.regex' => 'El código postal debe contener exactamente 5 dígitos numéricos.',
            'ciudad.required' => 'La ciudad o municipio es obligatorio.',
            'ciudad.min' => 'La ciudad debe tener al menos :min caracteres.',
            'estado_republica.required' => 'El estado de la república es obligatorio.',
            'estado_republica.min' => 'El estado de la república debe tener al menos :min caracteres.',
            'datos_casa.required' => 'La descripción y características de la casa son obligatorias.',
            'datos_casa.min' => 'La descripción de la vivienda debe contener al menos :min caracteres.',
            'datos_casa.max' => 'La descripción de la casa no puede exceder los :max caracteres.',
            'datos_vehiculos.max' => 'Los datos de vehículos no pueden exceder los :max caracteres.',
            'referencias_laborales.max' => 'Las referencias laborales no pueden exceder los :max caracteres.',
            'datos_familiares.*.nombre.required_with' => 'El nombre completo del familiar de referencia es obligatorio.',
            'datos_familiares.*.parentesco.required_with' => 'El parentesco del familiar de referencia es obligatorio.',
        ]);

        SolicitudDistribuidor::create([
            'nombres' => $request->nombres,
            'apellidos' => $request->apellidos,
            'telefono' => $request->telefono,
            'fecha_nacimiento' => $request->fecha_nacimiento,
            'curp' => strtoupper($request->curp),
            'rfc' => strtoupper($request->rfc),
            'lugar_nacimiento' => $request->lugar_nacimiento,
            'calle' => $request->calle,
            'colonia' => $request->colonia,
            'codigo_postal' => $request->codigo_postal,
            'ciudad' => $request->ciudad,
            'estado_republica' => $request->estado_republica,
            'datos_familiares' => $request->datos_familiares,
            'datos_vehiculos' => $request->datos_vehiculos,
            'datos_casa' => $request->datos_casa,
            'referencias_laborales' => $request->referencias_laborales,
            'coordinador_id' => Auth::id(),
            'sucursal_id' => Auth::user()->sucursal_id,
            'estado' => 'en espera de verificacion',
        ]);

        return redirect()->route('coordinador.solicitudes.index')
                         ->with('success', 'Solicitud de distribuidora creada exitosamente y enviada a verificación.');
    }

    /**
     * Ver el detalle de una solicitud de distribuidora.
     */
    public function show(SolicitudDistribuidor $solicitud)
    {
        if ($solicitud->sucursal_id !== Auth::user()->sucursal_id) {
            abort(403, 'No tienes permiso para ver esta solicitud.');
        }

        $solicitud->load(['coordinador', 'sucursal', 'verificador']);

        return view('coordinador.solicitudes.show', compact('solicitud'));
    }

    /**
     * Enviar a verificación
     */
    public function enviarAVerificacion(SolicitudDistribuidor $solicitud)
    {
        if ($solicitud->sucursal_id !== Auth::user()->sucursal_id) {
            abort(403, 'Acceso denegado.');
        }

        $solicitud->update([
            'estado' => 'en espera de verificacion'
        ]);

        return redirect()->route('coordinador.solicitudes.index')
                         ->with('success', 'La solicitud ha sido enviada al Verificador para la evaluación presencial.');
    }

    /**
     * Registrar solicitud de incremento de límite de crédito para un distribuidor
     */
    public function solicitarCredito(Request $request, User $distribuidor)
    {
        $coordinador = Auth::user();

        // Validaciones de seguridad
        if (!$distribuidor->esDistribuidor() || ($distribuidor->coordinador_id && $distribuidor->coordinador_id !== $coordinador->id)) {
            abort(403, 'Acceso denegado: El distribuidor no pertenece a tu coordinación.');
        }

        $request->validate([
            'limite_nuevo' => 'required|numeric|min:' . ($distribuidor->limite_credito + 0.01),
            'motivo' => 'required|string|min:5|max:500',
        ], [
            'limite_nuevo.required' => 'Debes ingresar el nuevo límite de crédito solicitado.',
            'limite_nuevo.numeric' => 'El nuevo límite de crédito debe ser un número válido.',
            'limite_nuevo.min' => 'El nuevo límite ($:value) debe ser mayor al límite de crédito actual ($' . number_format($distribuidor->limite_credito, 2) . ').',
            'motivo.required' => 'El motivo o justificación del aumento de crédito es obligatorio.',
            'motivo.min' => 'El motivo debe contener al menos :min caracteres.',
            'motivo.max' => 'El motivo no puede exceder los :max caracteres.',
        ]);

        SolicitudCredito::create([
            'distribuidor_id' => $distribuidor->id,
            'coordinador_id' => $coordinador->id,
            'limite_actual' => $distribuidor->limite_credito,
            'limite_nuevo' => $request->limite_nuevo,
            'motivo' => $request->motivo,
            'estado' => 'pendiente',
        ]);

        return redirect()->route('coordinador.dashboard')
            ->with('success', 'Solicitud de incremento de crédito enviada al Gerente de Sucursal para su autorización.');
    }

    /**
     * Solicitar transferencia de distribuidora a otro coordinador
     */
    public function solicitarTransferencia(Request $request, User $distribuidor)
    {
        $emisor = Auth::user();

        if (!$distribuidor->esDistribuidor()) {
            abort(403, 'El usuario seleccionado no es un distribuidor.');
        }

        $request->validate([
            'coordinador_receptor_id' => 'required|exists:users,id|different:coordinador_emisor_id',
            'motivo' => 'required|string|min:5|max:600',
        ], [
            'coordinador_receptor_id.required' => 'Debes seleccionar el coordinador de destino.',
            'coordinador_receptor_id.exists' => 'El coordinador seleccionado no es válido o no existe.',
            'coordinador_receptor_id.different' => 'No puedes transferirte la distribuidora a ti mismo.',
            'motivo.required' => 'El motivo del traspaso de la distribuidora es obligatorio.',
            'motivo.min' => 'El motivo del traspaso debe contener al menos :min caracteres.',
            'motivo.max' => 'El motivo del traspaso no puede superar los :max caracteres.',
        ]);

        $receptor = User::with('sucursal')->findOrFail($request->coordinador_receptor_id);
        if (!$receptor->esCoordinador()) {
            return back()->withErrors(['coordinador_receptor_id' => 'El usuario seleccionado no tiene rol de Coordinador.']);
        }

        // Verificar que no haya una solicitud pendiente para esta misma distribuidora
        $existente = SolicitudTransferencia::where('distribuidor_id', $distribuidor->id)
            ->whereIn('estado', ['pendiente_coordinador', 'pendiente_gerente'])
            ->first();

        if ($existente) {
            return back()->with('error', 'Ya existe una solicitud de transferencia en trámite para esta distribuidora.');
        }

        $sucursalOrigenId = $distribuidor->sucursal_id ?? $emisor->sucursal_id;
        $sucursalDestinoId = $receptor->sucursal_id ?? $sucursalOrigenId;

        $transferencia = SolicitudTransferencia::create([
            'distribuidor_id' => $distribuidor->id,
            'coordinador_emisor_id' => $emisor->id,
            'coordinador_receptor_id' => $receptor->id,
            'sucursal_origen_id' => $sucursalOrigenId,
            'sucursal_destino_id' => $sucursalDestinoId,
            'motivo' => $request->motivo,
            'estado' => 'pendiente_coordinador',
        ]);

        // Enviar Notificación al Coordinador Receptor con enlace directo de revisión
        NotificacionCajero::enviar(
            $receptor->id,
            'transferencia_distribuidora',
            'Nueva Solicitud de Transferencia de Distribuidora',
            "El coordinador {$emisor->name} te propone transferir la distribuidora {$distribuidor->name}. Haz clic para revisar su información y préstamos.",
            [
                'transferencia_id' => $transferencia->id,
                'url' => route('coordinador.transferencias.revisar', $transferencia),
                'entidad_tipo' => 'solicitud_transferencia',
                'entidad_id' => $transferencia->id,
            ]
        );

        return redirect()->route('coordinador.dashboard')
            ->with('success', "Solicitud de transferencia enviada exitosamente a {$receptor->name}. Se le ha notificado para su revisión.");
    }

    /**
     * Vista de revisión de la transferencia para el Coordinador Receptor
     */
    public function revisarTransferencia(SolicitudTransferencia $transferencia)
    {
        $user = Auth::user();

        // Validar que el usuario sea el receptor o el emisor
        if ($user->id !== $transferencia->coordinador_receptor_id && $user->id !== $transferencia->coordinador_emisor_id) {
            abort(403, 'No tienes autorización para revisar esta transferencia.');
        }

        $transferencia->load([
            'distribuidor.sucursal',
            'coordinadorEmisor.sucursal',
            'coordinadorReceptor.sucursal',
            'sucursalOrigen',
            'sucursalDestino',
            'gerente',
        ]);

        $distribuidora = $transferencia->distribuidor;

        // Cargar préstamos activos de la distribuidora y clientes
        $prestamosActivos = Prestamo::with(['cliente', 'productoVale'])
            ->where('created_by_user_id', $distribuidora->id)
            ->where('estado', 'activo')
            ->orderBy('created_at', 'desc')
            ->get();

        $esReceptor = ($user->id === $transferencia->coordinador_receptor_id);

        return view('coordinador.transferencias.show', compact('transferencia', 'distribuidora', 'prestamosActivos', 'esReceptor'));
    }

    /**
     * Decisión del Coordinador Receptor (Aceptar o Rechazar)
     */
    public function decidirTransferencia(Request $request, SolicitudTransferencia $transferencia)
    {
        $receptor = Auth::user();

        if ($receptor->id !== $transferencia->coordinador_receptor_id) {
            abort(403, 'Solo el coordinador receptor puede responder a esta solicitud.');
        }

        if ($transferencia->estado !== 'pendiente_coordinador') {
            return back()->with('error', 'Esta solicitud ya ha sido procesada previamente.');
        }

        $request->validate([
            'accion' => 'required|in:aceptar,rechazar',
            'observaciones' => 'nullable|string|max:600',
        ], [
            'accion.required' => 'Debes seleccionar una acción (aceptar o rechazar).',
            'accion.in' => 'La acción seleccionada no es válida.',
            'observaciones.max' => 'Las observaciones no pueden superar los :max caracteres.',
        ]);

        $distribuidora = $transferencia->distribuidor;
        $emisor = $transferencia->coordinadorEmisor;

        if ($request->accion === 'rechazar') {
            $transferencia->update([
                'estado' => 'rechazada_coordinador',
                'observaciones_coordinador_receptor' => $request->observaciones,
                'resolved_at' => now(),
            ]);

            // Notificar al emisor del rechazo
            NotificacionCajero::enviar(
                $emisor->id,
                'transferencia_rechazada',
                'Transferencia de Distribuidora Rechazada',
                "El coordinador {$receptor->name} ha rechazado la transferencia de {$distribuidora->name}." . ($request->observaciones ? " Motivo: \"{$request->observaciones}\"" : ""),
                ['transferencia_id' => $transferencia->id]
            );

            return redirect()->route('coordinador.dashboard')
                ->with('info', "Has rechazado la solicitud de transferencia de la distribuidora {$distribuidora->name}.");
        }

        // Si acepta: Pasa a estado 'pendiente_gerente' y notifica al Gerente de la Sucursal Receptora
        $transferencia->update([
            'estado' => 'pendiente_gerente',
            'observaciones_coordinador_receptor' => $request->observaciones,
        ]);

        // Buscar el/los Gerentes de la sucursal receptora
        $gerentesSucursal = User::whereHas('rol', fn($q) => $q->whereIn('nombre', ['Gerente de Sucursal', 'gerente de sucursal', 'Gerente Sucursal']))
            ->where('sucursal_id', $transferencia->sucursal_destino_id)
            ->where('activo', true)
            ->get();

        foreach ($gerentesSucursal as $gs) {
            NotificacionCajero::enviar(
                $gs->id,
                'transferencia_requiere_autorizacion',
                'Solicitud de Traspaso de Distribuidora Pendiente de Aprobación',
                "El coordinador {$receptor->name} aceptó el traspaso de {$distribuidora->name}. Se requiere tu autorización para formalizar el cambio de sucursal/coordinación.",
                [
                    'transferencia_id' => $transferencia->id,
                    'url' => route('gerente-sucursal.transferencias.revisar', $transferencia),
                    'entidad_tipo' => 'solicitud_transferencia',
                    'entidad_id' => $transferencia->id,
                ]
            );
        }

        // Notificar también a Gerencia General / Dirección Corporativa
        $gerentesGenerales = User::whereHas('rol', fn($q) => $q->whereIn('nombre', ['Gerente General', 'gerente general', 'Administrador', 'administrador']))
            ->where('activo', true)
            ->get();

        foreach ($gerentesGenerales as $gg) {
            NotificacionCajero::enviar(
                $gg->id,
                'transferencia_requiere_autorizacion',
                'Solicitud de Traspaso de Distribuidora (Corporativo)',
                "El coordinador {$receptor->name} aceptó el traspaso de {$distribuidora->name} hacia la sucursal {$transferencia->sucursalDestino?->nombre}. Se requiere autorización gerencial para formalizar el traspaso.",
                [
                    'transferencia_id' => $transferencia->id,
                    'url' => route('gerente-sucursal.transferencias.revisar', $transferencia),
                    'entidad_tipo' => 'solicitud_transferencia',
                    'entidad_id' => $transferencia->id,
                ]
            );
        }

        // Notificar al Emisor del avance
        NotificacionCajero::enviar(
            $emisor->id,
            'transferencia_aceptada_coordinador',
            'Transferencia Aceptada por Coordinador - Pendiente de Gerencia',
            "El coordinador {$receptor->name} ha aceptado la transferencia de {$distribuidora->name}. La solicitud fue enviada a la Gerencia para su visto bueno.",
            ['transferencia_id' => $transferencia->id]
        );

        return redirect()->route('coordinador.dashboard')
            ->with('success', "Has aceptado la transferencia de {$distribuidora->name}. La solicitud fue turnada a la Gerencia para su autorización definitiva.");
    }

    /**
     * Pre-aprobar o rechazar conciliación manual enviada por un cajero (Paso 1).
     */
    public function decidirConciliacion(Request $request, \App\Models\Conciliacion $conciliacion)
    {
        $request->validate([
            'accion' => 'required|in:aceptar,rechazar',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $coordinador = Auth::user();

        if ($conciliacion->estado !== 'pendiente_coordinador') {
            return back()->with('error', 'Esta conciliación ya ha sido procesada o no se encuentra en estado pendiente de revisión por el coordinador.');
        }

        if ($request->accion === 'rechazar') {
            $conciliacion->update([
                'estado' => 'rechazada',
                'notas_resolucion' => $request->observaciones ?? 'Rechazada por el Coordinador',
            ]);

            // Notificar al cajero solicitante
            if ($conciliacion->solicitante_id) {
                NotificacionCajero::enviar(
                    $conciliacion->solicitante_id,
                    'conciliacion_rechazada',
                    'Conciliación Manual Rechazada por Coordinador',
                    "El coordinador {$coordinador->name} ha rechazado tu solicitud de conciliación (Ref: {$conciliacion->referencia_conciliacion}). Nota: " . ($request->observaciones ?? 'Sin observaciones.')
                );
            }

            return redirect()->route('coordinador.dashboard')
                ->with('info', 'La conciliación manual ha sido rechazada y se notificó al cajero.');
        }

        // Pre-aprobar conciliación -> Pasa a la Gerencia
        $conciliacion->update([
            'estado' => 'pendiente_gerencia',
            'notas_resolucion' => 'Pre-aprobada por Coordinador ' . $coordinador->name . '. ' . ($request->observaciones ?? ''),
        ]);

        // Notificar a Gerentes de Sucursal
        $gerentesSucursal = User::whereHas('rol', fn($q) => $q->whereIn('nombre', ['Gerente de Sucursal', 'gerente de sucursal']))
            ->where('sucursal_id', $coordinador->sucursal_id)
            ->where('activo', true)
            ->get();

        foreach ($gerentesSucursal as $gs) {
            NotificacionCajero::enviar(
                $gs->id,
                'conciliacion_pendiente_gerencia',
                'Conciliación Pre-Aprobada por Coordinación',
                "El coordinador {$coordinador->name} pre-aprobó la conciliación de la cajera " . ($conciliacion->solicitante?->name ?? 'Cajero') . " por \${$conciliacion->monto_corregido}. Se requiere tu autorización final.",
                ['conciliacion_id' => $conciliacion->id]
            );
        }

        // Notificar a Gerentes Generales
        $gerentesGenerales = User::whereHas('rol', fn($q) => $q->whereIn('nombre', ['Gerente General', 'gerente general', 'Administrador', 'administrador']))
            ->where('activo', true)
            ->get();

        foreach ($gerentesGenerales as $gg) {
            NotificacionCajero::enviar(
                $gg->id,
                'conciliacion_pendiente_gerencia',
                'Conciliación Pre-Aprobada por Coordinación (Corporativo)',
                "El coordinador {$coordinador->name} pre-aprobó una conciliación manual por \${$conciliacion->monto_corregido}. Pendiente de autorización gerencial.",
                ['conciliacion_id' => $conciliacion->id]
            );
        }

        return redirect()->route('coordinador.dashboard')
            ->with('success', 'Conciliación pre-aprobada exitosamente. Se ha turnado a la Gerencia de Sucursal y Gerencia General para su autorización final.');
    }
}
