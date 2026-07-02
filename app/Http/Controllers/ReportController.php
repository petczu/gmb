<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\GeneratedReport;
use App\Models\ReportShare;
use App\Services\Reports\ReportData;
use App\Services\Reports\ReportGenerator;
use App\Support\DashboardPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

/**
 * Renders the Monthly Performance Report — the same Blade template is used for
 * the on-screen preview (iframe in the Filament Reports page) and the PDF
 * download. Both reuse the AI summary that was produced by the explicit
 * "Generate" action (ReportGenerator); they never spend AI tokens themselves.
 */
class ReportController extends Controller
{
    public function preview(Request $request, ReportData $data, ReportGenerator $generator): Response
    {
        // Always serve a fresh preview (don't let the browser cache the iframe).
        return response(view('reports.monthly', $this->payload($request, $data, $generator)))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function download(Request $request, ReportData $data, ReportGenerator $generator): PdfBuilder
    {
        $payload = $this->payload($request, $data, $generator);
        $name = str($payload['data']['businessName'])->slug()->value();
        $filename = "report-{$name}-".CarbonImmutable::now()->format('Y-m-d').'.pdf';

        return Pdf::view('reports.monthly', $payload)
            ->format('a4')
            // Page margins so nothing is clipped when printed.
            ->margins(10, 10, 10, 10)
            ->withBrowsershot(function ($browsershot): void {
                if ($chrome = config('services.pdf.chrome_path')) {
                    $browsershot->setChromePath($chrome);
                }
                $browsershot->noSandbox()->waitUntilNetworkIdle()->setDelay(500);
            })
            ->name($filename)
            ->download();
    }

    /** View a previously saved report snapshot. */
    public function savedPreview(string $id): Response
    {
        return response(GeneratedReport::findOrFail($id)->html);
    }

    /** PUBLIC (no login): a shared report via its token, honoring window + password. */
    public function shared(Request $request, string $token): Response
    {
        $share = ReportShare::query()->where('token', $token)->first();

        if ($share === null) {
            return response()->view('reports.share-not-found', [], 404);
        }

        if (! $share->withinWindow()) {
            return response()->view('reports.share-unavailable', [], 403);
        }

        if ($share->hasPassword() && ! $request->session()->get($this->shareUnlockKey($share))) {
            return response()->view('reports.share-password', ['token' => $token, 'error' => null]);
        }

        return response($share->html);
    }

    /** PUBLIC: verify the share password and unlock for this session. */
    public function sharedUnlock(Request $request, string $token): RedirectResponse|Response
    {
        $share = ReportShare::query()->where('token', $token)->firstOrFail();

        if ($share->hasPassword() && Hash::check((string) $request->input('password'), $share->password)) {
            $request->session()->put($this->shareUnlockKey($share), true);

            return redirect()->route('reports.shared', $token);
        }

        return response()->view('reports.share-password', ['token' => $token, 'error' => 'Incorrect password.'], 401);
    }

    /**
     * Session unlock key, tied to the CURRENT password hash. Changing (or
     * removing) the password changes the key, so previously-unlocked sessions
     * stop granting access and the visitor is re-prompted.
     */
    private function shareUnlockKey(ReportShare $share): string
    {
        return 'report_share:'.$share->token.':'.md5((string) $share->password);
    }

    /** Download a previously saved report snapshot as PDF. */
    public function savedDownload(string $id): PdfBuilder
    {
        $report = GeneratedReport::findOrFail($id);
        $filename = 'report-'.str($report->title)->slug()->value().'-'.$report->created_at->format('Y-m-d').'.pdf';

        return Pdf::html($report->html)
            ->format('a4')
            ->margins(10, 10, 10, 10)
            ->withBrowsershot(function ($browsershot): void {
                if ($chrome = config('services.pdf.chrome_path')) {
                    $browsershot->setChromePath($chrome);
                }
                $browsershot->noSandbox()->waitUntilNetworkIdle()->setDelay(500);
            })
            ->name($filename)
            ->download();
    }

    /**
     * @return array{data: array<string, mixed>, insights: array<string, mixed>, generatedAt: string}
     */
    protected function payload(Request $request, ReportData $data, ReportGenerator $generator): array
    {
        $language = in_array($request->query('language'), ['en', 'de'], true) ? $request->query('language') : 'en';
        app()->setLocale($language); // localizes the report labels via __()

        $period = DashboardPeriod::fromFilters([
            'period' => $request->query('period', 'last_30'),
            'startDate' => $request->query('startDate'),
            'endDate' => $request->query('endDate'),
            'location_id' => $request->query('location_id'),
            'compareMode' => $request->query('compareMode', 'previous'),
            'compareStartDate' => $request->query('compareStartDate'),
            'compareEndDate' => $request->query('compareEndDate'),
        ]);

        $report = $data->build($period);

        return [
            'data' => $report,
            'insights' => $generator->cachedOrFallback($period, $report, $language),
            'generatedAt' => CarbonImmutable::now()->format('M j, Y'),
            'blocks' => \App\Support\ReportBlocks::normalize($request->query('blocks')),
            'brand' => \App\Services\Reports\ReportBranding::for(\App\Models\Workspace::find(session('current_workspace_id'))),
        ];
    }
}
