@extends('layouts.app')
@section('title', 'Select Meta assets')
@section('content')
@php($activeStep = 2)
<div class="mx-auto wizard-container">
    <div class="d-flex justify-content-between mb-3"><div><h1 class="h3 fw-bold mb-1">Select Meta assets</h1><p class="text-secondary mb-0">Only assets imported for this workspace are available.</p></div><a href="{{ route('campaigns.index') }}" class="btn btn-outline-secondary btn-sm align-self-start">Save & leave</a></div>
    @include('campaigns.partials.wizard-nav')
    @if($adAccounts->isEmpty() || $pages->isEmpty())<div class="alert alert-warning">No usable {{ $adAccounts->isEmpty() ? 'ad account' : 'Facebook Page' }} is available. <a href="{{ route('meta-connection.index') }}">Review Meta setup</a>.</div>@endif
    <form method="POST" action="{{ route('campaigns.wizard.assets.update', $campaign) }}">@csrf @method('PUT')
        <div class="card content-card"><div class="card-body p-4 row g-4">
            <div class="col-12"><label class="form-label fw-semibold" for="meta_ad_account_id">Meta ad account</label><select class="form-select" id="meta_ad_account_id" name="meta_ad_account_id" required><option value="">Choose an ad account</option>@foreach($adAccounts as $asset)<option value="{{ $asset->id }}" {{ (string)old('meta_ad_account_id', $campaign->meta_ad_account_id) === (string)$asset->id ? 'selected' : '' }}>{{ $asset->name }} · {{ $asset->currency ?: 'Currency unavailable' }}</option>@endforeach</select></div>
            <div class="col-12 col-md-6"><label class="form-label fw-semibold" for="meta_page_id">Facebook Page</label><select class="form-select" id="meta_page_id" name="meta_page_id" required><option value="">Choose a Page</option>@foreach($pages as $asset)<option value="{{ $asset->id }}" {{ (string)old('meta_page_id', $campaign->meta_page_id) === (string)$asset->id ? 'selected' : '' }}>{{ $asset->name }}</option>@endforeach</select></div>
            <div class="col-12 col-md-6"><label class="form-label fw-semibold" for="meta_instagram_account_id">Instagram account <span class="text-secondary fw-normal">(optional)</span></label><select class="form-select" id="meta_instagram_account_id" name="meta_instagram_account_id"><option value="">No Instagram account</option>@foreach($instagramAccounts as $asset)<option value="{{ $asset->id }}" data-page="{{ $asset->meta_page_id }}" {{ (string)old('meta_instagram_account_id', $campaign->meta_instagram_account_id) === (string)$asset->id ? 'selected' : '' }}>{{ '@'.($asset->username ?: $asset->name ?: $asset->meta_instagram_account_id) }}</option>@endforeach</select><div class="form-text">Instagram must be linked to the selected Page.</div></div>
        </div><div class="card-footer bg-white d-flex justify-content-between p-4"><a href="{{ route('campaigns.wizard.goal.edit', $campaign) }}" class="btn btn-outline-secondary">Back</a><button class="btn btn-primary">Save & continue</button></div></div>
    </form>
</div>
<script>document.getElementById('meta_page_id').addEventListener('change',function(){const page=this.value,select=document.getElementById('meta_instagram_account_id');[...select.options].forEach((option,index)=>{if(index){option.hidden=option.dataset.page!==page;if(option.hidden&&option.selected)select.value='';}});});</script>
@endsection
