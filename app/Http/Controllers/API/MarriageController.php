<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Marriage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class MarriageController extends Controller
{
    public function update(Request $request, Marriage $marriage)
    {
        try {            $validator = Validator::make($request->all(), [
                'marriage_date' => 'nullable|date',
                'divorce_date' => 'nullable|date',
                'documents' => 'nullable|array',
                'notes' => 'nullable|string',
                'status' => 'nullable|in:active,divorced,widowed'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // تحديث بيانات الزواج
            $marriage->update($request->all());

            // إعادة تحميل العلاقات
            $marriage->load(['person1', 'person2']);            Log::info('Marriage updated successfully', ['id' => $marriage->getKey()]);

            return response()->json($marriage);
        } catch (\Exception $e) {
            Log::error('Error updating marriage', [
                'id' => $marriage->getKey(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'حدث خطأ أثناء تحديث بيانات الزواج',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Marriage $marriage)
    {
        try {
            $marriage->load(['person1', 'person2']);
            
            // إضافة معلومات الخط الزمني
            $marriage->timeline = $marriage->getTimeline();            return response()->json($marriage);
        } catch (\Exception $e) {
            Log::error('Error fetching marriage details', [
                'id' => $marriage->getKey(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'حدث خطأ أثناء جلب بيانات الزواج',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
