@php($blocked = $this->bookingBlockReason !== null)
@php($delay = ($cardIndex ?? 0) * 0.04)
<div wire:key="resource-{{ $resource->id }}" data-rf="resource-{{ $resource->id }}"
     class="group bg-[var(--md-sys-color-surface)] rounded-2xl border border-[var(--md-sys-color-outline-variant)] shadow-[var(--md-sys-elevation-1)] hover:shadow-[0_20px_40px_color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] hover:-translate-y-1.5 transition-all duration-500 flex flex-col overflow-hidden relative h-full animate-slide-up-fade"
     style="border-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 40%, transparent); animation-delay: {{ $delay }}s;">

    <div x-data="{ imageError: false }"
         class="w-full h-56 relative bg-[var(--md-sys-color-surface-container-highest)] overflow-hidden shrink-0">

        @if($resource->display_image)
            <img src="{{ $resource->displayImageUrl }}"
                 class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-110"
                 alt="{{ $resource->name }}"
                 x-on:error="imageError = true"
                 x-show="!imageError"
            >

            <button x-show="!imageError"
                    type="button"
                    wire:click="$set('zoomImageUrl', '{{  $resource->displayImageUrl }}')"
                    class="absolute top-4 left-4 w-10 h-10 rounded-xl bg-black/20 hover:bg-black/40 border border-white/10 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 translate-y-2 group-hover:translate-y-0 transition-all duration-300 z-20 shadow-xl"
                    title="بزرگنمایی"
            >
                <span class="material-symbols-rounded text-[22px]">zoom_in</span>
            </button>
        @endif

        <div
            x-show="imageError || !{{ $resource->display_image ? 'true' : 'false' }}"
            class="absolute inset-0 w-full h-full flex items-center justify-center bg-[var(--md-sys-color-surface-variant)] transition-transform duration-1000 group-hover:scale-105"
            style="background-image: radial-gradient(color-mix(in srgb, var(--md-sys-color-on-surface) 8%, transparent) 1px, transparent 1px); background-size: 20px 20px;"
        >
            <span class="material-symbols-rounded !text-4xl text-[var(--md-sys-color-primary)] opacity-50">
               {{ $resource->icon }}
            </span>
        </div>

        <div
            class="absolute inset-0 bg-gradient-to-t from-[var(--md-sys-color-surface)] via-transparent to-transparent opacity-80 z-0"></div>

        <div class="absolute bottom-4 right-5 left-5 z-10 flex justify-start">
            <div
                class="inline-flex items-center gap-2 px-2 py-1.5 bg-black/40 border border-white/10 rounded-lg text-white shadow-lg max-w-full">
                <span class="text-sm font-medium tracking-wide truncate">
                {{ $resource->name }}
                </span>
                <span class="material-symbols-rounded text-[18px] font-fill shrink-0">
                    {{ $resource->icon }}
                </span>
            </div>
        </div>
    </div>

    <div class="p-6 flex-1 flex flex-col relative bg-[var(--md-sys-color-surface)]">
        @if($resource->metadata['notes'] ?? null)
            <div class="flex items-start gap-2 mb-4 px-1">
                <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-primary)]">sticky_note_2</span>
                <p class="text-[12px] leading-6 font-semibold text-[var(--md-sys-color-on-surface)]">{{ $resource->metadata['notes'] }}</p>
            </div>
        @endif

        <div class="relative mb-6">
            <div class="flex flex-nowrap gap-2 overflow-x-auto pb-2 custom-scrollbar mask-fade-right">
                @foreach($resource->formatted_metadata as $item)
                    <div
                        class="inline-flex items-center gap-2 shrink-0 text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] bg-[var(--md-sys-color-surface-container-low)] px-3 py-1.5 rounded-lg border border-[var(--md-sys-color-outline-variant)]/20 shadow-sm transition-transform active:scale-95">
                        <span class="material-symbols-rounded text-[15px] text-[var(--md-sys-color-primary)]">
                            {{ $item->icon ?? 'label' }}
                        </span>
                        <span class="{{ $item->class }} whitespace-nowrap">
                            {{ $item->label }}{{ $item->value }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-auto w-full">
            @if($blocked)
                <div class="flex items-center gap-1.5 mb-2 px-1">
                    <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-error)]">block</span>
                    <span class="text-[11px] font-semibold text-[var(--md-sys-color-error)] leading-tight">{{ $this->bookingBlockReason }}</span>
                </div>
            @endif

            <x-ui.buttons.submit
                wire:click="book({{ $resource->id }})"
                :disabled="$blocked"
                :target="'book('.$resource->id.')'"
                :text="!(\App\Enums\ResourceType::tryFrom($activeTab)?->isFullDay() ?? true) ? 'ثبت زمان ('.convertToPersian($startTime).' - '.convertToPersian($endTime).')' : 'ثبت رزرو کامل'"
                loadingText="در حال ثبت..."
                icon="add_circle"
                iconSize="text-[20px]"
                class="w-full h-13 rounded-2xl font-black text-[13px] shadow-lg shadow-[var(--md-sys-color-primary)]/20 hover:shadow-xl hover:brightness-110"
            />
        </div>
    </div>
</div>
