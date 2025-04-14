<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\Member;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $subscriptions = Subscription::with('member')->paginate(10);
        return response()->json([
            'success' => true,
            'data' => $subscriptions
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
            'subscription_status' => 'required|in:paid,unpaid,overdue',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $subscription = Subscription::create($request->all());
        
        return response()->json([
            'success' => true,
            'message' => 'تم إضافة الاشتراك بنجاح',
            'data' => $subscription
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $subscription = Subscription::with('member')->findOrFail($id);
        return response()->json($subscription);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $subscription = Subscription::findOrFail($id);
        
        $request->validate([
            'member_id' => 'required|exists:members,id',
            'subscription_status' => 'required|in:paid,unpaid,overdue',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $subscription->update($request->all());
        
        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الاشتراك بنجاح',
            'data' => $subscription
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $subscription = Subscription::findOrFail($id);
        $subscription->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'تم حذف الاشتراك بنجاح'
        ]);
    }

    /**
     * Export subscriptions to Excel
     */
    public function export(Request $request)
    {
        $query = Subscription::with('member');

        // Apply filters
        if ($request->filled('start_date')) {
            $query->whereDate('payment_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('payment_date', '<=', $request->end_date);
        }
        if ($request->filled('subscription_status')) {
            $query->where('subscription_status', $request->subscription_status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('member', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('membership_number', 'like', "%{$search}%");
            });
        }

        $subscriptions = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $sheet->setCellValue('A1', 'رقم العضوية');
        $sheet->setCellValue('B1', 'اسم العضو');
        $sheet->setCellValue('C1', 'حالة الاشتراك');
        $sheet->setCellValue('D1', 'تاريخ الدفع');
        $sheet->setCellValue('E1', 'المبلغ');
        $sheet->setCellValue('F1', 'طريقة الدفع');
        $sheet->setCellValue('G1', 'ملاحظات');

        // Add data
        $row = 2;
        foreach ($subscriptions as $subscription) {
            $sheet->setCellValue('A' . $row, $subscription->member->membership_number);
            $sheet->setCellValue('B' . $row, $subscription->member->name);
            $sheet->setCellValue('C' . $row, $subscription->subscription_status);
            $sheet->setCellValue('D' . $row, $subscription->payment_date);
            $sheet->setCellValue('E' . $row, $subscription->amount);
            $sheet->setCellValue('F' . $row, $subscription->payment_method);
            $sheet->setCellValue('G' . $row, $subscription->notes);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Create the Excel file
        $writer = new Xlsx($spreadsheet);
        $filename = 'subscriptions_' . date('Y-m-d_His') . '.xlsx';
        
        // Save to temporary file
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($tempFile);

        // Return the file
        return Response::download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
