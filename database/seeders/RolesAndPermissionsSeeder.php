<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        $permissions = [
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
            'view-roles',
            'manage-roles',
            'view-corpora',
            'manage-corpora',
            'view-dialogs',
            'manage-dialogs',
            'publish-dialogs',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        // create roles and assign existing permissions
        $admin = Role::findOrCreate('Amministratore');
        $admin->syncPermissions(Permission::all());

        Role::findOrCreate('Docente');
        Role::findOrCreate('Studente');
        Role::findOrCreate('Ricercatore');
    }
}
