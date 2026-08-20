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

            @if($hasApex)
                <div class="orgc-apex-row" dir="rtl">
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
                <div class="orgc-depts {{ $hasApex ? 'orgc-depts--bus' : '' }}" dir="rtl">
                    @foreach($depts as $d)
                        @include('livewire.dashboard.tab.status.dept-node', ['d' => $d])
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>
