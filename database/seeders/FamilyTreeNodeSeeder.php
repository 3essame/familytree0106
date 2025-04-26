<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FamilyTreeNode;

class FamilyTreeNodeSeeder extends Seeder
{
    public function run(): void
    {
        // الجد والجدة من جهة الأب
        $grandfather = FamilyTreeNode::create([
            'name' => 'محمد علي',
            'gender' => 'male',
            'birth_date' => '1930-01-01',
            'notes' => 'مؤسس العائلة',
            'info' => ['birth_place' => 'مكة المكرمة'],
        ]);

        $grandmother = FamilyTreeNode::create([
            'name' => 'نورة أحمد',
            'gender' => 'female',
            'birth_date' => '1935-03-15',
            'info' => ['birth_place' => 'المدينة المنورة'],
        ]);

        // ربط الزوجين
        $grandfather->spouse_id = $grandmother->id;
        $grandfather->save();
        $grandmother->spouse_id = $grandfather->id;
        $grandmother->save();

        // الأب
        $father = FamilyTreeNode::create([
            'name' => 'أحمد محمد',
            'gender' => 'male',
            'birth_date' => '1960-06-10',
            'father_id' => $grandfather->id,
            'mother_id' => $grandmother->id,
            'notes' => 'الابن الأكبر للعائلة',
            'info' => ['birth_place' => 'الرياض'],
        ]);

        // عم
        $uncle = FamilyTreeNode::create([
            'name' => 'عبدالله محمد',
            'gender' => 'male',
            'birth_date' => '1963-08-20',
            'father_id' => $grandfather->id,
            'mother_id' => $grandmother->id,
            'info' => ['birth_place' => 'الرياض'],
        ]);

        // الأم
        $mother = FamilyTreeNode::create([
            'name' => 'فاطمة سعيد',
            'gender' => 'female',
            'birth_date' => '1965-02-01',
            'info' => ['birth_place' => 'جدة'],
        ]);

        // ربط الأب والأم
        $father->spouse_id = $mother->id;
        $father->save();
        $mother->spouse_id = $father->id;
        $mother->save();

        // الأبناء
        $son1 = FamilyTreeNode::create([
            'name' => 'محمد أحمد',
            'gender' => 'male',
            'birth_date' => '1990-03-01',
            'father_id' => $father->id,
            'mother_id' => $mother->id,
            'notes' => 'الابن الأكبر',
            'info' => ['birth_place' => 'الرياض'],
        ]);

        $daughter = FamilyTreeNode::create([
            'name' => 'سارة أحمد',
            'gender' => 'female',
            'birth_date' => '1992-07-15',
            'father_id' => $father->id,
            'mother_id' => $mother->id,
            'info' => ['birth_place' => 'الرياض'],
        ]);

        $son2 = FamilyTreeNode::create([
            'name' => 'عبدالرحمن أحمد',
            'gender' => 'male',
            'birth_date' => '1995-11-20',
            'father_id' => $father->id,
            'mother_id' => $mother->id,
            'notes' => 'الابن الأصغر',
            'info' => ['birth_place' => 'الرياض'],
        ]);

        // أبناء العم
        $cousin1 = FamilyTreeNode::create([
            'name' => 'سلطان عبدالله',
            'gender' => 'male',
            'birth_date' => '1991-04-10',
            'father_id' => $uncle->id,
            'info' => ['birth_place' => 'الرياض'],
        ]);

        $cousin2 = FamilyTreeNode::create([
            'name' => 'نوف عبدالله',
            'gender' => 'female',
            'birth_date' => '1993-09-25',
            'father_id' => $uncle->id,
            'info' => ['birth_place' => 'الرياض'],
        ]);
    }
}
