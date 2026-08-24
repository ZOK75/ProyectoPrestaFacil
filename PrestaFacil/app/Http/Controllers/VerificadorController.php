<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\SolicitudDistribuidor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerificadorController extends Controller
{
    /**
     * Dashboard del Verificador:
     * - Muestra solicitudes "en espera de verificacion" en su misma sucursal
     * - Historial de solicitudes procesadas por este verificador
     */
    public function dashboard()
    {
        $user = Auth::user();

        // Solicitudes pendientes de evaluar en la sucursal del verificador
        $solicitudesPendientes = SolicitudDistribuidor::where('sucursal_id', $user->sucursal_id)
            ->whereIn('estado', ['en espera de verificacion', 'en espera'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Solicitudes resueltas por este verificador
        $solicitudesResueltas = SolicitudDistribuidor::where('verificador_id', $user->id)
            ->with(['coordinador'])
            ->orderBy('resolved_at', 'desc')
            ->get();

        return view('verificador.dashboard', compact('solicitudesPendientes', 'solicitudesResueltas'));
    }

    /**
     * Detalle de la solicitud para el verificador
     */
    public function showSolicitud(SolicitudDistribuidor $solicitud)
    {
        // Validar que la solicitud sea de la sucursal del verificador
        if ($solicitud->sucursal_id !== Auth::user()->sucursal_id) {
            abort(403, 'No tienes permiso para ver esta solicitud.');
        }

        $solicitud->load(['coordinador', 'sucursal']);

        return view('verificador.solicitudes.show', compact('solicitud'));
    }

    /**
     * Procesar solicitud (Enviar verificación con correcciones y dictamen a Gerencia)
     */
    public function procesarSolicitud(Request $request, SolicitudDistribuidor $solicitud)
    {
        // Validar sucursal
        if ($solicitud->sucursal_id !== Auth::user()->sucursal_id) {
            abort(403, 'No tienes permiso para procesar esta solicitud.');
        }

        if ($solicitud->estado !== 'en espera de verificacion') {
            return back()->with('error', 'Esta solicitud ya no se encuentra pendiente de verificación.');
        }

        $request->validate([
            'dictamen_verificador' => 'required|in:aceptado,rechazado',
            'comentarios_verificador' => 'required|string|max:1000',
            // Datos Personales
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'telefono' => 'required|digits:10',
            'fecha_nacimiento' => 'required|date|before:today',
            'lugar_nacimiento' => 'nullable|string|max:150',
            'curp' => ['required', 'string', 'size:18', 'regex:/^[A-Z]{4}\d{6}[HM][A-Z]{2}[B-DF-HJ-NP-TV-Z]{3}[A-Z0-9]\d$/i'],
            'rfc' => ['required', 'string', 'min:12', 'max:13', 'regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/i'],
            // Dirección Domiciliaria
            'calle' => 'required|string|max:200',
            'colonia' => 'required|string|max:100',
            'codigo_postal' => 'required|digits:5',
            'ciudad' => 'required|string|max:100',
            'estado_republica' => 'required|string|max:100',
            // Información del Hogar y Referencias
            'datos_casa' => 'required|string|max:500',
            'datos_vehiculos' => 'nullable|string|max:500',
            'referencias_laborales' => 'nullable|string|max:500',
            'datos_familiares' => 'nullable|array',
            'datos_familiares.*.nombre' => 'required_with:datos_familiares|string|max:100',
            'datos_familiares.*.parentesco' => 'required_with:datos_familiares|string|max:50',
            'datos_familiares.*.contacto' => 'nullable|string|max:50',
            'checks' => 'nullable|array',
        ], [
            'dictamen_verificador.required' => 'Debes seleccionar un dictamen (Aceptado o Rechazado).',
            'comentarios_verificador.required' => 'Debes registrar tus notas u observaciones de la visita domiciliaria.',
            'nombres.required' => 'El nombre o nombres de la distribuidora son obligatorios.',
            'apellidos.required' => 'Los apellidos de la distribuidora son obligatorios.',
            'telefono.required' => 'El número de teléfono celular es obligatorio.',
            'telefono.digits' => 'El número de teléfono debe contener exactamente 10 dígitos numéricos.',
            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a la fecha de hoy.',
            'curp.required' => 'La clave CURP es obligatoria.',
            'curp.size' => 'La CURP debe tener exactamente 18 caracteres.',
            'curp.regex' => 'El formato de la CURP es inválido (ejemplo: ABCD000000HDFRRN01).',
            'rfc.required' => 'El RFC es obligatorio.',
            'rfc.regex' => 'El formato del RFC es inválido.',
            'calle.required' => 'La calle y número de domicilio son obligatorios.',
            'colonia.required' => 'La colonia es obligatoria.',
            'codigo_postal.required' => 'El código postal es obligatorio.',
            'codigo_postal.digits' => 'El código postal debe contener exactamente 5 dígitos numéricos.',
            'ciudad.required' => 'La ciudad o municipio es obligatorio.',
            'estado_republica.required' => 'El estado de la república es obligatorio.',
            'datos_casa.required' => 'La descripción y características de la casa son obligatorias.',
        ]);

        $verificador = Auth::user();

        $datosVerificacion = [
            'nombres' => $request->nombres,
            'apellidos' => $request->apellidos,
            'telefono' => $request->telefono,
            'fecha_nacimiento' => $request->fecha_nacimiento,
            'lugar_nacimiento' => $request->lugar_nacimiento,
            'curp' => strtoupper(trim($request->curp)),
            'rfc' => strtoupper(trim($request->rfc)),
            'calle' => $request->calle,
            'colonia' => $request->colonia,
            'codigo_postal' => $request->codigo_postal,
            'ciudad' => $request->ciudad,
            'estado_republica' => $request->estado_republica,
            'datos_casa' => $request->datos_casa,
            'datos_vehiculos' => $request->datos_vehiculos,
            'referencias_laborales' => $request->referencias_laborales,
            'datos_familiares' => $request->datos_familiares ?? [],
            'checks' => $request->checks ?? [],
            'fecha_verificacion' => now()->toDateTimeString(),
        ];

        $solicitud->update([
            'dictamen_verificador' => $request->dictamen_verificador,
            'comentarios_verificador' => $request->comentarios_verificador,
            'datos_verificacion' => $datosVerificacion,
            'verificador_id' => $verificador->id,
            'estado' => 'en espera', // Pasa a evaluación gerencial
        ]);

        // Notificar a Gerencia de Sucursal
        $gerentesSucursal = User::whereHas('rol', fn($q) => $q->whereIn('nombre', ['Gerente de Sucursal', 'gerente de sucursal', 'Gerente Sucursal']))
            ->where('sucursal_id', $solicitud->sucursal_id)
            ->where('activo', true)
            ->get();

        foreach ($gerentesSucursal as $gs) {
            \App\Models\NotificacionCajero::enviar(
                $gs->id,
                'solicitud_verificada_gerencia',
                'Solicitud Verificada Pendiente de Dictamen',
                "El verificador {$verificador->name} ha concluido la visita domiciliaria de {$solicitud->nombre_completo} (Dictamen: " . strtoupper($request->dictamen_verificador) . "). Se requiere tu dictamen gerencial comparativo.",
                [
                    'solicitud_id' => $solicitud->id,
                    'url' => route('gerente-sucursal.solicitudes.comparar', $solicitud),
                    'entidad_tipo' => 'solicitud_distribuidor',
                    'entidad_id' => $solicitud->id,
                ]
            );
        }

        // Notificar a Gerencia General / Dirección Corporativa
        $gerentesGenerales = User::whereHas('rol', fn($q) => $q->whereIn('nombre', ['Gerente General', 'gerente general', 'Administrador', 'administrador']))
            ->where('activo', true)
            ->get();

        foreach ($gerentesGenerales as $gg) {
            \App\Models\NotificacionCajero::enviar(
                $gg->id,
                'solicitud_verificada_gerencia',
                'Solicitud Verificada (Corporativo) - ' . ($solicitud->sucursal?->nombre ?? ''),
                "El verificador {$verificador->name} completó la verificación presencial de {$solicitud->nombre_completo} (Dictamen: " . strtoupper($request->dictamen_verificador) . "). Pendiente de dictamen gerencial.",
                [
                    'solicitud_id' => $solicitud->id,
                    'url' => route('gerente-general.solicitudes.comparar', $solicitud),
                    'entidad_tipo' => 'solicitud_distribuidor',
                    'entidad_id' => $solicitud->id,
                ]
            );
        }

        return redirect()->route('verificador.dashboard')
            ->with('success', "La solicitud de {$solicitud->nombre_completo} ha sido verificada y turnada a la Gerencia con el expediente corregido para su dictamen comparativo.");
    }
}
