@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div><h1 class="h3 fw-bold mb-1">Dashboard</h1><p class="text-secondary mb-0">A quick look at how your advertising is performing.</p></div>
        <a href="{{ route('advertisements.create') }}" class="btn btn-primary px-4">Create advertisement</a>
    </div>
    <div class="alert alert-warning d-flex align-items-start gap-3" role="alert">
        <span class="fs-4" aria-hidden="true">!</span>
        <div class="flex-grow-1"><div class="fw-semibold">Connect your Meta account</div><div class="small">Connect Facebook and Instagram to start creating and tracking advertisements.</div></div>
        <a href="{{ route('meta-connection.index') }}" class="btn btn-sm btn-outline-dark">Connect</a>
    </div>
    <div class="row g-3 mb-4">
        @foreach ([
            ['label' => 'Active Campaigns', 'value' => '0', 'icon' => '◫', 'color' => 'primary'],
            ['label' => 'Total Ad Spend', 'value' => '$0.00', 'icon' => '$', 'color' => 'warning'],
            ['label' => 'Total Leads', 'value' => '0', 'icon' => '♙', 'color' => 'success'],
            ['label' => 'Total Reach', 'value' => '0', 'icon' => '↗', 'color' => 'info'],
        ] as $stat)
            <div class="col-12 col-sm-6 col-xl-3"><div class="card stat-card h-100"><div class="card-body d-flex justify-content-between align-items-start">
                <div><div class="stat-label mb-2">{{ $stat['label'] }}</div><div class="stat-value">{{ $stat['value'] }}</div></div>
                <span class="stat-icon bg-{{ $stat['color'] }}-subtle text-{{ $stat['color'] }}">{{ $stat['icon'] }}</span>
            </div></div></div>
        @endforeach
    </div>
    <div class="row g-4">
        <div class="col-12 col-xl-8"><section class="card content-card h-100"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center"><div><h2 class="h5 mb-1">Campaign performance</h2><p class="text-secondary small mb-0">Spend and results over the last 30 days</p></div><span class="badge text-bg-light">Last 30 days</span></div>
            <div class="chart-placeholder" aria-label="Campaign performance chart placeholder">
                @foreach ([28, 44, 37, 62, 51, 76, 58, 82, 66, 88, 72, 94] as $height)<div class="chart-bar" style="height: {{ $height }}%"></div>@endforeach
            </div>
        </div></section></div>
        <div class="col-12 col-xl-4"><section class="card content-card h-100"><div class="card-body">
            <h2 class="h5 mb-1">Quick start</h2><p class="text-secondary small">Complete these steps to launch your first ad.</p>
            <div class="list-group list-group-flush">
                <a href="{{ route('meta-connection.index') }}" class="list-group-item list-group-item-action px-0 py-3">1. Connect Meta account</a>
                <a href="{{ route('advertisements.create') }}" class="list-group-item list-group-item-action px-0 py-3">2. Create an advertisement</a>
                <a href="{{ route('reports.index') }}" class="list-group-item list-group-item-action px-0 py-3">3. Review performance</a>
            </div>
        </div></section></div>
        <div class="col-12"><section class="card content-card">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center pt-4 px-4"><div><h2 class="h5 mb-1">Recent campaigns</h2><p class="text-secondary small mb-0">Your latest advertising campaigns</p></div><a href="{{ route('campaigns.index') }}" class="btn btn-sm btn-outline-primary">View all</a></div>
            <div class="card-body px-4"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Campaign</th><th>Status</th><th>Budget</th><th>Leads</th><th>Reach</th></tr></thead><tbody><tr><td colspan="5"><div class="empty-state">No campaigns yet. Create your first advertisement to get started.</div></td></tr></tbody></table></div></div>
        </section></div>
    </div>
@endsection
