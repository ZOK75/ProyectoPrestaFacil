<?php

namespace App\Services;

use App\Models\NotificacionCajero;
use App\Models\User;

class NotificacionService
{
    /**
     * Envía notificación a un usuario específico (ej: Cajera)
     */
    public static function enviar(string $userId, string $tipo, string $titulo, string $mensaje, array $data = []): void
    {
        NotificacionCajero::enviar($userId, $tipo, $titulo, $mensaje, $data);
    }

    /**
     * Notifica a todos los autorizadores (Coordinador, Gerente de Sucursal y Gerente General) 
     * relevantes para una sucursal específica.
     */
    public static function notificarAutorizadores(string $sucursalId, string $tipo, string $titulo, string $mensaje, array $data = []): void
    {
        // Obtener autorizadores de la sucursal (Coordinador y Gerente de Sucursal)
        // y al Gerente General (que es global)
        $autorizadores = User::whereHas('rol', function ($q) {
            $q->whereIn('nombre', ['Coordinador', 'Gerente de Sucursal', 'Gerente General']);
        })->where(function ($q) use ($sucursalId) {
            // Coincide con la sucursal, o es global (sucursal_id null o Gerente General)
            $q->where('sucursal_id', $sucursalId)
              ->orWhereNull('sucursal_id')
              ->orWhereHas('rol', function($q2) {
                  $q2->where('nombre', 'Gerente General');
              });
        })->where('activo', true)->get();

        foreach ($autorizadores as $autorizador) {
            self::enviar($autorizador->id, $tipo, $titulo, $mensaje, $data);
        }
    }

    /**
     * Notifica a todos los cajeros activos de una sucursal específica.
     */
    public static function notificarCajerosSucursal(string $sucursalId, string $tipo, string $titulo, string $mensaje, array $data = []): void
    {
        $cajeros = User::whereHas('rol', function ($q) {
            $q->where('nombre', 'Cajero');
        })->where('sucursal_id', $sucursalId)
          ->where('activo', true)
          ->get();

        foreach ($cajeros as $cajero) {
            self::enviar($cajero->id, $tipo, $titulo, $mensaje, $data);
        }
    }
}
