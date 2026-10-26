<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class GoogleContactsSeeder extends Seeder
{
    public function run()
    {
        foreach (['Listar Contactos', 'Sincronizar Contactos', 'Configurar Google Contacts', 'Ver Diagnostico Google'] as $name) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            if ($admin = Role::where('name', 'Admin')->where('guard_name', 'web')->first()) {
                $admin->givePermissionTo($permission);
            }
        }
        if ($secretaria = Role::where('name', 'Secretaria')->where('guard_name', 'web')->first()) {
            $secretaria->givePermissionTo(['Listar Contactos', 'Sincronizar Contactos']);
        }
        // Other roles receive access only through explicit permission assignment.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
