<?php

namespace App\Services\Meta\Insights;

use App\Exceptions\MetaApiException;
use App\Models\AdBudgetChangeLog;
use App\Models\AdCampaign;
use App\Models\User;
use App\Notifications\BudgetUpdateCompleted;
use App\Notifications\BudgetUpdateFailed;
use App\Services\Ads\SpendingControlService;
use App\Services\Meta\MetaGraphApiClient;
use App\Services\Meta\Publishing\MetaBudgetConverter;
use Illuminate\Support\Carbon;

class CampaignBudgetUpdateService
{
    public function __construct(private MetaGraphApiClient $client, private MetaBudgetConverter $money, private SpendingControlService $controls) {}

    public function update(AdCampaign $campaign, User $user, string $amount, ?Carbon $endsAt = null): void
    {
        $campaign->loadMissing(['business.spendingControl', 'metaConnection', 'budget', 'metaAdAccount']);
        $this->controls->validate($campaign, $amount, $user);
        $field = $campaign->budget->budget_type->value === 'daily' ? 'daily_budget' : 'lifetime_budget';
        $current = $this->client->get($campaign->meta_adset_id, $campaign->metaConnection->access_token, ['fields' => 'daily_budget,lifetime_budget,end_time,configured_status']);
        $log = AdBudgetChangeLog::withoutBusinessScope()->create([
            'business_id' => $campaign->business_id, 'ad_campaign_id' => $campaign->id, 'changed_by' => $user->id,
            'old_budget_type' => $campaign->budget->budget_type->value, 'old_amount' => $campaign->budget->amount,
            'new_budget_type' => $campaign->budget->budget_type->value, 'new_amount' => $amount,
            'currency_code' => $campaign->budget->currency_code, 'meta_adset_id' => $campaign->meta_adset_id, 'status' => 'pending',
        ]);
        try {
            $payload = [$field => $this->money->toMinorUnits($amount, $campaign->budget->currency_code)];
            if ($endsAt) {
                $payload['end_time'] = $endsAt->setTimezone($campaign->metaAdAccount->timezone_name)->format('Y-m-d\TH:i:sP');
            }
            $this->client->postFormWithToken($campaign->meta_adset_id, $campaign->metaConnection->access_token, $payload);
            $campaign->budget->update(['amount' => $amount, 'ends_at' => $endsAt ?: $campaign->budget->ends_at]);
            $log->update(['status' => 'completed', 'completed_at' => now()]);
            $user->notify(BudgetUpdateCompleted::for($campaign));
        } catch (MetaApiException $exception) {
            $context = $exception->context();
            $log->update(['status' => 'failed', 'meta_error_code' => $context['meta_code'] ?? null, 'safe_error_message' => $exception->getMessage(), 'completed_at' => now()]);
            $user->notify(BudgetUpdateFailed::for($campaign));
            throw $exception;
        }
    }
}
