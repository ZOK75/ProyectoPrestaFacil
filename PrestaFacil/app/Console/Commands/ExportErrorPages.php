<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

class ExportErrorPages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'error:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exporta las vistas de errores (500, 502, 503, 504) a HTML estático en la carpeta public para ser servidas por Nginx.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $errors = [500, 502, 503, 504];
        
        $this->info('Exportando páginas de error estáticas...');

        if (!File::exists(public_path('build/manifest.json'))) {
            $this->warn('ADVERTENCIA: No se encontró public/build/manifest.json.');
            $this->warn('Asegúrate de ejecutar "npm run build" antes de este comando para que los estilos se apliquen correctamente.');
        }

        foreach ($errors as $error) {
            $viewName = "errors.{$error}";
            
            // Verificamos si existe la vista (la 503 puede llamarse database o 503)
            if (!View::exists($viewName)) {
                if ($error == 503 && View::exists('errors.database')) {
                    $viewName = 'errors.database';
                } else {
                    $this->warn("Vista para error {$error} no encontrada, omitiendo.");
                    continue;
                }
            }

            try {
                // Instanciamos un mensaje vacío
                $html = view($viewName, ['exception' => new \Exception('', $error)])->render();
                
                $path = public_path("{$error}.html");
                File::put($path, $html);
                
                $this->info("Exportado: public/{$error}.html");
            } catch (\Exception $e) {
                $this->error("No se pudo exportar el error {$error}: " . $e->getMessage());
            }
        }

        $this->info('Exportación completada.');
        $this->info('Ahora puedes configurar Nginx (error_page) apuntando a /500.html, /502.html, etc.');
    }
}
