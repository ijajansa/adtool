@extends('layouts.app')

@section('title', $title)

@section('content')
    <h1 class="h3 fw-bold mb-1">{{ $title }}</h1>
    <p class="text-secondary mb-4">This area is ready for the next phase of the application.</p>
    <div class="card content-card"><div class="card-body empty-state m-4">{{ $title }} features will be added here.</div></div>
@endsection
