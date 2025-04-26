<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // إنشاء المستخدمين والأدوار والصلاحيات
        $this->call([
            PermissionSeeder::class,
            AdminUserSeeder::class,
            FamilyTreeSeeder::class,
        ]);

        // User::factory(10)->create();

        // ملاحظة: تم نقل إنشاء المستخدمين والأدوار إلى AdminUserSeeder
    }
}
