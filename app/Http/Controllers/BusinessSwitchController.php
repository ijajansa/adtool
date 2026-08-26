<?php

namespace App\Http\Controllers;

use App\Http\Requests\SwitchBusinessRequest;
use App\Models\Business;
use Illuminate\Http\RedirectResponse;

class BusinessSwitchController extends Controller
{
    public function __invoke(SwitchBusinessRequest $request, Business $business): RedirectResponse
    {
        $request->user()->forceFill(['current_business_id' => $business->id])->save();
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
