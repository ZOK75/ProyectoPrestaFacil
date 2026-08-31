<?php

namespace App\Console\Commands;

use App\Services\CorteCobranzaService;
use Illuminate\Console\Command;

class ProcesarCorteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'corte:procesar';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica la hora del servidor, procesa el corte automático, envía notificaciones a distribuidoras y aplica multas por vencimiento.';

    /**
     * Execute the console command.
     */
    public function handle(CorteCobranzaService $corteService): int
    {
        $this->info('Iniciando verificación y procesamiento de corte y vencimientos...');

        $resultados = $corteService->verificarYProcesarCortesYVencimientos();
        $config = \App\Models\Configuracion::actual();
        $corteStr = $config->fecha_corte ? $config->fecha_corte->format('d/m/Y H:i:s') : 'N/A';
        $limiteStr = $config->fecha_limite_pago ? $config->fecha_limite_pago->format('d/m/Y') : 'N/A';

        if ($resultados['cortes_notificados'] > 0 || $resultados['multas_aplicadas'] > 0) {
            \App\Services\AuditService::registrar(
                'PROCESAMIENTO_CORTE_AUTOMATICO',
                "Corte automático ejecutado por el sistema (Fecha de Corte: {$corteStr} | Fecha Límite de Pago: {$limiteStr}) - {$resultados['cortes_notificados']} relaciones de corte procesadas, {$resultados['multas_aplicadas']} multas aplicadas",
                [
                    'entidad_tipo' => 'configuraciones',
                    'fecha_corte' => $corteStr,
                    'fecha_limite_pago' => $limiteStr,
                    'cortes_notificados' => $resultados['cortes_notificados'],
                    'multas_aplicadas' => $resultados['multas_aplicadas'],
                    'puntos_otorgados' => $resultados['puntos_otorgados'],
                ]
            );
        }

        $this->info("Proceso completado con éxito:");
        $this->line("- Cortes notificados a distribuidoras: {$resultados['cortes_notificados']}");
        $this->line("- Multas aplicadas por fecha límite vencida: {$resultados['multas_aplicadas']}");

        return Command::SUCCESS;
    }
}
