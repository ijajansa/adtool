<x-guest-layout>
    <div class="text-center mb-4"><h1 class="h4 fw-bold">Forgot your password?</h1><p class="text-secondary mb-0">Enter your email and we’ll send you a reset link.</p></div>
    @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf
        <div class="mb-4"><label for="email" class="form-label">Email address</label><input id="email" class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" required autofocus>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <button class="btn btn-primary w-100 py-2" type="submit">Email password reset link</button>
    </form>
    <p class="text-center small mt-4 mb-0"><a href="{{ route('login') }}">Back to login</a></p>
</x-guest-layout>
