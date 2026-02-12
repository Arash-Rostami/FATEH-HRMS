<div class="bg-white p-8 rounded-lg shadow">
    <h1 class="text-2xl font-semibold mb-4">Sign in</h1>

    <form wire:submit.prevent="login" class="space-y-4">
        <div>
            <label class="block text-sm">Email</label>
            <input wire:model.lazy="email" type="email" class="mt-1 block w-full rounded-md border p-2" />
            @error('email') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm">Password</label>
            <input wire:model.lazy="password" type="password" class="mt-1 block w-full rounded-md border p-2" />
            @error('password') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm">
                <input wire:model="remember" type="checkbox" />
                Remember me
            </label>
            <a href="{{ route('password.request') }}" class="text-sm text-sky-600">Forgot?</a>
        </div>

        <div>
            <button type="submit" class="w-full py-2 rounded-md bg-sky-600 text-white">Sign in</button>
        </div>

        <p class="text-sm text-center">Don't have an account? <a href="{{ route('register') }}" class="text-sky-600">Register</a></p>
    </form>
</div>
