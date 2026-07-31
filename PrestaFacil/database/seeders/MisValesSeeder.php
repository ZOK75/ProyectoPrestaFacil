<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class MisValesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {

       DB::table('roles')->insertOrIgnore([
            [
                'id' => 1,
                'nombre' => 'Administrador',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Puedes agregar los demás roles aquí en el mismo formato
        ]);

        // 2. Insertar usuarios en la tabla 'users'
        DB::table('users')->insertOrIgnore([
            [
                'name' => 'Leonardo Mendez',
                'email' => 'leomendez@misvales.com',
                'password' => Hash::make('ContraseñaMI$vAL3S1234'),
                'rol_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        
    }
}
