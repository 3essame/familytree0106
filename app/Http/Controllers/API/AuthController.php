<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    /**
     * الحصول على معلومات المستخدم المسجل دخوله مع الأدوار والصلاحيات
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function user()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم غير مسجل دخول'
                ], 401);
            }
            
            // الحصول على أدوار المستخدم
            $roles = $user->getRoleNames()->toArray();
            
            // الحصول على صلاحيات المستخدم (المباشرة والمرتبطة بالأدوار)
            $permissions = $user->getAllPermissions()->pluck('name')->toArray();
            
            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status,
                    'roles' => $roles,
                    'permissions' => $permissions
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('خطأ في الحصول على بيانات المستخدم: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات المستخدم',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * تسجيل الدخول وإرجاع توكن المصادقة
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);
            
            if (Auth::attempt(['email' => $request->email, 'password' => $request->password, 'status' => 'active'])) {
                $user = Auth::user();
                
                // إنشاء توكن يدويًا
                $tokenName = 'auth_token';
                $token = Str::random(40);
                
                // حفظ التوكن في قاعدة البيانات
                $personalAccessToken = new PersonalAccessToken();
                $personalAccessToken->tokenable_type = get_class($user);
                $personalAccessToken->tokenable_id = $user->id;
                $personalAccessToken->name = $tokenName;
                $personalAccessToken->token = hash('sha256', $token);
                $personalAccessToken->save();
                
                // الحصول على أدوار المستخدم
                $roles = $user->getRoleNames()->toArray();
                
                // الحصول على صلاحيات المستخدم (المباشرة والمرتبطة بالأدوار)
                $permissions = $user->getAllPermissions()->pluck('name')->toArray();
                
                return response()->json([
                    'success' => true,
                    'token' => $personalAccessToken->getKey().'|'.$token,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'status' => $user->status,
                        'roles' => $roles,
                        'permissions' => $permissions
                    ]
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'بيانات الاعتماد غير صحيحة أو الحساب غير نشط'
            ], 401);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('خطأ في تسجيل الدخول: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل الدخول',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * تسجيل الخروج
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            // حذف التوكن الحالي
            if ($request->user()) {
                $request->user()->tokens()->where('id', $request->user()->currentAccessToken()->id)->delete();
            }
            
            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الخروج بنجاح'
            ]);
        } catch (\Exception $e) {
            Log::error('خطأ في تسجيل الخروج: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل الخروج',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
