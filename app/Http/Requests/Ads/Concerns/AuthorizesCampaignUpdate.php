<?php

namespace App\Http\Requests\Ads\Concerns;

use App\Models\AdCampaign;
use Illuminate\Support\Facades\Gate;

trait AuthorizesCampaignUpdate
{
    public function authorize(): bool
    {
        $campaign = $this->route('campaign');

        return $campaign instanceof AdCampaign && Gate::allows('update', $campaign);
    }
}
