<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        
        $this->call([
            RoleSeeder::class,
            SuperUsuarioSeeder::class,
            CategoriaSeeder::class,
            ProductoSeeder::class,
            PresentacionSeeder::class,
            CodigoBarraSeeder::class,
            ProveedorSeeder::class,
            CompraSeeder::class,
        ]);
    }
}
