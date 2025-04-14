<?php

use Illuminate\Support\Facades\Route;

// مسار SPA - سيتم توجيه جميع الطلبات غير API إلى تطبيق Vue
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
