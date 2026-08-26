@extends('layouts.app')
@section('title', 'Confirm Meta publication')
@section('content')
<div class="mx-auto" style="max-width: 850px">
    <div class="mb-4">
        <a href="{{ route('campaigns.show', $campaign) }}" class="text-decoration-none">&larr; Back to campaign</a>
        <h1 class="h3 fw-bold mt-3">Confirm publication</h1>
        <p class="text-secondary">Review the final checks for {{ $campaign->name }}. Publication runs safely in the background.</p>
    </div>

    @if ($preflightErrors->isNotEmpty())
        <div class="alert alert-danger"><h2 class="h6">Resolve these issues first</h2><ul class="mb-0">@foreach ($preflightErrors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @else
        <div class="alert alert-success">Preflight passed. Your campaign, ad set, and advertisement will all be created <strong>PAUSED</strong>.</div>
    @endif

    <div class="card content-card"><div class="card-body p-4">
        <dl class="row mb-4">
            <dt class="col-sm-4">Goal</dt><dd class="col-sm-8">{{ $campaign->goal->label() }}</dd>
            <dt class="col-sm-4">Ad account</dt><dd class="col-sm-8">{{ $campaign->metaAdAccount?->name }}</dd>
            <dt class="col-sm-4">Facebook Page</dt><dd class="col-sm-8">{{ $campaign->metaPage?->name }}</dd>
            <dt class="col-sm-4">Instagram</dt><dd class="col-sm-8">{{ $campaign->metaInstagramAccount?->username ? '@'.$campaign->metaInstagramAccount->username : 'Not selected — Facebook identity only' }}</dd>
            <dt class="col-sm-4">Audience</dt><dd class="col-sm-8">Ages {{ $campaign->audience?->age_min }}–{{ $campaign->audience?->age_max }}, {{ str($campaign->audience?->location_type?->value)->title() }}</dd>
            <dt class="col-sm-4">Budget</dt><dd class="col-sm-8">{{ $campaign->budget?->currency_code }} {{ $campaign->budget?->amount }} {{ $campaign->budget?->budget_type?->value }} (estimated total: {{ $campaign->estimatedTotalBudget() ?: 'depends on schedule' }})</dd>
            <dt class="col-sm-4">Schedule</dt><dd class="col-sm-8">{{ $campaign->budget?->starts_at?->setTimezone($campaign->metaAdAccount?->timezone_name ?: 'UTC')->format('M j, Y g:i A') }} – {{ $campaign->budget?->ends_at?->setTimezone($campaign->metaAdAccount?->timezone_name ?: 'UTC')->format('M j, Y g:i A') ?: 'No end date' }} ({{ $campaign->metaAdAccount?->timezone_name ?: 'UTC' }})</dd>
            <dt class="col-sm-4">Special category</dt><dd class="col-sm-8">{{ $campaign->special_ad_category_declared ? implode(', ', $campaign->special_ad_categories ?? []) : 'None declared' }}</dd>
        </dl>
        <div class="preview-media rounded overflow-hidden mb-4">@if(str_starts_with($campaign->creative?->mime_type ?? '', 'video/'))<video src="{{ route('campaigns.media.show', $campaign) }}" controls></video>@else<img src="{{ route('campaigns.media.show', $campaign) }}" alt="Advertisement creative preview">@endif</div>
        <div class="alert alert-warning"><strong>Meta payment notice:</strong> Advertising charges will be billed directly by Meta using the payment method configured on the selected Meta ad account.</div>
        <form method="POST" action="{{ route('campaigns.publish', $campaign) }}">
            @csrf
            @foreach ([
                'confirm_paused' => 'I confirm the advertisement details are correct and understand it will be created paused.',
                'confirm_billing' => 'I understand Meta will charge the selected ad account after I activate the advertisement.',
                'confirm_meta_terms' => 'I confirm this advertisement follows Meta advertising policies and the special-ad-category declaration is accurate.',
            ] as $name => $label)
                <div class="form-check mb-3"><input class="form-check-input" type="checkbox" value="1" id="{{ $name }}" name="{{ $name }}" required><label class="form-check-label" for="{{ $name }}">{{ $label }}</label></div>
            @endforeach
            <button class="btn btn-primary" @disabled($preflightErrors->isNotEmpty())>Queue paused publication</button>
        </form>
    </div></div>
</div>
@endsection
