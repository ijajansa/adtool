@extends('layouts.app')

@section('title', 'Business Settings')

@section('content')
    <div class="mb-4"><h1 class="h3 fw-bold mb-1">Business settings</h1><p class="text-secondary mb-0">Manage the profile for {{ $business->name }}.</p></div>
    <section class="card content-card"><div class="card-body p-4">
        @if (! $canUpdate)<div class="alert alert-info">You have view-only access to these business settings.</div>@endif
        <form method="POST" action="{{ route('business.settings.update') }}" enctype="multipart/form-data" novalidate>
            @csrf @method('put')
            <fieldset @disabled(! $canUpdate)>
                <div class="row g-3">
                    <div class="col-12 col-md-7"><label for="name" class="form-label">Business name</label><input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $business->name) }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-12 col-md-5"><label for="industry" class="form-label">Industry</label><select id="industry" name="industry" class="form-select @error('industry') is-invalid @enderror" required>@foreach ($industries as $industry)<option value="{{ $industry }}" @selected(old('industry', $business->industry) === $industry)>{{ $industry }}</option>@endforeach</select>@error('industry')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-12 col-md-7"><label for="website" class="form-label">Website</label><input id="website" name="website" type="url" class="form-control @error('website') is-invalid @enderror" value="{{ old('website', $business->website) }}">@error('website')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-12 col-md-5"><label for="phone" class="form-label">Phone</label><input id="phone" name="phone" type="tel" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $business->phone) }}">@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-12 col-md-4"><label for="country_code" class="form-label">Country</label><select id="country_code" name="country_code" class="form-select @error('country_code') is-invalid @enderror">@foreach ($countries as $code => $country)<option value="{{ $code }}" @selected(old('country_code', $business->country_code) === $code)>{{ $country }}</option>@endforeach</select>@error('country_code')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-12 col-md-4"><label for="currency_code" class="form-label">Currency</label><select id="currency_code" name="currency_code" class="form-select @error('currency_code') is-invalid @enderror">@foreach ($currencies as $code => $currency)<option value="{{ $code }}" @selected(old('currency_code', $business->currency_code) === $code)>{{ $currency }}</option>@endforeach</select>@error('currency_code')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-12 col-md-4"><label for="timezone" class="form-label">Timezone</label><select id="timezone" name="timezone" class="form-select @error('timezone') is-invalid @enderror">@foreach ($timezones as $timezone)<option value="{{ $timezone }}" @selected(old('timezone', $business->timezone) === $timezone)>{{ $timezone }}</option>@endforeach</select>@error('timezone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-12"><label for="logo" class="form-label">Logo</label><input id="logo" name="logo" type="file" accept=".jpg,.jpeg,.png,.webp" class="form-control @error('logo') is-invalid @enderror">@error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror@if ($business->logo_path)<div class="form-text">A logo is currently stored. Uploading a new one will replace it.</div>@endif</div>
                </div>
                @if ($canUpdate)<button class="btn btn-primary mt-4" type="submit">Save business settings</button>@endif
            </fieldset>
        </form>
    </div></section>
@endsection
