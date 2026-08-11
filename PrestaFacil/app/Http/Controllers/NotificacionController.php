<?php

namespace App\Http\Controllers;

use App\Models\NotificacionCajero;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificacionController extends Controller
{
    private function operador(): ?User
    {
        return Auth::user();
    }

    /**
     * Listado de notificaciones del usuario autenticado.
     */
    public function index()
    {
        $operador = $this->operador();
        $notificaciones = NotificacionCajero::where('user_id', $operador->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('notificaciones.index', compact('notificaciones', 'operador'));
    }

    /**
     * Marca una notificación como leída.
     */
    public function marcarLeida(NotificacionCajero $notificacion)
    {
        if ($notificacion->user_id === Auth::id()) {
            $notificacion->update([
                'leida' => true,
                'leida_at' => now(),
            ]);
        }

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Notificación marcada como leída.');
    }

    /**
     * Marca todas las notificaciones del usuario como leídas.
     */
    public function marcarTodasLeidas()
    {
        NotificacionCajero::where('user_id', Auth::id())
            ->where('leida', false)
            ->update([
                'leida' => true,
                'leida_at' => now(),
            ]);

        return back()->with('success', 'Todas las notificaciones fueron marcadas como leídas.');
    }
}
