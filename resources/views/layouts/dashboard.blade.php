@extends('layouts.app')
<div class="min-h-screen flex flex-col">
    <header class="bg-white shadow">
        <div class="max-w-4xl mx-auto p-4 flex justify-between items-center">
            <a href="/" class="font-bold text-lg">{{ config('app.name') }}</a>
            <nav class="space-x-4">
                @auth
                    <livewire:auth.logout-button/>
                @endauth
            </nav>
        </div>
    </header>


    <footer class="text-center py-6 text-sm text-gray-500">
        © {{ date('Y') }} {{ config('app.name') }}
    </footer>
</div>

