@extends('layouts.app')

@section('title', 'Meta Connection')

@section('content')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div><h1 class="h3 fw-bold mb-1">Meta Connection</h1><p class="text-secondary mb-0">Import the Meta assets available to {{ $business->name }}.</p></div>
        @if ($connection && $connection->status !== \App\Models\MetaConnection::STATUS_REVOKED)
            <span class="badge text-bg-{{ $connection->status === 'connected' ? 'success' : ($connection->status === 'expired' ? 'danger' : 'warning') }} fs-6">{{ Str::headline($connection->status) }}</span>
        @endif
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert"><ul class="mb-0 ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    @if (! $connection || $connection->status === \App\Models\MetaConnection::STATUS_REVOKED)
        <section class="card content-card"><div class="card-body p-4 p-lg-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <span class="badge text-bg-primary mb-3">Official Meta OAuth</span>
                    <h2 class="h4 fw-bold">Connect Facebook and Instagram securely</h2>
                    <p class="text-secondary">You will be redirected to Meta to approve access. AdSimplify never requests or stores your Facebook or Instagram password.</p>
                    <h3 class="h6 mt-4">Requested permissions</h3>
                    <div class="d-flex flex-wrap gap-2">@foreach ($requestedScopes as $scope)<span class="badge text-bg-light border">{{ $scope }}</span>@endforeach</div>
                </div>
                <div class="col-lg-5 text-lg-end">
                    @can('connect', [\App\Models\MetaConnection::class, $business])
                        <a class="btn btn-primary btn-lg" href="{{ route('meta-connection.redirect') }}">Connect with Meta</a>
                    @else
                        <div class="alert alert-info mb-0 text-start">Ask a business owner or admin to connect Meta.</div>
                    @endcan
                </div>
            </div>
        </div></section>
    @else
        <section class="card content-card mb-4"><div class="card-body p-4">
            <div class="row g-4 align-items-start">
                <div class="col-lg-7">
                    <h2 class="h5 mb-3">Connection details</h2>
                    <dl class="row mb-0 small">
                        <dt class="col-sm-4 text-secondary">Meta user</dt><dd class="col-sm-8">{{ $connection->meta_user_name ?: 'Not available' }}</dd>
                        <dt class="col-sm-4 text-secondary">Token expires</dt><dd class="col-sm-8">{{ $connection->token_expires_at?->timezone($business->timezone)->format('M j, Y g:i A T') ?? 'Not provided by Meta' }}</dd>
                        <dt class="col-sm-4 text-secondary">Last synchronized</dt><dd class="col-sm-8">{{ $connection->last_synced_at?->timezone($business->timezone)->diffForHumans() ?? 'Not yet synchronized' }}</dd>
                    </dl>
                    <div class="mt-3"><span class="small text-secondary d-block mb-2">Granted permissions</span>@forelse ($connection->granted_scopes ?? [] as $scope)<span class="badge text-bg-success me-1 mb-1">{{ $scope }}</span>@empty<span class="small">None reported</span>@endforelse</div>
                    @if ($connection->declined_scopes)<div class="mt-3"><span class="small text-secondary d-block mb-2">Declined permissions</span>@foreach ($connection->declined_scopes as $scope)<span class="badge text-bg-warning me-1 mb-1">{{ $scope }}</span>@endforeach</div>@endif
                </div>
                <div class="col-lg-5">
                    <div class="row g-2 text-center mb-3">
                        @foreach ([['Business portfolios', $business->metaBusinessAccounts->count()], ['Ad accounts', $business->metaAdAccounts->count()], ['Pages', $business->metaPages->count()], ['Instagram', $business->metaInstagramAccounts->count()]] as [$label, $count])
                            <div class="col-6"><div class="rounded-3 bg-light p-3"><div class="h4 mb-0">{{ $count }}</div><div class="small text-secondary">{{ $label }}</div></div></div>
                        @endforeach
                    </div>
                    @can('sync', $connection)<form method="POST" action="{{ route('meta-connection.sync') }}" class="mb-2">@csrf<button type="submit" class="btn btn-outline-primary w-100">Sync assets</button></form>@endcan
                    @if (in_array($connection->status, [\App\Models\MetaConnection::STATUS_EXPIRED, \App\Models\MetaConnection::STATUS_ERROR], true))
                        @can('connect', [\App\Models\MetaConnection::class, $business])<a class="btn btn-primary w-100 mb-2" href="{{ route('meta-connection.redirect') }}">Reconnect with Meta</a>@endcan
                    @endif
                    @can('disconnect', $connection)
                        <form method="POST" action="{{ route('meta-connection.disconnect') }}" onsubmit="return confirm('Disconnect Meta and clear stored access tokens?')">
                            @csrf @method('delete')
                            <label for="disconnect_password" class="form-label small">Confirm password to disconnect</label>
                            <input id="disconnect_password" name="password" type="password" class="form-control form-control-sm mb-2 @error('password') is-invalid @enderror" required autocomplete="current-password">
                            <button type="submit" class="btn btn-outline-danger w-100">Disconnect</button>
                        </form>
                    @endcan
                </div>
            </div>
        </div></section>

        @can('selectAssets', $connection)
            <section class="card content-card mb-4"><div class="card-body p-4">
                <h2 class="h5">Choose assets for this workspace</h2><p class="text-secondary small">An ad account and Facebook Page are required. The optional Instagram account must belong to the selected Page.</p>
                <form method="POST" action="{{ route('meta-connection.assets.update') }}">@csrf @method('put')
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="meta_business_account_id">Business portfolio <span class="small text-secondary">(optional)</span></label><select class="form-select" id="meta_business_account_id" name="meta_business_account_id"><option value="">None</option>@foreach ($business->metaBusinessAccounts as $asset)<option value="{{ $asset->id }}" @selected(old('meta_business_account_id', $asset->is_selected ? $asset->id : null) == $asset->id)>{{ $asset->name }}</option>@endforeach</select></div>
                        <div class="col-md-6"><label class="form-label" for="meta_ad_account_id">Ad account</label><select class="form-select @error('meta_ad_account_id') is-invalid @enderror" id="meta_ad_account_id" name="meta_ad_account_id" required><option value="">Choose an ad account</option>@foreach ($business->metaAdAccounts as $asset)<option value="{{ $asset->id }}" @selected(old('meta_ad_account_id', $asset->is_selected ? $asset->id : null) == $asset->id)>{{ $asset->name }} ({{ $asset->currency }})</option>@endforeach</select>@error('meta_ad_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6"><label class="form-label" for="meta_page_id">Facebook Page</label><select class="form-select @error('meta_page_id') is-invalid @enderror" id="meta_page_id" name="meta_page_id" required><option value="">Choose a Page</option>@foreach ($business->metaPages as $asset)<option value="{{ $asset->id }}" @selected(old('meta_page_id', $asset->is_selected ? $asset->id : null) == $asset->id)>{{ $asset->name }}</option>@endforeach</select>@error('meta_page_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6"><label class="form-label" for="meta_instagram_account_id">Instagram account <span class="small text-secondary">(optional)</span></label><select class="form-select @error('meta_instagram_account_id') is-invalid @enderror" id="meta_instagram_account_id" name="meta_instagram_account_id"><option value="">None</option>@foreach ($business->metaInstagramAccounts as $asset)<option value="{{ $asset->id }}" @selected(old('meta_instagram_account_id', $asset->is_selected ? $asset->id : null) == $asset->id)>{{ $asset->username ? '@'.$asset->username : ($asset->name ?: 'Instagram account') }}</option>@endforeach</select>@error('meta_instagram_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    </div>
                    <button class="btn btn-primary mt-3" type="submit">Save asset selection</button>
                </form>
            </div></section>
        @endcan

        @foreach ([
            ['title' => 'Meta Business Portfolios', 'assets' => $business->metaBusinessAccounts, 'status' => 'verification_status', 'id' => 'meta_business_id'],
            ['title' => 'Ad Accounts', 'assets' => $business->metaAdAccounts, 'status' => 'account_status', 'id' => 'meta_ad_account_id'],
            ['title' => 'Facebook Pages', 'assets' => $business->metaPages, 'status' => 'category', 'id' => 'meta_page_id'],
            ['title' => 'Instagram Accounts', 'assets' => $business->metaInstagramAccounts, 'status' => 'username', 'id' => 'meta_instagram_account_id'],
        ] as $section)
            <section class="card content-card mb-3"><div class="card-body p-0"><div class="px-4 pt-4"><h2 class="h5">{{ $section['title'] }}</h2></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Name</th><th>Meta ID</th><th>Status</th><th>Selection</th></tr></thead><tbody>
                @forelse ($section['assets'] as $asset)<tr><td>{{ $asset->name ?? ($asset->username ? '@'.$asset->username : 'Unnamed') }}</td><td><code>{{ Str::mask((string) $asset->{$section['id']}, '*', 0, max(strlen((string) $asset->{$section['id']}) - 4, 0)) }}</code></td><td>{{ $asset->{$section['status']} ?: 'Available' }}</td><td><span class="badge text-bg-{{ $asset->is_selected ? 'success' : 'light' }}">{{ $asset->is_selected ? 'Selected' : 'Not selected' }}</span></td></tr>@empty<tr><td colspan="4" class="text-center text-secondary py-4">No assets imported.</td></tr>@endforelse
            </tbody></table></div></div></section>
        @endforeach
    @endif
@endsection
