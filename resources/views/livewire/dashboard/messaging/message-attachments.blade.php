@if(count($msg['attachments']))
    <div @class(['flex flex-wrap gap-1.5', 'mt-2' => $msg['body'] !== ''])>
        @foreach($msg['attachments'] as $i => $att)
            @if($att['is_image'])
                <a href="{{ $att['url'] }}" target="_blank" rel="noopener noreferrer"
                   class="block w-32 h-32 rounded-lg overflow-hidden border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]">
                    <img src="{{ $att['url'] }}" alt="{{ $att['name'] }}" loading="lazy"
                         class="w-full h-full object-cover">
                </a>
            @else
                <button type="button"
                        wire:click="downloadAttachment({{ $msg['id'] }}, {{ $i }})"
                        @class([
                            'flex items-center gap-2 px-2.5 py-2 rounded-lg max-w-[220px] transition-colors text-right',
                            'bg-[color-mix(in_srgb,var(--md-sys-color-on-primary)_12%,transparent)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-on-primary)_18%,transparent)]' => $msg['is_mine'],
                            'bg-[color-mix(in_srgb,var(--md-sys-color-primary)_6%,transparent)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)]' => !$msg['is_mine'],
                        ])>
                        <span class="material-symbols-rounded text-[18px] flex-shrink-0">description</span>
                        <span class="min-w-0">
                            <span class="block text-[11px] font-medium truncate">{{ $att['name'] }}</span>
                            <span class="block text-[9px] opacity-70">{{ $att['size_label'] }}</span>
                        </span>
                        <span class="material-symbols-rounded text-[15px] flex-shrink-0 opacity-70">download</span>
                </button>
            @endif
        @endforeach
    </div>
@endif