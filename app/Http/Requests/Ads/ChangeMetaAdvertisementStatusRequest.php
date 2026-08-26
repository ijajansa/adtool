<?php

namespace App\Http\Requests\Ads;

use Illuminate\Foundation\Http\FormRequest;

class ChangeMetaAdvertisementStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->routeIs('campaigns.activate') ? 'activate' : 'pause';

        return $this->user()?->can($ability, $this->route('campaign')) ?? false;
    }

    public function rules(): array
    {
        return ['confirm' => ['accepted']];
    }
}
