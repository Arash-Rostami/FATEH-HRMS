<div wire:key="reservation-daily-range"
     class="flex flex-col gap-2"
     x-data='{
        fill(e) {
            const name = e.target.name || "";
            if (!name.startsWith("daily.from")) return;
            const target = this.$el.querySelector(`select[name="daily.to${name.slice(10)}"]`);
            if (target && !target.value) {
                target.value = e.target.value;
                target.dispatchEvent(new Event("change", { bubbles: true }));
            }
        }
     }'
     x-on:change="fill($event)">
    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 sm:items-start">
    @foreach(['from' => 'از تاریخ', 'to' => 'تا تاریخ'] as $side => $caption)
        <div class="min-w-0">
            <span class="mb-1 flex items-center gap-1 text-xs font-bold text-[var(--md-sys-color-on-surface-variant)]">
                <span class="material-symbols-rounded text-base">event</span>{{ $caption }}
            </span>
            <div class="grid grid-cols-3 gap-1.5">
                <x-ui.forms.select label="روز" name="daily.{{ $side }}Day" wire:model="daily.{{ $side }}Day">
                    <option value="">روز</option>
                    @foreach(range(1, 31) as $day)
                        <option value="{{ $day }}">{{ convertToPersian($day) }}</option>
                    @endforeach
                </x-ui.forms.select>
                <x-ui.forms.select label="ماه" name="daily.{{ $side }}Month" wire:model="daily.{{ $side }}Month">
                    <option value="">ماه</option>
                    @foreach($monthOptions as $number => $monthName)
                        <option value="{{ $number }}">{{ $monthName }}</option>
                    @endforeach
                </x-ui.forms.select>
                <x-ui.forms.select label="سال" name="daily.{{ $side }}Year" wire:model="daily.{{ $side }}Year">
                    <option value="">سال</option>
                    @foreach($yearOptions as $year)
                        <option value="{{ $year }}">{{ convertToPersian($year) }}</option>
                    @endforeach
                </x-ui.forms.select>
            </div>
        </div>
    @endforeach
    </div>

    <div class="flex items-center gap-1">
        <x-ui.buttons.form variant="tonal" icon="check" loading="applyDailyRange" wire:click="applyDailyRange" class="!h-10 !px-3 text-xs">اعمال</x-ui.buttons.form>
        <x-ui.buttons.form variant="ghost" icon="close" loading="clearDailyRange" wire:click="clearDailyRange" class="!h-10 !px-3 text-xs">حذف</x-ui.buttons.form>
    </div>
</div>
