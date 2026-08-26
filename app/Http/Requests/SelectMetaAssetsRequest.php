<?php

namespace App\Http\Requests;

use App\Models\MetaConnection;
use App\Models\MetaInstagramAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SelectMetaAssetsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $connection = $this->user()?->currentBusiness?->metaConnection;

        return $connection instanceof MetaConnection && Gate::allows('selectAssets', $connection);
    }

    public function rules(): array
    {
        $businessId = $this->user()->current_business_id;
        $connectionId = $this->user()->currentBusiness->metaConnection->id;
        $ownedByTenant = fn ($query) => $query
            ->where('business_id', $businessId)
            ->where('meta_connection_id', $connectionId);

        return [
            'meta_business_account_id' => ['nullable', 'integer', Rule::exists('meta_business_accounts', 'id')->where($ownedByTenant)],
            'meta_ad_account_id' => ['required', 'integer', Rule::exists('meta_ad_accounts', 'id')->where($ownedByTenant)],
            'meta_page_id' => ['required', 'integer', Rule::exists('meta_pages', 'id')->where($ownedByTenant)],
            'meta_instagram_account_id' => ['nullable', 'integer', Rule::exists('meta_instagram_accounts', 'id')->where($ownedByTenant)],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty() || ! $this->filled('meta_instagram_account_id')) {
                    return;
                }

                $instagram = MetaInstagramAccount::find($this->integer('meta_instagram_account_id'));
                if (! $instagram || $instagram->meta_page_id !== $this->integer('meta_page_id')) {
                    $validator->errors()->add(
                        'meta_instagram_account_id',
                        'The selected Instagram account is not connected to the selected Facebook Page.',
                    );
                }
            },
        ];
    }
}
