<?php

namespace App\Http\Controllers;

use App\Models\SolicitudDistribuidor;
use App\Models\SolicitudCredito;
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
        
        // El coordinador administra a los distribuidores de su sucursal
        $distribuidores = User::whereHas('rol', function($q) {
                $q->where('nombre', 'Distribuidor')
                  ->orWhere('nombre', 'Distribuidora');
            })
            ->where('activo', true)
            ->where('sucursal_id', $user->sucursal_id)
            ->get();

        // Obtener historial de solicitudes de incremento de crédito solicitadas por este coordinador
        $solicitudesCredito = SolicitudCredito::where('coordinador_id', $user->id)
            ->with(['distribuidor', 'gerente'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('coordinador.dashboard', compact('distribuidores', 'solicitudesCredito'));
    }

    /**
     * Listado de solicitudes de distribuidor de la sucursal.
     */
    public function index()
    {
        $user = Auth::user();

        // Mostrar todas las solicitudes de la misma sucursal
        $solicitudes = SolicitudDistribuidor::where('sucursal_id', $user->sucursal_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('coordinador.solicitudes.index', compact('solicitudes'));
    }

    /**
     * Formulario para crear una nueva solicitud de distribuidor de forma interna.
     */
    public function create()
    {
        return view('coordinador.solicitudes.create');
    }

    /**
     * Guardar una nueva solicitud de distribuidor de forma interna.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'telefono' => 'required|string|max:20',
            'fecha_nacimiento' => 'required|date|before:today',
            'curp' => 'required|string|size:18|regex:/^[A-Z]{4}[0-9]{6}[H,M][A-Z]{5}[0-9,A-Z][0-9]$/i',
            'rfc' => 'required|string|min:12|max:13|regex:/^[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}$/i',
            'lugar_nacimiento' => 'nullable|string|max:255',
            'calle' => 'required|string|max:255',
            'colonia' => 'required|string|max:255',
            'codigo_postal' => 'required|string|max:10',
            'ciudad' => 'required|string|max:255',
            'estado_republica' => 'required|string|max:255',
            'datos_familiares' => 'nullable|array',
            'datos_vehiculos' => 'nullable|string',
            'datos_casa' => 'required|string|max:1000',
            'referencias_laborales' => 'nullable|string',
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
            'estado' => 'en espera',
        ]);

        return redirect()->route('coordinador.solicitudes.index')
                         ->with('success', 'Solicitud de distribuidora creada exitosamente y puesta en espera.');
    }

    /**
     * Ver el detalle de una solicitud de distribuidora.
     */
    public function show(SolicitudDistribuidor $solicitud)
    {
        // Validar que la solicitud pertenezca a la misma sucursal
        if ($solicitud->sucursal_id !== Auth::user()->sucursal_id) {
            abort(403, 'No tienes permiso para ver esta solicitud.');
        }

        $solicitud->load(['coordinador', 'sucursal', 'verificador']);

        return view('coordinador.solicitudes.show', compact('solicitud'));
    }

    /**
     * Enviar a verificación (Cambia el estado a "en espera de verificacion")
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
        if (!$distribuidor->esDistribuidor() || $distribuidor->sucursal_id !== $coordinador->sucursal_id) {
            abort(403, 'Acceso denegado: El distribuidor no pertenece a tu sucursal.');
        }

        $request->validate([
            'limite_nuevo' => 'required|numeric|min:' . ($distribuidor->limite_credito + 0.01),
            'motivo' => 'required|string|max:500',
        ], [
            'limite_nuevo.min' => 'El nuevo límite debe ser mayor al límite actual ($' . number_format($distribuidor->limite_credito, 2) . ').',
        ]);

        // Crear la solicitud
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
}
