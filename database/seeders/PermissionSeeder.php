<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\Permissions\FamilyTreePermissions;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // إنشاء صلاحيات شجرة العائلة
        foreach (FamilyTreePermissions::getPermissions() as $permission => $description) {
            Permission::create([
                'name' => $permission,
                'description' => $description,
                'guard_name' => 'web'
            ]);
        }

        $this->command->info('تم إنشاء صلاحيات شجرة العائلة بنجاح!');
    }
} 