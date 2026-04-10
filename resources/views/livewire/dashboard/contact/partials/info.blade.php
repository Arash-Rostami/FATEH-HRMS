<div x-show="showInfo"
     x-transition:enter="ease-out duration-300"
     x-transition:enter-start="opacity-0 -translate-x-8"
     x-transition:enter-end="opacity-100 translate-x-0"
     x-transition:leave="ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0 -translate-x-8"
     x-on:click.outside="showInfo = false"
     class="absolute inset-y-0 left-0 z-30 w-72 md:w-80 bg-[var(--md-sys-color-primary-container)] overflow-y-auto contact-scrollbar rounded-xl"
     role="dialog">
    <div class="p-5 space-y-5 border-none">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">اطلاعات همکار</h3>
            <button x-on:click="showInfo = false"
                    aria-label="بستن"
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)]">
                <span class="material-symbols-rounded text-base" aria-hidden="true">close</span>
            </button>
        </div>
        <div class="flex flex-col items-center gap-3 py-4">
            <div
                class="w-20 h-20 rounded-xl flex items-center justify-center text-2xl font-bold shadow-lg overflow-hidden bg-[linear-gradient(135deg, var(--md-sys-color-primary-container), var(--md-sys-color-secondary-container))] text-[var(--md-sys-color-on-primary-container)]">
                <x-dashboard.avatar
                    :image="null"
                    :existingImage="$activeContact->profile?->image"
                    class="rounded-lg" />
            </div>
            <p class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">{{ $activeContact->name }}</p>
            @if($activeContact->profile?->position)
                <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]">{{ $activeContact->profile->position }}</p>
            @endif
        </div>
        <div class="space-y-3">
            @if($activeContact->profile?->department?->name)
                <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-[var(--md-sys-color-primary)]">
                    <span class="material-symbols-rounded text-base text-[var(--md-sys-color-on-primary)]" aria-hidden="true">apartment</span>
                    <div>
                        <p class="text-[10px] text-[var(--md-sys-color-on-primary)]">واحد سازمانی</p>
                        <p class="text-xs font-medium text-[var(--md-sys-color-on-primary)]">{{ $activeContact->profile->department->name }}</p>
                    </div>
                </div>
            @endif
            @if($activeContact->email)
                <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-[var(--md-sys-color-primary)]">
                    <span class="material-symbols-rounded text-base text-[var(--md-sys-color-on-primary)]" aria-hidden="true">email</span>
                    <div class="min-w-0">
                        <p class="text-[10px] text-[var(--md-sys-color-on-primary)]">
                            ایمیل</p>
                        <p class="text-xs font-medium truncate text-[var(--md-sys-color-on-primary)]" dir="ltr">{{ $activeContact->email }}</p>
                    </div>
                </div>
            @endif
            @if($activeContact->profile?->cellphone)
                <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-[var(--md-sys-color-primary)]">
                    <span class="material-symbols-rounded text-base text-[var(--md-sys-color-on-primary)]" aria-hidden="true">phone</span>
                    <div>
                        <p class="text-[10px] text-[var(--md-sys-color-on-primary)]">تلفن</p>
                        <p class="text-xs font-medium text-[var(--md-sys-color-on-primary)]" dir="ltr">{{ $activeContact->profile->cellphone }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
