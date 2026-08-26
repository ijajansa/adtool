<section class="card content-card"><div class="card-body p-4">
    <h2 class="h5">Profile information</h2><p class="text-secondary small">Update your contact details and email address.</p>
    <form id="send-verification" method="POST" action="{{ route('verification.send') }}">@csrf</form>
    <form method="POST" action="{{ route('profile.update') }}" novalidate>
        @csrf @method('patch')
        <div class="mb-3"><label for="name" class="form-label">Name</label><input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required autocomplete="name">@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="mb-3"><label for="email" class="form-label">Email address</label><input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required autocomplete="username">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="mb-3"><label for="phone" class="form-label">Phone</label><input id="phone" name="phone" type="tel" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone) }}" autocomplete="tel">@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        @if (! $user->hasVerifiedEmail())
            <div class="alert alert-warning small">Your email address is unverified. <button form="send-verification" class="btn btn-link btn-sm p-0 align-baseline">Resend verification email</button></div>
        @endif
        <button class="btn btn-primary" type="submit">Save profile</button>
    </form>
</div></section>
