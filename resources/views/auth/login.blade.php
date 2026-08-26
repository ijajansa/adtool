<x-guest-layout>
    <div class="text-center mb-4"><h1 class="h4 fw-bold">Welcome back</h1><p class="text-secondary mb-0">Sign in to manage your advertisements.</p></div>
    @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0 ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input id="email" class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <div class="d-flex justify-content-between"><label for="password" class="form-label">Password</label><a class="small" href="{{ route('password.request') }}">Forgot password?</a></div>
            <input id="password" class="form-control @error('password') is-invalid @enderror" type="password" name="password" required autocomplete="current-password">
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-check mb-4"><input id="remember" class="form-check-input" type="checkbox" name="remember"><label class="form-check-label" for="remember">Remember me</label></div>
        <button class="btn btn-primary w-100 py-2" type="submit">Log in</button>
    </form>
    <p class="text-center text-secondary small mt-4 mb-0">New here? <a href="{{ route('register') }}">Create an account</a></p>
</x-guest-layout>
