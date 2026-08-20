@php
    $tier = $tier ?? 'member';
    $showToggle = $showToggle ?? false;
    $deptCode = $deptCode ?? '';
    $key = $key ?? ('orgc-node-' . $user->id);
    $d = $statusPresenter->nodeData($user, $tier);
    $p = $d['p'];
    $obscured = $d['obscured'];
    $img = $d['img'];
    $position = $d['position'];
    $deptName = $d['deptName'];
    $rankMeta = $d['rankMeta'];
    $tierConfig = $d['tierConfig'];
    $presenceBg = $d['presenceBg'];
    $aboutPayload = $d['aboutPayload'];
@endphp

<div
    wire:key="{{ $key }}"
    tabindex="0"
    role="button"
    aria-label="{{ $user->name }} — {{ $position }}"
    x-on:click.stop="$dispatch('open-about-me', {{ \Illuminate\Support\Js::from($aboutPayload) }}); $wire.openAboutMe({{ $user->id }})"
    x-on:keydown.enter.prevent="$dispatch('open-about-me', {{ \Illuminate\Support\Js::from($aboutPayload) }}); $wire.openAboutMe({{ $user->id }})"
    x-on:keydown.space.prevent="$dispatch('open-about-me', {{ \Illuminate\Support\Js::from($aboutPayload) }}); $wire.openAboutMe({{ $user->id }})"
    class="orgc-node group relative flex flex-col items-center {{ $tierConfig['width'] }} {{ $tierConfig['surface'] }} {{ $tierConfig['shadow'] }} {{ $tierConfig['spacing'] }} border {{ $tierConfig['border'] }} rounded-2xl px-3 pt-3.5 pb-3 cursor-pointer select-none transition-[box-shadow,border-color] duration-200 hover:shadow-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-primary)] focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--md-sys-color-surface)]"
>
    <div class="absolute top-0 inset-x-5 h-[2px] rounded-full {{ $tierConfig['accent'] }} opacity-90"></div>

    @if($showToggle && $tier === 'apex')
        <button
            type="button"
            x-on:click.stop="toggleDept(@js($deptCode))"
            aria-label="نمایش/پنهان‌سازی اعضای دپارتمان"
            title="نمایش/پنهان‌سازی اعضا"
            class="absolute !-bottom-3.5 left-1/2 -translate-x-1/2 w-7 h-7 rounded-full bg-[var(--md-sys-color-surface-container-high)] hover:bg-[var(--md-sys-color-surface-container-highest)] border border-[var(--md-sys-color-outline-variant)] flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-primary)] shadow-md transition-colors duration-150 z-20 focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-primary)]"
        >
            <span class="material-symbols-rounded text-[16px] transition-transform duration-200 ease-out" :class="collapsed[@js($deptCode)] ? '' : 'rotate-180'">keyboard_arrow_down</span>
        </button>
    @endif

    <div class="relative mb-2.5 {{ $obscured ? 'opacity-50 grayscale' : '' }} transition-[opacity,filter] duration-200">
        <div class="relative {{ $tierConfig['avatar'] }} rounded-full p-[2px] ring-1 {{ $tierConfig['ring'] }} group-hover:ring-[var(--md-sys-color-primary)]/50 transition-[box-shadow,ring-color] duration-200 shadow-sm">
            <img
                src="{{ $img }}"
                alt="{{ $user->name }}"
                class="w-full h-full rounded-full object-cover bg-[var(--md-sys-color-surface-container)] {{ $p->imageClasses() }}"
                loading="lazy"
            >
        </div>

        <span class="absolute bottom-0 right-0 w-3.5 h-3.5 rounded-full {{ $presenceBg }} ring-[2.5px] ring-[var(--md-sys-color-surface)] flex items-center justify-center shadow-sm">
            <span class="material-symbols-rounded text-white text-[8px] font-bold leading-none">{{ $p->icon() }}</span>
        </span>
    </div>

    <div class="w-full text-center space-y-1.5 {{ $obscured ? 'opacity-60 blur-[1.5px]' : '' }} transition-[opacity,filter] duration-200 group-hover:opacity-100 group-hover:blur-none">
        <h4 class="text-[12.5px] font-bold text-[var(--md-sys-color-on-surface)] flex items-center justify-center gap-1 tracking-tight leading-snug" title="{{ $user->name }}">
            @if(in_array($tier, ['apex', 'head']))
                <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-primary)]">military_tech</span>
            @endif
            <span class="truncate">{{ $user->name }}</span>
        </h4>

        <div class="flex justify-center">
            <span class="inline-flex items-center gap-1 px-2 py-[3px] rounded-full text-[10px] font-medium tracking-tight {{ $rankMeta['chip'] }} max-w-full leading-none">
                <span class="material-symbols-rounded text-[12px] shrink-0 leading-none">{{ $rankMeta['icon'] }}</span>
                <span class="truncate">{{ $position }}</span>
            </span>
        </div>

        @if($deptName && $tier !== 'member')
            <p class="text-[9.5px] font-normal text-[var(--md-sys-color-on-surface-variant)]/85 truncate leading-none pt-0.5">
                {{ $deptName }}
            </p>
        @endif
    </div>
</div>
