<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        DB::table('users')->insert([
            
            [
                'name'=> 'Leonardo Mendez',
                'email' => 'leomendez@misvales.com',
                'password' => Hash::make('ContraseñaMI$vAL3S1234'),
                'rol_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),

            ]
            
        ]);

    }
}
