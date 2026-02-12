<div class="bg-white p-8 rounded-lg shadow max-w-md mx-auto text-center">
    <h1 class="text-2xl font-semibold mb-4">Verify Your Email</h1>

    @if (session('status'))
        <div class="bg-green-100 p-2 rounded mb-4 text-green-700">{{ session('status') }}</div>
    @endif

    <p class="mb-4">A verification link has been sent to your email. Please check your inbox.</p>

    <div class="space-y-2">
        <button wire:click="resend" class="w-full py-2 rounded-md bg-sky-600 text-white">Resend Verification Email</button>
        <a href="{{ route('logout') }}" onclick="event.preventDefault(); $wire.logout();" class="block text-sm text-sky-600 mt-2">Logout</a>
    </div>
</div>
