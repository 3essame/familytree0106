<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // إنشاء الصلاحيات
        $permissions = [
            // صلاحيات الأعضاء
            'view members',
            'create members',
            'edit members',
            'delete members',
            
            // صلاحيات الاشتراكات
            'view subscriptions',
            'create subscriptions',
            'edit subscriptions',
            'delete subscriptions',
            
            // صلاحيات التقارير
            'view reports',
            'create reports',
            'edit reports',
            'delete reports',
            
            // صلاحيات الإعدادات
            'view settings',
            'edit settings',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // إنشاء الأدوار وتعيين الصلاحيات
        // دور المشرف (Admin)
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // دور المدير (Manager)
        $managerRole = Role::create(['name' => 'manager']);
        $managerRole->givePermissionTo([
            'view members', 'create members', 'edit members',
            'view subscriptions', 'create subscriptions', 'edit subscriptions',
            'view reports', 'create reports',
            'view settings',
        ]);

        // دور المحرر (Editor)
        $editorRole = Role::create(['name' => 'editor']);
        $editorRole->givePermissionTo([
            'view members',
            'view subscriptions',
            'view reports', 'create reports',
        ]);

        // دور العضو (Member)
        $memberRole = Role::create(['name' => 'member']);
        $memberRole->givePermissionTo([
            'view members',
            'view subscriptions',
        ]);
    }
}
