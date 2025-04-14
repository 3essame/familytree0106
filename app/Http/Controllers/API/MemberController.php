<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Member;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class MemberController extends Controller
{
    /**
     * عرض قائمة الأعضاء
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = Member::query();
            
            // البحث
            if ($request->has('search')) {
                $search = $request->search;
                $query->when($search, function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('national_id', 'like', "%{$search}%");
                });
            }
            
            // تصفية حسب الحالة
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }
            
            // تصفية حسب نطاق التاريخ
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59'
                ]);
            }
            
            // تصفية حسب حالة الاشتراك
            if ($request->has('subscription') && $request->subscription) {
                $query->where('subscription_status', $request->subscription);
            }
            
            // ترتيب النتائج
            $query->orderBy($request->get('sort_by', 'created_at'), $request->get('sort_dir', 'desc'));
            
            // تقسيم الصفحات
            $members = $query->paginate($request->get('per_page', 10));
            
            return response()->json([
                'success' => true,
                'data' => $members
            ]);
        } catch (\Exception $e) {
            Log::error('خطأ في جلب قائمة الأعضاء: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب قائمة الأعضاء',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إضافة عضو جديد
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // التحقق من صحة البيانات
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'email' => 'required|email|max:255|unique:members',
                'national_id' => 'required|string|max:50|unique:members',
                'membership_number' => 'required|string|max:50|unique:members',
                'status' => 'required|in:active,inactive',
                'job_title' => 'nullable|string|max:255',
                'workplace' => 'nullable|string|max:255',
                'address' => 'nullable|string|max:255',
                'notes' => 'nullable|string'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطأ في التحقق من البيانات',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // إنشاء العضو الجديد
            $member = Member::create($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'تم إضافة العضو بنجاح',
                'data' => $member
            ], 201);
        } catch (\Exception $e) {
            Log::error('خطأ في إضافة عضو جديد: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة العضو',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض بيانات عضو محدد
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $member = Member::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $member
            ]);
        } catch (\Exception $e) {
            Log::error('خطأ في عرض بيانات العضو: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء عرض بيانات العضو',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحديث بيانات عضو محدد
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $member = Member::findOrFail($id);
            
            // التحقق من صحة البيانات
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'email' => 'required|email|max:255|unique:members,email,' . $id,
                'national_id' => 'required|string|max:50|unique:members,national_id,' . $id,
                'membership_number' => 'required|string|max:50|unique:members,membership_number,' . $id,
                'status' => 'required|in:active,inactive',
                'job_title' => 'nullable|string|max:255',
                'workplace' => 'nullable|string|max:255',
                'address' => 'nullable|string|max:255',
                'notes' => 'nullable|string'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطأ في التحقق من البيانات',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // تحديث بيانات العضو
            $member->update($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث بيانات العضو بنجاح',
                'data' => $member
            ]);
        } catch (\Exception $e) {
            Log::error('خطأ في تحديث بيانات العضو: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث بيانات العضو',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف عضو محدد
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $member = Member::findOrFail($id);
            $member->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'تم حذف العضو بنجاح'
            ]);
        } catch (\Exception $e) {
            Log::error('خطأ في حذف العضو: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف العضو',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
