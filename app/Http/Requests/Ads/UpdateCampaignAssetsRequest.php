<?php

namespace App\Http\Requests\Ads;

use App\Http\Requests\Ads\Concerns\AuthorizesCampaignUpdate;
use App\Models\MetaInstagramAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCampaignAssetsRequest extends FormRequest
{
    use AuthorizesCampaignUpdate;

    public function rules(): array
    {
        $businessId = $this->user()->current_business_id;
        $connectionId = $this->user()->currentBusiness->metaConnection?->id ?? 0;
        $owned = fn ($query) => $query->where('business_id', $businessId)->where('meta_connection_id', $connectionId);

        return [
            'meta_ad_account_id' => ['required', 'integer', Rule::exists('meta_ad_accounts', 'id')->where($owned)],
            'meta_page_id' => ['required', 'integer', Rule::exists('meta_pages', 'id')->where($owned)],
            'meta_instagram_account_id' => ['nullable', 'integer', Rule::exists('meta_instagram_accounts', 'id')->where($owned)],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || ! $this->filled('meta_instagram_account_id')) {
                return;
            }
            $instagram = MetaInstagramAccount::find($this->integer('meta_instagram_account_id'));
            if (! $instagram || $instagram->meta_page_id !== $this->integer('meta_page_id')) {
                $validator->errors()->add('meta_instagram_account_id', 'The Instagram account must be connected to the selected Facebook Page.');
            }
        }];
    }
}
