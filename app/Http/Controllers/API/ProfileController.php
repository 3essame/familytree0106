<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ProfileController extends Controller
{
    /**
     * تحديث الملف الشخصي للمستخدم
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        // التحقق من البيانات
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'current_password' => 'nullable|required_with:new_password',
            'new_password' => 'nullable|min:6|required_with:current_password',
            'new_password_confirmation' => 'nullable|required_with:new_password|same:new_password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $validator->errors()
            ], 422);
        }

        // تحديث الاسم
        $user->name = $request->name;

        // تحديث كلمة المرور إذا تم توفيرها
        if ($request->filled('current_password') && $request->filled('new_password')) {
            // التحقق من كلمة المرور الحالية
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'كلمة المرور الحالية غير صحيحة'
                ], 422);
            }

            // تعيين كلمة المرور الجديدة
            $user->password = Hash::make($request->new_password);
        }

        // حفظ التغييرات
        $user->save();

        // تحضير البيانات للإرجاع
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name')
        ];

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الملف الشخصي بنجاح',
            'user' => $userData
        ]);
    }
}
