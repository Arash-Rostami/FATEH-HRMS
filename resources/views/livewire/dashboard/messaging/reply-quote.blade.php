@if($msg['reply_to'])
    <div @class([
                'flex items-start gap-2 mb-2.5 px-2.5 py-2 rounded-lg cursor-pointer transition-all duration-150 hover:brightness-110',
                'bg-[color-mix(in_srgb,var(--md-sys-color-on-primary)_12%,transparent)] border-l-[2.5px] border-[color-mix(in_srgb,var(--md-sys-color-on-primary)_45%,transparent)]' => $msg['is_mine'],
                'bg-[color-mix(in_srgb,var(--md-sys-color-primary)_6%,transparent)] border-r-[2.5px] border-[var(--md-sys-color-primary)]' => !$msg['is_mine']
            ])
         data-id="{{ $msg['reply_to']['id'] ?? 0 }}"
         x-on:click="scrollToMessage(Number($el.dataset.id))"
         x-on:keydown.enter.prevent="scrollToMessage(Number($el.dataset.id))"
         x-on:keydown.space.prevent="scrollToMessage(Number($el.dataset.id))"
         role="button"
         tabindex="0"
         title="رفتن به پیام اصلی"
         aria-label="رفتن به پیام اصلی">
        <span @class([
                'material-symbols-rounded text-[11px] mt-0.5 flex-shrink-0',
                'text-[color-mix(in_srgb,var(--md-sys-color-on-primary)_65%,transparent)]' => $msg['is_mine'],
                'text-[var(--md-sys-color-primary)]' => !$msg['is_mine']
            ])>reply</span>
        <div class="min-w-0">
            <p @class([
                    'text-[10px] font-bold mb-0.5 truncate',
                    'text-[color-mix(in_srgb,var(--md-sys-color-on-primary)_85%,transparent)]' => $msg['is_mine'],
                    'text-[var(--md-sys-color-primary)]' => !$msg['is_mine']
                ])>{{ $msg['reply_to']['sender_name'] }}</p>
            <p @class([
                    'text-[11px] truncate',
                    'text-[color-mix(in_srgb,var(--md-sys-color-on-primary)_55%,transparent)]' => $msg['is_mine'],
                    'text-[var(--md-sys-color-on-surface-variant)] opacity-70' => !$msg['is_mine']
                ])>{{ $msg['reply_to']['body'] }}</p>
        </div>
    </div>
@endif