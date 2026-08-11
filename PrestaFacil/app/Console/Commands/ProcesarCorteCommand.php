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

        $this->info("Proceso completado con éxito:");
        $this->line("- Cortes notificados a distribuidoras: {$resultados['cortes_notificados']}");
        $this->line("- Multas aplicadas por fecha límite vencida: {$resultados['multas_aplicadas']}");

        return Command::SUCCESS;
    }
}
