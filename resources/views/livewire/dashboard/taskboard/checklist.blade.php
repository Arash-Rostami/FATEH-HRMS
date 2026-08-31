<div x-show="formTab === 'followup'" x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
     class="space-y-4">
    <div
        class="rounded-2xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] bg-[var(--md-sys-color-surface-container-low)] transition-all overflow-hidden">
        <button type="button"
                @click="foldChecklist = !foldChecklist"
                class="w-full flex items-center justify-between p-3.5 sm:p-4 text-right select-none focus:outline-none bg-[var(--md-sys-color-surface-container)]/30 hover:bg-[var(--md-sys-color-surface-container)]/70 transition-colors">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-rounded text-lg text-[var(--md-sys-color-primary)]">checklist</span>
                <span class="text-xs sm:text-sm font-bold text-[var(--md-sys-color-on-surface)]">گزارش اقدام (چک‌لیست)</span>
                <div class="relative inline-flex items-center justify-center shrink-0"
                     style="width: 26px; height: 26px;" x-show="checklistTotal > 0">
                    <svg width="26" height="26" viewBox="0 0 26 26" class="-rotate-90">
                        <circle cx="13" cy="13" r="11.25" fill="none"
                                stroke="var(--md-sys-color-surface-variant)" stroke-width="3.5"/>
                        <circle cx="13" cy="13" r="11.25" fill="none" stroke-width="3.5" stroke-linecap="round"
                                class="transition-all duration-500"
                                :stroke="checklistCompleted === checklistTotal ? 'var(--md-sys-color-tertiary)' : 'var(--md-sys-color-primary)'"
                                :stroke-dasharray="2 * Math.PI * 11.25"
                                :stroke-dashoffset="(2 * Math.PI * 11.25) * (1 - checklistProgress / 100)"/>
                    </svg>
                    <span
                        class="absolute inset-0 flex items-center justify-center text-[10px] font-bold tabular-nums"
                        :style="{ color: checklistCompleted === checklistTotal ? 'var(--md-sys-color-tertiary)' : 'var(--md-sys-color-primary)' }"
                        x-text="checklistCompleted + '/' + checklistTotal"></span>
                </div>
            </div>
            <span
                class="material-symbols-rounded text-lg text-[var(--md-sys-color-outline)] transition-transform duration-200"
                :class="{ 'rotate-180': foldChecklist }">expand_more</span>
        </button>

        <div x-show="foldChecklist" x-collapse
             class="p-3.5 sm:p-5 space-y-4 border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]">
            @unless($isReadOnly)
                <p class="text-[11px] leading-relaxed text-[var(--md-sys-color-on-surface-variant)] px-0.5">
                    اگر فقط می‌خواهید بگویید چه کاری انجام داده‌اید، همین‌جا یک مورد کافی است. برای گزارش دقیق‌تر، کار را به چند بخش تقسیم کنید و با کشیدن روی هر ردیف، سهم (٪) هرکدام را مشخص کنید.
                </p>

                <div
                    class="group relative flex items-center rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_70%,transparent)] bg-[var(--md-sys-color-surface-container-lowest)] p-1.5 overflow-hidden transition-all duration-300 focus-within:border-[var(--md-sys-color-primary)] focus-within:ring-4 focus-within:ring-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] shadow-sm">
                    <div dir="ltr" class="absolute inset-0 flex items-stretch pointer-events-none overflow-hidden select-none"
                         x-show="checklist.length > 0">
                        <template x-for="(item, index) in checklist" :key="'aggregate-seg-' + item._uid">
                            <div class="relative h-full min-w-0 transition-all duration-300 ease-out border-r border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] last:border-r-0"
                                 :style="`width: ${item.weight}%`">
                                <div class="absolute inset-0 transition-colors duration-300"
                                     :style="item.done
                                         ? 'background-color: color-mix(in srgb, var(--md-sys-color-tertiary) 32%, transparent)'
                                         : 'background-color: color-mix(in srgb, var(--md-sys-color-primary) 20%, transparent)'"></div>
                                <div class="absolute inset-0 opacity-20 pointer-events-none"
                                     style="background-image: repeating-linear-gradient(to right, currentColor 0, currentColor 1px, transparent 1px, transparent 8px); color: var(--md-sys-color-outline);"></div>
                                <div x-show="item.done"
                                     class="absolute inset-x-0 top-0 h-1/2 bg-gradient-to-b from-white/20 to-transparent"></div>
                            </div>
                        </template>
                    </div>

                    <div class="relative z-10 flex items-center justify-between w-full gap-2">
                        <input type="text"
                               x-model="newChecklistItem"
                               @keydown.enter.prevent="addChecklist()"
                               placeholder="شرح اقدام یا بخشی از آن… و فشردن Enter"
                               class="w-full bg-transparent px-3 py-2 text-[13px] sm:text-sm text-[var(--md-sys-color-on-surface)] placeholder-[color-mix(in_srgb,var(--md-sys-color-outline)_70%,transparent)] outline-none border-none focus:ring-0 font-medium"/>

                        <div class="flex items-center gap-2 shrink-0">
                            <span x-show="checklist.length > 0"
                                  class="inline-flex items-center justify-center h-6 px-2.5 rounded-md text-[11px] font-black tabular-nums bg-[var(--md-sys-color-surface-container-high)]/90 text-[var(--md-sys-color-on-surface-variant)] backdrop-blur-sm border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] shadow-xs">
                                <span x-text="(checklist.reduce((s, i) => s + Number(i.weight || 0), 0)) + '٪'"></span>
                            </span>

                            <button type="button"
                                    @click="addChecklist()"
                                    :disabled="!newChecklistItem.trim()"
                                    class="shrink-0 flex items-center justify-center w-9 h-9 rounded-lg bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] transition-all duration-200 hover:brightness-110 active:scale-90 disabled:opacity-30 disabled:pointer-events-none shadow-[0_2px_8px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)]"
                                    aria-label="افزودن مورد">
                                <span class="material-symbols-rounded text-[20px]">add</span>
                            </button>
                        </div>
                    </div>
                </div>
            @else
                <div class="relative flex items-center justify-between h-11 px-4 rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] bg-[var(--md-sys-color-surface-container-lowest)] overflow-hidden"
                     x-show="checklist.length > 0">
                    <div dir="ltr" class="absolute inset-0 flex items-stretch pointer-events-none overflow-hidden select-none">
                        <template x-for="(item, index) in checklist" :key="'aggregate-ro-' + item._uid">
                            <div class="relative h-full min-w-0 transition-all duration-300 ease-out border-r border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] last:border-r-0"
                                 :style="`width: ${item.weight}%`">
                                <div class="absolute inset-0 transition-colors duration-300"
                                     :style="item.done
                                         ? 'background-color: color-mix(in srgb, var(--md-sys-color-tertiary) 32%, transparent)'
                                         : 'background-color: color-mix(in srgb, var(--md-sys-color-primary) 20%, transparent)'"></div>
                            </div>
                        </template>
                    </div>
                    <span class="relative z-10 text-xs font-bold text-[var(--md-sys-color-on-surface-variant)]">مجموع سهم مراحل</span>
                    <span class="relative z-10 text-xs font-black tabular-nums text-[var(--md-sys-color-primary)]"
                          x-text="(checklist.reduce((s, i) => s + Number(i.weight || 0), 0)) + '٪'"></span>
                </div>
            @endunless

            <div wire:ignore class="flex flex-col gap-2.5">
                <template x-for="(item, index) in checklist" :key="item._uid">
                    <div
                        class="group relative flex items-center justify-between gap-3 px-3.5 py-3 rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] bg-[var(--md-sys-color-surface-container-lowest)] overflow-hidden select-none transition-colors duration-200 hover:border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_80%,transparent)] hover:shadow-sm">

                        <div class="absolute inset-y-0 left-0 pointer-events-none overflow-hidden will-change-[width]"
                             :class="checklistDragIndex === index ? 'transition-none' : 'transition-[width] duration-300 ease-out'"
                             :style="`width: ${item.weight}%`">
                            <div class="absolute inset-0"
                                 :class="item.done ? 'bg-[color-mix(in_srgb,var(--md-sys-color-tertiary)_18%,transparent)]' : 'bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)]'"></div>

                            <div class="absolute inset-0 opacity-25 pointer-events-none"
                                 style="background-image: repeating-linear-gradient(to right, currentColor 0, currentColor 1px, transparent 1px, transparent 8px); color: var(--md-sys-color-outline);"></div>
                        </div>

                        <div class="absolute inset-y-0 pointer-events-none z-20 flex items-center justify-center -translate-x-1/2 will-change-[left]"
                             :class="checklistDragIndex === index ? 'transition-none' : 'transition-[left] duration-300 ease-out'"
                             :style="`left: ${item.weight}%`">

                            <div class="absolute inset-y-0 w-[1.5px] pointer-events-none"
                                 :class="item.done ? 'bg-[var(--md-sys-color-tertiary)]' : 'bg-[var(--md-sys-color-primary)]'"></div>

                            <div class="absolute top-0 flex flex-col gap-0.5 items-center w-3 py-0.5 opacity-70 pointer-events-none">
                                <div class="w-2.5 h-[1.5px] rounded-full" :class="item.done ? 'bg-[var(--md-sys-color-tertiary)]' : 'bg-[var(--md-sys-color-primary)]'"></div>
                                <div class="w-1.5 h-[1px] rounded-full" :class="item.done ? 'bg-[var(--md-sys-color-tertiary)]' : 'bg-[var(--md-sys-color-primary)]'"></div>
                                <div class="w-2 h-[1px] rounded-full" :class="item.done ? 'bg-[var(--md-sys-color-tertiary)]' : 'bg-[var(--md-sys-color-primary)]'"></div>
                            </div>

                            <div class="absolute bottom-0 flex flex-col-reverse gap-0.5 items-center w-3 py-0.5 opacity-70 pointer-events-none">
                                <div class="w-2.5 h-[1.5px] rounded-full" :class="item.done ? 'bg-[var(--md-sys-color-tertiary)]' : 'bg-[var(--md-sys-color-primary)]'"></div>
                                <div class="w-1.5 h-[1px] rounded-full" :class="item.done ? 'bg-[var(--md-sys-color-tertiary)]' : 'bg-[var(--md-sys-color-primary)]'"></div>
                                <div class="w-2 h-[1px] rounded-full" :class="item.done ? 'bg-[var(--md-sys-color-tertiary)]' : 'bg-[var(--md-sys-color-primary)]'"></div>
                            </div>

                            @unless($isReadOnly)
                                <div @pointerdown.stop="startChecklistWeightDrag($event, index)"
                                     role="slider"
                                     :aria-valuenow="item.weight"
                                     aria-valuemin="0"
                                     aria-valuemax="100"
                                     tabindex="0"
                                     @keydown.right.prevent="setChecklistWeight(index, Math.min(100, item.weight + 1))"
                                     @keydown.left.prevent="setChecklistWeight(index, Math.max(0, item.weight - 1))"
                                     @keydown.up.prevent="setChecklistWeight(index, Math.min(100, item.weight + 5))"
                                     @keydown.down.prevent="setChecklistWeight(index, Math.max(0, item.weight - 5))"
                                     @keydown.home.prevent="setChecklistWeight(index, 0)"
                                     @keydown.end.prevent="setChecklistWeight(index, 100)"
                                     :class="[
                                         item.done ? 'bg-[var(--md-sys-color-tertiary)] text-[var(--md-sys-color-on-tertiary)] border-[var(--md-sys-color-tertiary)]' : 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-[var(--md-sys-color-primary)]',
                                         checklistDragIndex === index ? 'scale-110 shadow-lg cursor-grabbing' : 'opacity-90 hover:opacity-100 hover:scale-105 cursor-ew-resize'
                                     ]"
                                     class="relative pointer-events-auto touch-none flex items-center justify-center w-3.5 h-6 rounded-full border shadow-sm transition-transform duration-150 focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)] focus:ring-offset-1">
                                    <div class="flex flex-col gap-0.5 items-center pointer-events-none">
                                        <div class="w-1 h-0.5 bg-current rounded-full opacity-80"></div>
                                        <div class="w-1 h-0.5 bg-current rounded-full opacity-80"></div>
                                        <div class="w-1 h-0.5 bg-current rounded-full opacity-80"></div>
                                    </div>
                                </div>
                            @endunless
                        </div>

                        <div class="relative z-10 flex items-center gap-3.5 flex-1 min-w-0 pointer-events-none">
                            <div class="relative flex items-center justify-center w-5 h-5 shrink-0 pointer-events-auto">
                                <input type="checkbox"
                                       :checked="item.done"
                                       @change="toggleChecklistItem(index)"
                                       @disabled($isReadOnly)
                                       class="peer appearance-none w-5 h-5 rounded-md border-2 border-[var(--md-sys-color-outline)] checked:border-[var(--md-sys-color-primary)] checked:bg-[var(--md-sys-color-primary)] focus:ring-4 focus:ring-[color-mix(in_srgb,var(--md-sys-color-primary)_15%,transparent)] focus:ring-offset-0 transition-all duration-200 cursor-pointer disabled:cursor-not-allowed disabled:opacity-50"/>
                                <span
                                    class="material-symbols-rounded absolute text-[var(--md-sys-color-on-primary)] text-[15px] font-bold opacity-0 peer-checked:opacity-100 pointer-events-none transition-opacity duration-200">check</span>
                            </div>

                            <template x-if="editingChecklistIndex !== index">
                                <span
                                    class="text-right text-[13px] sm:text-sm select-none break-words flex-1 transition-all duration-300"
                                    :class="[item.done ? 'line-through text-[var(--md-sys-color-outline)] opacity-70' : 'text-[var(--md-sys-color-on-surface)] font-medium', '{{ $isReadOnly ? '' : 'pointer-events-auto cursor-text' }}']"
                                    @dblclick="{{ $isReadOnly ? '' : 'startEditingChecklistItem(index)' }}"
                                    x-text="item.text"></span>
                            </template>
                            @unless($isReadOnly)
                                <template x-if="editingChecklistIndex === index">
                                    <input type="text"
                                           contenteditable="true"
                                           x-model="item.text"
                                           x-init="$nextTick(() => { $el.focus(); $el.select(); })"
                                           @keydown.enter="saveChecklistItemText(index)"
                                           @keydown.escape="editingChecklistIndex = null"
                                           @blur="saveChecklistItemText(index)"
                                           @click.stop
                                           class="relative z-10 pointer-events-auto text-right text-[13px] sm:text-sm flex-1 min-w-0 bg-transparent border-b border-[var(--md-sys-color-primary)] outline-none text-[var(--md-sys-color-on-surface)] font-medium"/>
                                </template>
                            @endunless
                        </div>

                        @unless($isReadOnly)
                            <div class="relative z-10 flex items-center gap-2.5 shrink-0 pointer-events-auto">
                                <div class="relative flex items-center h-8 w-16 rounded-lg border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_70%,transparent)] bg-[var(--md-sys-color-surface)]/70 backdrop-blur-sm transition-all duration-200 focus-within:border-[var(--md-sys-color-primary)] focus-within:ring-2 focus-within:ring-[color-mix(in_srgb,var(--md-sys-color-primary)_20%,transparent)]">
                                    <input type="number" min="0" max="100" step="1"
                                           :value="item.weight"
                                           @click.stop
                                           @input="setChecklistWeight(index, $event.target.value)"
                                           class="w-full h-full bg-transparent text-center text-xs sm:text-[13px] font-bold tabular-nums text-[var(--md-sys-color-on-surface)] outline-none border-none p-0 pe-4 focus:ring-0 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none [-moz-appearance:textfield]"/>
                                    <span
                                        class="absolute left-2 top-1/2 -translate-y-1/2 text-[10px] text-[var(--md-sys-color-outline)] pointer-events-none font-black select-none">٪</span>
                                </div>

                                <button type="button"
                                        @click.stop="startEditingChecklistItem(index)"
                                        aria-label="ویرایش متن"
                                        class="opacity-0 group-hover:opacity-100 focus-visible:opacity-100 flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)] hover:shadow-sm active:scale-90 transition-all duration-200">
                                    <span class="material-symbols-rounded text-[18px]">edit</span>
                                </button>

                                <button type="button"
                                        @pointerdown.stop
                                        @click.stop="removeChecklistItem(index)"
                                        aria-label="حذف"
                                        class="flex items-center justify-center w-7 h-7 sm:w-8 sm:h-8 rounded-lg text-[var(--md-sys-color-error)] opacity-70 hover:opacity-100 hover:bg-[color-mix(in_srgb,var(--md-sys-color-error)_12%,transparent)] hover:shadow-sm active:scale-90 transition-all duration-200 focus-visible:opacity-100">
                                    <span class="material-symbols-rounded text-[18px] sm:text-[19px] pointer-events-none">delete</span>
                                </button>
                            </div>
                        @else
                            <span
                                class="relative z-10 text-[11px] font-bold tabular-nums text-[var(--md-sys-color-on-surface-variant)] shrink-0"
                                x-text="item.weight + '٪'"></span>
                        @endunless
                    </div>
                </template>

                <div
                    class="flex flex-col items-center justify-center py-10 px-4 rounded-2xl border-2 border-dashed border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-lowest)_50%,transparent)] text-center transition-all duration-300"
                    x-show="checklist.length === 0" x-cloak>
                    <div
                        class="flex items-center justify-center w-14 h-14 rounded-full bg-[color-mix(in_srgb,var(--md-sys-color-surface-variant)_40%,transparent)] mb-3.5 shadow-inner">
                        <span class="material-symbols-rounded text-[28px] text-[var(--md-sys-color-outline)]">fact_check</span>
                    </div>
                    <p class="text-sm text-[var(--md-sys-color-on-surface-variant)] font-bold tracking-wide">هنوز گزارشی ثبت نشده است</p>
                    <p class="text-xs text-[var(--md-sys-color-outline)] mt-1.5 font-medium">با یک مورد بگویید چه کاری انجام داده‌اید، یا کار را به چند بخش تقسیم کنید</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 md:gap-5">

        <x-ui.forms.select label="تعیین تکلیف" name="form.state" wire:model="form.state" :disabled="$isReadOnly">
            <option value="">انتخاب کنید</option>
            @foreach(\App\Filament\Resources\TaskResource\Enums\TaskState::cases() as $stateCase)
                <option value="{{ $stateCase->value }}">{{ $stateCase->getLabel() }}</option>
            @endforeach
        </x-ui.forms.select>

        <x-ui.forms.select label="همکاران" name="form.collaborators" wire:model="form.collaborators" multiple class="min-h-[100px]" :disabled="$isReadOnly">
            @foreach($staffMembers as $staff)
                <option value="{{ $staff['id'] }}">{{ $staff['full_name'] }}</option>
            @endforeach
        </x-ui.forms.select>

        <div class="md:col-span-2">
            <label class="{{ $labelClass }}">پیوست‌ها</label>

            <ul class="mb-2 space-y-1.5" x-show="existingAttachments.length > 0">
                <template x-for="(attachment, index) in existingAttachments" :key="index">
                    <li class="flex items-center justify-between gap-2 px-3 py-2 text-xs rounded-lg bg-[var(--md-sys-color-surface-container)]">
                        <template x-if="(attachment.mime || '').startsWith('image/')">
                            <a :href="`{{ rtrim(asset('storage'), '/') }}/${attachment.path}`"
                               data-fancybox="task-{{ $editingTaskId }}-attachments"
                               :data-caption="attachment.name"
                               class="flex items-center gap-1.5 truncate text-[var(--md-sys-color-primary)]">
                                <img :src="`{{ rtrim(asset('storage'), '/') }}/${attachment.path}`" class="object-cover w-5 h-5 rounded flex-shrink-0" alt="">
                                <span x-text="attachment.name ?? attachment.path.split('/').pop()"></span>
                            </a>
                        </template>

                        <template x-if="!(attachment.mime || '').startsWith('image/')">
                            <a :href="`{{ rtrim(asset('storage'), '/') }}/${attachment.path}`" target="_blank"
                               class="flex items-center gap-1.5 truncate text-[var(--md-sys-color-primary)]">
                                <span class="text-sm material-symbols-rounded">description</span>
                                <span x-text="attachment.name ?? attachment.path.split('/').pop()"></span>
                            </a>
                        </template>

                        @unless($isReadOnly)
                            <button type="button" @click="removeExistingAttachment(index)" class="flex items-center justify-center text-[var(--md-sys-color-error)]">
                                <span class="text-sm material-symbols-rounded">close</span>
                            </button>
                        @endunless
                    </li>
                </template>
            </ul>

            @if($isReadOnly)
                <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]" x-show="existingAttachments.length === 0">
                    پیوستی ثبت نشده است.
                </p>
            @endif

            @unless($isReadOnly)
                <input type="file"
                       multiple
                       wire:model="form.attachments"
                       class="w-full p-2.5 text-xs border rounded-xl border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface)] file:ml-2 file:px-3 file:py-1.5 file:border-0 file:rounded-lg file:text-xs file:font-bold file:bg-[var(--md-sys-color-primary-container)] file:text-[var(--md-sys-color-on-primary-container)]">

                <div wire:loading wire:target="form.attachments" class="mt-2 text-xs text-[var(--md-sys-color-primary)]">
                    در حال آپلود...
                </div>

                @if(!empty($form->attachments))
                    <ul class="mt-2 space-y-1.5">
                        @foreach($form->attachments as $index => $file)
                            <li class="flex items-center justify-between gap-2 px-3 py-2 text-xs rounded-lg bg-[var(--md-sys-color-surface-container)]">
                                <span class="flex items-center gap-1.5 truncate text-[var(--md-sys-color-on-surface)]">
                                    @if(str_starts_with($file->getMimeType() ?? '', 'image/'))
                                        <img src="{{ $file->temporaryUrl() }}" class="object-cover w-5 h-5 rounded flex-shrink-0" alt="">
                                    @else
                                        <span class="text-sm material-symbols-rounded">upload_file</span>
                                    @endif
                                    {{ $file->getClientOriginalName() }}
                                </span>

                                <button type="button" wire:click="removeAttachment({{ $index }})" class="flex items-center justify-center text-[var(--md-sys-color-error)]">
                                    <span class="text-sm material-symbols-rounded">close</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @foreach(['form.attachments', 'attachments', 'attachments.*'] as $errorKey)
                    @error($errorKey)
                    <div class="{{ $errorClass }}">
                        <span class="text-sm material-symbols-rounded">error</span>
                        <span>{{ $message }}</span>
                    </div>
                    @enderror
                @endforeach
            @endunless
        </div>
    </div>
</div>
