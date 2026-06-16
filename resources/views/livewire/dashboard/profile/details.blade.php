<div class="space-y-6" dir="rtl">

    @unless($hasProfile)
        <div class="flex items-center gap-3 rounded-2xl border border-[var(--md-sys-color-error)]/40 bg-[var(--md-sys-color-error-container)]/40 p-4 text-[var(--md-sys-color-on-error-container)]">
            <span class="material-symbols-rounded shrink-0">info</span>
            <p class="text-sm font-medium">
                برای ثبت اطلاعات تکمیلی، ابتدا باید تب «اطلاعات فردی» را تکمیل و ذخیره کنید.
            </p>
        </div>
    @endunless

    <form wire:submit.prevent="save" class="space-y-6">

        @foreach($groups as $section)
            @php($group = $section['group'])

            <div class="bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] rounded-2xl overflow-hidden shadow-sm">

                {{-- Section header --}}
                <div class="px-6 py-4 border-b border-[var(--md-sys-color-outline-variant)]/60 flex items-center gap-3">
                    <span class="material-symbols-rounded text-[var(--md-sys-color-primary)] text-xl">{{ $group->materialIcon() }}</span>
                    <h3 class="font-bold text-[var(--md-sys-color-on-surface)] text-sm">{{ $group->getLabel() }}</h3>
                </div>

                {{-- Section fields --}}
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($section['fields'] as $key => $def)
                            @php($name = 'form.values.' . $key)

                            @if($def['type'] === 'select')
                                <x-ui.forms.select :label="$def['label']" :name="$name" wire:model="{{ $name }}">
                                    <option value="">انتخاب کنید</option>
                                    @foreach($def['options'] as $optValue => $optLabel)
                                        <option value="{{ $optValue }}">{{ $optLabel }}</option>
                                    @endforeach
                                </x-ui.forms.select>

                            @elseif($def['type'] === 'textarea')
                                <div class="col-span-1 md:col-span-2 lg:col-span-3">
                                    <x-ui.forms.textarea :label="$def['label']" :name="$name" wire:model="{{ $name }}" rows="2"/>
                                </div>

                            @elseif($def['type'] === 'number')
                                <x-ui.forms.input type="number" :label="$def['label']" :name="$name" wire:model="{{ $name }}"/>

                            @elseif($def['type'] === 'date')
                                <x-ui.forms.jalali-date
                                    :label="$def['label']"
                                    :prefix="$name"
                                    :startYear="1350"
                                    :endYear="1410"
                                />

                            @else
                                <x-ui.forms.input type="text" :label="$def['label']" :name="$name" wire:model="{{ $name }}"/>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach

        <div class="sticky bottom-4 z-30 flex items-center justify-end">
            <div class="rounded-xl p-2">
                <x-ui.buttons.form type="submit" loading="save" loading-text="در حال ذخیره..." icon="save" variant="primary">
                    ذخیره اطلاعات تکمیلی
                </x-ui.buttons.form>
            </div>
        </div>
    </form>
</div>
