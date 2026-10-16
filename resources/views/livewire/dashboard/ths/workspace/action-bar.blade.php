@php
    $hasPrimary = $this->canAssign || $this->canReroute;
    $hasSecondary = $this->canRelease || $this->canWithdraw;
@endphp

<div class="sticky top-0 z-10 flex flex-wrap items-center gap-x-3 gap-y-2.5 px-4 sm:px-6 py-3 border-b border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)] backdrop-blur-sm bg-[color-mix(in_srgb,var(--md-sys-color-surface)_90%,transparent)]">
    @if($this->canAssign)
        @php $selectedAssignee = $this->assignableUsers->firstWhere('id', (int) $assigneeId); @endphp
        <div wire:key="ths-workspace-assign" class="flex items-center gap-2 flex-wrap max-sm:w-full">
            <div x-data="{ open: false }" data-ths-picker class="relative max-sm:flex-1 min-w-0">
                <button type="button"
                        x-on:click="open = !open"
                        x-on:click.outside="open = false"
                        aria-haspopup="listbox"
                        :aria-expanded="open"
                        class="w-full sm:w-auto flex items-center gap-2 text-xs rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] pr-2 pl-3 py-1.5 min-h-[44px] sm:min-h-10 text-[var(--md-sys-color-on-surface)] focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)] sm:min-w-[180px]">
                    @if($selectedAssignee)
                        <div wire:key="ths-assignee-selected" class="flex-shrink-0">
                            <x-dashboard.user-avatar :user="$selectedAssignee" size="xs"/>
                        </div>
                    @else
                        <span wire:key="ths-assignee-placeholder" class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-on-surface-variant)]">person_search</span>
                    @endif
                    <span class="truncate">{{ $selectedAssignee?->name ?? 'انتخاب کارشناس...' }}</span>
                    @if($selectedAssignee)<x-ui.presence-tag wire:key="ths-assignee-tag" :status="$selectedAssignee->presence"/>@endif
                    <span class="material-symbols-rounded text-[16px] mr-auto text-[var(--md-sys-color-on-surface-variant)]">expand_more</span>
                </button>

                <div x-show="open" x-transition style="display: none;" role="listbox"
                     class="absolute z-50 mt-1 w-60 max-w-full bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] rounded-xl shadow-lg overflow-hidden">
                    <ul class="max-h-56 overflow-y-auto custom-scrollbar">
                        @foreach($this->assignableUsers as $candidate)
                            <li wire:key="ths-assignee-{{ $candidate->id }}">
                                <button type="button"
                                        wire:click="$set('assigneeId', '{{ $candidate->id }}')"
                                        x-on:click="open = false"
                                        role="option"
                                        aria-selected="{{ $candidate->id === (int) $assigneeId ? 'true' : 'false' }}"
                                        class="w-full flex items-center gap-2 text-right px-3 py-2 min-h-[44px] text-xs hover:bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] transition-colors {{ $candidate->id === (int) $assigneeId ? 'bg-[var(--md-sys-color-primary-container)]/50 font-bold' : '' }}">
                                    <div class="flex-shrink-0">
                                        <x-dashboard.user-avatar :user="$candidate" size="sm"/>
                                    </div>
                                    <span class="truncate">{{ $candidate->name }}</span>
                                    <x-ui.presence-tag :status="$candidate->presence" class="mr-auto"/>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <x-ui.buttons.form type="button" wire:click="assign" loading="assign" icon="person_add"
                    x-bind:disabled="!$wire.assigneeId"
                    class="!h-auto max-sm:min-h-[44px] !px-3 !py-2 rounded-xl text-xs font-bold bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] hover:brightness-110">
                تخصیص
            </x-ui.buttons.form>
        </div>
    @endif

    @if($this->canWithdraw || $this->canRelease || $this->canReroute)
        <div wire:key="ths-workspace-actions" class="contents">
            @if($this->canReroute)
                <div wire:key="ths-workspace-reroute" class="flex items-end gap-2 flex-wrap max-sm:w-full">
                    <div class="w-56 max-sm:flex-1 max-sm:min-w-0">
                        <x-ui.forms.select label="واحد مقصد" name="rerouteTo" wire:model="rerouteTo" icon="domain">
                            <option value="">انتخاب واحد...</option>
                            @foreach($this->departmentOptions as $code => $name)
                                <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </x-ui.forms.select>
                    </div>
                    <x-ui.buttons.form type="button" loading="rerouteTicket" variant="tonal" icon="alt_route"
                            wire:click="rerouteTicket" wire:confirm="این تیکت به واحد انتخاب‌شده ارجاع شود؟"
                            x-bind:disabled="!$wire.rerouteTo || !$wire.replyForm.body.trim()"
                            class="!h-auto max-sm:min-h-[44px] !px-4 !py-2 rounded-xl text-xs font-bold">
                        ارجاع به واحد دیگر
                    </x-ui.buttons.form>
                </div>
            @endif

            @if($hasPrimary && $hasSecondary)
                <span wire:key="ths-workspace-actions-sep" class="hidden sm:block w-px self-stretch bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]" aria-hidden="true"></span>
            @endif

            @if($this->canRelease)
                <x-ui.buttons.form wire:key="ths-release-ticket" type="button" loading="releaseTicket" variant="tonal" icon="undo"
                        wire:click="releaseTicket" wire:confirm="این تیکت به صف بازگردانده شود؟"
                        x-bind:disabled="!$wire.replyForm.body.trim()"
                        class="!h-auto max-sm:min-h-[44px] !px-4 !py-2 rounded-xl text-xs font-bold">
                    بازگشت به صف
                </x-ui.buttons.form>
            @endif

            @if($this->canWithdraw)
                <x-ui.buttons.form wire:key="ths-withdraw-ticket" type="button" loading="withdrawTicket" variant="none" icon="cancel"
                        wire:click="withdrawTicket" wire:confirm="درخواست شما لغو شود؟"
                        class="sm:mr-auto !h-auto max-sm:min-h-[44px] !px-4 !py-2 gap-1.5 rounded-xl text-xs font-bold border border-[color-mix(in_srgb,var(--md-sys-color-error)_45%,transparent)] text-[var(--md-sys-color-error)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-error)_10%,transparent)] transition-all duration-200 active:scale-95 disabled:opacity-50">
                    لغو درخواست
                </x-ui.buttons.form>
            @endif

            @if($this->canReroute || $this->canRelease)
                <p wire:key="ths-workspace-actions-hint" class="w-full flex items-center gap-1.5 text-[11px] text-[var(--md-sys-color-on-surface-variant)]">
                    <span class="material-symbols-rounded text-[14px]">info</span>
                    توضیح را در کادر «گفتگو» بنویسید.
                </p>
            @endif
        </div>
    @endif

    <div class="flex items-center mr-auto">
        <x-dashboard.reminder-trigger :for="$this->ticket"/>
    </div>
</div>
