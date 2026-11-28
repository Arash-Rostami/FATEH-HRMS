@php
    $rows = $this->letters;
    $chips = $listTab === 'templates' ? [] : $this->recentTemplates;
@endphp

<div x-data="correspondenceList()" x-on:confirmation-confirmed.window="{{ confirmDialogListener(['shareBatch' => false]) }}" class="print:hidden flex flex-col gap-3">
    <div wire:key="correspondence-list-tabs" class="w-fit max-w-full mx-auto md:mx-0">
        <x-ui.buttons.tab-selector :active-tab="$listTab" class="!mb-0" :tabs="$listTabs"/>
    </div>

    @if($chips !== [])
        <div wire:key="correspondence-template-chips" class="flex flex-col gap-2">
            <p class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)]">شروع از الگو</p>
            <div class="flex flex-wrap gap-1.5">
                @foreach($chips as $chip)
                    <button type="button" wire:key="correspondence-chip-{{ $chip['id'] }}" wire:click="useTemplate({{ (int) $chip['id'] }})" class="px-2.5 py-1 rounded-lg text-xs font-bold bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]">
                        <bdi>{{ $chip['name'] }}</bdi>
                    </button>
                @endforeach
                <button type="button" wire:key="correspondence-templates-open" x-on:click="openModal('correspondence-templates')" class="px-2.5 py-1 rounded-lg text-xs font-bold text-[var(--md-sys-color-primary)]">همه الگوها</button>
            </div>
        </div>
    @endif

    <x-ui.modals.dialog name="correspondence-templates" title="همه الگوها">
        <div class="flex flex-col gap-3">
            <x-ui.forms.input label="جست‌وجوی الگو" name="templateSearch" wire:model.live.debounce.500ms="templateSearch"/>
            @php
                $found = $templateSearch !== '' ? $this->templates : ($listTab === 'templates' ? $rows : $chips);
            @endphp
            @forelse($found as $item)
                <div wire:key="correspondence-template-{{ $item['id'] }}" class="contents">
                <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] px-3 py-2">
                    <div class="min-w-0">
                        <p class="text-sm font-bold truncate"><bdi>{{ $item['name'] ?? (($item['template_name'] ?? '') !== '' ? $item['template_name'] : ($item['subject'] ?? '')) }}</bdi></p>
                        <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]"><bdi>{{ $item['type'] ?? '' }}</bdi>@if(($item['scope_label'] ?? '') !== '') · {{ $item['scope_label'] }}@endif @if(($item['used'] ?? '') !== '') · <bdi>{{ $item['used'] }}</bdi>@endif</p>
                        @if(($item['snippet'] ?? '') !== '')
                            <p wire:key="correspondence-template-snippet" class="text-xs text-[var(--md-sys-color-on-surface-variant)] line-clamp-2"><bdi>{{ $item['snippet'] }}</bdi></p>
                        @endif
                    </div>
                    <div class="flex items-center gap-1">
                        <x-ui.buttons.form type="button" variant="tonal" x-on:click="useTemplate({{ (int) $item['id'] }})">استفاده</x-ui.buttons.form>
                        <x-ui.buttons.form type="button" variant="ghost" x-on:click="openBatch({{ (int) $item['id'] }})">ایجاد برای چند گیرنده</x-ui.buttons.form>
                    </div>
                </div>
                <x-ui.modals.dialog name="correspondence-batch-{{ (int) $item['id'] }}" title="ایجاد برای چند گیرنده">
                    <div class="flex flex-col gap-3">
                        <x-ui.forms.textarea label="هر گیرنده در یک خط" name="batchLines" wire:model="batchLines"/>
                        <x-ui.buttons.form type="button" variant="tonal" loading="createBatch" wire:click="createBatch({{ (int) $item['id'] }})">ایجاد</x-ui.buttons.form>
                        @if($batchIds !== [])
                            <div wire:key="correspondence-batch-share" class="contents">
                                <x-ui.buttons.form type="button" variant="primary" loading="shareBatch" x-on:click="{{ confirmDialog('shareBatch', 'ارسال همین‌ها برای امضا', convertToPersian(count($batchIds)) . ' نامه برای امضا ارسال شود؟') }}">ارسال همین‌ها برای امضا</x-ui.buttons.form>
                            </div>
                        @endif
                    </div>
                </x-ui.modals.dialog>
                </div>
            @empty
                <div wire:key="correspondence-templates-empty" class="contents">
                    <x-ui.empty icon="content_copy" title="الگویی نیست"/>
                </div>
            @endforelse
        </div>
    </x-ui.modals.dialog>

    @forelse($rows as $row)
        <button type="button" wire:key="correspondence-row-{{ $row['id'] }}" wire:click="open({{ (int) $row['id'] }})" class="w-full text-start rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm px-3 py-2.5 flex flex-col gap-1 {{ $letterId === $row['id'] ? 'border-[var(--md-sys-color-primary)]' : '' }}">
            <span class="flex items-center gap-2 min-w-0">
                <span class="h-2.5 w-2.5 rounded-full shrink-0 {{ $row['dot'] }}"></span>
                <span class="font-bold text-sm truncate {{ $row['overdue'] ? 'text-[var(--md-sys-color-error)]' : 'text-[var(--md-sys-color-on-surface)]' }}"><bdi>{{ $row['template_name'] !== '' ? $row['template_name'] : $row['subject'] }}</bdi></span>
                @foreach($row['tags'] as $chip)
                    <span wire:key="correspondence-row-tag-{{ $loop->index }}" class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">{{ $chip }}</span>
                @endforeach
                @if($row['urgent'])
                    <x-ui.badge wire:key="correspondence-row-urgent-{{ $row['id'] }}" variant="danger" class="!px-1.5 !py-0.5 !text-[10px] shrink-0">{{ $row['urgency'] }}</x-ui.badge>
                @endif
                @if($row['wet'])
                    <x-ui.badge wire:key="correspondence-row-wet-{{ $row['id'] }}" class="!px-1.5 !py-0.5 !text-[10px] shrink-0">{{ __('resources/correspondence/strings.sign_mode.wet') }}</x-ui.badge>
                @endif
            </span>
            <span class="text-xs text-[var(--md-sys-color-on-surface-variant)] flex flex-wrap gap-x-2">
                @if($row['number'] !== '')<span wire:key="correspondence-row-number-{{ $row['id'] }}"><bdi>{{ $row['number'] }}</bdi></span>@endif
                <bdi>{{ $row['type'] }}</bdi>
                <span>{{ $row['status_label'] }}</span>
                @if($row['step'] !== '')<span wire:key="correspondence-row-step-{{ $row['id'] }}">{{ $row['step'] }}</span>@endif
                @if($row['awaiting'] !== '')<span wire:key="correspondence-row-awaiting-{{ $row['id'] }}"><bdi>{{ $row['awaiting'] }}</bdi></span>@endif
                <span>{{ $row['waiting'] }} روز</span>
                @if($row['deadline'] !== '')<span wire:key="correspondence-row-deadline-{{ $row['id'] }}" class="{{ $row['overdue'] ? 'text-[var(--md-sys-color-error)]' : '' }}">مهلت <bdi>{{ $row['deadline'] }}</bdi></span>@endif
            </span>
        </button>
    @empty
        <div wire:key="correspondence-list-empty" class="contents">
            <x-ui.empty icon="mail" title="نامه‌ای در این فهرست نیست"/>
        </div>
    @endforelse

    @if($this->hasMore)
        <x-ui.buttons.load-more wire:key="correspondence-list-load-more" action="loadMore" text="نمایش بیشتر" loading-text="در حال بارگذاری…"
                                class="mx-auto my-1 px-4 py-2 rounded-xl text-xs font-medium bg-[var(--md-sys-color-surface-variant)]/50 text-[var(--md-sys-color-on-surface-variant)]"/>
    @endif
</div>
