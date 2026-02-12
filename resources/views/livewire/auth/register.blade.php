<div class="bg-white p-8 rounded-lg shadow max-w-md mx-auto">
    <h1 class="text-2xl font-semibold mb-4">Create Account</h1>

    <form wire:submit.prevent="register" class="space-y-4">

        <div>
            <label class="block text-sm">Name</label>
            <input wire:model.lazy="name" type="text" class="mt-1 block w-full rounded-md border p-2" />
            @error('name') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

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

        <div>
            <label class="block text-sm">Confirm Password</label>
            <input wire:model.lazy="password_confirmation" type="password" class="mt-1 block w-full rounded-md border p-2" />
        </div>

        <div>
            <button type="submit" class="w-full py-2 rounded-md bg-sky-600 text-white">Register</button>
        </div>

        <p class="text-sm text-center">Already have an account?
            <a href="{{ route('login') }}" class="text-sky-600">Sign in</a>
        </p>
    </form>
</div>
