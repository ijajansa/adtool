<x-guest-layout>
    <div class="text-center mb-4"><h1 class="h4 fw-bold">Reset password</h1><p class="text-secondary mb-0">Choose a new password for your account.</p></div>
    <form method="POST" action="{{ route('password.store') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div class="mb-3"><label for="email" class="form-label">Email address</label><input id="email" class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="mb-3"><label for="password" class="form-label">New password</label><input id="password" class="form-control @error('password') is-invalid @enderror" type="password" name="password" required autocomplete="new-password">@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="mb-4"><label for="password_confirmation" class="form-label">Confirm password</label><input id="password_confirmation" class="form-control" type="password" name="password_confirmation" required autocomplete="new-password"></div>
        <button class="btn btn-primary w-100 py-2" type="submit">Reset password</button>
    </form>
</x-guest-layout>
