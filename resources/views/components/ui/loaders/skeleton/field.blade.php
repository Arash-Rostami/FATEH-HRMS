@props(['labelWidth' => 'w-28'])

<div class="flex flex-col gap-1.5">
    <x-ui.loaders.skeleton.bar :width="$labelWidth" height="h-3"/>
    <x-ui.loaders.skeleton.bar width="w-full" height="h-10"/>
</div>
