@props(['show' => null])

@if($show ?? appUpdateIsRecent())
    <span class="w-2 h-2 rounded-full bg-yellow-500 animate-pulse shadow-[0_0_8px_rgba(16,185,129,0.6)] mr-auto"></span>
@endif
