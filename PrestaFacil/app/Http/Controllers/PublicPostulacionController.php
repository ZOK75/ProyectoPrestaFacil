<?php

namespace App\Http\Controllers;

use App\Models\SolicitudDistribuidor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PublicPostulacionController extends Controller
{
    /**
     * Mostrar formulario público de postulación
     */
    public function create(User $coordinador)
    {
        // Verificar que el usuario indicado realmente sea un coordinador
        if (!$coordinador->esCoordinador()) {
            abort(404, 'Coordinador no encontrado o inválido.');
        }

        // Cargar sucursal
        $coordinador->load('sucursal');

        return view('solicitudes-distribuidor.public_form', compact('coordinador'));
    }

    /**
     * Procesar y guardar la postulación
     */
    public function store(Request $request, User $coordinador)
    {
        if (!$coordinador->esCoordinador()) {
            abort(404, 'Coordinador no encontrado o inválido.');
        }

        $validator = Validator::make($request->all(), [
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
        ], [
            'curp.regex' => 'El formato del CURP es inválido.',
            'rfc.regex' => 'El formato del RFC es inválido.',
            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior al día de hoy.',
            'datos_casa.required' => 'La descripción de la casa es obligatoria.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Guardar solicitud
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
            'estado_republica' => $request->estado_republica,
            'ciudad' => $request->ciudad,
            'datos_familiares' => $request->datos_familiares, // Se guardará como JSON por cast en el modelo
            'datos_vehiculos' => $request->datos_vehiculos,
            'datos_casa' => $request->datos_casa,
            'referencias_laborales' => $request->referencias_laborales,
            'coordinador_id' => $coordinador->id,
            'sucursal_id' => $coordinador->sucursal_id,
            'estado' => 'en espera',
        ]);

        return redirect()->back()->with('success_postulacion', 'Tu solicitud de postulación ha sido enviada exitosamente. Un coordinador revisará tus datos e iniciará el proceso de verificación.');
    }
}
