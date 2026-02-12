<form wire:submit.prevent="submitReset" class="space-y-4">
    <input type="hidden" wire:model="token" />

    <div>
        <label class="block text-sm">Email</label>
        <input wire:model.lazy="email" type="email" class="mt-1 block w-full rounded-md border p-2" />
        @error('email') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm">New Password</label>
        <input wire:model.lazy="password" type="password" class="mt-1 block w-full rounded-md border p-2" />
        @error('password') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm">Confirm Password</label>
        <input wire:model.lazy="password_confirmation" type="password" class="mt-1 block w-full rounded-md border p-2" />
    </div>

    <div>
        <button type="submit" class="w-full py-2 rounded-md bg-sky-600 text-white">Reset Password</button>
    </div>

    <p class="text-sm text-center">
        <a href="{{ route('login') }}" class="text-sky-600">Back to login</a>
    </p>
</form>
