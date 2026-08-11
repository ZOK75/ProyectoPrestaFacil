<?php

namespace App\Http\Controllers;

use App\Models\SolicitudDistribuidor;
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
        $distribuidores = \App\Models\User::whereHas('rol', function($q) {
                $q->where('nombre', 'Distribuidor')
                  ->orWhere('nombre', 'Distribuidora');
            })
            ->where('sucursal_id', $user->sucursal_id)
            ->get();

        return view('coordinador.dashboard', compact('distribuidores'));
    }

    /**
     * Listado de solicitudes de distribuidor creadas por este coordinador.
     */
    public function index()
    {
        $solicitudes = SolicitudDistribuidor::where('coordinador_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('coordinador.solicitudes.index', compact('solicitudes'));
    }

    /**
     * Formulario para crear una nueva solicitud de distribuidor.
     */
    public function create()
    {
        return view('coordinador.solicitudes.create');
    }

    /**
     * Guardar una nueva solicitud de distribuidor.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'acta_nacimiento' => 'nullable|string|max:255',
            'curp' => 'required|string|max:18',
            'rfc' => 'required|string|max:13',
            'lugar_nacimiento' => 'nullable|string|max:255',
            'calle' => 'required|string|max:255',
            'colonia' => 'required|string|max:255',
            'codigo_postal' => 'required|string|max:10',
            'estado_republica' => 'required|string|max:255',
            'ciudad' => 'required|string|max:255',
            'datos_familiares' => 'nullable|array',
            'datos_vehiculos' => 'nullable|string',
            'datos_casa' => 'nullable|string',
            'referencias_laborales' => 'nullable|string',
        ]);

        $data = $request->all();
        $data['coordinador_id'] = Auth::id();
        $data['sucursal_id'] = Auth::user()->sucursal_id;
        $data['estado'] = 'en espera'; // Estado inicial

        SolicitudDistribuidor::create($data);

        return redirect()->route('coordinador.solicitudes.index')
                         ->with('success', 'Solicitud de distribuidora creada exitosamente y puesta en espera.');
    }

    /**
     * Enviar a verificación (Cambia el estado a "en espera de verificacion")
     */
    public function enviarAVerificacion(SolicitudDistribuidor $solicitud)
    {
        if ($solicitud->coordinador_id !== Auth::id()) {
            abort(403);
        }

        $solicitud->update([
            'estado' => 'en espera de verificacion'
        ]);

        return redirect()->route('coordinador.solicitudes.index')
                         ->with('success', 'La solicitud ha sido enviada al Verificador.');
    }
}
