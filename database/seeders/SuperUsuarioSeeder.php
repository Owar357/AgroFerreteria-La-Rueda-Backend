<?php

namespace Database\Seeders;

use App\Models\User;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperUsuarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $usuario = User::firstOrCreate(
        ['email' => 'admin@test.com'],
        [
          'name' => 'Super Admin',
          'password' => Hash::make('1234567890'),
        ]
        );

        $usuario->assignRole('ADMIN');
    }
}
