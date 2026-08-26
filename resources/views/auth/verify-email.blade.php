<x-guest-layout>
    <div class="text-center mb-4"><h1 class="h4 fw-bold">Verify your email</h1><p class="text-secondary">We sent a verification link to your email address. Click it before continuing to your dashboard.</p></div>
    @if (session('status') === 'verification-link-sent')<div class="alert alert-success">A new verification link has been sent to your email address.</div>@endif
    <form method="POST" action="{{ route('verification.send') }}">@csrf<button class="btn btn-primary w-100 py-2" type="submit">Resend verification email</button></form>
    <form method="POST" action="{{ route('logout') }}" class="text-center mt-3">@csrf<button type="submit" class="btn btn-link text-secondary">Log out</button></form>
</x-guest-layout>
