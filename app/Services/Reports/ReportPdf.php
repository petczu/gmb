<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Support\DashboardPeriod;
use Carbon\CarbonImmutable;
use Spatie\LaravelPdf\Facades\Pdf;

/**
 * Renders the report to a PDF file (in the system temp dir — NOT storage_path,
 * which stancl tenancy redirects to a per-tenant folder that may not exist).
 * Shared by the scheduled-email job; the on-screen download uses its own
 * streamed response in ReportController.
 */
class ReportPdf
{
    public function __construct(
        private readonly ReportData $data,
        private readonly ReportInsights $insights,
        private readonly \App\Services\Billing\AiUsageService $usage,
        private readonly \App\Services\Ai\AiCreditService $credits,
    ) {}

    /**
     * @return array{path: string, filename: string, businessName: string, summary: string}
     */
    public function generate(DashboardPeriod $period): array
    {
        $report = $this->data->build($period);

        // Respect the plan's monthly AI-report allowance (scheduled sends count
        // too); over the cap, fall back to a basic non-AI summary.
        $workspace = tenant();
        if ($workspace && $this->usage->canGenerateReport($workspace)) {
            $insights = $this->insights->generate($report);
            $this->credits->credit($workspace, 0, 'report', 'report', substr(md5(uniqid('rpt', true)), 0, 32));
        } else {
            $insights = $this->insights->fallbackFor($report);
        }

        $payload = [
            'data' => $report,
            'insights' => $insights,
            'generatedAt' => CarbonImmutable::now()->format('M j, Y'),
        ];

        $slug = str($report['businessName'])->slug()->value();
        $filename = "report-{$slug}-".CarbonImmutable::now()->format('Y-m-d').'.pdf';
        $path = tempnam(sys_get_temp_dir(), 'report_').'.pdf';

        Pdf::view('reports.monthly', $payload)
            ->format('a4')
            ->withBrowsershot(function ($browsershot): void {
                if ($chrome = config('services.pdf.chrome_path')) {
                    $browsershot->setChromePath($chrome);
                }
                $browsershot->noSandbox()->waitUntilNetworkIdle()->setDelay(500);
            })
            ->save($path);

        return [
            'path' => $path,
            'filename' => $filename,
            'businessName' => $report['businessName'],
            'summary' => $insights['summary'],
        ];
    }
}
