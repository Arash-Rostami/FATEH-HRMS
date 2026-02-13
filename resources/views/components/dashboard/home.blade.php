@extends('layouts.app')
@section('content')
    <livewire:auth.logout-button />
    <h1 class="text-3xl font-bold text-center mt-10">Welcome to Home Page</h1>
    <p class="text-center mt-4 text-gray-600">This is loaded inside the layout!</p>
@endsection
