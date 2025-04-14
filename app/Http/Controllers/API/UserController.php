<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * عرض قائمة المستخدمين
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $users = User::with('roles')->get();
            
            return response()->json([
                'success' => true,
                'users' => $users
            ]);
        } catch (\Exception $e) {
            Log::error('خطأ في عرض المستخدمين: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات المستخدمين',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إنشاء مستخدم جديد
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'status' => 'required|in:active,inactive',
                'roles' => 'nullable|array',
                'roles.*' => 'exists:roles,name'
            ]);
            
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'status' => $request->status
            ]);
            
            // إسناد الأدوار إذا تم تحديدها
            if ($request->has('roles') && !empty($request->roles)) {
                $user->syncRoles($request->roles);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء المستخدم بنجاح',
                'user' => $user->load('roles')
            ], 201);
        } catch (\Exception $e) {
            Log::error('خطأ في إنشاء مستخدم: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء المستخدم',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض بيانات مستخدم محدد
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $user = User::with('roles')->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'user' => $user
            ]);
        } catch (\Exception $e) {
            Log::error('خطأ في عرض المستخدم: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات المستخدم',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحديث بيانات مستخدم محدد
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('users')->ignore($user->id)
                ],
                'password' => 'nullable|string|min:8',
                'status' => 'required|in:active,inactive',
                'roles' => 'nullable|array',
                'roles.*' => 'exists:roles,name'
            ]);
            
            $user->name = $request->name;
            $user->email = $request->email;
            $user->status = $request->status;
            
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }
            
            $user->save();
            
            // تحديث الأدوار إذا تم تحديدها
            if ($request->has('roles')) {
                $user->syncRoles($request->roles);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث المستخدم بنجاح',
                'user' => $user->load('roles')
            ]);
        } catch (\Exception $e) {
            Log::error('خطأ في تحديث المستخدم: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث بيانات المستخدم',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف مستخدم محدد
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            
            // التحقق من أن المستخدم ليس المستخدم الحالي
            if (auth()->id() === $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف المستخدم الحالي'
                ], 403);
            }
            
            $user->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'تم حذف المستخدم بنجاح'
            ]);
        } catch (\Exception $e) {
            Log::error('خطأ في حذف المستخدم: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف المستخدم',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
