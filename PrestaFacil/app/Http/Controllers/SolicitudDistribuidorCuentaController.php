<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\SolicitudDistribuidor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SolicitudDistribuidorCuentaController extends Controller
{
    /**
     * Crear cuenta de usuario para un distribuidor cuya solicitud ha sido aprobada
     */
    public function crearCuenta(Request $request, SolicitudDistribuidor $solicitud)
    {
        $operador = Auth::user();

        // Validar permisos de gerente
        if (!$operador->esGerenteGeneral() && !$operador->esGerenteSucursal()) {
            abort(403, 'Acceso denegado: Se requieren permisos de Gerente.');
        }

        // Si es Gerente de Sucursal, validar sucursal de la solicitud
        if ($operador->esGerenteSucursal() && $solicitud->sucursal_id !== $operador->sucursal_id) {
            abort(403, 'Acceso denegado: Esta solicitud pertenece a otra sucursal.');
        }

        // Validar que la solicitud esté aprobada y no tenga cuenta aún
        if ($solicitud->estado !== 'aprobado') {
            return back()->with('error', 'La solicitud debe estar en estado Aprobado para crear una cuenta.');
        }

        if ($solicitud->user_id !== null) {
            return back()->with('error', 'Esta solicitud ya tiene una cuenta de usuario vinculada.');
        }

        // Validar entradas
        $request->validate([
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
        ], [
            'email.unique' => 'Este correo electrónico ya está en uso en el sistema.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
        ]);

        // Buscar rol de Distribuidor
        $rolDistribuidor = Rol::where('nombre', 'Distribuidor')
            ->orWhere('nombre', 'Distribuidora')
            ->first();

        if (!$rolDistribuidor) {
            return back()->with('error', 'No se encontró el rol de Distribuidor en el sistema. Contacta a soporte.');
        }

        // Crear el usuario distribuidor
        $user = User::create([
            'name' => $solicitud->nombre_completo,
            'email' => strtolower(trim($request->email)),
            'password' => Hash::make($request->password),
            'rol_id' => $rolDistribuidor->id,
            'sucursal_id' => $solicitud->sucursal_id,
            'categoria_distribuidor' => 'cobre',
            'limite_credito' => 20000.00,
            'activo' => true,
        ]);

        // Vincular el usuario a la solicitud
        $solicitud->update([
            'user_id' => $user->id,
        ]);

        // Notificar al coordinador que fue aprobada y se le crearon credenciales (Paso 13)
        \App\Models\NotificacionCajero::enviar(
            $solicitud->coordinador_id,
            'informativa',
            'Distribuidora Aprobada y Activada',
            "La solicitud de {$solicitud->nombre_completo} ha sido aprobada por Gerencia. Se ha creado su cuenta de acceso con el correo: {$request->email} y contraseña: {$request->password}. El distribuidor ya puede iniciar sesión."
        );

        $redirection = $operador->esGerenteGeneral() ? 'gerente-general.dashboard' : 'gerente-sucursal.dashboard';

        return redirect()->route($redirection)
            ->with('success', "Se ha creado la cuenta del distribuidor '{$solicitud->nombre_completo}' con el correo '{$request->email}'.");
    }
}
