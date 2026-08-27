<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GerenteSucursalController extends Controller
{
    /**
     * Dashboard Gerente de Sucursal:
     * - Gestión y supervisión del equipo / personal asignado a su sucursal.
     */
    public function index(Request $request): View
    {
        $operador = Auth::user()->load('rol', 'sucursal');
        $sucursalId = $operador->sucursal_id;

        // Personal asignado a esta sucursal
        $personalSucursal = User::where('sucursal_id', $sucursalId)
            ->with(['rol', 'sucursal'])
            ->orderBy('name')
            ->get();

        $statsEquipo = [
            'total_personal' => $personalSucursal->count(),
            'activos' => $personalSucursal->where('activo', true)->count(),
            'distribuidores' => $personalSucursal->filter(fn($u) => $u->esDistribuidor())->count(),
            'cajeros' => $personalSucursal->filter(fn($u) => $u->esCajero())->count(),
            'otros' => $personalSucursal->reject(fn($u) => $u->esDistribuidor() || $u->esCajero())->count(),
        ];

        // Distribuidores de la sucursal con sus préstamos
        $distribuidores = User::where('sucursal_id', $sucursalId)
            ->whereHas('rol', fn($q) => $q->whereIn('nombre', ['Distribuidor', 'distribuidor', 'Distribuidora', 'distribuidora']))
            ->with(['prestamos' => fn($q) => $q->where('estado', 'activo')])
            ->orderBy('name')
            ->get();

        // Solicitudes pendientes de incremento de crédito para distribuidores de su sucursal
        $solicitudesCreditoPendientes = \App\Models\SolicitudCredito::where('estado', 'pendiente')
            ->whereHas('distribuidor', function ($q) use ($sucursalId) {
                $q->where('sucursal_id', $sucursalId);
            })
            ->with(['distribuidor', 'coordinador'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Solicitudes pendientes de cambio de categoría para distribuidores de su sucursal
        $solicitudesCategoriaPendientes = \App\Models\SolicitudCategoria::where('estado', 'pendiente')
            ->whereHas('distribuidor', function ($q) use ($sucursalId) {
                $q->where('sucursal_id', $sucursalId);
            })
            ->with(['distribuidor', 'coordinador'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Solicitudes en espera de la decisión final del gerente (dictaminadas por verificador)
        $solicitudesEnEspera = \App\Models\SolicitudDistribuidor::where('sucursal_id', $sucursalId)
            ->where('estado', 'en espera')
            ->with(['coordinador', 'verificador'])
            ->orderBy('updated_at', 'desc')
            ->get();

        // Solicitudes de distribuidores aprobadas por el gerente pero pendientes de cuenta
        $solicitudesAprobadasSinCuenta = \App\Models\SolicitudDistribuidor::where('sucursal_id', $sucursalId)
            ->where('estado', 'aprobado')
            ->whereNull('user_id')
            ->with(['coordinador', 'verificador'])
            ->orderBy('resolved_at', 'desc')
            ->get();

        // Solicitudes de transferencias de distribuidora dirigidas a esta sucursal
        $transferenciasPendientesGerente = \App\Models\SolicitudTransferencia::where('sucursal_destino_id', $sucursalId)
            ->where('estado', 'pendiente_gerente')
            ->with(['distribuidor', 'coordinadorEmisor', 'coordinadorReceptor', 'sucursalOrigen'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Solicitudes de transferencias de coordinadores recibidas en esta sucursal (Paso 1)
        $transferenciasCoordinadorRecibidas = \App\Models\SolicitudTransferenciaCoordinador::where('sucursal_destino_id', $sucursalId)
            ->where('estado', 'pendiente_gerente_receptor')
            ->with(['coordinador', 'gerenteEmisor', 'sucursalOrigen'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Gerentes de otras sucursales para el modal de traspaso de coordinador
        $otrosGerentesSucursal = User::whereHas('rol', fn($q) => $q->whereIn('nombre', ['Gerente de Sucursal', 'gerente de sucursal', 'Gerente Sucursal']))
            ->where('id', '!=', $operador->id)
            ->where('activo', true)
            ->with('sucursal')
            ->get();

        // Coordinadores activos en esta sucursal
        $coordinadoresSucursal = User::whereHas('rol', fn($q) => $q->whereIn('nombre', ['Coordinador', 'coordinadora', 'Coordinador']))
            ->where('sucursal_id', $sucursalId)
            ->where('activo', true)
            ->get();

        // Distribuidoras en riesgo por 3+ retrasos o marcadas como morosas
        $distribuidorasMorosasOEnRiesgo = User::where('sucursal_id', $sucursalId)
            ->whereHas('rol', fn($q) => $q->whereIn('nombre', ['Distribuidor', 'Distribuidora', 'distribuidor', 'distribuidora']))
            ->where('activo', true)
            ->where(function($q) {
                $q->where('conteo_retrasos', '>=', 3)
                  ->orWhere('es_morosa', true);
            })
            ->with(['coordinador', 'morosaPor'])
            ->orderBy('es_morosa', 'desc')
            ->orderBy('conteo_retrasos', 'desc')
            ->get();

        // Conciliaciones manuales pre-aprobadas por el coordinador pendientes de autorización gerencial
        $conciliacionesPendientesGerencia = \App\Models\Conciliacion::where('estado', 'pendiente_gerencia')
            ->whereHas('solicitante', fn($q) => $q->where('sucursal_id', $sucursalId))
            ->with(['solicitante', 'distribuidora', 'prestamo.cliente'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('gerente-sucursal.dashboard', compact(
            'operador',
            'personalSucursal',
            'statsEquipo',
            'distribuidores',
            'solicitudesCreditoPendientes',
            'solicitudesCategoriaPendientes',
            'solicitudesEnEspera',
            'solicitudesAprobadasSinCuenta',
            'transferenciasPendientesGerente',
            'transferenciasCoordinadorRecibidas',
            'otrosGerentesSucursal',
            'coordinadoresSucursal',
            'distribuidorasMorosasOEnRiesgo',
            'conciliacionesPendientesGerencia'
        ));
    }

    /**
     * Vista comparativa no editable entre la solicitud del Coordinador y la del Verificador
     */
    public function compararSolicitudDistribuidor(\App\Models\SolicitudDistribuidor $solicitud)
    {
        $user = Auth::user();
        $esGeneral = $user->esGerenteGeneral() || $user->esAdministrador();

        if (!$esGeneral && $user->sucursal_id !== $solicitud->sucursal_id) {
            abort(403, 'No tienes permiso para evaluar esta solicitud.');
        }

        $solicitud->load(['coordinador', 'verificador', 'sucursal']);

        return view('gerente.solicitudes.comparar', compact('solicitud', 'esGeneral'));
    }

    /**
     * Decisión gerencial comparativa y creación directa de la cuenta de distribuidora
     */
    public function decidirSolicitudConCuenta(Request $request, \App\Models\SolicitudDistribuidor $solicitud)
    {
        $user = Auth::user();
        $esGeneral = $user->esGerenteGeneral() || $user->esAdministrador();

        if (!$esGeneral && $user->sucursal_id !== $solicitud->sucursal_id) {
            abort(403, 'No tienes permiso para evaluar esta solicitud.');
        }

        if ($solicitud->estado !== 'en espera') {
            return back()->with('error', 'Esta solicitud ya no se encuentra en espera de decisión.');
        }

        $request->validate([
            'accion' => 'required|in:aprobar,rechazar',
            'observaciones_resolucion' => 'nullable|string|max:1000',
            'email' => $request->accion === 'aprobar' ? 'required|email|max:255|unique:users,email' : 'nullable',
            'limite_credito' => $request->accion === 'aprobar' ? 'required|numeric|min:1000' : 'nullable',
        ], [
            'email.required' => 'El correo electrónico institucional es obligatorio para dar de alta a la distribuidora.',
            'email.unique' => 'Este correo electrónico ya está registrado en el sistema.',
            'limite_credito.required' => 'Debes asignar un límite de crédito inicial.',
            'limite_credito.min' => 'El límite de crédito inicial mínimo es de $1,000.',
        ]);

        if ($request->accion === 'rechazar') {
            $solicitud->update([
                'estado' => 'rechazado',
                'observaciones_resolucion' => $request->observaciones_resolucion,
                'resolved_at' => now(),
            ]);

            // Notificar al coordinador
            \App\Models\NotificacionCajero::enviar(
                $solicitud->coordinador_id,
                'solicitud_rechazada',
                'Solicitud de Distribuidora Rechazada',
                "La solicitud de {$solicitud->nombre_completo} ha sido rechazada por " . ($esGeneral ? "Gerencia General" : "Gerencia de Sucursal") . "." . ($request->observaciones_resolucion ? " Motivo: \"{$request->observaciones_resolucion}\"" : "")
            );

            // Si lo rechazó el Gerente General, notificar a Gerencia de Sucursal
            if ($esGeneral) {
                $gerentesSucursal = User::whereHas('rol', fn($q) => $q->whereIn('nombre', ['Gerente de Sucursal', 'gerente de sucursal', 'Gerente Sucursal']))
                    ->where('sucursal_id', $solicitud->sucursal_id)
                    ->where('activo', true)
                    ->get();

                foreach ($gerentesSucursal as $gs) {
                    \App\Models\NotificacionCajero::enviar(
                        $gs->id,
                        'solicitud_rechazada',
                        'Solicitud Rechazada por Dirección General',
                        "La Dirección General ha rechazado la postulación de {$solicitud->nombre_completo} en tu sucursal."
                    );
                }
            }

            \App\Services\AuditService::registrar(
                'RECHAZAR_SOLICITUD_DISTRIBUIDOR',
                "Gerencia rechaza solicitud de distribuidora {$solicitud->nombre_completo}",
                ['solicitud_id' => $solicitud->id, 'observaciones' => $request->observaciones_resolucion]
            );

            $rutaVolver = $esGeneral ? 'gerente-general.dashboard' : 'gerente-sucursal.dashboard';
            return redirect()->route($rutaVolver)
                ->with('info', "La solicitud de {$solicitud->nombre_completo} ha sido rechazada definitivamente.");
        }

        // APROBAR: Crear cuenta de usuario con la CURP como contraseña
        $rolDistribuidor = \App\Models\Rol::where('nombre', 'Distribuidor')
            ->orWhere('nombre', 'Distribuidora')
            ->first();

        if (!$rolDistribuidor) {
            return back()->with('error', 'No se encontró el rol de Distribuidor en el sistema.');
        }

        // Usar datos verificados
        $nombresFinal = $solicitud->getDatoVerificado('nombres');
        $apellidosFinal = $solicitud->getDatoVerificado('apellidos');
        $curpFinal = strtoupper(trim($solicitud->getDatoVerificado('curp')));
        $nombreCompletoFinal = "{$nombresFinal} {$apellidosFinal}";

        // Crear usuario con CURP como contraseña, sucursal del coordinador y límite de crédito
        $newUser = User::create([
            'name' => $nombreCompletoFinal,
            'email' => $request->email,
            'password' => \Illuminate\Support\Facades\Hash::make($curpFinal),
            'rol_id' => $rolDistribuidor->id,
            'sucursal_id' => $solicitud->sucursal_id,
            'coordinador_id' => $solicitud->coordinador_id,
            'limite_credito' => $request->limite_credito,
            'categoria_distribuidor' => 'cobre',
            'activo' => true,
        ]);

        $solicitud->update([
            'estado' => 'aprobado',
            'user_id' => $newUser->id,
            'observaciones_resolucion' => $request->observaciones_resolucion,
            'resolved_at' => now(),
        ]);

        \App\Services\AuditService::registrar(
            'APROBAR_SOLICITUD_DISTRIBUIDOR',
            "Gerencia aprueba solicitud y crea cuenta para distribuidora {$nombreCompletoFinal} ({$request->email}) con límite de \${$request->limite_credito}",
            ['solicitud_id' => $solicitud->id, 'nuevo_user_id' => $newUser->id]
        );

        // Notificar al coordinador
        \App\Models\NotificacionCajero::enviar(
            $solicitud->coordinador_id,
            'solicitud_aprobada',
            '¡Distribuidora Aprobada y Cuenta Activada!',
            "La solicitud de {$nombreCompletoFinal} ha sido APROBADA. Su cuenta fue activada con correo: {$request->email}, contraseña inicial (su CURP): {$curpFinal} y línea de crédito: $" . number_format($request->limite_credito, 2) . "."
        );

        // Si lo aprobó Gerencia General, notificar al Gerente de Sucursal
        if ($esGeneral) {
            $gerentesSucursal = User::whereHas('rol', fn($q) => $q->whereIn('nombre', ['Gerente de Sucursal', 'gerente de sucursal', 'Gerente Sucursal']))
                ->where('sucursal_id', $solicitud->sucursal_id)
                ->where('activo', true)
                ->get();

            foreach ($gerentesSucursal as $gs) {
                \App\Models\NotificacionCajero::enviar(
                    $gs->id,
                    'solicitud_aprobada',
                    'Distribuidora Aprobada por Gerencia General',
                    "La Gerencia General ha aprobado e incorporado a la distribuidora {$nombreCompletoFinal} a tu sucursal ({$solicitud->sucursal?->nombre})."
                );
            }
        }

        $rutaVolver = $esGeneral ? 'gerente-general.dashboard' : 'gerente-sucursal.dashboard';
        return redirect()->route($rutaVolver)
            ->with('success', "La distribuidora {$nombreCompletoFinal} ha sido APROBADA y dada de alta exitosamente en el sistema con su CURP como contraseña.");
    }

    /**
     * Revisión de transferencia de distribuidora para Gerente de Sucursal o Gerente General
     */
    public function revisarTransferencia(\App\Models\SolicitudTransferencia $transferencia)
    {
        $gerente = Auth::user();
        $esGeneral = $gerente->esGerenteGeneral() || $gerente->esAdministrador();

        if (!$esGeneral && $gerente->sucursal_id !== $transferencia->sucursal_destino_id) {
            abort(403, 'No tienes autorización para dictaminar sobre esta transferencia.');
        }

        $transferencia->load([
            'distribuidor.sucursal',
            'coordinadorEmisor.sucursal',
            'coordinadorReceptor.sucursal',
            'sucursalOrigen',
            'sucursalDestino'
        ]);

        $distribuidora = $transferencia->distribuidor;
        $prestamosActivos = \App\Models\Prestamo::with(['cliente', 'productoVale'])
            ->where('created_by_user_id', $distribuidora->id)
            ->where('estado', 'activo')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('gerente-sucursal.transferencias.show', compact('transferencia', 'distribuidora', 'prestamosActivos', 'esGeneral'));
    }

    /**
     * Decisión final del Gerente (de Sucursal o General) sobre el traspaso de distribuidora
     */
    public function decidirTransferencia(Request $request, \App\Models\SolicitudTransferencia $transferencia)
    {
        $gerente = Auth::user();
        $esGeneral = $gerente->esGerenteGeneral() || $gerente->esAdministrador();

        if (!$esGeneral && $gerente->sucursal_id !== $transferencia->sucursal_destino_id) {
            abort(403, 'No tienes autorización para dictaminar sobre esta transferencia.');
        }

        if ($transferencia->estado !== 'pendiente_gerente') {
            return back()->with('error', 'Esta transferencia no está pendiente de autorización gerencial.');
        }

        $request->validate([
            'accion' => 'required|in:aprobar,rechazar',
            'observaciones_gerente' => 'nullable|string|max:600'
        ]);

        $distribuidora = $transferencia->distribuidor;
        $emisor = $transferencia->coordinadorEmisor;
        $receptor = $transferencia->coordinadorReceptor;

        if ($request->accion === 'rechazar') {
            $transferencia->update([
                'estado' => 'rechazada_gerente',
                'observaciones_gerente' => $request->observaciones_gerente,
                'gerente_id' => $gerente->id,
                'resolved_at' => now(),
            ]);

            // Si lo rechazó el Gerente General, se le notifica al Gerente de la Sucursal receptora
            if ($esGeneral) {
                $gerentesSucursal = User::whereHas('rol', fn($q) => $q->whereIn('nombre', ['Gerente de Sucursal', 'gerente de sucursal', 'Gerente Sucursal']))
                    ->where('sucursal_id', $transferencia->sucursal_destino_id)
                    ->where('activo', true)
                    ->get();

                foreach ($gerentesSucursal as $gs) {
                    \App\Models\NotificacionCajero::enviar(
                        $gs->id,
                        'transferencia_rechazada_gerente',
                        'Traspaso Rechazado por Dirección General',
                        "La Dirección General ha rechazado el traspaso de {$distribuidora->name} a tu sucursal. " . ($request->observaciones_gerente ? "Observaciones: \"{$request->observaciones_gerente}\"" : ""),
                        ['transferencia_id' => $transferencia->id]
                    );
                }
            }

            // Notificar a ambos coordinadores
            \App\Models\NotificacionCajero::enviar(
                $emisor->id,
                'transferencia_rechazada_gerente',
                'Traspaso de Distribuidora No Aprobado por Gerencia',
                "La Gerencia " . ($esGeneral ? "General" : "de Sucursal") . " rechazó el traspaso de {$distribuidora->name}. " . ($request->observaciones_gerente ? "Observaciones: \"{$request->observaciones_gerente}\"" : ""),
                ['transferencia_id' => $transferencia->id]
            );

            \App\Models\NotificacionCajero::enviar(
                $receptor->id,
                'transferencia_rechazada_gerente',
                'Traspaso de Distribuidora No Aprobado por Gerencia',
                "La Gerencia " . ($esGeneral ? "General" : "de Sucursal") . " rechazó la incorporación de {$distribuidora->name} a tu equipo. " . ($request->observaciones_gerente ? "Observaciones: \"{$request->observaciones_gerente}\"" : ""),
                ['transferencia_id' => $transferencia->id]
            );

            $rutaRedireccion = $esGeneral ? 'gerente-general.dashboard' : 'gerente-sucursal.dashboard';
            return redirect()->route($rutaRedireccion)
                ->with('info', "Has rechazado el traspaso de la distribuidora {$distribuidora->name}.");
        }

        // APROBADA: Actualizar estado y formalizar el traspaso en la base de datos
        $transferencia->update([
            'estado' => 'aprobada',
            'observaciones_gerente' => $request->observaciones_gerente,
            'gerente_id' => $gerente->id,
            'resolved_at' => now(),
        ]);

        // Reasignar distribuidora a su nuevo coordinador y nueva sucursal
        $distribuidora->update([
            'coordinador_id' => $transferencia->coordinador_receptor_id,
            'sucursal_id' => $transferencia->sucursal_destino_id,
        ]);

        // Si la acepta el Gerente General, se le notifica al Gerente de la sucursal receptora
        if ($esGeneral) {
            $gerentesSucursal = User::whereHas('rol', fn($q) => $q->whereIn('nombre', ['Gerente de Sucursal', 'gerente de sucursal', 'Gerente Sucursal']))
                ->where('sucursal_id', $transferencia->sucursal_destino_id)
                ->where('activo', true)
                ->get();

            foreach ($gerentesSucursal as $gs) {
                \App\Models\NotificacionCajero::enviar(
                    $gs->id,
                    'transferencia_completada',
                    'Traspaso de Distribuidora Aprobado por Gerencia General',
                    "El Gerente General {$gerente->name} ha autorizado el traspaso de la distribuidora {$distribuidora->name} a tu sucursal ({$transferencia->sucursalDestino?->nombre}), asignada al coordinador {$receptor->name}.",
                    [
                        'transferencia_id' => $transferencia->id,
                        'url' => route('gerente-sucursal.dashboard')
                    ]
                );
            }
        }

        // Notificar a todos los involucrados (Emisor, Receptor, Distribuidora)
        \App\Models\NotificacionCajero::enviar(
            $emisor->id,
            'transferencia_completada',
            'Traspaso de Distribuidora Formalizado',
            "Se ha completado el traspaso de {$distribuidora->name} al coordinador {$receptor->name} (Sucursal: {$transferencia->sucursalDestino?->nombre}) con el visto bueno de " . ($esGeneral ? "Gerencia General" : "Gerencia de Sucursal") . ".",
            ['transferencia_id' => $transferencia->id]
        );

        \App\Models\NotificacionCajero::enviar(
            $receptor->id,
            'transferencia_completada',
            'Nueva Distribuidora Incorporada a tu Equipo',
            "¡Traspaso formalizado! La distribuidora {$distribuidora->name} ha sido asignada formalmente a tu coordinación tras la autorización de " . ($esGeneral ? "Gerencia General" : "Gerencia de Sucursal") . ".",
            ['transferencia_id' => $transferencia->id]
        );

        \App\Models\NotificacionCajero::enviar(
            $distribuidora->id,
            'cambio_coordinador_asignado',
            'Actualización de tu Coordinador y Sucursal',
            "Se ha formalizado tu cambio de coordinación. Tu nuevo coordinador es {$receptor->name} (Sucursal: {$transferencia->sucursalDestino?->nombre}).",
            ['transferencia_id' => $transferencia->id]
        );

        $rutaRedireccion = $esGeneral ? 'gerente-general.dashboard' : 'gerente-sucursal.dashboard';
        return redirect()->route($rutaRedireccion)
            ->with('success', "Traspaso de {$distribuidora->name} APROBADO y formalizado exitosamente con el coordinador {$receptor->name}.");
    }

    /**
     * Solicitar traspaso de Coordinador a otro Gerente de Sucursal (Paso 1)
     */
    public function solicitarTraspasoCoordinador(Request $request)
    {
        $gerenteEmisor = Auth::user();
        if (!$gerenteEmisor->esGerenteSucursal()) {
            return back()->with('error', 'Únicamente los Gerentes de Sucursal pueden iniciar este traspaso.');
        }

        $request->validate([
            'coordinador_id' => 'required|exists:users,id',
            'gerente_receptor_id' => 'required|exists:users,id|different:' . $gerenteEmisor->id,
            'motivo' => 'required|string|max:1000',
        ]);

        $coordinador = User::findOrFail($request->coordinador_id);
        $gerenteReceptor = User::findOrFail($request->gerente_receptor_id);

        if ($coordinador->sucursal_id !== $gerenteEmisor->sucursal_id) {
            return back()->with('error', 'El coordinador seleccionado no pertenece a tu sucursal.');
        }

        // Verificar si ya tiene solicitud activa
        $existente = \App\Models\SolicitudTransferenciaCoordinador::where('coordinador_id', $coordinador->id)
            ->whereIn('estado', ['pendiente_gerente_receptor', 'pendiente_gerente_general'])
            ->exists();

        if ($existente) {
            return back()->with('error', 'Este coordinador ya cuenta con una solicitud de transferencia en proceso.');
        }

        $transferencia = \App\Models\SolicitudTransferenciaCoordinador::create([
            'coordinador_id' => $coordinador->id,
            'gerente_emisor_id' => $gerenteEmisor->id,
            'gerente_receptor_id' => $gerenteReceptor->id,
            'sucursal_origen_id' => $gerenteEmisor->sucursal_id,
            'sucursal_destino_id' => $gerenteReceptor->sucursal_id,
            'motivo' => $request->motivo,
            'estado' => 'pendiente_gerente_receptor',
        ]);

        \App\Services\AuditService::registrar(
            'SOLICITUD_TRASPASO_COORDINADOR',
            "Gerencia Emisora {$gerenteEmisor->name} solicita traspaso del coordinador {$coordinador->name} a la sucursal de {$gerenteReceptor->name}",
            ['transferencia_id' => $transferencia->id]
        );

        // Notificar al Gerente Receptor
        \App\Models\NotificacionCajero::enviar(
            $gerenteReceptor->id,
            'solicitud_traspaso_coordinador',
            'Solicitud de Traspaso de Coordinador Recibida',
            "El Gerente {$gerenteEmisor->name} de la sucursal {$gerenteEmisor->sucursal?->nombre} te solicita transferir al coordinador {$coordinador->name}. Revisa y dictamina.",
            ['transferencia_id' => $transferencia->id]
        );

        return back()->with('success', "Se ha enviado la solicitud de traspaso del coordinador {$coordinador->name} al Gerente {$gerenteReceptor->name}.");
    }

    /**
     * Decisión del Gerente Destino sobre el traspaso de Coordinador (Paso 1)
     */
    public function decidirTraspasoCoordinador(Request $request, \App\Models\SolicitudTransferenciaCoordinador $transferencia)
    {
        $gerenteReceptor = Auth::user();
        if ($gerenteReceptor->id !== $transferencia->gerente_receptor_id && !$gerenteReceptor->esGerenteGeneral()) {
            return back()->with('error', 'No estás autorizado para responder esta solicitud.');
        }

        if ($transferencia->estado !== 'pendiente_gerente_receptor') {
            return back()->with('error', 'Esta solicitud ya fue procesada.');
        }

        $request->validate([
            'accion' => 'required|in:aceptar,rechazar',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        $coordinador = $transferencia->coordinador;
        $gerenteEmisor = $transferencia->gerenteEmisor;

        if ($request->accion === 'rechazar') {
            $transferencia->update([
                'estado' => 'rechazada_gerente_receptor',
                'observaciones_gerente_receptor' => $request->observaciones,
                'resolved_at' => now(),
            ]);

            \App\Services\AuditService::registrar(
                'RECHAZAR_TRASPASO_COORDINADOR_SUCURSAL',
                "Gerente Receptor {$gerenteReceptor->name} rechaza recibir al coordinador {$coordinador->name}",
                ['transferencia_id' => $transferencia->id]
            );

            // Notificar al emisor
            \App\Models\NotificacionCajero::enviar(
                $gerenteEmisor->id,
                'alerta',
                'Traspaso de Coordinador Rechazado',
                "El Gerente Destino {$gerenteReceptor->name} ha rechazado la solicitud de traspaso del coordinador {$coordinador->name}." . ($request->observaciones ? " Motivo: {$request->observaciones}" : "")
            );

            return back()->with('info', "Has rechazado el traspaso del coordinador {$coordinador->name}.");
        }

        // ACEPTADO por Gerente Receptor -> pasa a revisión del Gerente General (Paso 2)
        $transferencia->update([
            'estado' => 'pendiente_gerente_general',
            'observaciones_gerente_receptor' => $request->observaciones,
        ]);

        \App\Services\AuditService::registrar(
            'ACEPTAR_TRASPASO_COORDINADOR_SUCURSAL',
            "Gerente Receptor {$gerenteReceptor->name} acepta recibir al coordinador {$coordinador->name} (Pendiente Visto Bueno Gerencia General)",
            ['transferencia_id' => $transferencia->id]
        );

        // Notificar al emisor
        \App\Models\NotificacionCajero::enviar(
            $gerenteEmisor->id,
            'informativa',
            'Traspaso Aceptado por Gerente Destino (En Espera de Dirección General)',
            "El Gerente Destino {$gerenteReceptor->name} aceptó el traspaso de {$coordinador->name}. Ahora la solicitud ha pasado a la Gerencia General para su aprobación final."
        );

        // Notificar a todos los Gerentes Generales
        $gerentesGenerales = User::whereHas('rol', fn($q) => $q->where('nombre', 'Gerente General'))
            ->where('activo', true)
            ->get();

        foreach ($gerentesGenerales as $gg) {
            \App\Models\NotificacionCajero::enviar(
                $gg->id,
                'solicitud_traspaso_coordinador',
                'Aprobación de Traspaso de Coordinador Requerida',
                "Los Gerentes {$gerenteEmisor->name} y {$gerenteReceptor->name} acordaron el traspaso del coordinador {$coordinador->name} a la sucursal '{$transferencia->sucursalDestino?->nombre}'. Requiere tu autorización final."
            );
        }

        return back()->with('success', "Has aceptado el traspaso del coordinador {$coordinador->name}. La solicitud fue enviada al Gerente General para su resolución final.");
    }

    /**
     * Dictamen Gerencial de Morosidad (Gerente General, Gerente de Sucursal o Administrador):
     * Marca o desmarca a una distribuidora en estado de morosidad.
     */
    public function decidirMorosidad(Request $request, User $distribuidor)
    {
        $gerente = Auth::user();
        if (!$gerente->esGerenteGeneral() && !$gerente->esGerenteSucursal() && !$gerente->esAdministrador()) {
            abort(403, 'No estás autorizado para gestionar el estado de morosidad.');
        }

        if ($gerente->esGerenteSucursal() && $distribuidor->sucursal_id !== $gerente->sucursal_id) {
            abort(403, 'Esta distribuidora no pertenece a tu sucursal.');
        }

        $request->validate([
            'accion' => 'required|in:marcar,desmarcar',
            'motivo' => 'nullable|string|max:500',
        ]);

        if ($request->accion === 'marcar') {
            $distribuidor->marcarComoMorosa($gerente, $request->motivo);
            \App\Services\AuditService::registrar(
                'MOROSIDAD_DECLARADA',
                "{$gerente->name} declara a la distribuidora {$distribuidor->name} en estado de MOROSIDAD" . ($request->motivo ? " (Motivo: {$request->motivo})" : ""),
                ['distribuidor_id' => $distribuidor->id, 'motivo' => $request->motivo]
            );
            return back()->with('warning', "Se ha marcado a la distribuidora {$distribuidor->name} como MOROSA. Sus vales pendientes fueron desactivados y la colocación de nuevos vales ha sido bloqueada.");
        } else {
            $distribuidor->desmarcarMorosidad();
            \App\Services\AuditService::registrar(
                'MOROSIDAD_RETIRADA',
                "{$gerente->name} retira el estado de morosidad a la distribuidora {$distribuidor->name}",
                ['distribuidor_id' => $distribuidor->id]
            );
            return back()->with('success', "Se ha retirado el estado de morosidad a la distribuidora {$distribuidor->name}. Ya puede volver a emitir vales con normalidad.");
        }
    }

    /**
     * Autorización final o rechazo de conciliación manual por Gerente de Sucursal o Gerente General (Paso 2).
     */
    public function decidirConciliacionGerencia(Request $request, \App\Models\Conciliacion $conciliacion)
    {
        $request->validate([
            'accion' => 'required|in:aceptar,rechazar',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $gerente = Auth::user();
        if (!$gerente->esGerenteGeneral() && !$gerente->esGerenteSucursal() && !$gerente->esAdministrador()) {
            abort(403, 'No estás autorizado para resolver conciliaciones manuales.');
        }

        if ($conciliacion->estado !== 'pendiente_gerencia') {
            return back()->with('error', 'Esta conciliación ya ha sido resuelta previamente o no se encuentra pendiente de autorización gerencial.');
        }

        $cajeroSolicitante = $conciliacion->solicitante;
        $sucursalId = $cajeroSolicitante?->sucursal_id;

        if ($request->accion === 'rechazar') {
            $conciliacion->update([
                'estado' => 'rechazada',
                'autorizador_id' => $gerente->id,
                'notas_resolucion' => $request->observaciones ?? 'Rechazada por la Gerencia',
            ]);

            // Notificar al cajero
            if ($cajeroSolicitante) {
                \App\Models\NotificacionCajero::enviar(
                    $cajeroSolicitante->id,
                    'conciliacion_rechazada_gerencia',
                    'Conciliación Manual Rechazada por Gerencia',
                    "La Gerencia ({$gerente->name}) ha rechazado tu solicitud de conciliación (Ref: {$conciliacion->referencia_conciliacion}). Nota: " . ($request->observaciones ?? 'Sin notas.')
                );
            }

            // Notificar a coordinadores
            $coordinadores = User::whereHas('rol', fn($q) => $q->whereIn('nombre', ['Coordinador', 'coordinador']))
                ->where('sucursal_id', $sucursalId)
                ->where('activo', true)
                ->get();

            foreach ($coordinadores as $coord) {
                \App\Models\NotificacionCajero::enviar(
                    $coord->id,
                    'conciliacion_rechazada_gerencia',
                    'Conciliación Pre-Aprobada fue Rechazada por Gerencia',
                    "La Gerencia ({$gerente->name}) rechazó la conciliación que habías pre-aprobado (Ref: {$conciliacion->referencia_conciliacion})."
                );
            }

            \App\Services\AuditService::registrar(
                'RECHAZAR_CONCILIACION',
                "Gerencia rechaza conciliación manual (Ref: {$conciliacion->referencia_conciliacion})",
                ['conciliacion_id' => $conciliacion->id, 'observaciones' => $request->observaciones]
            );

            return back()->with('info', 'La conciliación ha sido rechazada y se han enviado las notificaciones correspondientes.');
        }

        // APROBAR CONCILIACIÓN
        \Illuminate\Support\Facades\DB::transaction(function () use ($conciliacion, $gerente, $request, $cajeroSolicitante, $sucursalId) {
            $conciliacion->update([
                'estado' => 'conciliado',
                'autorizador_id' => $gerente->id,
                'conciliado_por_user_id' => $gerente->id,
                'conciliado_at' => now(),
                'notas_resolucion' => $request->observaciones ?? 'Aprobada por Gerencia ' . $gerente->name,
            ]);

            // Si la conciliación está ligada a un pago existente, actualizar el monto abonado
            if ($conciliacion->pago_prestamo_id && $conciliacion->pagoPrestamo) {
                $pago = $conciliacion->pagoPrestamo;
                $diferencia = floatval($conciliacion->monto_corregido) - floatval($pago->monto_abonado);

                $pago->update([
                    'monto_abonado' => $conciliacion->monto_corregido,
                    'observaciones' => ($pago->observaciones ? $pago->observaciones . ' | ' : '') . "Conciliado por {$gerente->name} (Ref: {$conciliacion->referencia_conciliacion})",
                ]);

                if ($pago->prestamo && $diferencia != 0) {
                    $pago->prestamo->decrement('adeudo_pendiente', $diferencia);
                    $pago->prestamo->increment('pagos_recibidos', $diferencia);
                }
            }

            // Notificar al Cajero Solicitante
            if ($cajeroSolicitante) {
                \App\Models\NotificacionCajero::enviar(
                    (string) $cajeroSolicitante->id,
                    'conciliacion_aprobada',
                    '🎉 Conciliación Manual Aprobada',
                    "La Gerencia ({$gerente->name}) ha autorizado la conciliación manual (Ref: {$conciliacion->referencia_conciliacion}) por \${$conciliacion->monto_corregido}."
                );
            }

            // Notificar a los Coordinadores de la sucursal
            $coordinadores = User::whereHas('rol', fn($q) => $q->whereIn('nombre', ['Coordinador', 'coordinador']))
                ->where('sucursal_id', $sucursalId)
                ->where('activo', true)
                ->get();

            foreach ($coordinadores as $coord) {
                \App\Models\NotificacionCajero::enviar(
                    (string) $coord->id,
                    'conciliacion_aprobada',
                    'Conciliación Pre-Aprobada Autorizada por Gerencia',
                    "La Gerencia ({$gerente->name}) autorizó definitivamente la conciliación (Ref: {$conciliacion->referencia_conciliacion}) por \${$conciliacion->monto_corregido}."
                );
            }

            // Notificar al OTRO Gerente (Si aprobó el Gerente de Sucursal, notificar al Gerente General y viceversa)
            if ($gerente->esGerenteSucursal()) {
                $gerentesGenerales = User::whereHas('rol', fn($q) => $q->whereIn('nombre', ['Gerente General', 'gerente general', 'Administrador', 'administrador']))
                    ->where('activo', true)
                    ->get();

                foreach ($gerentesGenerales as $gg) {
                    \App\Models\NotificacionCajero::enviar(
                        (string) $gg->id,
                        'conciliacion_resuelta_otra_gerencia',
                        'Conciliación Resuelta por Gerente de Sucursal',
                        "El Gerente de Sucursal {$gerente->name} aprobó la conciliación (Ref: {$conciliacion->referencia_conciliacion}) por \${$conciliacion->monto_corregido}."
                    );
                }
            } else {
                $gerentesSucursal = User::whereHas('rol', fn($q) => $q->whereIn('nombre', ['Gerente de Sucursal', 'gerente de sucursal']))
                    ->where('sucursal_id', $sucursalId)
                    ->where('activo', true)
                    ->get();

                foreach ($gerentesSucursal as $gs) {
                    \App\Models\NotificacionCajero::enviar(
                        (string) $gs->id,
                        'conciliacion_resuelta_otra_gerencia',
                        'Conciliación Resuelta por Gerencia General',
                        "La Gerencia General ({$gerente->name}) aprobó la conciliación de tu sucursal (Ref: {$conciliacion->referencia_conciliacion}) por \${$conciliacion->monto_corregido}."
                    );
                }
            }

            \App\Services\AuditService::registrar(
                'APROBAR_CONCILIACION',
                "Gerencia aprueba conciliación manual (Ref: {$conciliacion->referencia_conciliacion}) por \${$conciliacion->monto_corregido}",
                ['conciliacion_id' => $conciliacion->id, 'monto_corregido' => $conciliacion->monto_corregido]
            );
        });

        return back()->with('success', "La conciliación manual ha sido autorizada y aplicada exitosamente.");
    }
}
