<?php

namespace App\Enums;

enum AdPublicationStatus: string
{
    case Queued = 'queued';
    case Validating = 'validating';
    case UploadingMedia = 'uploading_media';
    case CreatingForm = 'creating_form';
    case CreatingCampaign = 'creating_campaign';
    case CreatingAdSet = 'creating_adset';
    case CreatingCreative = 'creating_creative';
    case CreatingAd = 'creating_ad';
    case Completed = 'completed';
    case Failed = 'failed';
    case Partial = 'partial';

    public function terminal(): bool
    {
        return in_array($this, [self::Completed, self::Failed, self::Partial], true);
    }
}
