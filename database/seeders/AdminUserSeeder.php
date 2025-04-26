<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // إنشاء الصلاحيات
        $permissions = [

            // صلاحيات الإعدادات
            'manage settings',

            // صلاحيات الأدوار والصلاحيات
            'manage roles',
            'manage permissions'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // إنشاء الأدوار
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $memberRole = Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);

        // إعطاء جميع الصلاحيات للمسؤول
        $adminRole->givePermissionTo(Permission::all());

        // إعطاء بعض الصلاحيات للمدير
        $managerRole->givePermissionTo([
            'view family tree',
            'manage family tree'
        ]);

        // إعطاء صلاحيات محدودة للعضو
        $memberRole->givePermissionTo([
            'view family tree'
        ]);

        // إنشاء مستخدم مسؤول
        $admin = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'status' => 'active',
            ]
        );

        // إنشاء مستخدم مدير
        $manager = User::firstOrCreate(
            ['email' => 'manager@admin.com'],
            [
                'name' => 'Manager',
                'password' => Hash::make('password'),
                'status' => 'active',
            ]
        );

        // إنشاء مستخدم عادي
        $member = User::firstOrCreate(
            ['email' => 'member@example.com'],
            [
                'name' => 'Member',
                'password' => Hash::make('password'),
                'status' => 'active',
            ]
        );

        // إعطاء دور المسؤول للمستخدم
        $admin->assignRole('admin');

        // إعطاء دور المدير للمستخدم
        $manager->assignRole('manager');

        // إعطاء دور العضو للمستخدم
        $member->assignRole('member');
    }
}
