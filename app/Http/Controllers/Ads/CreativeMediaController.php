<?php

namespace App\Http\Controllers\Ads;

use App\Http\Controllers\Controller;
use App\Models\AdCampaign;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CreativeMediaController extends Controller
{
    public function __invoke(AdCampaign $campaign): StreamedResponse
    {
        Gate::authorize('view', $campaign);
        $creative = $campaign->creative;
        abort_unless($creative && Storage::disk('local')->exists($creative->media_path), 404);

        return Storage::disk('local')->response($creative->media_path, 'creative-media', [
            'Content-Type' => $creative->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
