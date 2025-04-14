<?php

namespace App\Services;

use Mpdf\Mpdf;
use App\Models\Member;
use App\Models\Subscription;
use Exception;
use Illuminate\Support\Facades\Log;

class PdfService
{
    protected $mpdf;

    public function __construct()
    {
        try {
            $this->mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'direction' => 'rtl',
                'default_font' => 'sans-serif',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 15,
                'margin_bottom' => 15,
            ]);
        } catch (Exception $e) {
            Log::error('MPDF Initialization Error: ' . $e->getMessage());
            throw new Exception('Failed to initialize PDF generator: ' . $e->getMessage());
        }
    }

    public function generateSubscriptionReport($startDate = null, $endDate = null)
    {
        try {
            Log::info('Generating subscription report', [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);

            $query = Member::with(['subscriptions' => function($query) use ($startDate, $endDate) {
                if ($startDate) {
                    $query->whereDate('payment_date', '>=', $startDate);
                }
                if ($endDate) {
                    $query->whereDate('payment_date', '<=', $endDate);
                }
            }]);

            $members = $query->get();
            
            Log::info('Retrieved members count: ' . $members->count());

            $html = view('reports.subscription_report', [
                'members' => $members,
                'startDate' => $startDate,
                'endDate' => $endDate
            ])->render();

            Log::info('HTML template rendered successfully');

            $this->mpdf->WriteHTML($html);
            
            Log::info('PDF generated successfully');
            
            return $this->mpdf->Output('subscription_report.pdf', 'S');
        } catch (Exception $e) {
            Log::error('PDF Generation Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            throw new Exception('Failed to generate PDF report: ' . $e->getMessage());
        }
    }
} 