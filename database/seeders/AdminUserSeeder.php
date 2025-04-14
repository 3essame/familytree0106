<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
            'export reports',
            
            // صلاحيات الإعدادات
            'manage settings',
            
            // صلاحيات الأدوار والصلاحيات
            'manage roles',
            'manage permissions'
        ];
        
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
        
        // إنشاء الأدوار
        $adminRole = Role::create(['name' => 'admin']);
        $managerRole = Role::create(['name' => 'manager']);
        $memberRole = Role::create(['name' => 'member']);
        
        // إعطاء جميع الصلاحيات للمسؤول
        $adminRole->givePermissionTo(Permission::all());
        
        // إعطاء بعض الصلاحيات للمدير
        $managerRole->givePermissionTo([
            'view members',
            'create members',
            'edit members',
            'view subscriptions',
            'create subscriptions',
            'edit subscriptions',
            'view reports',
            'create reports',
            'export reports'
        ]);
        
        // إعطاء صلاحيات محدودة للعضو
        $memberRole->givePermissionTo([
            'view members',
            'view subscriptions'
        ]);
        
        // إنشاء مستخدم مسؤول
        $admin = User::create([
            'name' => 'المسؤول',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'status' => 'active'
        ]);
        
        // إعطاء دور المسؤول للمستخدم
        $admin->assignRole('admin');
        
        // إنشاء مستخدم مدير
        $manager = User::create([
            'name' => 'المدير',
            'email' => 'manager@example.com',
            'password' => Hash::make('password'),
            'status' => 'active'
        ]);
        
        // إعطاء دور المدير للمستخدم
        $manager->assignRole('manager');
        
        // إنشاء مستخدم عادي
        $member = User::create([
            'name' => 'عضو',
            'email' => 'member@example.com',
            'password' => Hash::make('password'),
            'status' => 'active'
        ]);
        
        // إعطاء دور العضو للمستخدم
        $member->assignRole('member');
    }
}
