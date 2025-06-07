<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\FamilyTreeNode;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class FamilyTreeController extends Controller
{
    // عرض جميع الأفراد أو شجرة العائلة
    public function index()
    {
        try {
            DB::beginTransaction();
            
            // استرجاع جميع العقد مع العلاقات
            $nodes = FamilyTreeNode::with([
                'children',
                'father',
                'mother',
                'children.father',
                'children.mother'
            ])->get();
            Log::info('جلب قائمة الآباء/الأفراد بنجاح: ' . $nodes->count() . ' عنصر');

            // تسجيل عدد العقد المسترجعة
            Log::info('Retrieved ' . count($nodes) . ' family tree nodes');

            // إذا كانت المصفوفة فارغة، قم بإنشاء عقدة افتراضية للاختبار
            if ($nodes->isEmpty()) {
                Log::info('No nodes found, creating a test node');

                // إنشاء عقدة الجد
                $grandfather = FamilyTreeNode::create([
                    'name' => 'عبدالله محمد',
                    'gender' => 'male',
                    'notes' => 'مؤسس العائلة',
                    'birth_date' => '1930-01-01',
                    'relation' => 'جد',
                    'info' => ['occupation' => 'تاجر']
                ]);

                // إنشاء عقدة الجدة
                $grandmother = FamilyTreeNode::create([
                    'name' => 'فاطمة أحمد',
                    'gender' => 'female',
                    'notes' => 'زوجة مؤسس العائلة',
                    'birth_date' => '1935-01-01',
                    'relation' => 'جدة',
                    'info' => ['occupation' => 'ربة منزل']
                ]);

                // إنشاء عقدة الأب
                $father = FamilyTreeNode::create([
                    'name' => 'محمد عبدالله',
                    'gender' => 'male',
                    'notes' => 'ابن مؤسس العائلة',
                    'birth_date' => '1960-01-01',
                    'father_id' => $grandfather->id,
                    'mother_id' => $grandmother->id,
                    'relation' => 'أب',
                    'info' => ['occupation' => 'مهندس']
                ]);

                // إنشاء عقدة الأم
                $mother = FamilyTreeNode::create([
                    'name' => 'سعاد علي',
                    'gender' => 'female',
                    'notes' => 'زوجة محمد عبدالله',
                    'birth_date' => '1965-01-01',
                    'relation' => 'أم',
                    'info' => ['occupation' => 'معلمة']
                ]);

                // إعادة استرجاع العقد بعد إنشاء العقد الافتراضية
                $nodes = FamilyTreeNode::with([
                    'children',
                    'father',
                    'mother',
                    'children.father',
                    'children.mother'
                ])->get();
                
                Log::info('Created test nodes, now have ' . count($nodes) . ' nodes');
            }

            DB::commit();
            return response()->json($nodes);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in FamilyTreeController@index: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'حدث خطأ أثناء استرجاع بيانات شجرة العائلة',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // إضافة فرد جديد
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            
            Log::info('Creating new family tree node with data: ' . json_encode($request->all()));

            $data = $request->validate([
                'name' => 'required|string|max:255',
                'relation' => 'nullable|string|max:255',
                'info' => 'nullable|array',
                'father_id' => 'nullable|exists:family_tree_nodes,id',
                'mother_id' => 'nullable|exists:family_tree_nodes,id',
                'birth_date' => 'nullable|date',
                'death_date' => 'nullable|date',
                'gender' => 'required|in:male,female',
                'notes' => 'nullable|string',
            ]);

            $node = FamilyTreeNode::create($data);
            Log::info('Created new family tree node with ID: ' . $node->id);

            // استرجاع العقدة مع العلاقات
            $node = FamilyTreeNode::with([
                'children',
                'father',
                'mother',
                'children.father',
                'children.mother'
            ])->find($node->id);

            DB::commit();
            return response()->json($node, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('Validation error in FamilyTreeController@store: ' . json_encode($e->errors()));
            return response()->json(['error' => 'خطأ في البيانات المدخلة', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in FamilyTreeController@store: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'حدث خطأ أثناء إنشاء فرد جديد',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // عرض بيانات فرد محدد
    public function show($id)
    {
        try {
            Log::info('Fetching family tree node ID: ' . $id);

            $node = FamilyTreeNode::with([
                'children',
                'father',
                'mother',
                'children.father',
                'children.mother'
            ])->findOrFail($id);

            return response()->json($node);
        } catch (ModelNotFoundException $e) {
            Log::error('Family tree node not found: ' . $id);
            return response()->json(['error' => 'الفرد غير موجود'], 404);
        } catch (\Exception $e) {
            Log::error('Error in FamilyTreeController@show: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'حدث خطأ أثناء استرجاع بيانات الفرد',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // تحديث بيانات فرد
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            
            Log::info('Updating family tree node ID: ' . $id . ' with data: ' . json_encode($request->all()));

            $node = FamilyTreeNode::findOrFail($id);

            $data = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'relation' => 'nullable|string|max:255',
                'info' => 'nullable|array',
                'father_id' => 'nullable|exists:family_tree_nodes,id',
                'mother_id' => 'nullable|exists:family_tree_nodes,id',
                'birth_date' => 'nullable|date',
                'death_date' => 'nullable|date',
                'gender' => 'required|in:male,female',
                'notes' => 'nullable|string',
            ]);

            // التحقق من أن الفرد لا يشير إلى نفسه كأب أو أم
            if (isset($data['father_id']) && $data['father_id'] == $id) {
                return response()->json(['error' => 'لا يمكن أن يكون الفرد أبًا لنفسه'], 422);
            }

            if (isset($data['mother_id']) && $data['mother_id'] == $id) {
                return response()->json(['error' => 'لا يمكن أن يكون الفرد أمًا لنفسه'], 422);
            }

            $node->update($data);
            Log::info('Updated family tree node ID: ' . $id);

            // استرجاع العقدة مع العلاقات
            $node = FamilyTreeNode::with([
                'children',
                'father',
                'mother',
                'children.father',
                'children.mother'
            ])->find($id);

            DB::commit();
            return response()->json($node);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            Log::error('Family tree node not found: ' . $id);
            return response()->json(['error' => 'الفرد غير موجود'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('Validation error in FamilyTreeController@update: ' . json_encode($e->errors()));
            return response()->json(['error' => 'خطأ في البيانات المدخلة', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in FamilyTreeController@update: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'حدث خطأ أثناء تحديث بيانات الفرد',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // حذف فرد
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            
            Log::info('Deleting family tree node ID: ' . $id);

            $node = FamilyTreeNode::findOrFail($id);

            // التحقق من وجود أبناء لهذا الفرد
            $childrenCount = FamilyTreeNode::where('father_id', $id)->orWhere('mother_id', $id)->count();

            if ($childrenCount > 0) {
                return response()->json(['error' => 'لا يمكن حذف هذا الفرد لأنه لديه أبناء مرتبطين به'], 422);
            }

            $node->delete();
            Log::info('Deleted family tree node ID: ' . $id);

            DB::commit();
            return response()->json(['message' => 'تم حذف الفرد بنجاح']);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            Log::error('Family tree node not found: ' . $id);
            return response()->json(['error' => 'الفرد غير موجود'], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in FamilyTreeController@destroy: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'حدث خطأ أثناء حذف الفرد',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // البحث عن فرد بالاسم
    public function search(Request $request)
    {
        try {
            $q = $request->input('q');
            Log::info('Searching for family tree nodes with query: ' . $q);

            if (empty($q)) {
                return response()->json(['error' => 'يرجى إدخال نص للبحث'], 422);
            }

            $results = FamilyTreeNode::where('name', 'like', "%$q%")
                ->orWhere('relation', 'like', "%$q%")
                ->orWhere('notes', 'like', "%$q%")
                ->with([
                    'children',
                    'father',
                    'mother',
                    'children.father',
                    'children.mother'
                ])
                ->get();

            Log::info('Found ' . count($results) . ' family tree nodes matching query: ' . $q);

            return response()->json($results);
        } catch (\Exception $e) {
            Log::error('Error in FamilyTreeController@search: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'حدث خطأ أثناء البحث عن الأفراد',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
