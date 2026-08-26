<?php

namespace App\Http\Requests;

use App\Models\MetaConnection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DisconnectMetaConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $connection = $this->user()?->currentBusiness?->metaConnection;

        return $connection instanceof MetaConnection && Gate::allows('disconnect', $connection);
    }

    public function rules(): array
    {
        return ['password' => ['required', 'current_password']];
    }
}
