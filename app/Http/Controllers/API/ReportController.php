<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\PdfService;
use Exception;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    protected $pdfService;

    public function __construct(PdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    public function generateSubscriptionReport(Request $request)
    {
        try {
            Log::info('Starting subscription report generation', [
                'request' => $request->all()
            ]);

            $pdf = $this->pdfService->generateSubscriptionReport(
                $request->start_date,
                $request->end_date
            );

            Log::info('PDF generated successfully, sending response');

            return response($pdf)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="subscription_report.pdf"');
        } catch (Exception $e) {
            Log::error('Report generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report: ' . $e->getMessage(),
                'details' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }
}