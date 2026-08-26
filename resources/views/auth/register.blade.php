<x-guest-layout>
    <div class="text-center mb-4"><h1 class="h4 fw-bold">Create your account</h1><p class="text-secondary mb-0">Start simplifying your social advertising.</p></div>
    @if ($errors->any())<div class="alert alert-danger" role="alert">Please correct the highlighted fields.</div>@endif
    <form method="POST" action="{{ route('register') }}" novalidate>
        @csrf
        <div class="mb-3"><label for="name" class="form-label">Name</label><input id="name" class="form-control @error('name') is-invalid @enderror" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="mb-3"><label for="email" class="form-label">Email address</label><input id="email" class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="mb-3"><label for="phone" class="form-label">Phone <span class="text-secondary small">(optional)</span></label><input id="phone" class="form-control @error('phone') is-invalid @enderror" type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel">@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="mb-3"><label for="password" class="form-label">Password</label><input id="password" class="form-control @error('password') is-invalid @enderror" type="password" name="password" required autocomplete="new-password">@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="mb-4"><label for="password_confirmation" class="form-label">Confirm password</label><input id="password_confirmation" class="form-control" type="password" name="password_confirmation" required autocomplete="new-password"></div>
        <button class="btn btn-primary w-100 py-2" type="submit">Register</button>
    </form>
    <p class="text-center text-secondary small mt-4 mb-0">Already registered? <a href="{{ route('login') }}">Log in</a></p>
</x-guest-layout>
