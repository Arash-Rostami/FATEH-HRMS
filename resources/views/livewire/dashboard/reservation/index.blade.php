<div x-data="reservation" class="w-full h-full flex flex-col p-4 space-y-4">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Reservations</h2>
        <div class="flex space-x-2 rtl:space-x-reverse">
            <!-- Filter Actions -->
            <button @click="openFilterModal = true" class="px-4 py-2 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700 transition">Filter</button>
        </div>
    </div>

    <!-- Smart Table / Grid view -->
    <div class="flex-1 overflow-auto bg-white dark:bg-gray-800 rounded-xl shadow-md p-4">
        @if($errorMessage)
            <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg dark:bg-red-200 dark:text-red-800" role="alert">
                {{ $errorMessage }}
            </div>
        @endif

        @if(count($resources) > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($resources as $resource)
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg shadow border border-gray-200 dark:border-gray-600 flex flex-col justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $resource->name }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 capitalize">{{ $resource->type }}</p>
                        </div>
                        <button wire:click="bookResource({{ $resource->id }})" class="mt-4 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">Book Now</button>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 dark:text-gray-400 text-center">Select filters to view available resources.</p>
        @endif
    </div>
</div>
