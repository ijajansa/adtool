@extends('layouts.app')
@section('title', 'Budget and schedule')
@section('content')
@php($activeStep = 5)
@php($budget = $campaign->budget)
@php($currency = strtoupper($campaign->metaAdAccount->currency ?: $campaign->business->currency_code))
<div class="mx-auto wizard-container">
    <div class="d-flex justify-content-between mb-3"><div><h1 class="h3 fw-bold mb-1">Budget and schedule</h1><p class="text-secondary mb-0">Set how much to spend and when your advertisement should run.</p></div><a href="{{ route('campaigns.index') }}" class="btn btn-outline-secondary btn-sm align-self-start">Save & leave</a></div>
    @include('campaigns.partials.wizard-nav')
    <form method="POST" action="{{ route('campaigns.wizard.budget.update',$campaign) }}">@csrf @method('PUT')
    <div class="card content-card"><div class="card-body p-4 row g-4">
        <div class="col-12"><div class="alert alert-light border mb-0"><strong>Currency:</strong> {{ $currency }} <span class="mx-2">·</span><strong>Timezone:</strong> {{ $timezone }}<div class="small text-secondary mt-1">Times below use the selected ad account timezone. They are stored in UTC.</div></div></div>
        <div class="col-md-6"><label class="form-label fw-semibold" for="budget_type">Budget type</label><select class="form-select" id="budget_type" name="budget_type"><option value="daily" {{ old('budget_type',$budget?->budget_type?->value)==='daily'?'selected':'' }}>Daily</option><option value="lifetime" {{ old('budget_type',$budget?->budget_type?->value)==='lifetime'?'selected':'' }}>Lifetime</option></select></div>
        <div class="col-md-6"><label class="form-label fw-semibold" for="amount">Amount ({{ $currency }})</label><input type="number" step="0.01" min="0" class="form-control" id="amount" name="amount" value="{{ old('amount',$budget?->amount) }}" required><div class="form-text">Stored in major currency units. Local draft minimum: {{ number_format(config('ads.minimum_budget.'.$currency,config('ads.minimum_budget.default')),2) }} {{ $currency }}. Meta validates its own minimum later.</div></div>
        <div class="col-md-6"><label class="form-label fw-semibold" for="starts_at">Start date and time</label><input type="datetime-local" class="form-control" id="starts_at" name="starts_at" value="{{ old('starts_at',$budget?->starts_at?->copy()->setTimezone($timezone)->format('Y-m-d\TH:i')) }}" required></div>
        <div class="col-md-6"><label class="form-label fw-semibold" for="ends_at">End date and time <span class="text-secondary fw-normal" id="endOptional">(optional for daily)</span></label><input type="datetime-local" class="form-control" id="ends_at" name="ends_at" value="{{ old('ends_at',$budget?->ends_at?->copy()->setTimezone($timezone)->format('Y-m-d\TH:i')) }}"></div>
    </div><div class="card-footer bg-white d-flex justify-content-between p-4"><a href="{{ route('campaigns.wizard.audience.edit',$campaign) }}" class="btn btn-outline-secondary">Back</a><button class="btn btn-primary">Save & review</button></div></div></form>
</div>
<script>(()=>{const type=document.getElementById('budget_type'),end=document.getElementById('ends_at'),label=document.getElementById('endOptional'),refresh=()=>{end.required=type.value==='lifetime';label.textContent=type.value==='lifetime'?'(required)':'(optional for daily)'};type.addEventListener('change',refresh);refresh();})();</script>
@endsection
