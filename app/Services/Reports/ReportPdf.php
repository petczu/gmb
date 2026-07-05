<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Services\Ai\AiCreditService;
use App\Services\Billing\AiUsageService;
use App\Support\DashboardPeriod;
use App\Support\ReportBlocks;
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
        private readonly AiUsageService $usage,
        private readonly AiCreditService $credits,
    ) {}

    /**
     * @return array{path: string, filename: string, businessName: string, summary: string}
     */
    public function generate(DashboardPeriod $period, string $language = 'en'): array
    {
        $report = $this->data->build($period);

        // Respect the plan's monthly AI-report allowance (scheduled sends count
        // too); over the cap, fall back to a basic non-AI summary.
        $workspace = tenant();
        if ($workspace && $this->usage->canGenerateReport($workspace)) {
            $insights = $this->insights->generate($report, $language);
            $this->credits->credit($workspace, 0, 'report', 'report', substr(md5(uniqid('rpt', true)), 0, 32));
        } else {
            $insights = $this->insights->fallbackFor($report);
        }

        $payload = [
            'data' => $report,
            'insights' => $insights,
            'generatedAt' => CarbonImmutable::now()->format('M j, Y'),
            // Same extras as the on-screen paths (ReportController::payload):
            // without them the blade falls back to a text-only header (no logo)
            // and renders every block regardless of the workspace's selection.
            'blocks' => ReportBlocks::normalize($workspace?->report_blocks),
            'brand' => ReportBranding::for($workspace),
        ];

        $slug = str($report['businessName'])->slug()->value();
        $filename = "report-{$slug}-".CarbonImmutable::now()->format('Y-m-d').'.pdf';
        $path = tempnam(sys_get_temp_dir(), 'report_').'.pdf';

        Pdf::view('reports.monthly', $payload)
            ->format('a4')
            // Page margins so nothing is clipped when printed (same as the
            // on-screen download paths in ReportController).
            ->margins(10, 10, 10, 10)
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
