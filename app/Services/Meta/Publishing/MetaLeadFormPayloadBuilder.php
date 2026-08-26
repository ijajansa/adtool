<?php

namespace App\Services\Meta\Publishing;

use App\Models\AdCampaign;

class MetaLeadFormPayloadBuilder
{
    public function build(AdCampaign $campaign): array
    {
        $creative = $campaign->creative;
        $questions = collect($creative->requested_fields)->map(fn ($field) => ['type' => $field])->all();
        $thankYou = [
            'title' => $creative->completion_title,
            'body' => $creative->completion_message,
            'button_text' => $creative->completion_button_text,
            'button_type' => $creative->completion_destination_url ? 'VIEW_WEBSITE' : 'NONE',
        ];
        if ($creative->completion_destination_url) {
            $thankYou['website_url'] = $creative->completion_destination_url;
        }

        return [
            'name' => $creative->lead_form_name,
            'questions' => $questions,
            'privacy_policy' => ['url' => $creative->privacy_policy_url, 'link_text' => $creative->privacy_policy_link_text],
            'thank_you_page' => $thankYou,
            'allow_organic_lead_retrieval' => false,
        ];
    }
}
