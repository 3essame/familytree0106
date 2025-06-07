<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\FamilyTreeController;
use App\Http\Controllers\API\MarriageController;


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

    // مسارات الأدوار
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::post('/', [RoleController::class, 'store']);
        Route::get('/{id}', [RoleController::class, 'show']);
        Route::put('/{id}', [RoleController::class, 'update']);
        Route::delete('/{id}', [RoleController::class, 'destroy']);
        Route::get('/permissions', [RoleController::class, 'permissions']);
    });

    // مسارات شجرة العائلة
    Route::apiResource('family-tree', FamilyTreeController::class);

    // مسارات الزواج
    Route::get('/marriages/{marriage}', [MarriageController::class, 'show']);
    Route::put('/marriages/{marriage}', [MarriageController::class, 'update']);
});
