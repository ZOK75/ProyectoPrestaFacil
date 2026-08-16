<?php

namespace Tests\Feature;

use App\Models\NotificacionCajero;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use Tests\TestCase;

class NotificacionRelacionPdfTest extends TestCase
{
    public function test_distribuidor_can_open_relacion_pdf_from_notification_without_connection_refused_error()
    {
        $distribuidorRol = Rol::firstOrCreate(['nombre' => 'Distribuidor']);
        $sucursal = Sucursal::firstOrCreate(['nombre' => 'Sucursal Norte'], ['activo' => true]);

        $distribuidor = User::factory()->create([
            'rol_id' => $distribuidorRol->id,
            'sucursal_id' => $sucursal->id,
            'activo' => true,
        ]);

        $notif = NotificacionCajero::create([
            'user_id' => $distribuidor->id,
            'tipo' => 'corte_generado',
            'titulo' => '🔔 Corte de Cobranza Generado',
            'mensaje' => 'Tu Relación de Cobranza ya está lista para descargar en PDF.',
            'data' => [
                'url' => 'http://localhost/prestamos-relacion-pdf', // formato con host que daba connection refused
            ],
            'leida' => false,
        ]);

        // 1. Ver la bandeja de notificaciones (debe resolver dinámicamente a la URL actual del entorno)
        $response = $this->actingAs($distribuidor)->get('/notificaciones');
        $response->assertStatus(200);
        $response->assertSee('Abrir PDF');
        $response->assertSee(route('prestamos.relacion-pdf'));

        // 2. Abrir directamente la relación PDF
        $responsePdf = $this->actingAs($distribuidor)->get('/prestamos-relacion-pdf');
        $responsePdf->assertStatus(200);
        $responsePdf->assertSee('Total a PAGAR');
    }
}
