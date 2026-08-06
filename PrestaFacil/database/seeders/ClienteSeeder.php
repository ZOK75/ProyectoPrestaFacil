<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClienteSeeder extends Seeder
{
    public function run(): void
    {
        $distribuidor = User::whereHas('rol', fn ($q) => $q->whereIn('nombre', ['Distribuidor', 'Distribuidora']))
            ->first() ?? User::first();

        $clientes = [
            [
                'nombre' => 'Guadalupe Hernández Vázquez',
                'curp' => 'HEVG850412MDFRRN01',
                'rfc' => 'HEVG850412AB1',
                'fecha_nacimiento' => '1985-04-12',
                'lugar_nacimiento' => 'Ciudad de México',
                'calle' => 'Av. Insurgentes Sur #450 Int 302',
                'colonia' => 'Roma Sur',
                'codigo_postal' => '06760',
                'ciudad' => 'Cuauhtémoc',
                'estado' => 'CDMX',
                'path_ine_pdf' => null,
                'path_comprobante_pdf' => null,
                'activo' => true,
                'created_by_user_id' => $distribuidor?->id,
            ],
            [
                'nombre' => 'Fernando Castro Ruiz',
                'curp' => 'CARF901125HNTNN02',
                'rfc' => 'CARF901125CD2',
                'fecha_nacimiento' => '1990-11-25',
                'lugar_nacimiento' => 'Monterrey, Nuevo León',
                'calle' => 'Calle Benito Juárez #120',
                'colonia' => 'Centro',
                'codigo_postal' => '64000',
                'ciudad' => 'Monterrey',
                'estado' => 'Nuevo León',
                'path_ine_pdf' => null,
                'path_comprobante_pdf' => null,
                'activo' => true,
                'created_by_user_id' => $distribuidor?->id,
            ],
            [
                'nombre' => 'Adriana Morales Peña (Inactiva)',
                'curp' => 'MOPA820805MGRRR03',
                'rfc' => 'MOPA820805EF3',
                'fecha_nacimiento' => '1982-08-05',
                'lugar_nacimiento' => 'Guadalajara, Jalisco',
                'calle' => 'Calzada del Federalismo #340',
                'colonia' => 'Moderno',
                'codigo_postal' => '44190',
                'ciudad' => 'Guadalajara',
                'estado' => 'Jalisco',
                'path_ine_pdf' => null,
                'path_comprobante_pdf' => null,
                'activo' => false,
                'desactivado_at' => now()->subDays(2),
                'created_by_user_id' => $distribuidor?->id,
                'desactivado_by_user_id' => $distribuidor?->id,
            ],
        ];

        foreach ($clientes as $cliente) {
            Cliente::updateOrCreate(['curp' => $cliente['curp']], $cliente);
        }
    }
}
