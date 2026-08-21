<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\NotificacionCajero;
use App\Models\Prestamo;
use App\Models\ProductoVale;
use App\Models\Rol;
use App\Models\SolicitudTransferencia;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoordinadorRoleFlowTest extends TestCase
{
    use RefreshDatabase;

    private Rol $rolCoordinador;
    private Rol $rolDistribuidor;
    private Rol $rolGerenteSucursal;
    private Rol $rolGerenteGeneral;
    private Sucursal $sucursalNorte;
    private Sucursal $sucursalSur;
    private User $coordinadorEmisor;
    private User $coordinadorReceptor;
    private User $gerenteSur;
    private User $gerenteGeneral;
    private User $distribuidora;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rolCoordinador = Rol::firstOrCreate(['nombre' => 'Coordinador'], ['id' => '11111111-1111-1111-1111-111111111115']);
        $this->rolDistribuidor = Rol::firstOrCreate(['nombre' => 'Distribuidor'], ['id' => '11111111-1111-1111-1111-111111111114']);
        $this->rolGerenteSucursal = Rol::firstOrCreate(['nombre' => 'Gerente de Sucursal'], ['id' => '11111111-1111-1111-1111-111111111112']);
        $this->rolGerenteGeneral = Rol::firstOrCreate(['nombre' => 'Gerente General'], ['id' => '11111111-1111-1111-1111-111111111111']);

        $this->sucursalNorte = Sucursal::create(['nombre' => 'Sucursal Norte', 'direccion' => 'Av Norte 1', 'activo' => true]);
        $this->sucursalSur = Sucursal::create(['nombre' => 'Sucursal Sur', 'direccion' => 'Av Sur 2', 'activo' => true]);

        $this->coordinadorEmisor = User::create([
            'name' => 'Coordinador Carlos Emisor',
            'email' => 'carlos.emisor@prestafacil.com',
            'password' => bcrypt('password'),
            'rol_id' => $this->rolCoordinador->id,
            'sucursal_id' => $this->sucursalNorte->id,
            'activo' => true,
        ]);

        $this->coordinadorReceptor = User::create([
            'name' => 'Coordinadora Laura Receptora',
            'email' => 'laura.receptora@prestafacil.com',
            'password' => bcrypt('password'),
            'rol_id' => $this->rolCoordinador->id,
            'sucursal_id' => $this->sucursalSur->id,
            'activo' => true,
        ]);

        $this->gerenteSur = User::create([
            'name' => 'Gerente Gabriel Sur',
            'email' => 'gabriel.sur@prestafacil.com',
            'password' => bcrypt('password'),
            'rol_id' => $this->rolGerenteSucursal->id,
            'sucursal_id' => $this->sucursalSur->id,
            'activo' => true,
        ]);

        $this->gerenteGeneral = User::create([
            'name' => 'Gerente General Gonzalo',
            'email' => 'gonzalo.general@prestafacil.com',
            'password' => bcrypt('password'),
            'rol_id' => $this->rolGerenteGeneral->id,
            'sucursal_id' => $this->sucursalNorte->id,
            'activo' => true,
        ]);

        $this->distribuidora = User::create([
            'name' => 'Distribuidora Diana',
            'email' => 'diana.dist@prestafacil.com',
            'password' => bcrypt('password'),
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursalNorte->id,
            'coordinador_id' => $this->coordinadorEmisor->id,
            'limite_credito' => 30000,
            'categoria_distribuidor' => 'Oro',
            'activo' => true,
        ]);
    }

    public function test_login_as_coordinador_redirects_to_coordinador_dashboard(): void
    {
        $response = $this->post('/login', [
            'email' => $this->coordinadorEmisor->email,
            'password' => 'password',
        ]);

        $response->assertStatus(302);
    }

    public function test_root_and_dashboard_redirect_coordinador_to_coordinador_dashboard(): void
    {
        $this->actingAs($this->coordinadorEmisor);

        $this->get('/')->assertRedirect(route('coordinador.dashboard'));
        $this->get('/dashboard')->assertRedirect(route('coordinador.dashboard'));
    }

    public function test_coordinador_navbar_shows_inicio_and_prestamos(): void
    {
        $this->actingAs($this->coordinadorEmisor);

        $response = $this->get(route('coordinador.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Inicio');
        $response->assertSee('Préstamos');
        // Debe haber quitado el enlace de postulación pública
        $response->assertDontSee('Enlace de Postulación Pública');
        $response->assertDontSee('Copiar Enlace');
    }

    public function test_coordinador_prestamos_view_displays_assigned_distribuidoras_and_active_loans(): void
    {
        $this->actingAs($this->coordinadorEmisor);

        $cliente = Cliente::create([
            'nombre' => 'María Pérez Cliente',
            'fecha_nacimiento' => '1990-01-01',
            'lugar_nacimiento' => 'Monterrey, N.L.',
            'curp' => 'PERM900101HDFRRN01',
            'calle' => 'Calle 10',
            'colonia' => 'Centro',
            'codigo_postal' => '64000',
            'ciudad' => 'Monterrey',
            'estado' => 'Nuevo León',
            'activo' => true,
        ]);

        $vale = ProductoVale::create([
            'clave' => 'VALE-5000',
            'nombre' => 'Vale Quincenal $5,000',
            'monto_prestamo' => 5000,
            'plazo_quincenas' => 12,
            'tasa_interes_quincenal' => 3.5,
            'activo' => true,
        ]);

        $prestamo = Prestamo::create([
            'referencia' => 'REF-VALE-2026-TEST-001',
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $vale->id,
            'tipo' => 'vale',
            'monto_prestamo' => 5000,
            'cuota_quincenal' => 500,
            'pagos_totales' => 12,
            'pagos_realizados' => 2,
            'monto_total_pagar' => 6000,
            'adeudo_pendiente' => 5000,
            'pagos_recibidos' => 1000,
            'multas' => 0,
            'estado' => 'activo',
            'activo' => true,
            'created_by_user_id' => $this->distribuidora->id,
        ]);

        $response = $this->get(route('coordinador.prestamos'));
        $response->assertStatus(200);
        $response->assertSee('REF-VALE-2026-TEST-001');
        $response->assertSee('Distribuidora Diana');
        $response->assertSee('María Pérez Cliente');
        $response->assertSee('$5,000.00');
    }

    public function test_full_distribuidora_transfer_flow_between_coordinators_and_branch_manager(): void
    {
        // 1. Coordinador Emisor emite la solicitud de traspaso
        $this->actingAs($this->coordinadorEmisor);

        $responseTransfer = $this->post(route('coordinador.distribuidores.solicitar-transferencia', $this->distribuidora), [
            'coordinador_receptor_id' => $this->coordinadorReceptor->id,
            'motivo' => 'Cambio de domicilio de la distribuidora a la zona Sur.',
        ]);

        $responseTransfer->assertRedirect(route('coordinador.dashboard'));
        $responseTransfer->assertSessionHas('success');

        $transferencia = SolicitudTransferencia::where('distribuidor_id', $this->distribuidora->id)->first();
        $this->assertNotNull($transferencia);
        $this->assertEquals('pendiente_coordinador', $transferencia->estado);
        $this->assertEquals($this->coordinadorEmisor->id, $transferencia->coordinador_emisor_id);
        $this->assertEquals($this->coordinadorReceptor->id, $transferencia->coordinador_receptor_id);
        $this->assertEquals($this->sucursalSur->id, $transferencia->sucursal_destino_id);

        // Verificar que el Coordinador Receptor recibió notificación
        $this->assertTrue(
            NotificacionCajero::where('user_id', $this->coordinadorReceptor->id)
                ->where('tipo', 'transferencia_distribuidora')
                ->exists()
        );

        // 2. Coordinador Receptor revisa la solicitud y la acepta
        $this->actingAs($this->coordinadorReceptor);

        $responseReview = $this->get(route('coordinador.transferencias.revisar', $transferencia));
        $responseReview->assertStatus(200);
        $responseReview->assertSee('Distribuidora Diana');
        $responseReview->assertSee('Cambio de domicilio de la distribuidora a la zona Sur.');

        $responseDecisionReceptor = $this->post(route('coordinador.transferencias.decidir', $transferencia), [
            'accion' => 'aceptar',
            'observaciones' => 'Conozco a la distribuidora y tengo cupo en mi equipo de la sucursal Sur.',
        ]);

        $responseDecisionReceptor->assertRedirect(route('coordinador.dashboard'));

        $transferencia->refresh();
        $this->assertEquals('pendiente_gerente', $transferencia->estado);

        // Verificar que el Gerente de Sucursal Receptora recibió notificación
        $this->assertTrue(
            NotificacionCajero::where('user_id', $this->gerenteSur->id)
                ->where('tipo', 'transferencia_requiere_autorizacion')
                ->exists()
        );

        // 3. Gerente de Sucursal Receptora revisa y aprueba el traspaso
        $this->actingAs($this->gerenteSur);

        $responseGerenteReview = $this->get(route('gerente-sucursal.transferencias.revisar', $transferencia));
        $responseGerenteReview->assertStatus(200);
        $responseGerenteReview->assertSee('Distribuidora Diana');

        $responseGerenteDecision = $this->post(route('gerente-sucursal.transferencias.decidir', $transferencia), [
            'accion' => 'aprobar',
            'observaciones_gerente' => 'Aprobado formalmente el cambio de sucursal y coordinación.',
        ]);

        $responseGerenteDecision->assertRedirect(route('gerente-sucursal.dashboard'));

        $transferencia->refresh();
        $this->assertEquals('aprobada', $transferencia->estado);
        $this->assertEquals($this->gerenteSur->id, $transferencia->gerente_id);
        $this->assertNotNull($transferencia->resolved_at);

        // Verificar que la Distribuidora fue reasignada efectivamente
        $this->distribuidora->refresh();
        $this->assertEquals($this->coordinadorReceptor->id, $this->distribuidora->coordinador_id);
        $this->assertEquals($this->sucursalSur->id, $this->distribuidora->sucursal_id);

        // Verificar que se notificó a la distribuidora, emisor y receptor
        $this->assertTrue(
            NotificacionCajero::where('user_id', $this->distribuidora->id)
                ->where('tipo', 'cambio_coordinador_asignado')
                ->exists()
        );
        $this->assertTrue(
            NotificacionCajero::where('user_id', $this->coordinadorEmisor->id)
                ->where('tipo', 'transferencia_completada')
                ->exists()
        );
        $this->assertTrue(
            NotificacionCajero::where('user_id', $this->coordinadorReceptor->id)
                ->where('tipo', 'transferencia_completada')
                ->exists()
        );
    }

    public function test_solicitud_distribuidora_validations_and_spanish_messages(): void
    {
        $this->actingAs($this->coordinadorEmisor);

        // 1. Enviar formulario vacío
        $response = $this->post(route('coordinador.solicitudes.store'), []);
        $response->assertSessionHasErrors([
            'nombres' => 'El nombre o nombres de la distribuidora son obligatorios.',
            'apellidos' => 'Los apellidos de la distribuidora son obligatorios.',
            'telefono' => 'El número de teléfono celular es obligatorio.',
            'fecha_nacimiento' => 'La fecha de nacimiento es obligatoria.',
            'curp' => 'La clave CURP es obligatoria.',
            'rfc' => 'El RFC es obligatorio.',
            'calle' => 'La calle y número de domicilio son obligatorios.',
            'colonia' => 'La colonia es obligatoria.',
            'codigo_postal' => 'El código postal es obligatorio.',
            'ciudad' => 'La ciudad o municipio es obligatorio.',
            'estado_republica' => 'El estado de la república es obligatorio.',
            'datos_casa' => 'La descripción y características de la casa son obligatorias.',
        ]);

        // 2. Enviar datos con formato inválido (teléfono < 10 dígitos, CURP inválido, CP inválido)
        $responseInvalid = $this->post(route('coordinador.solicitudes.store'), [
            'nombres' => 'A',
            'apellidos' => 'B',
            'telefono' => '12345',
            'fecha_nacimiento' => '2099-01-01',
            'curp' => 'CURPINVALIDA',
            'rfc' => 'RFCINVALIDO',
            'calle' => 'C',
            'colonia' => 'C',
            'codigo_postal' => '12',
            'ciudad' => 'C',
            'estado_republica' => 'C',
            'datos_casa' => 'Casa',
        ]);

        $responseInvalid->assertSessionHasErrors([
            'telefono' => 'El número de teléfono debe contener exactamente 10 dígitos numéricos.',
            'curp' => 'El formato de la CURP es inválido (ejemplo: ABCD000000HDFRRN01).',
            'codigo_postal' => 'El código postal debe contener exactamente 5 dígitos numéricos.',
            'fecha_nacimiento' => 'La fecha de nacimiento debe ser anterior a la fecha de hoy.',
        ]);

        // 3. Enviar datos válidos
        $responseValid = $this->post(route('coordinador.solicitudes.store'), [
            'nombres' => 'Verónica',
            'apellidos' => 'Soto Rivera',
            'telefono' => '8119876543',
            'fecha_nacimiento' => '1992-05-15',
            'curp' => 'SORV920515MDFTRN02',
            'rfc' => 'SORV920515XXX',
            'lugar_nacimiento' => 'Monterrey, NL',
            'calle' => 'Av. Hidalgo 1234',
            'colonia' => 'Obispado',
            'codigo_postal' => '64060',
            'ciudad' => 'Monterrey',
            'estado_republica' => 'Nuevo León',
            'datos_casa' => 'Casa propia de 2 pisos, color blanco con portón negro.',
            'datos_vehiculos' => 'Chevrolet Aveo 2020',
            'referencias_laborales' => 'Tienda de abarrotes familiar desde 2018.',
            'datos_familiares' => [
                ['nombre' => 'Roberto Soto', 'parentesco' => 'Esposa/o', 'contacto' => '8110001122']
            ],
        ]);

        $responseValid->assertRedirect(route('coordinador.solicitudes.index'));
        $responseValid->assertSessionHas('success');

        $this->assertDatabaseHas('solicitudes_distribuidores', [
            'curp' => 'SORV920515MDFTRN02',
            'nombres' => 'Verónica',
            'telefono' => '8119876543',
            'estado' => 'en espera',
        ]);
    }

    public function test_transfer_notification_does_not_say_abrir_pdf(): void
    {
        // Crear notificación de traspaso para el coordinador receptor
        NotificacionCajero::enviar(
            $this->coordinadorReceptor->id,
            'transferencia_distribuidora',
            'Nueva Solicitud de Transferencia de Distribuidora',
            'El coordinador Carlos te propone transferir la distribuidora Diana.',
            [
                'url' => '/coordinador/transferencias/123/revisar',
                'entidad_tipo' => 'solicitud_transferencia',
                'entidad_id' => '123',
            ]
        );

        $this->actingAs($this->coordinadorReceptor);

        $response = $this->get(route('notificaciones.index'));
        $response->assertStatus(200);
        $response->assertSee('Nueva Solicitud de Transferencia de Distribuidora');
        $response->assertSee('Revisar Traspaso');
        $response->assertDontSee('Abrir PDF');
    }

    public function test_transfer_can_be_approved_by_gerente_general_and_notifies_gerente_sucursal(): void
    {
        // 1. Coordinador Emisor emite la solicitud de traspaso
        $this->actingAs($this->coordinadorEmisor);
        $this->post(route('coordinador.distribuidores.solicitar-transferencia', $this->distribuidora), [
            'coordinador_receptor_id' => $this->coordinadorReceptor->id,
            'motivo' => 'Reasignación de zona a solicitud de la distribuidora.',
        ]);

        $transferencia = SolicitudTransferencia::where('distribuidor_id', $this->distribuidora->id)->first();

        // 2. Coordinador Receptor la acepta -> pasa a pendiente_gerente
        $this->actingAs($this->coordinadorReceptor);
        $this->post(route('coordinador.transferencias.decidir', $transferencia), [
            'accion' => 'aceptar',
            'observaciones' => 'Aceptada por coordinación receptora.',
        ]);

        $transferencia->refresh();
        $this->assertEquals('pendiente_gerente', $transferencia->estado);

        // Verificar que AMBOS (Gerente Sucursal y Gerente General) recibieron notificación
        $this->assertTrue(
            NotificacionCajero::where('user_id', $this->gerenteSur->id)
                ->where('tipo', 'transferencia_requiere_autorizacion')
                ->exists()
        );
        $this->assertTrue(
            NotificacionCajero::where('user_id', $this->gerenteGeneral->id)
                ->where('tipo', 'transferencia_requiere_autorizacion')
                ->exists()
        );

        // 3. El Gerente General revisa y aprueba directamente la solicitud
        $this->actingAs($this->gerenteGeneral);

        $responseReview = $this->get(route('gerente-sucursal.transferencias.revisar', $transferencia));
        $responseReview->assertStatus(200);
        $responseReview->assertSee('Distribuidora Diana');
        $responseReview->assertSee('Gerencia General Corporativa');

        $responseDecidir = $this->post(route('gerente-sucursal.transferencias.decidir', $transferencia), [
            'accion' => 'aprobar',
            'observaciones_gerente' => 'Aprobado directamente por la Dirección General.',
        ]);

        $responseDecidir->assertRedirect(route('gerente-general.dashboard'));

        $transferencia->refresh();
        $this->assertEquals('aprobada', $transferencia->estado);
        $this->assertEquals($this->gerenteGeneral->id, $transferencia->gerente_id);

        // Verificar que la distribuidora fue reasignada al receptor y a la sucursal sur
        $this->distribuidora->refresh();
        $this->assertEquals($this->coordinadorReceptor->id, $this->distribuidora->coordinador_id);
        $this->assertEquals($this->sucursalSur->id, $this->distribuidora->sucursal_id);

        // VERIFICAR REGLA CRUCIAL: Se le notificó al Gerente de la sucursal receptora
        $this->assertTrue(
            NotificacionCajero::where('user_id', $this->gerenteSur->id)
                ->where('tipo', 'transferencia_completada')
                ->where('titulo', 'Traspaso de Distribuidora Aprobado por Gerencia General')
                ->exists()
        );
    }

    public function test_verificador_can_correct_application_and_emit_dictamen(): void
    {
        $rolVerificador = Rol::firstOrCreate(['nombre' => 'Verificador'], ['id' => '11111111-1111-1111-1111-111111111116']);
        $verificador = User::create([
            'name' => 'Verificador Víctor',
            'email' => 'victor.verificador@prestafacil.com',
            'password' => bcrypt('password'),
            'rol_id' => $rolVerificador->id,
            'sucursal_id' => $this->sucursalNorte->id,
            'activo' => true,
        ]);

        $solicitud = \App\Models\SolicitudDistribuidor::create([
            'coordinador_id' => $this->coordinadorEmisor->id,
            'sucursal_id' => $this->sucursalNorte->id,
            'nombres' => 'Patricia',
            'apellidos' => 'Morales Gómez',
            'telefono' => '8111223344',
            'fecha_nacimiento' => '1991-03-10',
            'lugar_nacimiento' => 'Guadalajara, Jal.',
            'curp' => 'MOGP910310MDFRRN09',
            'rfc' => 'MOGP910310ABC',
            'calle' => 'Calle Falsa 123',
            'colonia' => 'Mitras Centro',
            'codigo_postal' => '64020',
            'ciudad' => 'Monterrey',
            'estado_republica' => 'Nuevo León',
            'datos_casa' => 'Casa azul 1 piso',
            'datos_vehiculos' => 'Nissan Versa 2019',
            'referencias_laborales' => 'Comercio de calzado',
            'datos_familiares' => [
                ['nombre' => 'Jorge Morales', 'parentesco' => 'Hermano/a', 'contacto' => '8119998877']
            ],
            'estado' => 'en espera de verificacion',
            'dictamen_verificador' => 'pendiente',
        ]);

        $this->actingAs($verificador);

        // Verificador accede a la vista de verificación
        $responseShow = $this->get(route('verificador.solicitudes.show', $solicitud));
        $responseShow->assertStatus(200);
        $responseShow->assertSee('Patricia Morales Gómez');
        $responseShow->assertSee('Calle Falsa 123');

        // Verificador corrige la calle y teléfono durante la visita física y emite dictamen aceptado
        $responseProcesar = $this->post(route('verificador.solicitudes.procesar', $solicitud), [
            'nombres' => 'Patricia',
            'apellidos' => 'Morales Gómez',
            'telefono' => '8119990011', // Teléfono corregido
            'fecha_nacimiento' => '1991-03-10',
            'lugar_nacimiento' => 'Guadalajara, Jal.',
            'curp' => 'MOGP910310MDFRRN09',
            'rfc' => 'MOGP910310ABC',
            'calle' => 'Av. Real de Cumbres 456', // Calle corregida
            'colonia' => 'Cumbres 2do Sector',
            'codigo_postal' => '64610',
            'ciudad' => 'Monterrey',
            'estado_republica' => 'Nuevo León',
            'datos_casa' => 'Casa propia de 2 pisos, comprobante de agua a su nombre verificado.',
            'datos_vehiculos' => 'Nissan Versa 2019',
            'referencias_laborales' => 'Comercio formal de calzado verificado.',
            'datos_familiares' => [
                ['nombre' => 'Jorge Morales', 'parentesco' => 'Hermano/a', 'contacto' => '8119998877']
            ],
            'dictamen_verificador' => 'aceptado',
            'comentarios_verificador' => 'Visita domiciliaria exitosa. La solicitante habita en el domicilio y los documentos son legítimos.',
        ]);

        $responseProcesar->assertRedirect(route('verificador.dashboard'));
        $responseProcesar->assertSessionHas('success');

        $solicitud->refresh();
        $this->assertEquals('en espera', $solicitud->estado);
        $this->assertEquals('aceptado', $solicitud->dictamen_verificador);
        $this->assertEquals($verificador->id, $solicitud->verificador_id);
        $this->assertEquals('Av. Real de Cumbres 456', $solicitud->getDatoVerificado('calle'));
        $this->assertTrue($solicitud->isCampoModificado('calle'));
        $this->assertTrue($solicitud->isCampoModificado('telefono'));
        $this->assertFalse($solicitud->isCampoModificado('curp'));

        // Se notificó a Gerente General y Gerente de Sucursal
        $this->assertTrue(
            NotificacionCajero::where('user_id', $this->gerenteGeneral->id)
                ->where('tipo', 'solicitud_verificada_gerencia')
                ->exists()
        );
    }

    public function test_gerente_sees_comparison_and_approves_creating_user_with_curp_as_password(): void
    {
        $gerenteNorte = User::create([
            'name' => 'Gerente Gloria Norte',
            'email' => 'gloria.norte@prestafacil.com',
            'password' => bcrypt('password'),
            'rol_id' => $this->rolGerenteSucursal->id,
            'sucursal_id' => $this->sucursalNorte->id,
            'activo' => true,
        ]);

        $solicitud = \App\Models\SolicitudDistribuidor::create([
            'coordinador_id' => $this->coordinadorEmisor->id,
            'sucursal_id' => $this->sucursalNorte->id,
            'nombres' => 'Claudia',
            'apellidos' => 'Navarro Ruiz',
            'telefono' => '8115554433',
            'fecha_nacimiento' => '1988-08-20',
            'lugar_nacimiento' => 'Monterrey, NL',
            'curp' => 'NARC880820MDFTRN05',
            'rfc' => 'NARC880820XXX',
            'calle' => 'Calle Antigua 100',
            'colonia' => 'Industrial',
            'codigo_postal' => '64440',
            'ciudad' => 'Monterrey',
            'estado_republica' => 'Nuevo León',
            'datos_casa' => 'Casa 1 planta',
            'estado' => 'en espera',
            'dictamen_verificador' => 'aceptado',
            'comentarios_verificador' => 'Documentación física e identificación validadas.',
            'datos_verificacion' => [
                'nombres' => 'Claudia',
                'apellidos' => 'Navarro Ruiz',
                'telefono' => '8115554433',
                'curp' => 'NARC880820MDFTRN05',
                'calle' => 'Calle Nueva 200', // Modificado
                'colonia' => 'Industrial',
                'codigo_postal' => '64440',
                'ciudad' => 'Monterrey',
                'estado_republica' => 'Nuevo León',
                'datos_casa' => 'Casa 1 planta verificada',
            ]
        ]);

        $this->actingAs($gerenteNorte);

        // 1. Gerente abre la vista comparativa
        $responseComparar = $this->get(route('gerente-sucursal.solicitudes.comparar', $solicitud));
        $responseComparar->assertStatus(200);
        $responseComparar->assertSee('Claudia Navarro Ruiz');
        $responseComparar->assertSee('Calle Antigua 100'); // Original
        $responseComparar->assertSee('Calle Nueva 200');   // Verificado
        $responseComparar->assertSee('Misma que su CURP');

        // 2. Gerente aprueba la solicitud asignando correo y límite de crédito
        $responseAprobar = $this->post(route('gerente.solicitudes.decidir-con-cuenta', $solicitud), [
            'accion' => 'aprobar',
            'email' => 'claudia.navarro@prestafacil.com',
            'limite_credito' => 35000,
            'observaciones_resolucion' => 'Candidata excelente con perfil acreditado.',
        ]);

        $responseAprobar->assertRedirect(route('gerente-sucursal.dashboard'));
        $responseAprobar->assertSessionHas('success');

        $solicitud->refresh();
        $this->assertEquals('aprobado', $solicitud->estado);
        $this->assertNotNull($solicitud->user_id);

        // Verificar que el usuario fue creado correctamente
        $newUser = User::find($solicitud->user_id);
        $this->assertNotNull($newUser);
        $this->assertEquals('Claudia Navarro Ruiz', $newUser->name);
        $this->assertEquals('claudia.navarro@prestafacil.com', $newUser->email);
        $this->assertEquals($this->rolDistribuidor->id, $newUser->rol_id);
        $this->assertEquals($this->sucursalNorte->id, $newUser->sucursal_id); // Misma sucursal del coordinador
        $this->assertEquals($this->coordinadorEmisor->id, $newUser->coordinador_id);
        $this->assertEquals(35000, $newUser->limite_credito);
        $this->assertTrue($newUser->activo);

        // REGLA CRUCIAL: La contraseña es exactamente igual a la CURP
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NARC880820MDFTRN05', $newUser->password));

        // Se notificó al Coordinador con las credenciales
        $this->assertTrue(
            NotificacionCajero::where('user_id', $this->coordinadorEmisor->id)
                ->where('tipo', 'solicitud_aprobada')
                ->where('mensaje', 'like', '%NARC880820MDFTRN05%')
                ->exists()
        );
    }
}
