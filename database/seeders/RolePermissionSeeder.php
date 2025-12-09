<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions (if cache table exists)
        try {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        } catch (\Exception $e) {
            // Cache table might not exist yet, continue anyway
        }

        // Create permissions
        $permissions = [
            // User Management
            'view users',
            'create users',
            'edit users',
            'delete users',
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
            
            // ERP Integration
            'erp-integration.view',
            'erp-integration.sync',
            'erp-integration.manage',
            
            // Sales
            'sales.view',
            'sales.create',
            'sales.update',
            'sales.manage',
            
            // Inventory
            'inventory.view',
            'inventory.create',
            'inventory.update',
            'inventory.manage',
            
            // Monitoring
            'monitoring.view',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $adminRole = Role::create(['name' => 'Super Admin']);
        $adminRole->givePermissionTo(Permission::all());

        $userRole = Role::create(['name' => 'User']);
        $userRole->givePermissionTo(['view users']);

        // You can create more roles and assign specific permissions
        $editorRole = Role::create(['name' => 'Editor']);
        $editorRole->givePermissionTo(['view users', 'create users', 'edit users']);

        // Add website management permissions
        $websitePermissions = [
            'website.settings.view',
            'website.settings.edit',
            'website.settings.manage',
            'website.configuration.view',
            'website.configuration.edit',
        ];

        foreach ($websitePermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Give Super Admin all new permissions
        $adminRole->givePermissionTo($websitePermissions);
    }
}

