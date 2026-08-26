<?php

namespace App\Services\Meta;

use App\Models\Business;
use App\Models\MetaConnection;

class MetaSetupStatusService
{
    /** @return array{steps: list<array{label: string, complete: bool}>, completed: int, warnings: list<string>} */
    public function for(Business $business): array
    {
        $connection = $business->metaConnection;
        $connected = $connection?->status === MetaConnection::STATUS_CONNECTED;
        $assetsSelected = $connected
            && $business->selectedMetaAdAccount !== null
            && $business->selectedMetaPage !== null;
        $warnings = [];

        if ($connection?->status === MetaConnection::STATUS_EXPIRED) {
            $warnings[] = 'Your Meta connection has expired. Reconnect it to continue synchronizing assets.';
        }

        $declinedRequired = array_intersect(config('meta.oauth_scopes'), $connection?->declined_scopes ?? []);
        if ($declinedRequired) {
            $warnings[] = 'Required Meta permissions were declined: '.implode(', ', $declinedRequired).'.';
        }

        if ($connected && $business->metaAdAccounts->isEmpty()) {
            $warnings[] = 'No accessible Meta ad account was found.';
        }

        if ($connected && $business->metaPages->isEmpty()) {
            $warnings[] = 'No accessible Facebook Page was found.';
        }

        if ($connected && ! $assetsSelected) {
            $warnings[] = 'Select an ad account and Facebook Page to complete Meta setup.';
        }

        $steps = [
            ['label' => 'Business profile completed', 'complete' => true],
            ['label' => 'Connect Meta account', 'complete' => $connected],
            ['label' => 'Select ad account and Facebook Page', 'complete' => $assetsSelected],
            ['label' => 'Create first advertisement', 'complete' => $business->adCampaigns()->exists()],
        ];

        return [
            'steps' => $steps,
            'completed' => count(array_filter($steps, fn (array $step) => $step['complete'])),
            'warnings' => $warnings,
        ];
    }
}
