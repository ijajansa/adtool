<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessSelected
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $user = $request->user();
        $accessibleBusinesses = $user->businesses()
            ->active()
            ->wherePivot('status', true)
            ->orderBy('businesses.name')
            ->get();

        if ($accessibleBusinesses->isEmpty()) {
            if ($user->current_business_id !== null) {
                $user->forceFill(['current_business_id' => null])->save();
            }

            return redirect()->route('business.onboarding.create');
        }

        $currentBusiness = $accessibleBusinesses->firstWhere('id', $user->current_business_id);

        if (! $currentBusiness) {
            $currentBusiness = $accessibleBusinesses->first();
            $user->forceFill(['current_business_id' => $currentBusiness->id])->save();
        }

        $user->setRelation('currentBusiness', $currentBusiness);
        $user->setRelation('businesses', $accessibleBusinesses);
        view()->share('accessibleBusinesses', $accessibleBusinesses);

        return $next($request);
    }
}
