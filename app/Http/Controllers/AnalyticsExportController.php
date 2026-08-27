<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnalyticsFilterRequest;
use App\Models\AdBudgetChangeLog;
use App\Models\CampaignInsightDaily;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsExportController extends Controller
{
    public function export(AnalyticsFilterRequest $request, string $type): StreamedResponse
    {
        Gate::authorize('exportAnalytics', $request->user()->currentBusiness);
        [$from,$to] = $request->range();
        $businessId = $request->user()->current_business_id;
        abort_unless(in_array($type, ['campaign-performance', 'daily-insights', 'ad-spend', 'budget-history'], true), 404);

        return response()->streamDownload(function () use ($type, $from, $to, $businessId): void {
            $out = fopen('php://output', 'w');
            if ($type === 'budget-history') {
                fputcsv($out, ['Campaign', 'Old budget', 'New budget', 'Currency', 'Status', 'Changed']);
                AdBudgetChangeLog::withoutBusinessScope()->where('business_id', $businessId)->with('campaign')->orderByDesc('created_at')->chunk(500, fn ($logs) => $logs->each(fn ($log) => fputcsv($out, [$this->safe($log->campaign->name), $log->old_amount, $log->new_amount, $log->currency_code, $log->status, $log->created_at])));
            } else {
                fputcsv($out, ['Date', 'Campaign', 'Currency', 'Spend', 'Impressions', 'Reach (daily)', 'Clicks', 'Results', 'Result type']);
                CampaignInsightDaily::withoutBusinessScope()->where('business_id', $businessId)->with('campaign')->whereBetween('insight_date', [$from->toDateString(), $to->toDateString()])->orderBy('insight_date')->chunk(500, fn ($rows) => $rows->each(fn ($row) => fputcsv($out, [$row->insight_date->toDateString(), $this->safe($row->campaign->name), $row->currency_code, $row->spend, $row->impressions, $row->reach, $row->clicks, $row->landing_page_views ?? $row->leads ?? $row->messaging_conversations_started, $row->result_type])));
            } fclose($out);
        }, "{$type}-{$from->toDateString()}-{$to->toDateString()}.csv", ['Content-Type' => 'text/csv']);
    }

    private function safe(?string $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@]/', $value) ? "'{$value}" : $value;
    }
}
