<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

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
                'nombre' => 'Gerente General',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => 2,
                'nombre' => 'Gerente Sucursal',
                'created_at' => now(),
                'updated_at' => now(),
            ]
          
        ]);

       
        DB::table('users')->insertOrIgnore([
            [
                'name' => 'Leonardo Mendez',
                'email' => 'leomendez@misvales.com',
                'password' => Hash::make('ContraseñaMI$vAL3S1234'),
                'rol_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'Antonio Lopez',
                'email' => 'antoniolopez@misvales.com',
                'password' => Hash::make('ContraseñaMI$vAL3S1234'),
                'rol_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        Schema::enableForeignKeyConstraints();
        
    }
}
