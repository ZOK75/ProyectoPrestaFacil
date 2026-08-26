<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('limpiar:prestamos-cortes', function () {
    \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    
    \App\Models\PagoPrestamo::truncate();
    \App\Models\Conciliacion::truncate();
    \App\Models\RelacionCobranza::truncate();
    \App\Models\CanjePuntos::truncate();
    \App\Models\Prestamo::truncate();
    \App\Models\NotificacionCajero::truncate();
    
    \App\Models\User::query()->update([
        'multas' => 0.00,
        'puntos' => 0,
        'conteo_retrasos' => 0,
        'es_morosa' => false,
        'morosa_at' => null,
        'morosa_by_user_id' => null,
    ]);

    $config = \App\Models\Configuracion::first();
    if ($config) {
        $corteHoy = $config->fechaCorteCalculada();
        $limiteHoy = $config->fechaLimitePagoCalculada();
        $config->update([
            'dia_corte' => 10,
            'hora_corte' => '22:20:00',
            'dia_limite_pago' => 15,
            'hora_limite_pago' => '23:59:00',
            'fecha_corte' => $corteHoy,
            'fecha_limite_pago' => $limiteHoy,
        ]);
    }
    
    \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    
    $this->info('✅ Préstamos, pagos, cortes, conciliaciones y notificaciones eliminados correctamente. Distribuidoras y fechas de corte restablecidas a estado limpio.');
})->purpose('Limpia todos los préstamos, abonos y relaciones de cobranza sin hacer refresh.');
