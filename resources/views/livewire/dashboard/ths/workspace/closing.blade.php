@php
    $effectiveness = $this->ticket->effectiveness;
    $score = (int) $this->ticket->satisfaction_score;
    $isRequester = $this->ticket->requester_id === auth()->id();
    $comment = $this->ticket->extra['satisfaction_comment'] ?? null;
    $effectLabels = ['5' => 'بسیار مؤثر', '4' => 'مؤثر', '3' => 'خنثی', '2' => 'کم‌اثر', '1' => 'بی‌اثر'];
    $showEffectiveness = $this->canSetEffectiveness || $effectiveness;
    $showRating = $score > 0 || ($this->ticket->status === 'closed' && $isRequester);
    $labelClass = 'shrink-0 text-[10px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-1';
@endphp

@if($showEffectiveness || $showRating)
    <div wire:key="ths-workspace-outcome"
         class="flex max-[359px]:flex-wrap items-center gap-x-3 gap-y-2 px-4 sm:px-6 py-2 border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)] bg-[var(--md-sys-color-surface-container-low)]">
        @if($showEffectiveness)
            <div wire:key="ths-workspace-effectiveness" class="flex-1 min-w-0 flex items-center gap-2 max-[359px]:w-full">
                <span class="{{ $labelClass }}">
                    <span class="material-symbols-rounded text-[14px]">insights</span>
                    اثربخشی
                </span>
                @if($this->canSetEffectiveness)
                    <div wire:key="ths-workspace-effect-form" class="min-w-0 flex items-center gap-1 overflow-x-auto scrollbar-hide" role="radiogroup" aria-label="اثربخشی">
                        @foreach($effectLabels as $value => $label)
                            <x-ui.buttons.form type="button" wire:click="setEffectiveness('{{ $value }}')" loading="setEffectiveness('{{ $value }}')"
                                    role="radio" :aria-checked="$effectiveness === (string) $value ? 'true' : 'false'" :title="$label"
                                    @class([
                                        'shrink-0 !h-7 !px-2 !py-0 rounded-lg text-[10px] font-semibold border whitespace-nowrap',
                                        'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-transparent' => $effectiveness === (string) $value,
                                        'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-surface-container-high)]' => $effectiveness !== (string) $value,
                                    ])>
                                {{ $label }}
                            </x-ui.buttons.form>
                        @endforeach
                    </div>
                @elseif($effectiveness)
                    <p wire:key="ths-workspace-effect-set" class="min-w-0 truncate text-xs font-bold text-[var(--md-sys-color-on-surface)]" dir="auto">
                        {{ ['5' => '★★★★★ — بسیار مؤثر', '4' => '★★★★☆ — مؤثر', '3' => '★★★☆☆ — خنثی', '2' => '★★☆☆☆ — کم‌اثر', '1' => '★☆☆☆☆ — بی‌اثر'][$effectiveness] ?? '—' }}
                    </p>
                @else
                    <p wire:key="ths-workspace-effect-none" class="min-w-0 truncate text-[11px] italic opacity-70 text-[var(--md-sys-color-on-surface-variant)]">هنوز ثبت نشده است.</p>
                @endif
            </div>
        @endif

        @if($showEffectiveness && $showRating)
            <span wire:key="ths-workspace-outcome-sep" class="max-[359px]:hidden w-px self-stretch bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)]" aria-hidden="true"></span>
        @endif

        @if($showRating)
            <div wire:key="ths-modal-rating" class="flex-1 min-w-0 flex items-center gap-2 max-[359px]:w-full">
                <span class="{{ $labelClass }}">
                    <span class="material-symbols-rounded text-[14px]">star_rate</span>
                    {{ $isRequester ? 'رضایت شما' : 'رضایت درخواست‌کننده' }}
                </span>
                @if($score > 0)
                    <span wire:key="ths-rating-stars" class="inline-flex gap-0.5 shrink-0" dir="ltr" aria-label="امتیاز {{ convertToPersian($score) }} از ۵">
                        @for($i = 1; $i <= 5; $i++)
                            <span class="material-symbols-rounded text-[16px] {{ $score >= $i ? 'text-[#FFB300] font-variation-fill' : 'text-[var(--md-sys-color-outline-variant)]/50' }}">star</span>
                        @endfor
                    </span>
                    <span class="shrink-0 text-xs font-bold text-[var(--md-sys-color-on-surface)] tabular-nums">{{ convertToPersian($score) }}/۵</span>
                    @if($comment)
                        <span wire:key="ths-modal-rating-comment" class="min-w-0 truncate text-[11px] italic text-[var(--md-sys-color-on-surface-variant)]" title="{{ $comment }}">«{{ $comment }}»</span>
                    @endif
                @else
                    <span wire:key="ths-rating-pending" class="min-w-0 truncate text-[11px] italic opacity-70 text-[var(--md-sys-color-on-surface-variant)]">در انتظار ارزیابی شما — از زبانهٔ «ارزیابی»</span>
                @endif
            </div>
        @endif

        @if($this->canClose && $this->ticket->status !== 'closed')
            <x-ui.buttons.form wire:key="ths-close-ticket" type="button" loading="closeTicket" icon="task_alt"
                    wire:click="closeTicket" wire:confirm="این تیکت بسته شود؟"
                    class="shrink-0 max-[359px]:w-full !h-auto min-h-[36px] max-sm:min-h-[44px] !px-3 !py-1.5 rounded-xl text-xs font-bold bg-[var(--md-sys-color-tertiary)] text-[var(--md-sys-color-on-tertiary)] hover:brightness-110">
                بستن تیکت
            </x-ui.buttons.form>
        @endif
    </div>
@endif
