<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperUsuarioSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'stevenAlfaro@gmail.com'],
            [
                'name' => ' Steven Omar Alfaro Villanueva',
                'password' => Hash::make('Larued@1234'),
            ]
        );
        $admin->assignRole('ADMIN');

       
        $cajero = User::firstOrCreate(
            ['email' => 'carlosOrellana@gmail.com'],
            [
                'name' => 'Roberto Carlos Orellana Rodriguez',
                'password' => Hash::make('Larued@1234'),
            ]
        );
        $cajero->assignRole('CAJERO');
    }
}