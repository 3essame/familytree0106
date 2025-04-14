<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Member;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SubscriptionImportController extends Controller
{
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all()
            ], 422);
        }

        try {
            $file = $request->file('file');
            
            // Validate file exists and is readable
            if (!$file->isValid()) {
                return response()->json([
                    'success' => false,
                    'errors' => ['الملف غير صالح أو غير قابل للقراءة']
                ], 422);
            }

            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Validate file has data
            if (count($rows) < 2) {
                return response()->json([
                    'success' => false,
                    'errors' => ['الملف لا يحتوي على بيانات']
                ], 422);
            }

            // Remove header row
            array_shift($rows);

            $errors = [];
            $successCount = 0;

            DB::beginTransaction();

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // Add 2 to account for 0-based index and header row

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Validate required fields
                if (empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3])) {
                    $errors[] = "سطر {$rowNumber}: جميع الحقول مطلوبة ما عدا الملاحظات";
                    continue;
                }

                // Validate member exists
                $member = Member::find($row[0]); // member_id is in first column
                if (!$member) {
                    $errors[] = "سطر {$rowNumber}: رقم العضو غير موجود";
                    continue;
                }

                // Validate subscription status
                if (!in_array($row[1], ['paid', 'unpaid', 'overdue'])) {
                    $errors[] = "سطر {$rowNumber}: حالة الاشتراك غير صحيحة";
                    continue;
                }

                // Validate date format
                if (!strtotime($row[2])) {
                    $errors[] = "سطر {$rowNumber}: تنسيق التاريخ غير صحيح";
                    continue;
                }

                // Validate amount is numeric
                if (!is_numeric($row[3])) {
                    $errors[] = "سطر {$rowNumber}: المبلغ يجب أن يكون رقماً";
                    continue;
                }

                try {
                    Subscription::create([
                        'member_id' => $row[0],
                        'subscription_status' => $row[1],
                        'payment_date' => $row[2],
                        'amount' => $row[3],
                        'payment_method' => $row[4] ?? 'cash',
                        'notes' => $row[5] ?? null
                    ]);
                    $successCount++;
                } catch (\Exception $e) {
                    $errors[] = "سطر {$rowNumber}: " . $e->getMessage();
                }
            }

            if (empty($errors)) {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => "تم استيراد {$successCount} اشتراك بنجاح"
                ]);
            } else {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'errors' => $errors,
                    'message' => 'حدثت أخطاء أثناء الاستيراد'
                ], 422);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'errors' => ['حدث خطأ أثناء معالجة الملف: ' . $e->getMessage()]
            ], 500);
        }
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = [
            'A1' => 'رقم العضو',
            'B1' => 'حالة الاشتراك',
            'C1' => 'تاريخ الدفع',
            'D1' => 'المبلغ',
            'E1' => 'طريقة الدفع',
            'F1' => 'ملاحظات'
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Add example row
        $example = [
            'A2' => '1',
            'B2' => 'paid',
            'C2' => '2024-03-20',
            'D2' => '100.00',
            'E2' => 'cash',
            'F2' => 'ملاحظات اختيارية'
        ];

        foreach ($example as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Add data validation for subscription_status
        $validation = $sheet->getCell('B2')->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $validation->setAllowBlank(false);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setFormula1('"paid,unpaid,overdue"');
        $validation->setPromptTitle('اختر حالة الاشتراك');
        $validation->setPrompt('يجب أن تكون القيمة واحدة من: paid, unpaid, overdue');
        $validation->setErrorTitle('خطأ في الإدخال');
        $validation->setError('القيمة المدخلة غير صحيحة');

        // Auto-size columns
        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Create the Excel file
        $writer = new Xlsx($spreadsheet);
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="subscriptions_template.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }
} 