<div x-data="calculator()" x-init="init()" x-ref="calculatorModal" dir="rtl" x-cloak>
    <x-ui.modals.backdrop x-show="open" :minimizable="true">
        <x-slot:icon>
            <span class="material-symbols-rounded" style="color:var(--md-sys-color-on-primary);font-size:24px;">calculate</span>
        </x-slot:icon>

        <x-slot:heading>
            <p class="text-sm font-semibold leading-none" style="color:var(--md-sys-color-on-primary);">ماشین حساب</p>
            <p class="mt-1 text-xs leading-none" style="color:rgba(255,255,255,.70);">محاسبات شخصی</p>
        </x-slot:heading>

        <div class="px-5 pt-5">
            <div class="flex flex-col rounded-xl overflow-hidden"
                 style="background:var(--md-sys-color-surface-variant);border:1px solid var(--md-sys-color-outline-variant);">

                <div x-show="history.length > 0">
                    <div class="flex items-center justify-between px-4 pt-2 pb-1">
                        <span class="text-[10px]" style="color:var(--md-sys-color-outline);">نوار حساب</span>
                        <div class="flex items-center gap-2">
                            <button @click="copyLedger()"
                                    class="flex items-center gap-1 text-[10px] transition-opacity hover:opacity-60"
                                    style="color:var(--md-sys-color-primary);">
                                <span class="material-symbols-rounded" style="font-size:12px;">content_copy</span>
                                کپی رسید
                            </button>
                            <button @click="history = []"
                                    class="text-[10px] transition-opacity hover:opacity-60"
                                    style="color:var(--md-sys-color-error);">پاک کردن
                            </button>
                        </div>
                    </div>
                    <div class="px-4 pb-2 flex flex-col-reverse gap-1 overflow-y-auto"
                         style="max-height: 120px; scrollbar-width: none;">
                        <template x-for="(item, index) in history" :key="index">
                            <div
                                class="flex justify-between items-center text-xs opacity-70 hover:opacity-100 transition-opacity">
                                <span x-text="item.eq" dir="ltr" class="font-mono text-[10px]"
                                      style="color:var(--md-sys-color-outline);"></span>
                                <button @click="useHistory(item.res)"
                                        class="font-mono font-bold cursor-pointer transition-colors"
                                        style="color:var(--md-sys-color-primary);"
                                        x-text="item.res" dir="ltr"></button>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="px-4 pb-3 pt-1"
                     :class="history.length > 0 ? 'border-t border-dashed' : ''"
                     style="border-color:var(--md-sys-color-outline-variant);">
                    <input type="text" x-model="formattedDisplay" x-ref="display" @click="copyToClipboard()" readonly
                           class="bg-transparent text-2xl font-mono text-right outline-none w-full cursor-pointer"
                           style="color:var(--md-sys-color-on-surface);font-feature-settings:'tnum';" dir="ltr">
                </div>
            </div>
        </div>

        <div class="p-5 pt-4">
            @php
                $buttons = [
                    ['l'=>'7','a'=>"appendToDisplay('7')"],['l'=>'8','a'=>"appendToDisplay('8')"],['l'=>'9','a'=>"appendToDisplay('9')"],['l'=>'÷','a'=>"appendToDisplay('/')"],
                    ['l'=>'4','a'=>"appendToDisplay('4')"],['l'=>'5','a'=>"appendToDisplay('5')"],['l'=>'6','a'=>"appendToDisplay('6')"],['l'=>'×','a'=>"appendToDisplay('*')"],
                    ['l'=>'1','a'=>"appendToDisplay('1')"],['l'=>'2','a'=>"appendToDisplay('2')"],['l'=>'3','a'=>"appendToDisplay('3')"],['l'=>'+','a'=>"appendToDisplay('+')"],
                    ['l'=>'C','a'=>'clearDisplay()'],['l'=>'.','a'=>"appendToDisplay('.')"],['l'=>'0','a'=>"appendToDisplay('0')"],['l'=>'−','a'=>"appendToDisplay('-')"],
                ];
            @endphp

            <div class="grid grid-cols-4 gap-2">
                @foreach($buttons as $btn)
                    @php
                        $isOp    = in_array($btn['l'], ['÷','×','+','−']);
                        $isClear = $btn['l'] === 'C';
                        $style   = $isOp
                            ? 'background:var(--md-sys-color-tertiary-container);color:var(--md-sys-color-on-tertiary-container);border:1px solid transparent;'
                            : ($isClear
                                ? 'background:color-mix(in srgb,var(--md-sys-color-error) 12%,transparent);color:var(--md-sys-color-error);border:1px solid var(--md-sys-color-outline-variant);'
                                : 'background:var(--md-sys-color-surface-variant);color:var(--md-sys-color-on-surface);border:1px solid var(--md-sys-color-outline-variant);');
                    @endphp
                    <button @click="{{ $btn['a'] }}"
                            class="flex items-center justify-center rounded-xl text-sm font-semibold transition-all duration-150 hover:-translate-y-px active:scale-95"
                            style="height:48px;{{ $style }}">{{ $btn['l'] }}</button>
                @endforeach

                <button @click="calculate()"
                        class="col-span-4 flex items-center justify-center rounded-xl text-base font-bold transition-all duration-150 hover:-translate-y-px active:scale-95"
                        style="height:52px;background:linear-gradient(135deg,var(--md-sys-color-primary),color-mix(in srgb,var(--md-sys-color-primary) 80%,#000 20%));color:var(--md-sys-color-on-primary);">
                    =
                </button>
            </div>
        </div>
    </x-ui.modals.backdrop>
    <template x-teleport="#tool-dock">
        <div x-show="minimized"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-x-8"
             x-transition:enter-end="opacity-100 translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-x-0"
             x-transition:leave-end="opacity-0 translate-x-8"
             class="flex items-center gap-3 overflow-hidden cursor-pointer select-none rounded-[1.35rem] bg-[var(--md-sys-color-surface-variant)] border border-[var(--md-sys-color-outline-variant)] shadow-md pr-1.5 pl-4 py-1.5 hover:bg-[var(--md-sys-color-surface)] transition-colors group"
             @click="restore()">

            <div class="flex h-[40px] w-[40px] shrink-0 items-center justify-center rounded-full bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-inner">
                <span class="material-symbols-rounded text-[20px]">calculate</span>
            </div>

            <div class="flex flex-col justify-center min-w-[80px]">
                <span class="text-[10px] font-medium text-[var(--md-sys-color-outline)] leading-tight">ماشین حساب</span>
                <span class="text-[13px] font-bold text-[var(--md-sys-color-on-surface)] leading-tight [font-feature-settings:'tnum']"
                      x-text="history.length ? history[0].res : (display ? formattedDisplay : 'آماده')" dir="ltr"></span>
            </div>

            <button @click.stop="closeModal()"
                    class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center rounded-full w-[28px] h-[28px] hover:bg-red-500/10 hover:text-red-500 text-[var(--md-sys-color-outline)] mr-2">
                <span class="material-symbols-rounded text-[18px]">close</span>
            </button>
        </div>
    </template>
</div>
