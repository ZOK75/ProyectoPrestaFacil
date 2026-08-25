<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Models\Cliente;
use App\Models\SolicitudCliente;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ClienteController extends Controller
{
    /**
     * Obtiene el usuario operador actual.
     */
    private function operador(): ?User
    {
        if (Auth::check()) {
            return Auth::user()->load('rol', 'sucursal');
        }

        return User::whereHas('rol', fn ($q) => $q->whereIn('nombre', ['Distribuidor', 'Distribuidora']))
            ->first() ?? User::first();
    }

    /**
     * Verifica que el usuario tenga acceso a clientes.
     */
    private function verificarAcceso(): ?\Illuminate\Http\RedirectResponse
    {
        $operador = $this->operador();
        if (!$operador) {
            return redirect()->route('login');
        }

        if ($operador->esGerenteGeneral() || $operador->esGerenteSucursal()) {
            $ruta = $operador->esGerenteGeneral() ? 'gerente-general.dashboard' : 'gerente-sucursal.dashboard';
            return redirect()->route($ruta)
                ->with('error', 'Acceso denegado: El rol gerencial no tiene permisos para acceder al módulo de clientes.');
        }

        return null;
    }

    /**
     * Listado de clientes con filtros por búsqueda y estado.
     */
    public function index(Request $request)
    {
        if ($redirect = $this->verificarAcceso()) {
            return $redirect;
        }

        $operador = $this->operador();
        $query = Cliente::with(['createdBy', 'desactivadoPor', 'solicitudPendiente']);

        // Si es distribuidor, filtra los clientes creados por él
        if ($operador->esDistribuidor()) {
            $query->where('created_by_user_id', $operador->id);
        }

        // Filtro por texto (Nombre, CURP, RFC)
        if ($request->filled('buscar')) {
            $buscar = $request->input('buscar');
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('curp', 'like', "%{$buscar}%")
                  ->orWhere('rfc', 'like', "%{$buscar}%");
            });
        }

        // Filtro por estado
        if ($request->filled('estado')) {
            if ($request->input('estado') === 'activo') {
                $query->where('activo', true);
            } elseif ($request->input('estado') === 'inactivo') {
                $query->where('activo', false);
            }
        }

        $clientes = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        $baseStats = $operador->esDistribuidor() 
            ? Cliente::where('created_by_user_id', $operador->id) 
            : Cliente::query();

        $stats = [
            'total' => (clone $baseStats)->count(),
            'activos' => (clone $baseStats)->where('activo', true)->count(),
            'inactivos' => (clone $baseStats)->where('activo', false)->count(),
        ];

        // Otras distribuidoras para modal de traspaso de cliente
        $otrasDistribuidoras = User::whereHas('rol', fn($q) => $q->whereIn('nombre', ['Distribuidor', 'Distribuidora', 'distribuidor', 'distribuidora']))
            ->where('id', '!=', $operador->id)
            ->where('activo', true)
            ->orderBy('name')
            ->get();

        // Solicitudes de traspaso de cliente recibidas por esta distribuidora (Paso 1)
        $traspasosClientesRecibidos = \App\Models\SolicitudTraspasoCliente::where('distribuidor_receptor_id', $operador->id)
            ->where('estado', 'pendiente_distribuidor_receptor')
            ->with(['cliente', 'distribuidorEmisor'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('clientes.index', compact('clientes', 'stats', 'operador', 'otrasDistribuidoras', 'traspasosClientesRecibidos'));
    }

    /**
     * Formulario de registro de cliente.
     */
    public function create()
    {
        if ($redirect = $this->verificarAcceso()) {
            return $redirect;
        }

        $operador = $this->operador();

        if ($operador->esAdministrador()) {
            return redirect()->route('clientes.index')
                ->with('error', 'Acceso denegado: El rol de Administrador cuenta con permisos de solo lectura (auditoría) y no puede registrar clientes.');
        }

        return view('clientes.create', compact('operador'));
    }

    /**
     * Registrar un nuevo cliente con la carga de 2 archivos PDF independientes.
     */
    public function store(StoreClienteRequest $request)
    {
        if ($redirect = $this->verificarAcceso()) {
            return $redirect;
        }

        $operador = $this->operador();

        if ($operador->esAdministrador()) {
            return redirect()->route('clientes.index')
                ->with('error', 'Acceso denegado: El rol de Administrador cuenta con permisos de solo lectura (auditoría) y no puede registrar clientes.');
        }

        $data = $request->validated();

        // Almacenar el archivo INE PDF
        if ($request->hasFile('pdf_ine')) {
            $pathIne = $request->file('pdf_ine')->store('expedientes_clientes/ine', 'public');
            $data['path_ine_pdf'] = $pathIne;
        }

        // Almacenar el archivo Comprobante de Domicilio PDF
        if ($request->hasFile('pdf_comprobante')) {
            $pathComprobante = $request->file('pdf_comprobante')->store('expedientes_clientes/comprobantes', 'public');
            $data['path_comprobante_pdf'] = $pathComprobante;
        }

        $data['created_by_user_id'] = Auth::id() ?? $operador?->id;
        $data['activo'] = true;
        $data['desactivado_at'] = null;

        $cliente = Cliente::create($data);

        AuditService::registrar(
            'CREACION_CLIENTE',
            "Cliente '{$cliente->nombre}' (CURP: {$cliente->curp}) registrado exitosamente por " . ($operador?->name ?? 'Usuario'),
            [
                'entidad_tipo' => 'clientes',
                'entidad_id' => $cliente->id,
                'user_id' => $data['created_by_user_id'],
                'user_rol' => $operador?->rol?->nombre,
                'sucursal_id' => $operador?->sucursal_id,
                'despues' => $cliente->toArray(),
            ]
        );

        return redirect()->route('clientes.index')
            ->with('success', "El cliente '{$cliente->nombre}' (CURP: {$cliente->curp}) fue registrado exitosamente.");
    }

    /**
     * Ficha técnica del cliente y visualización/descarga de expedientes PDF.
     */
    public function show(Cliente $cliente)
    {
        if ($redirect = $this->verificarAcceso()) {
            return $redirect;
        }

        $cliente->load(['createdBy', 'desactivadoPor', 'solicitudes.aprobadoPor', 'solicitudes.distribuidor']);
        $operador = $this->operador();

        return view('clientes.show', compact('cliente', 'operador'));
    }

    /**
     * Formulario de edición de cliente.
     */
    public function edit(Cliente $cliente)
    {
        if ($redirect = $this->verificarAcceso()) {
            return $redirect;
        }

        $operador = $this->operador();

        if ($operador->esAdministrador()) {
            return redirect()->route('clientes.index')
                ->with('error', 'Acceso denegado: El rol de Administrador cuenta con permisos de solo lectura (auditoría) y no puede editar clientes.');
        }

        if (!$cliente->activo) {
            return redirect()->route('clientes.index')
                ->with('info', "El cliente '{$cliente->nombre}' está desactivado y no puede ser modificado.");
        }

        if ($cliente->tieneSolicitudPendiente()) {
            return redirect()->route('clientes.index')
                ->with('warning', "El cliente '{$cliente->nombre}' ya tiene una solicitud pendiente de autorización.");
        }

        $cliente->load(['createdBy', 'desactivadoPor']);

        return view('clientes.edit', compact('cliente', 'operador'));
    }

    /**
     * Actualizar datos del cliente.
     */
    public function update(UpdateClienteRequest $request, Cliente $cliente)
    {
        if ($redirect = $this->verificarAcceso()) {
            return $redirect;
        }

        $operador = $this->operador();

        if ($operador->esAdministrador()) {
            return redirect()->route('clientes.index')
                ->with('error', 'Acceso denegado: El rol de Administrador cuenta con permisos de solo lectura (auditoría) y no puede modificar clientes.');
        }

        if (!$cliente->activo) {
            return redirect()->route('clientes.index')
                ->with('info', "El cliente '{$cliente->nombre}' está desactivado y no puede ser modificado.");
        }

        if ($cliente->tieneSolicitudPendiente()) {
            return redirect()->route('clientes.index')
                ->with('warning', "El cliente '{$cliente->nombre}' ya tiene una solicitud pendiente de autorización.");
        }

        $data = $request->validated();

        // FLUJO DISTRIBUIDOR: Envía solicitud de actualización
        if ($operador->esDistribuidor()) {
            $pdfIneNuevo = null;
            $pdfComprobanteNuevo = null;

            if ($request->hasFile('pdf_ine')) {
                $pdfIneNuevo = $request->file('pdf_ine')->store('expedientes_clientes/solicitudes_temp', 'public');
            }

            if ($request->hasFile('pdf_comprobante')) {
                $pdfComprobanteNuevo = $request->file('pdf_comprobante')->store('expedientes_clientes/solicitudes_temp', 'public');
            }

            $solicitud = SolicitudCliente::create([
                'tipo' => 'actualizacion',
                'estado' => 'pendiente',
                'cliente_id' => $cliente->id,
                'distribuidor_id' => $operador->id,
                'sucursal_id' => $operador->sucursal_id,
                'datos_originales' => $cliente->only([
                    'nombre', 'curp', 'rfc', 'fecha_nacimiento', 'lugar_nacimiento',
                    'calle', 'colonia', 'codigo_postal', 'ciudad', 'estado',
                    'path_ine_pdf', 'path_comprobante_pdf'
                ]),
                'datos_solicitados' => $data,
                'motivo' => $request->input('motivo_solicitud') ?? 'Actualización de datos generales del cliente por Distribuidor.',
                'pdf_ine_nuevo' => $pdfIneNuevo,
                'pdf_comprobante_nuevo' => $pdfComprobanteNuevo,
            ]);

            AuditService::registrar(
                'SOLICITUD_ACTUALIZACION_CLIENTE',
                "Solicitud de actualización enviada para cliente '{$cliente->nombre}' por " . ($operador?->name ?? 'Distribuidor'),
                [
                    'entidad_tipo' => 'solicitudes_cliente',
                    'entidad_id' => $solicitud->id,
                    'user_id' => $operador->id,
                    'user_rol' => $operador?->rol?->nombre,
                    'sucursal_id' => $operador?->sucursal_id,
                    'antes' => $solicitud->datos_originales,
                    'despues' => $solicitud->datos_solicitados,
                ]
            );

            return redirect()->route('clientes.index')
                ->with('success', "Se ha enviado la Solicitud de Actualización para '{$cliente->nombre}'.");
        }

        if ($request->hasFile('pdf_ine')) {
            if ($cliente->path_ine_pdf && Storage::disk('public')->exists($cliente->path_ine_pdf)) {
                Storage::disk('public')->delete($cliente->path_ine_pdf);
            }
            $data['path_ine_pdf'] = $request->file('pdf_ine')->store('expedientes_clientes/ine', 'public');
        }

        if ($request->hasFile('pdf_comprobante')) {
            if ($cliente->path_comprobante_pdf && Storage::disk('public')->exists($cliente->path_comprobante_pdf)) {
                Storage::disk('public')->delete($cliente->path_comprobante_pdf);
            }
            $data['path_comprobante_pdf'] = $request->file('pdf_comprobante')->store('expedientes_clientes/comprobantes', 'public');
        }

        $datosAntes = $cliente->toArray();
        $cliente->update($data);

        AuditService::registrar(
            'ACTUALIZACION_CLIENTE',
            "Datos del cliente '{$cliente->nombre}' actualizados por " . ($operador?->name ?? 'Usuario'),
            [
                'entidad_tipo' => 'clientes',
                'entidad_id' => $cliente->id,
                'user_id' => Auth::id() ?? $operador?->id,
                'user_rol' => $operador?->rol?->nombre,
                'sucursal_id' => $operador?->sucursal_id,
                'antes' => $datosAntes,
                'despues' => $cliente->fresh()->toArray(),
            ]
        );

        return redirect()->route('clientes.index')
            ->with('success', "Los datos del cliente '{$cliente->nombre}' fueron actualizados correctamente.");
    }

    /**
     * Desactivar cliente.
     */
    public function destroy(Request $request, Cliente $cliente)
    {
        if ($redirect = $this->verificarAcceso()) {
            return $redirect;
        }

        $operador = $this->operador();

        if ($operador->esAdministrador()) {
            return redirect()->route('clientes.index')
                ->with('error', 'Acceso denegado: El rol de Administrador cuenta con permisos de solo lectura (auditoría) y no puede desactivar clientes.');
        }

        if (!$cliente->activo) {
            return redirect()->route('clientes.index')
                ->with('info', "El cliente '{$cliente->nombre}' ya se encuentra desactivado.");
        }

        if ($cliente->tieneSolicitudPendiente()) {
            return redirect()->route('clientes.index')
                ->with('warning', "El cliente '{$cliente->nombre}' ya tiene una solicitud pendiente de autorización.");
        }

        // FLUJO DISTRIBUIDOR: Envía solicitud de desactivación
        if ($operador->esDistribuidor()) {
            $solicitud = SolicitudCliente::create([
                'tipo' => 'desactivacion',
                'estado' => 'pendiente',
                'cliente_id' => $cliente->id,
                'distribuidor_id' => $operador->id,
                'sucursal_id' => $operador->sucursal_id,
                'datos_originales' => $cliente->toArray(),
                'motivo' => $request->input('motivo_desactivacion') ?? 'Solicitud de baja/desactivación iniciada por Distribuidor.',
            ]);

            AuditService::registrar(
                'SOLICITUD_DESACTIVACION_CLIENTE',
                "Solicitud de desactivación enviada para cliente '{$cliente->nombre}' por " . ($operador?->name ?? 'Distribuidor'),
                [
                    'entidad_tipo' => 'solicitudes_cliente',
                    'entidad_id' => $solicitud->id,
                    'user_id' => $operador->id,
                    'user_rol' => $operador?->rol?->nombre,
                    'sucursal_id' => $operador?->sucursal_id,
                ]
            );

            return redirect()->route('clientes.index')
                ->with('success', "Se ha enviado la Solicitud de Desactivación para '{$cliente->nombre}'.");
        }

        // Desactivación inmediata
        $datosAntes = $cliente->toArray();
        $cliente->update([
            'activo' => false,
            'desactivado_at' => now(),
            'desactivado_by_user_id' => Auth::id() ?? $operador?->id,
        ]);

        AuditService::registrar(
            'DESACTIVACION_CLIENTE',
            "Cliente '{$cliente->nombre}' (CURP: {$cliente->curp}) desactivado por " . ($operador?->name ?? 'Usuario'),
            [
                'entidad_tipo' => 'clientes',
                'entidad_id' => $cliente->id,
                'user_id' => Auth::id() ?? $operador?->id,
                'user_rol' => $operador?->rol?->nombre,
                'sucursal_id' => $operador?->sucursal_id,
                'antes' => $datosAntes,
                'despues' => $cliente->fresh()->toArray(),
            ]
        );

        return redirect()->route('clientes.index')
            ->with('success', "El cliente '{$cliente->nombre}' (CURP: {$cliente->curp}) fue desactivado correctamente.");
    }

    /**
     * Iniciar la solicitud de traspaso de un cliente a otra Distribuidora.
     * Validación CRÍTICA: El cliente NO debe tener ningún préstamo o producto activo.
     */
    public function solicitarTraspaso(Request $request, Cliente $cliente)
    {
        $distribuidorEmisor = Auth::user();
        if (!$distribuidorEmisor->esDistribuidor()) {
            return back()->with('error', 'Únicamente los Distribuidores pueden solicitar el traspaso de sus clientes.');
        }

        if ($cliente->created_by_user_id !== $distribuidorEmisor->id) {
            return back()->with('error', 'Este cliente no pertenece a tu cartera asignada.');
        }

        // VALIDACIÓN CRÍTICA: Verificar que NO tenga productos o préstamos activos
        if ($cliente->tieneProductosActivos()) {
            return back()->with('error', "No se puede transferir al cliente '{$cliente->nombre}' porque cuenta con vales/préstamos activos o saldo pendiente. Debe liquidar sus adeudos antes de realizar el traspaso.");
        }

        $request->validate([
            'distribuidor_receptor_id' => 'required|exists:users,id|different:' . $distribuidorEmisor->id,
            'motivo' => 'required|string|max:1000',
        ]);

        $distribuidorReceptor = User::findOrFail($request->distribuidor_receptor_id);

        if (!$distribuidorReceptor->esDistribuidor()) {
            return back()->with('error', 'El usuario receptor seleccionado no es una Distribuidora activa.');
        }

        // Verificar si ya existe una solicitud en proceso para este cliente
        $existente = \App\Models\SolicitudTraspasoCliente::where('cliente_id', $cliente->id)
            ->whereIn('estado', ['pendiente_distribuidor_receptor', 'pendiente_coordinador'])
            ->exists();

        if ($existente) {
            return back()->with('error', "El cliente '{$cliente->nombre}' ya tiene un traspaso en proceso.");
        }

        $traspaso = \App\Models\SolicitudTraspasoCliente::create([
            'cliente_id' => $cliente->id,
            'distribuidor_emisor_id' => $distribuidorEmisor->id,
            'distribuidor_receptor_id' => $distribuidorReceptor->id,
            'coordinador_id' => $distribuidorReceptor->coordinador_id ?? $distribuidorEmisor->coordinador_id,
            'motivo' => $request->motivo,
            'estado' => 'pendiente_distribuidor_receptor',
        ]);

        // Notificar a la Distribuidora Destino
        \App\Models\NotificacionCajero::enviar(
            $distribuidorReceptor->id,
            'solicitud_traspaso_cliente',
            'Solicitud de Traspaso de Cliente Recibida',
            "La distribuidora {$distribuidorEmisor->name} te solicita ceder al cliente '{$cliente->nombre}'. Revisa la solicitud para aceptar o rechazar.",
            ['traspaso_id' => $traspaso->id]
        );

        AuditService::registrar(
            'SOLICITUD_TRASPASO_CLIENTE',
            "Solicitud de traspaso del cliente '{$cliente->nombre}' iniciada por {$distribuidorEmisor->name} hacia {$distribuidorReceptor->name}",
            [
                'entidad_tipo' => 'solicitudes_traspaso_cliente',
                'entidad_id' => $traspaso->id,
                'user_id' => $distribuidorEmisor->id,
                'user_rol' => $distribuidorEmisor->rol?->nombre,
                'sucursal_id' => $distribuidorEmisor->sucursal_id,
            ]
        );

        return back()->with('success', "Se ha enviado la solicitud de traspaso del cliente '{$cliente->nombre}' a la distribuidora {$distribuidorReceptor->name}.");
    }

    /**
     * Decisión de la Distribuidora Receptora sobre el traspaso del cliente (Paso 1).
     */
    public function decidirTraspasoReceptor(Request $request, \App\Models\SolicitudTraspasoCliente $traspaso)
    {
        $distribuidorReceptor = Auth::user();
        if ($distribuidorReceptor->id !== $traspaso->distribuidor_receptor_id) {
            return back()->with('error', 'No estás autorizado para dictaminar sobre este traspaso.');
        }

        if ($traspaso->estado !== 'pendiente_distribuidor_receptor') {
            return back()->with('error', 'Este traspaso ya no se encuentra pendiente de tu dictamen.');
        }

        $request->validate([
            'accion' => 'required|in:aceptar,rechazar',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        $cliente = $traspaso->cliente;
        $distribuidorEmisor = $traspaso->distribuidorEmisor;

        if ($request->accion === 'rechazar') {
            $traspaso->update([
                'estado' => 'rechazada_distribuidor_receptor',
                'observaciones_distribuidor_receptor' => $request->observaciones,
                'resolved_at' => now(),
            ]);

            // Notificar a la distribuidora emisora
            \App\Models\NotificacionCajero::enviar(
                $distribuidorEmisor->id,
                'alerta',
                'Traspaso de Cliente Rechazado por Distribuidora',
                "La distribuidora {$distribuidorReceptor->name} ha rechazado el traspaso del cliente '{$cliente->nombre}'." . ($request->observaciones ? " Motivo: {$request->observaciones}" : "")
            );

            AuditService::registrar(
                'RECHAZO_TRASPASO_CLIENTE',
                "Traspaso del cliente '{$cliente->nombre}' rechazado por {$distribuidorReceptor->name}",
                [
                    'entidad_tipo' => 'solicitudes_traspaso_cliente',
                    'entidad_id' => $traspaso->id,
                    'user_id' => $distribuidorReceptor->id,
                    'user_rol' => $distribuidorReceptor->rol?->nombre,
                    'sucursal_id' => $distribuidorReceptor->sucursal_id,
                ]
            );

            return back()->with('info', "Has rechazado el traspaso del cliente '{$cliente->nombre}'.");
        }

        // ACEPTADO por Distribuidora Receptor -> Pasa a revisión del Coordinador (Paso 2)
        $coordinadorEval = $distribuidorReceptor->coordinador_id ?? $distribuidorEmisor->coordinador_id;

        $traspaso->update([
            'estado' => 'pendiente_coordinador',
            'coordinador_id' => $coordinadorEval,
            'observaciones_distribuidor_receptor' => $request->observaciones,
        ]);

        // Notificar al emisor
        \App\Models\NotificacionCajero::enviar(
            $distribuidorEmisor->id,
            'informativa',
            'Traspaso de Cliente Aceptado por Distribuidora Destino',
            "La distribuidora {$distribuidorReceptor->name} ha aceptado el traspaso de '{$cliente->nombre}'. La solicitud ha pasado al Coordinador para la aprobación final."
        );

        // Notificar al Coordinador
        if ($coordinadorEval) {
            \App\Models\NotificacionCajero::enviar(
                $coordinadorEval,
                'solicitud_traspaso_cliente',
                'Aprobación de Traspaso de Cliente Requerida',
                "Las distribuidoras {$distribuidorEmisor->name} y {$distribuidorReceptor->name} acordaron el traspaso del cliente '{$cliente->nombre}'. Requiere tu visto bueno final."
            );
        }

        AuditService::registrar(
            'ACEPTACION_TRASPASO_CLIENTE',
            "Traspaso del cliente '{$cliente->nombre}' aceptado por {$distribuidorReceptor->name} (Turnado a Coordinación)",
            [
                'entidad_tipo' => 'solicitudes_traspaso_cliente',
                'entidad_id' => $traspaso->id,
                'user_id' => $distribuidorReceptor->id,
                'user_rol' => $distribuidorReceptor->rol?->nombre,
                'sucursal_id' => $distribuidorReceptor->sucursal_id,
            ]
        );

        return back()->with('success', "Has aceptado el traspaso del cliente '{$cliente->nombre}'. Fue enviado al Coordinador para su autorización final.");
    }

    /**
     * Decisión final del Coordinador sobre el traspaso del cliente (Paso 2).
     */
    public function decidirTraspasoCoordinador(Request $request, \App\Models\SolicitudTraspasoCliente $traspaso)
    {
        $coordinador = Auth::user();

        if (!$coordinador->esCoordinador() && !$coordinador->esGerenteGeneral() && !$coordinador->esAdministrador()) {
            return back()->with('error', 'No tienes permisos para dictaminar como Coordinador.');
        }

        if ($traspaso->estado !== 'pendiente_coordinador') {
            return back()->with('error', 'Este traspaso ya fue procesado o no está pendiente del Coordinador.');
        }

        $request->validate([
            'accion' => 'required|in:aprobar,rechazar',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        $cliente = $traspaso->cliente;
        $distribuidorEmisor = $traspaso->distribuidorEmisor;
        $distribuidorReceptor = $traspaso->distribuidorReceptor;

        if ($request->accion === 'rechazar') {
            $traspaso->update([
                'estado' => 'rechazada_coordinador',
                'observaciones_coordinador' => $request->observaciones,
                'resolved_at' => now(),
            ]);

            // Notificar a ambas distribuidoras
            \App\Models\NotificacionCajero::enviar(
                $distribuidorEmisor->id,
                'alerta',
                'Traspaso de Cliente Rechazado por Coordinación',
                "El Coordinador ha rechazado el traspaso del cliente '{$cliente->nombre}' a {$distribuidorReceptor->name}." . ($request->observaciones ? " Motivo: {$request->observaciones}" : "")
            );

            \App\Models\NotificacionCajero::enviar(
                $distribuidorReceptor->id,
                'alerta',
                'Traspaso de Cliente Rechazado por Coordinación',
                "El Coordinador no ha autorizado la incorporación del cliente '{$cliente->nombre}' a tu cartera."
            );

            AuditService::registrar(
                'RECHAZO_TRASPASO_COORDINADOR',
                "Traspaso del cliente '{$cliente->nombre}' a {$distribuidorReceptor->name} rechazado por Coordinador {$coordinador->name}",
                [
                    'entidad_tipo' => 'solicitudes_traspaso_cliente',
                    'entidad_id' => $traspaso->id,
                    'user_id' => $coordinador->id,
                    'user_rol' => $coordinador->rol?->nombre,
                    'sucursal_id' => $coordinador->sucursal_id,
                ]
            );

            return back()->with('info', "Has rechazado la transferencia del cliente '{$cliente->nombre}'.");
        }

        // APROBADA: Reasignar el cliente a la Distribuidora Receptora
        \Illuminate\Support\Facades\DB::transaction(function () use ($traspaso, $cliente, $distribuidorReceptor, $request, $coordinador) {
            $traspaso->update([
                'estado' => 'aprobada',
                'observaciones_coordinador' => $request->observaciones,
                'coordinador_id' => $coordinador->id,
                'resolved_at' => now(),
            ]);

            $cliente->update([
                'created_by_user_id' => $distribuidorReceptor->id,
            ]);

            AuditService::registrar(
                'APROBACION_TRASPASO_CLIENTE',
                "Traspaso del cliente '{$cliente->nombre}' a {$distribuidorReceptor->name} aprobado formalmente por {$coordinador->name}",
                [
                    'entidad_tipo' => 'solicitudes_traspaso_cliente',
                    'entidad_id' => $traspaso->id,
                    'user_id' => $coordinador->id,
                    'user_rol' => $coordinador->rol?->nombre,
                    'sucursal_id' => $coordinador->sucursal_id,
                ]
            );
        });

        // Notificaciones finales a todas las partes afectadas
        \App\Models\NotificacionCajero::enviar(
            $distribuidorEmisor->id,
            'informativa',
            'Traspaso de Cliente Finalizado',
            "El Coordinador ha aprobado formalmente la transferencia de tu cliente '{$cliente->nombre}' a la distribuidora {$distribuidorReceptor->name}."
        );

        \App\Models\NotificacionCajero::enviar(
            $distribuidorReceptor->id,
            'informativa',
            'Nuevo Cliente Integrado a tu Cartera',
            "¡Cliente Asignado! El cliente '{$cliente->nombre}' ha sido transferido oficialmente a tu cartera tras la aprobación de la Coordinación."
        );

        return back()->with('success', "Has APROBADO el traspaso del cliente '{$cliente->nombre}' a la distribuidora {$distribuidorReceptor->name} exitosamente.");
    }
}
