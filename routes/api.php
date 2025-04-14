<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\MemberController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\SubscriptionController;
use App\Http\Controllers\API\SubscriptionImportController;
use App\Http\Controllers\API\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// مسار CSRF token
Route::get('/csrf-token', function (Request $request) {
    return response()->json(['csrf_token' => csrf_token()]);
});

// مسارات المصادقة
Route::post('/login', [AuthController::class, 'login']);

// المسارات المحمية بتوكن المصادقة
Route::middleware('auth:sanctum')->group(function () {
    // معلومات المستخدم الحالي
    Route::get('/user', [AuthController::class, 'user']);

    // تسجيل الخروج
    Route::post('/logout', [AuthController::class, 'logout']);

    // تحديث الملف الشخصي
    Route::post('/profile/update', [ProfileController::class, 'update']);

    // مسارات إدارة المستخدمين
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });

    // مسارات الأعضاء
    Route::prefix('members')->group(function () {
        Route::get('/', [MemberController::class, 'index']);
        Route::post('/', [MemberController::class, 'store']);
        Route::get('/{id}', [MemberController::class, 'show']);
        Route::put('/{id}', [MemberController::class, 'update']);
        Route::delete('/{id}', [MemberController::class, 'destroy']);
    });

    // مسارات الأدوار
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::post('/', [RoleController::class, 'store']);
        Route::get('/{id}', [RoleController::class, 'show']);
        Route::put('/{id}', [RoleController::class, 'update']);
        Route::delete('/{id}', [RoleController::class, 'destroy']);
        Route::get('/permissions', [RoleController::class, 'permissions']);
    });

    // مسارات الاشتراكات
    Route::prefix('subscriptions')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index']);
        Route::post('/', [SubscriptionController::class, 'store']);
        Route::get('/{id}', [SubscriptionController::class, 'show']);
        Route::put('/{id}', [SubscriptionController::class, 'update']);
        Route::delete('/{id}', [SubscriptionController::class, 'destroy']);

        // مسارات استيراد الاشتراكات
        Route::post('/import', [SubscriptionImportController::class, 'import']);
        Route::get('/template', [SubscriptionImportController::class, 'downloadTemplate']);

        // مسار تصدير الاشتراكات
        Route::get('/export', [SubscriptionController::class, 'export']);
    });

    // مسارات التقارير
    // reports/subscriptions
    Route::prefix('reports')->group(function () {
        Route::get('/subscriptions', [ReportController::class, 'generateSubscriptionReport']);
    });
});
