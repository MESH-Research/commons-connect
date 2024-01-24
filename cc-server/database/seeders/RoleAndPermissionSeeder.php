<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        Permission::create( ['name' => 'make superadmin'] );
        Permission::create( ['name' => 'make admin'] );
        Permission::create( ['name' => 'make manager'] );
        Permission::create( ['name' => 'approve user'] );
        
        $superadmin_role = Role::create( [ 'name' => 'superadmin' ] )
            ->givePermissionTo( Permission::all() );

        $admin_role = Role::create( [ 'name' => 'admin' ] )
            ->givePermissionTo( [
                'make admin',
                'make manager',
                'approve user' 
            ] );

        $manager_role = Role::create( [ 'name' => 'manager' ] )
            ->givePermissionTo( [
                'make manager',
                'approve user' 
            ] );
    }
}
