<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\Member;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
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
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Remove header row
            array_shift($rows);

            $errors = [];
            $successCount = 0;

            DB::beginTransaction();

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // Add 2 to account for 0-based index and header row

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

                try {
                    Subscription::create([
                        'member_id' => $row[0],
                        'subscription_status' => $row[1],
                        'payment_date' => $row[2],
                        'amount' => $row[3],
                        'payment_method' => $row[4],
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
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
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
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
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
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="subscriptions_template.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }
} 