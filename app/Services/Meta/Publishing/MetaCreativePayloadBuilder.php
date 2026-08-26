<?php

namespace App\Services\Meta\Publishing;

use App\Enums\AdCreativeFormat;
use App\Models\AdCampaign;

class MetaCreativePayloadBuilder
{
    public function build(AdCampaign $campaign): array
    {
        $creative = $campaign->creative;
        $destination = match ($campaign->goal->value) {
            'website_traffic' => $creative->destination_url,
            'lead_generation' => 'https://www.facebook.com/'.$campaign->metaPage->meta_page_id,
            'whatsapp_messages' => 'https://wa.me/'.ltrim($creative->whatsapp_number, '+'),
        };
        $ctaValue = ['link' => $destination];
        if ($campaign->goal->value === 'lead_generation') {
            $ctaValue['lead_gen_form_id'] = $creative->meta_lead_form_id;
        }

        $story = ['page_id' => $campaign->metaPage->meta_page_id];
        if ($campaign->metaInstagramAccount) {
            $story['instagram_user_id'] = $campaign->metaInstagramAccount->meta_instagram_account_id;
        }
        if ($campaign->goal->value === 'whatsapp_messages') {
            $story['whats_app_business_phone_number'] = $creative->whatsapp_number;
        }

        $mediaData = [
            'message' => $creative->primary_text,
            'call_to_action' => ['type' => $creative->call_to_action, 'value' => $ctaValue],
        ];
        if ($creative->format === AdCreativeFormat::SingleImage) {
            $story['link_data'] = [...$mediaData, 'link' => $destination, 'name' => $creative->headline, 'description' => $creative->description, 'image_hash' => $creative->meta_image_hash];
        } else {
            $story['video_data'] = [...$mediaData, 'title' => $creative->headline, 'link_description' => $creative->description, 'video_id' => $creative->meta_video_id];
        }

        return ['name' => $campaign->name.' - Creative', 'object_story_spec' => $story];
    }
}
