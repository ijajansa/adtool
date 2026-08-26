<?php

namespace App\Http\Requests;

use App\Models\Business;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class SwitchBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        $business = $this->route('business');

        return $business instanceof Business && Gate::allows('view', $business);
    }

    public function rules(): array
    {
        return [];
    }
}
