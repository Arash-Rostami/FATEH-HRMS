@use('App\Livewire\Dashboard\Reservation\Presentation\PolicySummaryPresenter')
@if($this->policyChips !== [])
    <div wire:key="reservation-policy-chips" x-data="{ open: false }" class="mb-4">
        <x-ui.buttons.form variant="ghost" icon="rule" x-on:click="open = !open" x-bind:aria-expanded="open" class="!h-8 !px-2 text-[11px] font-bold">
            قوانین
            <x-ui.badge class="!px-1.5 !py-0 !text-[10px]">{{ convertToPersian((string) count($this->policyChips)) }}</x-ui.badge>
            <span class="material-symbols-rounded text-[16px] transition-transform duration-200" :class="open ? 'rotate-180' : ''">expand_more</span>
        </x-ui.buttons.form>

        <div x-show="open" x-collapse x-cloak class="mt-2 flex flex-col gap-2 rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container-low)] p-3">
            @foreach(PolicySummaryPresenter::grouped($this->policyChips) as $group => $chips)
                <div wire:key="reservation-policy-group-{{ $loop->index }}" class="flex flex-wrap items-center gap-1.5">
                    <span class="w-16 shrink-0 text-[10px] font-bold text-[var(--md-sys-color-on-surface-variant)]">{{ $group }}</span>
                    @foreach($chips as $chip)
                        <span wire:key="reservation-policy-chip-{{ $loop->parent->index }}-{{ $loop->index }}" class="contents">@include('livewire.dashboard.reservation.chip')</span>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
@endif
