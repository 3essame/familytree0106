<?php

namespace App\Permissions;

class FamilyTreePermissions
{
    public static function getPermissions()
    {
        return [
            'view family tree' => 'عرض شجرة العائلة',
            'create family member' => 'إضافة فرد جديد',
            'edit family member' => 'تعديل بيانات الفرد',
            'delete family member' => 'حذف فرد',
            'manage family tree' => 'إدارة شجرة العائلة',
        ];
    }
} 