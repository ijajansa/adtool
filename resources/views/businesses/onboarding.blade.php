<x-guest-layout wide>
    <div class="text-center mb-4">
        <span class="badge text-bg-primary mb-2">Step 1 of 3</span>
        <h1 class="h3 fw-bold">Tell us about your business</h1>
        <p class="text-secondary mb-0">This creates your private workspace for campaigns, leads, and reporting.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">Please correct the highlighted fields.</div>
    @endif

    <form method="POST" action="{{ route('business.onboarding.store') }}" novalidate>
        @csrf
        <div class="row g-3">
            <div class="col-12 col-md-7">
                <label for="name" class="form-label">Business name</label>
                <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required autofocus>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12 col-md-5">
                <label for="industry" class="form-label">Industry</label>
                <select id="industry" name="industry" class="form-select @error('industry') is-invalid @enderror" required>
                    <option value="">Choose industry</option>
                    @foreach ($industries as $industry)<option value="{{ $industry }}" @selected(old('industry') === $industry)>{{ $industry }}</option>@endforeach
                </select>
                @error('industry')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12 col-md-7">
                <label for="website" class="form-label">Website <span class="text-secondary small">(optional)</span></label>
                <input id="website" name="website" type="url" class="form-control @error('website') is-invalid @enderror" value="{{ old('website') }}" placeholder="https://example.com">
                @error('website')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12 col-md-5">
                <label for="phone" class="form-label">Phone <span class="text-secondary small">(optional)</span></label>
                <input id="phone" name="phone" type="tel" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}">
                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12 col-md-4">
                <label for="country_code" class="form-label">Country</label>
                <select id="country_code" name="country_code" class="form-select @error('country_code') is-invalid @enderror" required>
                    @foreach ($countries as $code => $country)<option value="{{ $code }}" @selected(old('country_code', 'IN') === $code)>{{ $country }}</option>@endforeach
                </select>
                @error('country_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12 col-md-4">
                <label for="currency_code" class="form-label">Currency</label>
                <select id="currency_code" name="currency_code" class="form-select @error('currency_code') is-invalid @enderror" required>
                    @foreach ($currencies as $code => $currency)<option value="{{ $code }}" @selected(old('currency_code', 'INR') === $code)>{{ $currency }}</option>@endforeach
                </select>
                @error('currency_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12 col-md-4">
                <label for="timezone" class="form-label">Timezone</label>
                <select id="timezone" name="timezone" class="form-select @error('timezone') is-invalid @enderror" required>
                    @foreach ($timezones as $timezone)<option value="{{ $timezone }}" @selected(old('timezone', 'Asia/Kolkata') === $timezone)>{{ $timezone }}</option>@endforeach
                </select>
                @error('timezone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <button class="btn btn-primary w-100 py-2 mt-4" type="submit">Create business workspace</button>
    </form>
    <form method="POST" action="{{ route('logout') }}" class="text-center mt-3">@csrf<button type="submit" class="btn btn-link text-secondary">Log out</button></form>
</x-guest-layout>
