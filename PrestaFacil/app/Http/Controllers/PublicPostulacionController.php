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
            'nombres' => 'required|string|min:2|max:255',
            'apellidos' => 'required|string|min:2|max:255',
            'telefono' => 'required|string|regex:/^[0-9]{10}$/',
            'fecha_nacimiento' => 'required|date|before:today',
            'curp' => ['required', 'string', 'size:18', 'regex:/^[A-Z]{4}\d{6}[HM][A-Z]{2}[B-DF-HJ-NP-TV-Z]{3}[A-Z0-9]\d$/i'],
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
            'nombres.required' => 'El nombre o nombres son obligatorios.',
            'nombres.min' => 'El campo nombres debe tener al menos :min caracteres.',
            'nombres.max' => 'El campo nombres no puede superar los :max caracteres.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'apellidos.min' => 'El campo apellidos debe tener al menos :min caracteres.',
            'apellidos.max' => 'El campo apellidos no puede superar los :max caracteres.',
            'telefono.required' => 'El número de teléfono es obligatorio.',
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
