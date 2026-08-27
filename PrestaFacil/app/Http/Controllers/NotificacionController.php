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

    /**
     * Endpoint API para transmisión y consulta de notificaciones en tiempo real.
     */
    public function livePoll()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['unread_count' => 0, 'notifications' => []]);
        }

        $unreadCount = $user->conteoNotificacionesSinLeer();

        $notifications = NotificacionCajero::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get()
            ->map(function ($n) {
                $url = $n->data['url'] ?? null;
                if ($n->tipo === 'corte_generado' && !$url) {
                    $url = route('prestamos.relacion-pdf');
                }
                return [
                    'id' => (string) $n->id,
                    'tipo' => $n->tipo,
                    'titulo' => $n->titulo,
                    'mensaje' => $n->mensaje,
                    'leida' => (bool) $n->leida,
                    'url' => $url,
                    'created_at_human' => $n->created_at ? $n->created_at->diffForHumans() : 'Hace un momento',
                    'created_at_full' => $n->created_at ? $n->created_at->format('d/m/Y H:i') : '',
                    'timestamp' => $n->created_at ? $n->created_at->timestamp : time(),
                ];
            });

        return response()->json([
            'unread_count' => $unreadCount,
            'notifications' => $notifications,
            'timestamp' => now()->timestamp,
        ]);
    }
}
