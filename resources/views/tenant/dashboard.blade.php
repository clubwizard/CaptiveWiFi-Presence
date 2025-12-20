@extends('tenant.layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="max-w-7xl mx-auto">
        {{-- Real-Time Overview Component --}}
        @livewire('dashboard.real-time-overview')
    </div>
@endsection
