@php
    $collapseKey = 'dept:' . $d['code'];
    $hasBody = $d['members']->isNotEmpty() || !empty($d['units']);
    $topLevel = $topLevel ?? true;
    $tierClass = $topLevel && $d['level'] === 2 ? 'orgc-dept--tier-2' : '';
@endphp
<section class="orgc-dept {{ $tierClass }}" wire:key="orgc-dept-{{ $d['code'] }}">
    @if($hasBody)
        <button
            type="button"
            @click="toggleDept(@js($collapseKey))"
            title="دپارتمان {{ $d['label'] }}"
            class="orgc-dept-label"
            dir="rtl"
        >
            <span class="orgc-dept-icon"><span class="material-symbols-rounded">corporate_fare</span></span>
            <span class="orgc-dept-name truncate">{{ $d['label'] }}</span>
            <span class="orgc-count">{{ convertToPersian($d['count']) }}</span>
            <span class="material-symbols-rounded orgc-chev" x-text="collapsed[@js($collapseKey)] ? 'expand_more' : 'expand_less'">expand_less</span>
        </button>
    @else
        <div class="orgc-dept-label orgc-dept-label--static" title="دپارتمان {{ $d['label'] }}" dir="rtl">
            <span class="orgc-dept-icon"><span class="material-symbols-rounded">corporate_fare</span></span>
            <span class="orgc-dept-name truncate">{{ $d['label'] }}</span>
            <span class="orgc-count">{{ convertToPersian($d['count']) }}</span>
        </div>
    @endif

    @if($d['head'] !== null)
        @include('livewire.dashboard.tab.status.node', [
            'user' => $d['head'],
            'tier' => 'head',
            'key' => 'orgc-head-' . $d['head']->id,
            'deptCode' => $d['code'],
            'showToggle' => $hasBody,
        ])
    @endif

    @if($hasBody)
        <div class="orgc-members"
             x-show="!collapsed[@js($collapseKey)]"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2">
            @foreach($d['members'] as $user)
                <div class="orgc-member" wire:key="orgc-member-wrap-{{ $user->id }}">
                    @include('livewire.dashboard.tab.status.node', ['user' => $user, 'tier' => 'member', 'key' => 'orgc-member-' . $user->id])
                </div>
            @endforeach

            @foreach($d['units'] as $unit)
                @php $unitKey = $collapseKey . ':unit:' . $unit['name']; @endphp
                <div class="orgc-cluster" wire:key="orgc-unit-{{ $d['code'] }}-{{ $unit['name'] }}">
                    <button type="button" @click="toggleDept(@js($unitKey))" class="orgc-cluster-label" dir="rtl">
                        <span class="material-symbols-rounded">apartment</span>
                        <span class="truncate">{{ $unit['name'] }}</span>
                        <span class="material-symbols-rounded orgc-chev" x-text="collapsed[@js($unitKey)] ? 'expand_more' : 'expand_less'">expand_less</span>
                    </button>
                    <div class="orgc-cluster-members" x-show="!collapsed[@js($unitKey)]" x-transition>
                        @foreach($unit['members'] as $user)
                            <div class="orgc-member" wire:key="orgc-member-wrap-{{ $user->id }}">
                                @include('livewire.dashboard.tab.status.node', ['user' => $user, 'tier' => 'member', 'key' => 'orgc-member-' . $user->id])
                            </div>
                        @endforeach

                        @foreach($unit['sections'] as $section)
                            @php $sectionKey = $unitKey . ':section:' . $section['name']; @endphp
                            <div class="orgc-cluster orgc-cluster--section" wire:key="orgc-section-{{ $d['code'] }}-{{ $unit['name'] }}-{{ $section['name'] }}">
                                <button type="button" @click="toggleDept(@js($sectionKey))" class="orgc-cluster-label" dir="rtl">
                                    <span class="material-symbols-rounded">layers</span>
                                    <span class="truncate">{{ $section['name'] }}</span>
                                    <span class="material-symbols-rounded orgc-chev" x-text="collapsed[@js($sectionKey)] ? 'expand_more' : 'expand_less'">expand_less</span>
                                </button>
                                <div class="orgc-cluster-members" x-show="!collapsed[@js($sectionKey)]" x-transition>
                                    @foreach($section['members'] as $user)
                                        <div class="orgc-member" wire:key="orgc-member-wrap-{{ $user->id }}">
                                            @include('livewire.dashboard.tab.status.node', ['user' => $user, 'tier' => 'member', 'key' => 'orgc-member-' . $user->id])
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if(!empty($d['children']))
        <div class="orgc-dept-children">
            @foreach($d['children'] as $child)
                @include('livewire.dashboard.tab.status.dept-node', ['d' => $child, 'topLevel' => false])
            @endforeach
        </div>
    @endif
</section>
