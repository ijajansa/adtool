<header class="app-topbar d-flex align-items-center justify-content-between gap-2 px-3 px-md-4 sticky-top">
    <div class="d-flex align-items-center gap-2 gap-md-3 min-w-0">
        <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebar" aria-controls="appSidebar" aria-label="Open navigation">Menu</button>
        <div class="dropdown">
            <button class="btn btn-light border d-flex align-items-center gap-2 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="business-avatar" aria-hidden="true">{{ Str::upper(Str::substr(auth()->user()->currentBusiness->name, 0, 1)) }}</span>
                <span class="d-none d-sm-inline text-truncate business-switcher-name">{{ auth()->user()->currentBusiness->name }}</span>
            </button>
            <ul class="dropdown-menu shadow-sm border-0">
                <li><h6 class="dropdown-header">Switch business</h6></li>
                @foreach ($accessibleBusinesses as $business)
                    <li>
                        <form method="POST" action="{{ route('businesses.switch', $business) }}">
                            @csrf
                            <button type="submit" class="dropdown-item d-flex justify-content-between align-items-center gap-3 {{ $business->is(auth()->user()->currentBusiness) ? 'active' : '' }}">
                                <span>{{ $business->name }}</span>
                                @if ($business->is(auth()->user()->currentBusiness))<span aria-label="Current business">✓</span>@endif
                            </button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
    <div class="dropdown">
        <button class="btn border-0 d-flex align-items-center gap-2 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="avatar">{{ Str::upper(Str::substr(auth()->user()->name, 0, 1)) }}</span>
            <span class="d-none d-md-inline text-start"><span class="d-block small fw-semibold">{{ auth()->user()->name }}</span><span class="d-block text-secondary" style="font-size: .72rem">{{ auth()->user()->email }}</span></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
            <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Account profile</a></li>
            <li><a class="dropdown-item" href="{{ route('business.settings.edit') }}">Business settings</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="dropdown-item text-danger">Logout</button></form></li>
        </ul>
    </div>
</header>
