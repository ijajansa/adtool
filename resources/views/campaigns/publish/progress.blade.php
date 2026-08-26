@extends('layouts.app')
@section('title', 'Publication progress')
@section('content')
<div class="mx-auto" style="max-width: 760px">
    <h1 class="h3 fw-bold">Publishing {{ $campaign->name }}</h1>
    <p class="text-secondary">You may leave this page. The queue worker will continue in the background.</p>
    <div class="card content-card"><div class="card-body p-4">
        <div class="d-flex align-items-center gap-3 mb-3"><div class="spinner-border text-primary" id="publicationSpinner" role="status"></div><div><div class="fw-semibold" id="publicationStatus">{{ str($campaign->publicationAttempt?->status?->value ?? 'queued')->replace('_', ' ')->title() }}</div><div class="text-secondary small" id="publicationStage">Stage: {{ str($campaign->publicationAttempt?->current_stage ?? 'queued')->replace('_', ' ') }}</div></div></div>
        <div id="publicationMessage" class="alert alert-danger d-none"></div>
        <p class="small text-secondary mb-4">Meta objects are always submitted as PAUSED. Activation is a separate confirmed action.</p>
        <a class="btn btn-outline-primary" href="{{ route('campaigns.show', $campaign) }}">View campaign</a>
        @if ($campaign->publicationAttempt?->retryable)
            <form class="d-inline" method="POST" action="{{ route('campaigns.publish.retry', $campaign) }}">@csrf<button class="btn btn-warning">Retry safely</button></form>
        @endif
    </div></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const endpoint = @json(route('campaigns.publish.status', $campaign));
    const terminal = new Set(['completed', 'failed']);
    const poll = async () => {
        try {
            const response = await fetch(endpoint, {headers: {'Accept': 'application/json'}});
            if (!response.ok) return;
            const data = await response.json();
            document.getElementById('publicationStatus').textContent = (data.attempt_status || 'queued').replaceAll('_', ' ');
            document.getElementById('publicationStage').textContent = 'Stage: ' + (data.stage || 'queued').replaceAll('_', ' ');
            if (data.message) { const message = document.getElementById('publicationMessage'); message.textContent = data.message; message.classList.remove('d-none'); }
            if (!terminal.has(data.attempt_status) && data.attempt_status !== 'partial') return setTimeout(poll, 2500);
            document.getElementById('publicationSpinner').classList.add('d-none');
            if (data.complete) setTimeout(() => window.location.href = @json(route('campaigns.show', $campaign)), 1000);
        } catch (_) { setTimeout(poll, 5000); }
    };
    setTimeout(poll, 1500);
});
</script>
@endsection
