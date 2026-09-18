                <div wire:key="reminder-tab-form" class="space-y-3" x-data="{ q: @entangle('form.title'), list: @js($this->suggestedTitles), open: false }">
                    <div class="relative">
                        <x-ui.forms.input label="عنوان" name="form.title" wire:model="form.title" x-on:focus="open = true" x-on:blur="setTimeout(() => open = false, 150)"/>
                        <div x-show="open && list.filter(s => s.toLowerCase().includes((q ?? '').toLowerCase())).length"
                             x-cloak
                             class="absolute z-10 mt-1 w-full bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/30 rounded-xl shadow-lg max-h-40 overflow-y-auto">
                            <template x-for="s in list.filter(s => s.toLowerCase().includes((q ?? '').toLowerCase()))" :key="s">
                                <div @click="$wire.set('form.title', s); open = false" x-text="s" class="px-3 py-2 text-sm cursor-pointer hover:bg-[var(--md-sys-color-surface-container-high)]"></div>
                            </template>
                        </div>
                    </div>

                    <x-ui.forms.textarea label="یادداشت" name="form.notes" wire:model="form.notes" rows="2"/>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <x-ui.forms.date label="تاریخ سررسید" prefix="form.due" :startYear="jNow()" :endYear="jNow() + 5"/>
                        <x-ui.forms.input label="ساعت" name="form.dueTime" type="time" wire:model="form.dueTime"/>
                    </div>

                    <x-ui.forms.select label="تکرار" name="form.recurs" wire:model="form.recurs">
                        @foreach(\App\Enums\ReminderRecurrence::cases() as $case)
                            <option value="{{ $case->value }}">{{ $case->label() }}</option>
                        @endforeach
                    </x-ui.forms.select>

                    <div class="flex items-center gap-4 text-xs flex-wrap">
                        @foreach($channelToggles as $ch)
                            <label class="flex items-center gap-1.5 {{ ($ch['disabled'] ?? false) ? 'opacity-40' : '' }}">
                                <input type="checkbox"
                                       @if($ch['disabled'] ?? false) disabled @else wire:model="{{ $ch['name'] }}" @endif
                                       class="w-4 h-4 rounded accent-[var(--md-sys-color-primary)]">
                                {{ $ch['label'] }}
                            </label>
                        @endforeach
                    </div>
                </div>
