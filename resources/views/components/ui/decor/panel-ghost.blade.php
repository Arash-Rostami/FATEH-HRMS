@php
    $icons = [
        'space_dashboard',
        'hub',
        'insights',
        'schema',
        'query_stats',
        'monitoring',
        'account_tree',
        'analytics',
        'leaderboard',
        'timeline'
    ];
    $randomIcon = $icons[array_rand($icons)];
@endphp

<div aria-hidden="true"
     class="pointer-events-none fixed top-0 left-0 -z-1 select-none
            opacity-[0.04] dark:opacity-[0.06]
            text-[var(--md-sys-color-primary)]
            translate-x-[-18%] translate-y-[12%]">
    <span class="material-symbols-rounded font-fill"
          style="font-size: 30rem; line-height: 1;">{{ $randomIcon }}</span>
</div>
