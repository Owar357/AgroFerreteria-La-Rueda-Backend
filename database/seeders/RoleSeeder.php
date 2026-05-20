<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create(['name' => 'ADMIN', 'guard_name' => 'api']);
        Role::create(['name' => 'CAJERO', 'guard_name' => 'api']);
        Role::create(['name' => 'CONTADOR', 'guard_name' => 'api']);
    }
}
