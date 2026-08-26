<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'AdSimplify') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="auth-page d-flex align-items-center justify-content-center p-3 py-5">
        <div class="auth-card card shadow-sm">
            <div class="card-body p-4 p-sm-5">
                <a class="brand-mark d-block text-center fs-3 mb-4" href="{{ route('home') }}">AdSimplify<span class="brand-dot">.</span></a>
                {{ $slot }}
            </div>
        </div>
    </main>
</body>
</html>
