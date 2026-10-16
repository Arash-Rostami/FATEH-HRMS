@php
    $steps = $presenter->statusSteps($selectedTicket['status'], $selectedTicket);
    $requesterFiles = $selectedTicket['requester_files'] ?? [];
    $assigneeFiles = $selectedTicket['assignee_files'] ?? [];
@endphp

@include('livewire.dashboard.ths.modal.ball', ['ball' => $presenter->ballState($selectedTicket, auth()->user())])

<div class="rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] shadow-sm p-4 sm:p-6">
    <div class="flex items-start">
        @foreach($steps as $i => $step)
            <div class="flex-1 min-w-0 flex flex-col items-center gap-2">
                <div class="flex items-center w-full">
                    <div class="flex-1 h-[3px] rounded-full {{ $i === 0 ? 'invisible' : ($step['state'] !== 'upcoming' ? 'bg-[var(--md-sys-color-primary)]' : 'bg-[var(--md-sys-color-outline-variant)]/50') }}"></div>
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 shadow-sm {{ match($step['state']) {
                        'done' => 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]',
                        'active' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-tertiary)] ring-4 ring-[var(--md-sys-color-tertiary)]/20',
                        'error' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-error)] ring-4 ring-[var(--md-sys-color-error)]/20',
                        default => 'bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] opacity-70',
                    } }}">
                        <span class="material-symbols-rounded text-[18px]">{{ $step['icon'] }}</span>
                    </div>
                    <div class="flex-1 h-[3px] rounded-full {{ $loop->last ? 'invisible' : (($steps[$i + 1]['state'] ?? 'upcoming') !== 'upcoming' ? 'bg-[var(--md-sys-color-primary)]' : 'bg-[var(--md-sys-color-outline-variant)]/50') }}"></div>
                </div>
                <span class="text-[11px] font-bold text-center leading-tight {{ $step['state'] === 'upcoming' ? 'text-[var(--md-sys-color-on-surface-variant)] opacity-70' : 'text-[var(--md-sys-color-on-surface)]' }}">{{ $step['label'] }}</span>
                <span class="text-[10px] text-center tabular-nums text-[var(--md-sys-color-on-surface-variant)]" dir="auto">{{ $step['meta'] ?? '—' }}</span>
            </div>
        @endforeach
    </div>
</div>

<div class="bg-[var(--md-sys-color-surface-container-low)] rounded-2xl p-4 sm:p-6 border border-[var(--md-sys-color-outline-variant)]/40 shadow-sm relative overflow-hidden">
    <div class="absolute top-0 right-0 w-1.5 h-full bg-[var(--md-sys-color-primary)]"></div>
    <h4 class="text-[var(--md-sys-color-on-surface)] text-base font-bold mb-4 flex items-start gap-2 leading-relaxed [overflow-wrap:anywhere]">
        <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)] mt-0.5 shrink-0">subject</span>
        {{ $selectedTicket['request_subject'] }}
    </h4>
    <div class="text-sm text-[var(--md-sys-color-on-surface-variant)] leading-relaxed text-justify whitespace-pre-wrap [overflow-wrap:anywhere] pl-2 border-r-2 border-[var(--md-sys-color-outline-variant)]/30 pr-4">
        {{ $selectedTicket['description'] }}
    </div>
</div>

@if($requesterFiles || $assigneeFiles)
    <div wire:key="ths-modal-files" class="bg-[var(--md-sys-color-surface-container-low)] rounded-2xl p-4 sm:p-6 border border-[var(--md-sys-color-outline-variant)]/40 shadow-sm space-y-5">
        @if($requesterFiles)
            <div wire:key="ths-modal-requester-files">
                <h5 class="text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-3 flex items-center gap-2">
                    <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">attachment</span>
                    فایل‌های ضمیمهٔ درخواست
                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)]">{{ convertToPersian(count($requesterFiles)) }}</span>
                </h5>
                <div class="flex flex-wrap gap-3">
                    @foreach($requesterFiles as $file)
                        @include('livewire.dashboard.ths.modal.file', ['file' => $file, 'prefix' => 'ths-requester-file', 'group' => 'ticket-' . $selectedTicket['id'] . '-requester-files', 'index' => $loop->index])
                    @endforeach
                </div>
            </div>
        @endif

        @if($assigneeFiles)
            <div wire:key="ths-modal-assignee-files">
                <h5 class="text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-3 flex items-center gap-2">
                    <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">attachment</span>
                    فایل‌های ضمیمهٔ پاسخ
                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)]">{{ convertToPersian(count($assigneeFiles)) }}</span>
                </h5>
                <div class="flex flex-wrap gap-3">
                    @foreach($assigneeFiles as $file)
                        @include('livewire.dashboard.ths.modal.file', ['file' => $file, 'prefix' => 'ths-assignee-file', 'group' => 'ticket-' . $selectedTicket['id'] . '-assignee-files', 'index' => $loop->index])
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endif
