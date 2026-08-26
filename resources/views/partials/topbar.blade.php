<header class="app-topbar d-flex align-items-center justify-content-between px-3 px-md-4 sticky-top">
    <div class="d-flex align-items-center gap-3">
        <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebar" aria-controls="appSidebar" aria-label="Open navigation">☰</button>
        <div>
            <div class="fw-semibold">Welcome back, {{ auth()->user()->name }}</div>
            <div class="text-secondary small d-none d-sm-block">Manage your advertising workspace</div>
        </div>
    </div>
    <div class="dropdown">
        <button class="btn border-0 d-flex align-items-center gap-2 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="avatar">{{ Str::upper(Str::substr(auth()->user()->name, 0, 1)) }}</span>
            <span class="d-none d-md-inline text-start">
                <span class="d-block small fw-semibold">{{ auth()->user()->name }}</span>
                <span class="d-block text-secondary" style="font-size: .72rem">{{ auth()->user()->email }}</span>
            </span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
            <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Settings</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="dropdown-item text-danger">Logout</button></form></li>
        </ul>
    </div>
</header>
