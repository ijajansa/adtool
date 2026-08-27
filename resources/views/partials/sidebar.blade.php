<aside class="app-sidebar offcanvas offcanvas-start" tabindex="-1" id="appSidebar" aria-labelledby="appSidebarLabel">
    <div class="offcanvas-header border-bottom border-secondary px-4 py-3">
        <a class="sidebar-brand" href="{{ route('dashboard') }}" id="appSidebarLabel">AdSimplify<span class="brand-dot">.</span></a>
        <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#appSidebar" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-3">
        <div class="sidebar-section-label px-3 pb-2 pt-1">WORKSPACE</div>
        <nav class="sidebar-nav nav nav-pills flex-column gap-1" aria-label="Main navigation">
            @php
                $navigation = [
                    ['route' => 'dashboard', 'match' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'D'],
                    ['route' => 'meta-connection.index', 'match' => 'meta-connection.*', 'label' => 'Meta Connection', 'icon' => 'M'],
                    ['route' => 'campaigns.index', 'match' => ['campaigns.index', 'campaigns.show'], 'label' => 'Campaigns', 'icon' => 'C'],
                    ['route' => 'campaigns.create', 'match' => ['campaigns.create', 'campaigns.store', 'campaigns.wizard.*', 'campaigns.review'], 'label' => 'Create Advertisement', 'icon' => '+'],
                    ['route' => 'leads.index', 'match' => 'leads.*', 'label' => 'Leads', 'icon' => 'L'],
                    ['route' => 'analytics.compare', 'match' => 'analytics.*', 'label' => 'Reports', 'icon' => 'R'],
                    ['route' => 'ad-spend.index', 'match' => 'ad-spend.*', 'label' => 'Ad Spend', 'icon' => 'B'],
                    ['route' => 'business.settings.edit', 'match' => 'business.settings.*', 'label' => 'Settings', 'icon' => 'S'],
                ];
            @endphp
            @foreach ($navigation as $item)
                <a class="nav-link {{ request()->routeIs($item['match']) ? 'active' : '' }}" href="{{ route($item['route']) }}" @if (request()->routeIs($item['match'])) aria-current="page" @endif>
                    <span class="nav-icon" aria-hidden="true">{{ $item['icon'] }}</span>{{ $item['label'] }}
                </a>
            @endforeach
        </nav>
        <div class="mt-auto rounded-3 p-3" style="background: rgba(255,255,255,.06)">
            <div class="small fw-semibold text-white">Need help?</div>
            <div class="small text-secondary mt-1">Contact support for help with your workspace.</div>
        </div>
    </div>
</aside>
