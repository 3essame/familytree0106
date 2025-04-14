<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Log;

class RoleController extends Controller
{
    /**
     * عرض قائمة الأدوار
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $roles = Role::with('permissions')->get();
            
            return response()->json([
                'success' => true,
                'roles' => $roles
            ]);
        } catch (\Exception $e) {
            Log::error('خطأ في عرض الأدوار: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات الأدوار',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إنشاء دور جديد
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:roles,name',
                'permissions' => 'nullable|array',
                'permissions.*' => 'exists:permissions,name'
            ]);
            
            $role = Role::create(['name' => $request->name]);
            
            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الدور بنجاح',
                'role' => $role->load('permissions')
            ], 201);
        } catch (\Exception $e) {
            Log::error('خطأ في إنشاء دور: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الدور',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض بيانات دور محدد
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $role = Role::with('permissions')->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'role' => $role
            ]);
        } catch (\Exception $e) {
            Log::error('خطأ في عرض الدور: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات الدور',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحديث بيانات دور محدد
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $role = Role::findOrFail($id);
            
            $request->validate([
                'name' => 'required|string|max:255|unique:roles,name,' . $id,
                'permissions' => 'nullable|array',
                'permissions.*' => 'exists:permissions,name'
            ]);
            
            $role->name = $request->name;
            $role->save();
            
            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الدور بنجاح',
                'role' => $role->load('permissions')
            ]);
        } catch (\Exception $e) {
            Log::error('خطأ في تحديث الدور: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث بيانات الدور',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف دور محدد
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $role = Role::findOrFail($id);
            
            // التحقق من أن الدور ليس الدور الافتراضي
            if ($role->name === 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف الدور الافتراضي للمدير'
                ], 403);
            }
            
            $role->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'تم حذف الدور بنجاح'
            ]);
        } catch (\Exception $e) {
            Log::error('خطأ في حذف الدور: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الدور',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * عرض قائمة الصلاحيات
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function permissions()
    {
        try {
            $permissions = Permission::all();
            
            return response()->json([
                'success' => true,
                'permissions' => $permissions
            ]);
        } catch (\Exception $e) {
            Log::error('خطأ في عرض الصلاحيات: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات الصلاحيات',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
