@extends('layouts.app')

@section('title', 'Settings')

@section('content')
    <div class="mb-4"><h1 class="h3 fw-bold mb-1">Settings</h1><p class="text-secondary mb-0">Manage your profile and account security.</p></div>
    <div class="row g-4">
        <div class="col-12 col-xl-7">@include('profile.partials.update-profile-information-form')</div>
        <div class="col-12 col-xl-7">@include('profile.partials.update-password-form')</div>
        <div class="col-12 col-xl-7">@include('profile.partials.delete-user-form')</div>
    </div>
@endsection
