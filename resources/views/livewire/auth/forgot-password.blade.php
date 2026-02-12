<div class="bg-white p-8 rounded-lg shadow max-w-md mx-auto">
    <h1 class="text-2xl font-semibold mb-4">Forgot Password</h1>

    @if (session('status'))
        <div class="bg-green-100 p-2 rounded mb-4 text-green-700">{{ session('status') }}</div>
    @endif

    <form wire:submit.prevent="send" class="space-y-4">
        <div>
            <label class="block text-sm">Email</label>
            <input wire:model.lazy="email" type="email" class="mt-1 block w-full rounded-md border p-2"/>
            @error('email') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <button type="submit" class="w-full py-2 rounded-md bg-sky-600 text-white">Send Reset Link</button>
        </div>

        <p class="text-sm text-center">
            <a href="{{ route('login') }}" class="text-sky-600">Back to login</a>
        </p>
    </form>
</div>
