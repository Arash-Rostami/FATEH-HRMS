@php
    $chart = $this->orgViewData;
    $apex = $chart['apex'];
    $depts = $chart['depts'];
    $hasApex = $chart['hasApex'];
    $hasDepts = $chart['hasDepts'];
    $stats = $chart['stats'];
@endphp

<div class="w-full">
    @if($apex->isEmpty() && !$hasDepts)
        <x-ui.empty icon="account_tree" title="ساختاری برای نمایش وجود ندارد" variant="search"/>
    @else
        <div class="orgc-chart" dir="ltr">
            <div class="orgc-stats" dir="rtl">
                @foreach($stats as $s)
                    <div class="orgc-stat">
                        <span class="orgc-stat-icon">
                            <span class="material-symbols-rounded">{{ $s['icon'] }}</span>
                        </span>
                        <span class="orgc-stat-value">{{ convertToPersian($s['value']) }}</span>
                        <span class="orgc-stat-label">{{ $s['label'] }}</span>
                    </div>
                @endforeach
            </div>

            <div class="orgc-stage"
                 :class="{ 'is-dragging': down }"
                 @mouseenter="hovering = true"
                 @mousedown="onMouseDown"
                 @mousemove="onMouseMove"
                 @mouseup="onMouseUp"
                 @mouseleave="onMouseLeave"
                 @wheel="onWheel"
                 @click.capture="onClickCapture">

                <template x-teleport="body">
                    <div class="contents">
                        <div x-show="!toolsOpen"
                             x-transition:enter="animate-pop"
                             x-transition:leave="animate-fade-out"
                             class="fixed bottom-24 right-6 md:bottom-10 md:right-13 z-50 group flex items-center justify-center"
                             x-cloak>
                            <button type="button"
                                    @click="openTools"
                                    class="h-10 w-10 md:h-12 md:w-12 rounded-lg bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_8px_20px_rgba(0,0,0,0.12)] hover:shadow-[0_12px_28px_rgba(0,0,0,0.2)] hover:-translate-y-1 transition-transform duration-300 flex items-center justify-center outline-none focus:ring-4 focus:ring-[var(--md-sys-color-primary)]/30 will-change-transform">
                                <span class="material-symbols-rounded text-[22px] md:text-[24px]">open_with</span>
                            </button>
                            <x-ui.modals.tooltip text="ابزار جابه‌جایی نمودار" position="left"/>
                        </div>

                        <button type="button"
                                x-show="hovering && !toolsOpen"
                                x-cloak
                                @click.stop="openTools"
                                class="orgc-cursor-hint"
                                title="ابزار جابه‌جایی نمودار"
                                :style="`left:${mouseX}px; top:${mouseY}px`">
                            <span class="material-symbols-rounded">swipe</span>
                        </button>

                        <div x-show="toolsOpen"
                             x-cloak
                             @click.outside="toolsOpen = false"
                             class="orgc-compass"
                             :style="`left:${toolsX}px; top:${toolsY}px`">
                            <button type="button" @click.stop="pan(0, -220)" class="orgc-compass-btn orgc-compass-btn--up" title="بالا">
                                <span class="material-symbols-rounded">keyboard_arrow_up</span>
                            </button>
                            <button type="button" @click.stop="pan(-220, 0)" class="orgc-compass-btn orgc-compass-btn--left" title="چپ">
                                <span class="material-symbols-rounded">chevron_left</span>
                            </button>
                            <button type="button" @click.stop="toolsOpen = false" class="orgc-compass-btn orgc-compass-btn--center" title="بستن">
                                <span class="material-symbols-rounded">close</span>
                            </button>
                            <button type="button" @click.stop="pan(220, 0)" class="orgc-compass-btn orgc-compass-btn--right" title="راست">
                                <span class="material-symbols-rounded">chevron_right</span>
                            </button>
                            <button type="button" @click.stop="pan(0, 220)" class="orgc-compass-btn orgc-compass-btn--down" title="پایین">
                                <span class="material-symbols-rounded">keyboard_arrow_down</span>
                            </button>
                        </div>
                    </div>
                </template>

                @if($hasApex)
                    <div class="orgc-apex-row">
                        @foreach($apex as $user)
                            @include('livewire.dashboard.tab.status.node', [
                                'user' => $user,
                                'tier' => 'apex',
                                'key' => 'orgc-apex-' . $user->id
                            ])
                        @endforeach
                    </div>
                @endif

                @if($hasApex && $hasDepts)
                    <div class="orgc-vline"></div>
                @endif

                @if($hasDepts)
                    <div class="orgc-depts {{ $hasApex ? 'orgc-depts--bus' : '' }}" x-ref="depts">
                        @foreach($depts as $d)
                            @include('livewire.dashboard.tab.status.dept-node', ['d' => $d])
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
