<div>
    <x-ui.card title="Document Repository" description="Manage your personnel documents." class="border-t-4 border-t-cyan-500">
        <x-slot name="actions">
            <x-ui.button x-on:click="$dispatch('open-modal', { name: 'upload-custom-modal' })" icon="fas fa-plus">
                Add Custom Document
            </x-ui.button>
        </x-slot>

        <div class="space-y-8">
            <!-- Standard Documents -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($standardTypes as $type => $details)
                    @php
                        $uploadedDoc = collect($this->parsedAttachments)->firstWhere('type', $type);
                    @endphp
                    <div class="relative group rounded-xl border border-gray-200 bg-white/50 p-5 hover:bg-white hover:shadow-lg hover:-translate-y-1 dark:border-white/10 dark:bg-white/5 dark:hover:bg-white/10 transition-all duration-300">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-4">
                                <div class="p-3 rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400 shadow-inner">
                                    <i class="{{ $details['icon'] }} text-2xl"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-900 dark:text-white">{{ $details['label'] }}</h4>
                                    @if($uploadedDoc)
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="flex h-2 w-2 relative">
                                              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                              <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                                            </span>
                                            <p class="text-xs text-green-600 dark:text-green-400 font-medium">
                                                Uploaded {{ \Carbon\Carbon::parse($uploadedDoc['uploaded_at'])->diffForHumans() }}
                                            </p>
                                        </div>
                                    @else
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-gray-300 dark:bg-gray-600"></span>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Not uploaded yet</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            @if($uploadedDoc)
                                <div class="flex gap-2">
                                    <a href="{{ Storage::url($uploadedDoc['path']) }}" target="_blank" class="text-gray-400 hover:text-blue-500 transition-colors p-2 hover:bg-blue-50 rounded-lg dark:hover:bg-blue-500/10">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button wire:click="deleteDocument('{{ $uploadedDoc['path'] }}')" wire:confirm="Are you sure you want to delete this document?" class="text-gray-400 hover:text-red-500 transition-colors p-2 hover:bg-red-50 rounded-lg dark:hover:bg-red-500/10">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            @endif
                        </div>

                        @if(!$uploadedDoc)
                            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-white/5">
                                <label class="flex flex-col items-center justify-center w-full h-24 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer bg-gray-50 hover:bg-blue-50 hover:border-blue-400 transition-all dark:bg-gray-800/50 dark:border-gray-600 dark:hover:border-blue-500 dark:hover:bg-gray-700">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                        <i class="fas fa-cloud-upload-alt text-gray-400 mb-2"></i>
                                        <p class="text-xs text-gray-500 dark:text-gray-400"><span class="font-semibold">Click to upload</span></p>
                                    </div>
                                    <input type="file" class="hidden" wire:model="files.{{ $type }}" wire:change="uploadStandard('{{ $type }}')" accept=".pdf,.jpg,.png,.jpeg">
                                </label>
                                @error("files.$type") <p class="text-xs text-red-500 mt-1 animate-pulse">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- Custom Documents -->
            @if(collect($this->parsedAttachments)->where('is_custom', true)->isNotEmpty())
                <div class="pt-8 border-t border-gray-200 dark:border-white/10">
                    <div class="flex items-center gap-3 mb-6">
                        <i class="fas fa-folder-plus text-purple-500 text-xl"></i>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Custom Documents</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach(collect($this->parsedAttachments)->where('is_custom', true) as $doc)
                            <div class="relative group rounded-xl border border-gray-200 bg-white/50 p-5 hover:bg-white hover:shadow-lg hover:-translate-y-1 dark:border-white/10 dark:bg-white/5 dark:hover:bg-white/10 transition-all duration-300">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-4">
                                        <div class="p-3 rounded-xl bg-purple-100 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400 shadow-inner">
                                            <i class="fas fa-file-alt text-2xl"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-gray-900 dark:text-white capitalize">{{ $doc['name'] }}</h4>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                Uploaded {{ \Carbon\Carbon::parse($doc['uploaded_at'])->diffForHumans() }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <a href="{{ Storage::url($doc['path']) }}" target="_blank" class="text-gray-400 hover:text-blue-500 transition-colors p-2 hover:bg-blue-50 rounded-lg dark:hover:bg-blue-500/10">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button wire:click="deleteDocument('{{ $doc['path'] }}')" wire:confirm="Are you sure you want to delete this document?" class="text-gray-400 hover:text-red-500 transition-colors p-2 hover:bg-red-50 rounded-lg dark:hover:bg-red-500/10">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </x-ui.card>

    <!-- Upload Custom Modal -->
    <x-ui.modal name="upload-custom-modal" title="Add Custom Document">
        <form wire:submit="uploadCustom" class="space-y-6">
            <x-ui.input label="Document Name" name="customType" wire:model="customType" placeholder="e.g., Gym Membership" icon="fas fa-tag" />

            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 ml-1">Select File</label>
                <div class="flex items-center justify-center w-full">
                    <label for="dropzone-file" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-xl cursor-pointer bg-gray-50 hover:bg-blue-50 hover:border-blue-500 dark:hover:bg-gray-700 dark:bg-gray-800 dark:border-gray-600 dark:hover:border-gray-500 transition-all">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-3"></i>
                            <p class="text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">PDF, PNG, JPG (MAX. 5MB)</p>
                        </div>
                        <input id="dropzone-file" type="file" wire:model="customFile" class="hidden" accept=".pdf,.jpg,.png,.jpeg" />
                    </label>
                </div>
                @error('customFile') <p class="text-xs text-red-500 mt-1 animate-pulse">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-white/5">
                <x-ui.button type="button" variant="ghost" x-on:click="$dispatch('close-modal', { name: 'upload-custom-modal' })">Cancel</x-ui.button>
                <x-ui.button type="submit" loading="uploadCustom" icon="fas fa-upload" class="shadow-blue-500/50">Upload Document</x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>
