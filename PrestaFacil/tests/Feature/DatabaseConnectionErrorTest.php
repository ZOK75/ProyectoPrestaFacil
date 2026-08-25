<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Route;
use PDOException;
use Tests\TestCase;

class DatabaseConnectionErrorTest extends TestCase
{
    public function test_pdo_exception_renders_custom_database_error_view_with_503(): void
    {
        Route::get('/_test/db-pdo-error', function () {
            throw new PDOException('SQLSTATE[HY000] [2002] Connection refused', 2002);
        });

        $response = $this->get('/_test/db-pdo-error');

        $response->assertStatus(503);
        $response->assertSee('Sin Conexión con la Base de Datos');
        $response->assertSee('Reintentar Conexión');
    }

    public function test_query_exception_with_connection_timeout_renders_database_view_with_503(): void
    {
        Route::get('/_test/db-timeout-error', function () {
            $pdo = new PDOException('SQLSTATE[HY000] [2002] Connection timed out', 2002);
            throw new QueryException('mysql', 'select * from users', [], $pdo);
        });

        $response = $this->get('/_test/db-timeout-error');

        $response->assertStatus(503);
        $response->assertSee('Sin Conexión con la Base de Datos');
    }

    public function test_connection_exception_returns_json_error_for_api_requests(): void
    {
        Route::get('/_test/db-json-error', function () {
            $pdo = new PDOException('SQLSTATE[HY000] [2002] Connection timed out', 2002);
            throw new QueryException('mysql', 'select * from users', [], $pdo);
        });

        $response = $this->getJson('/_test/db-json-error');

        $response->assertStatus(503);
        $response->assertJson([
            'error' => 'Error de conexión a la base de datos',
        ]);
        $this->assertStringContainsString('no responde o no está disponible', $response->json('message'));
    }

    public function test_pdo_exception_returns_json_error_for_ajax_request(): void
    {
        Route::get('/_test/db-ajax-pdo', function () {
            throw new PDOException('SQLSTATE[HY000] [2002] No route to host', 2002);
        });

        $response = $this->getJson('/_test/db-ajax-pdo');

        $response->assertStatus(503);
        $response->assertJsonStructure([
            'error',
            'message',
        ]);
    }
}
