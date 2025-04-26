<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FamilyTreeNode;
use Carbon\Carbon;

class FamilyTreeSeeder extends Seeder
{
    public function run()
    {
        try {
            // إنشاء الجد
            $grandfather = FamilyTreeNode::create([
                'name' => 'عبدالله محمد',
                'gender' => 'male',
                'birth_date' => '1930-01-01',
                'notes' => 'مؤسس العائلة',
                'relation' => 'جد',
                'info' => ['occupation' => 'تاجر']
            ]);

            // إنشاء الجدة
            $grandmother = FamilyTreeNode::create([
                'name' => 'فاطمة أحمد',
                'gender' => 'female',
                'birth_date' => '1935-01-01',
                'notes' => 'زوجة مؤسس العائلة',
                'relation' => 'جدة',
                'info' => ['occupation' => 'ربة منزل']
            ]);

            // إنشاء الأب
            $father = FamilyTreeNode::create([
                'name' => 'محمد عبدالله',
                'gender' => 'male',
                'birth_date' => '1960-01-01',
                'father_id' => $grandfather->id,
                'mother_id' => $grandmother->id,
                'notes' => 'ابن مؤسس العائلة',
                'relation' => 'أب',
                'info' => ['occupation' => 'مهندس']
            ]);

            // إنشاء الأم
            $mother = FamilyTreeNode::create([
                'name' => 'سعاد علي',
                'gender' => 'female',
                'birth_date' => '1965-01-01',
                'notes' => 'زوجة محمد عبدالله',
                'relation' => 'أم',
                'info' => ['occupation' => 'معلمة']
            ]);

            // إنشاء الأبناء
            $children = [
                [
                    'name' => 'أحمد محمد',
                    'gender' => 'male',
                    'birth_date' => '1990-01-01',
                    'father_id' => $father->id,
                    'mother_id' => $mother->id,
                    'notes' => 'الابن الأكبر',
                    'relation' => 'ابن',
                    'info' => ['occupation' => 'طبيب']
                ],
                [
                    'name' => 'سارة محمد',
                    'gender' => 'female',
                    'birth_date' => '1992-01-01',
                    'father_id' => $father->id,
                    'mother_id' => $mother->id,
                    'notes' => 'الابنة الكبرى',
                    'relation' => 'ابنة',
                    'info' => ['occupation' => 'محامية']
                ],
                [
                    'name' => 'علي محمد',
                    'gender' => 'male',
                    'birth_date' => '1995-01-01',
                    'father_id' => $father->id,
                    'mother_id' => $mother->id,
                    'notes' => 'الابن الأصغر',
                    'relation' => 'ابن',
                    'info' => ['occupation' => 'طالب']
                ]
            ];

            foreach ($children as $child) {
                FamilyTreeNode::create($child);
            }

            // إنشاء العم
            $uncle = FamilyTreeNode::create([
                'name' => 'خالد عبدالله',
                'gender' => 'male',
                'birth_date' => '1962-01-01',
                'father_id' => $grandfather->id,
                'mother_id' => $grandmother->id,
                'notes' => 'عم محمد',
                'relation' => 'عم',
                'info' => ['occupation' => 'محاسب']
            ]);

            // إنشاء زوجة العم
            $uncleWife = FamilyTreeNode::create([
                'name' => 'نادية محمود',
                'gender' => 'female',
                'birth_date' => '1964-01-01',
                'notes' => 'زوجة خالد',
                'relation' => 'زوجة العم',
                'info' => ['occupation' => 'ربة منزل']
            ]);

            // إنشاء أبناء العم
            $uncleChildren = [
                [
                    'name' => 'محمود خالد',
                    'gender' => 'male',
                    'birth_date' => '1991-01-01',
                    'father_id' => $uncle->id,
                    'mother_id' => $uncleWife->id,
                    'notes' => 'ابن خالد',
                    'relation' => 'ابن العم',
                    'info' => ['occupation' => 'مهندس']
                ],
                [
                    'name' => 'ليلى خالد',
                    'gender' => 'female',
                    'birth_date' => '1993-01-01',
                    'father_id' => $uncle->id,
                    'mother_id' => $uncleWife->id,
                    'notes' => 'ابنة خالد',
                    'relation' => 'ابنة العم',
                    'info' => ['occupation' => 'طبيبة']
                ]
            ];

            foreach ($uncleChildren as $child) {
                FamilyTreeNode::create($child);
            }

            $this->command->info('تم إنشاء بيانات شجرة العائلة بنجاح!');
        } catch (\Exception $e) {
            $this->command->error('حدث خطأ أثناء إنشاء بيانات شجرة العائلة: ' . $e->getMessage());
            throw $e;
        }
    }
} 