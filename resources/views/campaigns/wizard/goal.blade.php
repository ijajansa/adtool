@extends('layouts.app')
@section('title', 'Choose advertising goal')
@section('content')
@php($activeStep = 1)
<div class="mx-auto wizard-container">
    <div class="d-flex justify-content-between align-items-start mb-3"><div><h1 class="h3 fw-bold mb-1">Choose your goal</h1><p class="text-secondary mb-0">Start with the result that matters most to your business.</p></div>@if($campaign)<a href="{{ route('campaigns.index') }}" class="btn btn-outline-secondary btn-sm">Save & leave</a>@endif</div>
    @include('campaigns.partials.wizard-nav')
    <form method="POST" action="{{ $campaign ? route('campaigns.wizard.goal.update', $campaign) : route('campaigns.store') }}">
        @csrf @if($campaign) @method('PUT') @endif
        <div class="card content-card"><div class="card-body p-4">
            <label for="name" class="form-label fw-semibold">Campaign name</label>
            <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" maxlength="255" required value="{{ old('name', $campaign?->name) }}" placeholder="Example: Summer website promotion">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <fieldset class="mt-4"><legend class="h6">Advertising goal</legend><div class="row g-3">
                @foreach($goals as $value => $goal)
                    <div class="col-12 col-md-4"><input type="radio" class="btn-check" name="goal" id="goal-{{ $value }}" value="{{ $value }}" {{ old('goal', $campaign?->goal?->value) === $value ? 'checked' : '' }} required><label class="goal-card h-100" for="goal-{{ $value }}"><span class="goal-check">✓</span><strong>{{ $goal['label'] }}</strong><small>{{ $goal['description'] }}</small>@if($value === 'lead_generation')<small class="text-warning-emphasis mt-2">Meta form creation comes in the publishing phase.</small>@endif</label></div>
                @endforeach
            </div></fieldset>
        </div><div class="card-footer bg-white border-0 p-4 pt-0 text-end"><button class="btn btn-primary px-4">Save & continue</button></div></div>
    </form>
</div>
@endsection
