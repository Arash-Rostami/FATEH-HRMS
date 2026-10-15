@php
    $contact = $this->activeContact;
    $profile = $contact->profile;
    $hasPosition = (bool) $profile?->has_display_position;
    $department = $profile?->department;
@endphp

<div x-show="showInfo"
     x-transition:enter="animate-slide-fade-from-left"
     x-transition:leave="animate-slide-fade-to-left"
     x-on:click.outside="showInfo = false"
     class="absolute inset-y-0 left-0 z-30 flex w-72 md:w-80 flex-col overflow-hidden rounded-xl border-r border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[var(--md-sys-color-primary-container)] shadow-[0_16px_48px_color-mix(in_srgb,var(--md-sys-color-shadow)_24%,transparent)]"
     role="dialog"
     aria-label="اطلاعات همکار">

    <div class="flex flex-shrink-0 flex-col gap-3 border-b border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-low)_60%,transparent)] px-4 pt-3.5 pb-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                    <span class="material-symbols-rounded text-[15px]">badge</span>
                </span>
                <h3 class="text-xs font-bold tracking-tight text-[var(--md-sys-color-on-surface)]">اطلاعات همکار</h3>
            </div>
            <x-ui.modals.close-button close="showInfo = false"/>
        </div>

        <x-ui.buttons.tab-selector
            hasA11y
            :activeTab="''"
            :buttonBaseClass="'flex-1 justify-center px-2 py-1.5 rounded-lg text-[11px] font-bold transition-all duration-150'"
            :iconBaseClass="'material-symbols-rounded text-[14px]'"
            class="!mb-0 !w-full"
            :tabs="[
                ['id' => 'info', 'icon' => 'person', 'label' => 'مشخصات', 'description' => 'اطلاعات همکار',
                 'alpine' => 'if (filesOpen) toggleFiles()', 'activeExpr' => '!filesOpen'],
                ['id' => 'files', 'icon' => 'folder_open', 'label' => 'پیوست‌ها', 'description' => 'همهٔ فایل‌های ردوبدل‌شدهٔ این گفتگو',
                 'alpine' => 'if (!filesOpen) toggleFiles()', 'activeExpr' => 'filesOpen', 'countExpr' => 'filesList.length'],
            ]"/>
    </div>

    <div x-show="!filesOpen" class="flex flex-1 min-h-0 flex-col gap-3 p-4 overflow-y-auto contact-scrollbar">
        <div class="flex flex-col items-center gap-2.5 rounded-2xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] bg-[var(--md-sys-color-surface-container-low)] p-4 text-center">
            <div class="flex rounded-[28%] shadow-md ring-2 ring-[color-mix(in_srgb,var(--md-sys-color-primary)_20%,transparent)]">
                <x-dashboard.user-avatar :user="$contact" size="xl"/>
            </div>
            <div class="min-w-0 max-w-full">
                <p class="truncate text-sm font-bold text-[var(--md-sys-color-on-surface)]">{{ $contact->name }}</p>
                @if($hasPosition)
                    <p class="mt-0.5 truncate text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]">{{ $profile->display_position }}</p>
                @endif
            </div>
        </div>

        <div class="space-y-2">
            @if($department)
                <div class="flex items-center gap-3 rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] bg-[var(--md-sys-color-surface-container-low)] px-3 py-2.5">
                    <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-base" aria-hidden="true">apartment</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">واحد سازمانی</p>
                        <p class="truncate text-xs font-bold text-[var(--md-sys-color-on-surface)]" title="{{ $department->tooltipLabel() }}">
                            {{ $department->displayLabel() }}
                        </p>
                    </div>
                </div>
            @endif

            @if($contact->email)
                <div class="flex items-center gap-3 rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] bg-[var(--md-sys-color-surface-container-low)] px-3 py-2.5">
                    <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-base" aria-hidden="true">email</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">ایمیل</p>
                        <p class="truncate text-xs font-bold text-[var(--md-sys-color-on-surface)]" dir="ltr" title="{{ $contact->email }}">
                            {{ $contact->email }}
                        </p>
                    </div>
                </div>
            @endif

            @if($profile?->cellphone)
                <div class="flex items-center gap-3 rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] bg-[var(--md-sys-color-surface-container-low)] px-3 py-2.5">
                    <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-base" aria-hidden="true">phone</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">تلفن</p>
                        <p class="truncate text-xs font-bold text-[var(--md-sys-color-on-surface)]" dir="ltr">
                            {{ $profile->cellphone }}
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @include('livewire.dashboard.messaging.files')
</div>
