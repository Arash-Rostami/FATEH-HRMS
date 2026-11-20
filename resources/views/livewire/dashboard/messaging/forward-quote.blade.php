@if(($msg['forwarded_html'] ?? null) !== null)
    <div wire:key="messaging-forward-quote" @class([
                'flex items-start gap-2 mb-2.5 px-2.5 py-2 rounded-lg',
                'bg-[color-mix(in_srgb,var(--md-sys-color-on-primary)_12%,transparent)] border-l-[2.5px] border-[color-mix(in_srgb,var(--md-sys-color-on-primary)_45%,transparent)]' => $msg['is_mine'],
                'bg-[color-mix(in_srgb,var(--md-sys-color-primary)_6%,transparent)] border-r-[2.5px] border-[var(--md-sys-color-primary)]' => !$msg['is_mine']
            ])>
        <span @class([
                'material-symbols-rounded text-[11px] mt-0.5 flex-shrink-0 rotate-180',
                'text-[color-mix(in_srgb,var(--md-sys-color-on-primary)_65%,transparent)]' => $msg['is_mine'],
                'text-[var(--md-sys-color-primary)]' => !$msg['is_mine']
            ])>reply</span>
        <div class="min-w-0">
            <p @class([
                    'text-[10px] font-bold mb-0.5 truncate',
                    'text-[color-mix(in_srgb,var(--md-sys-color-on-primary)_85%,transparent)]' => $msg['is_mine'],
                    'text-[var(--md-sys-color-primary)]' => !$msg['is_mine']
                ])>بازارسال‌شده</p>
            @if($msg['forwarded_html'] !== '')
                <span dir="auto" class="block break-words text-sm [unicode-bidi:plaintext]">{!! $msg['forwarded_html'] !!}</span>
            @endif
        </div>
    </div>
@endif
