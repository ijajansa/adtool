<?php

namespace App\Services\Meta\Insights;

use Illuminate\Support\Collection;

class InsightAggregationService
{
    public function aggregate(Collection $rows): array
    {
        $impressions = (int) $rows->sum('impressions');
        $clicks = (int) $rows->sum('clicks');
        $results = (int) $rows->sum(fn ($row) => match ($row->campaign?->goal?->value) {
            'lead_generation' => $row->leads, 'whatsapp_messages' => $row->messaging_conversations_started, default => $row->landing_page_views ?? $row->inline_link_clicks ?? 0
        });
        $spendCents = (int) $rows->sum(fn ($row) => $this->minor((string) $row->spend));

        return [
            'impressions' => $impressions, 'reach' => (int) $rows->max('reach'), 'reach_is_derived' => $rows->count() > 1,
            'clicks' => $clicks, 'results' => $results, 'spend' => $this->major($spendCents),
            'cpm' => $this->ratio($spendCents * 1000, $impressions), 'cpc' => $this->ratio($spendCents, $clicks),
            'ctr' => $this->percent($clicks, $impressions), 'cost_per_result' => $this->ratio($spendCents, $results),
        ];
    }

    private function minor(string $amount): int
    {
        [$w, $f] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $w * 100) + (int) str_pad(substr($f, 0, 2), 2, '0');
    }

    private function major(int $minor): string
    {
        return intdiv($minor, 100).'.'.str_pad((string) abs($minor % 100), 2, '0', STR_PAD_LEFT);
    }

    private function ratio(int $cents, int $denominator): ?string
    {
        return $denominator ? $this->scaled($cents, $denominator, 4, 100) : null;
    }

    private function percent(int $numerator, int $denominator): ?string
    {
        return $denominator ? $this->scaled($numerator * 100, $denominator, 6, 1) : null;
    }

    private function scaled(int $numerator, int $denominator, int $scale, int $centDivisor): string
    {
        $factor = 10 ** $scale;
        $scaled = intdiv(($numerator * $factor) + intdiv($denominator, 2), $denominator * $centDivisor);

        return intdiv($scaled, $factor).'.'.str_pad((string) ($scaled % $factor), $scale, '0', STR_PAD_LEFT);
    }
}
