<x-guest-layout>
    <div class="text-center mb-4"><h1 class="h4 fw-bold">Confirm your password</h1><p class="text-secondary mb-0">This is a secure area. Confirm your password to continue.</p></div>
    <form method="POST" action="{{ route('password.confirm') }}" novalidate>
        @csrf
        <div class="mb-4"><label for="password" class="form-label">Password</label><input id="password" class="form-control @error('password') is-invalid @enderror" type="password" name="password" required autocomplete="current-password">@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <button class="btn btn-primary w-100 py-2" type="submit">Confirm</button>
    </form>
</x-guest-layout>
