@php
    $p = $this->presenter;
    $browseList = $p->browseList($this->joinableChannels);
@endphp
<div class="flex-1 overflow-y-auto px-4 md:px-8 py-6 msg-scrollbar">
    <div class="max-w-3xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-rounded text-[22px] text-[var(--md-sys-color-primary)]">explore</span>
                <div>
                    <h2 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">کانال‌های عمومی</h2>
                    <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">برای پیوستن روی کانال کلیک کنید</p>
                </div>
            </div>
            <button x-on:click="toggleBrowse()" aria-label="بستن"
                    class="w-8 h-8 rounded-lg flex items-center justify-center bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:brightness-95 active:scale-90 transition-all">
                <span class="material-symbols-rounded text-[18px]">close</span>
            </button>
        </div>

        @forelse($browseList as $ch)
            <div wire:key="joinable-{{ $ch['id'] }}"
                 class="flex items-center gap-3 px-4 py-3 mb-2 rounded-xl bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)] hover:border-[var(--md-sys-color-primary)] transition-all">
                <div class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                    <span class="material-symbols-rounded text-[20px]">campaign</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-[13px] font-semibold text-[var(--md-sys-color-on-surface)] truncate">{{ $ch['name'] }}</p>
                    @if(!empty($ch['slug_handle']))
                        <p class="text-[10px] text-[var(--md-sys-color-primary)] truncate -mt-0.5" dir="auto">{{ $ch['slug_handle'] }}</p>
                    @endif
                    <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] truncate">{{ $ch['description'] ?: 'بدون توضیحات' }}</p>
                    <p class="text-[10px] text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_60%,transparent)] mt-0.5">مدیر: {{ $ch['owner_name'] }}</p>
                </div>
                <button x-on:click="joinChannel({{ $ch['id'] }})" wire:loading.attr="disabled" wire:target="joinChannel({{ $ch['id'] }})"
                        class="flex-shrink-0 px-4 py-2 rounded-lg text-[11px] font-semibold transition-all hover:brightness-110 active:scale-95 disabled:opacity-50 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]">
                    <span wire:loading.remove wire:target="joinChannel({{ $ch['id'] }})">پیوستن</span>
                    <span wire:loading wire:target="joinChannel({{ $ch['id'] }})" class="material-symbols-rounded text-[12px] animate-spin">progress_activity</span>
                </button>
            </div>
        @empty
            <x-ui.empty icon="campaign" title="کانال عمومی‌ای برای پیوستن وجود ندارد" variant="default" />
        @endforelse
    </div>
</div>
