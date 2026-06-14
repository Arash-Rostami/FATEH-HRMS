@if($selectedTicket)
    <template x-teleport="body">
    <div x-data="{ isClosing: false }" x-cloak>
        <div class="fixed inset-0 !z-[100000] flex items-center justify-center p-4 sm:p-6 animate-slide-down"
             x-show="!isClosing"
             class="bg-[var(--md-sys-color-primary)]/60"
             dir="rtl">

            {{-- Modal Backdrop Click --}}
            <div class="absolute inset-0 bg-[var(--md-sys-color-primary)]/60"
                 @click="isClosing = true; setTimeout(() => $wire.set('selectedTicket', null), 300)"></div>

            {{-- Modal Content --}}
            <div
                class="relative w-full max-w-4xl bg-[var(--md-sys-color-on-primary)] rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
                x-show="!isClosing">

                {{-- Header --}}
                <div
                    class="flex items-center justify-between px-6 py-4 border-b border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container-low)]">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center">
                            <span class="material-symbols-rounded text-xl">confirmation_number</span>
                        </div>
                        <div>
                            <h3 title="📆 {{ $presenter->formatTimestamp($selectedTicket, 'created_at') }}" dir="ltr"
                                class="cursor-help font-bold text-[var(--md-sys-color-on-surface)] text-sm tracking-wide font-mono flex items-center gap-2 flex-row-reverse ltr">
                                {{ $presenter->formatId($selectedTicket)}}
                            </h3>
                        </div>
                    </div>

                    <button @click="isClosing = true; setTimeout(() => $wire.set('selectedTicket', null), 300)"
                            class="w-8 h-8 rounded-lg flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)] hover:text-[var(--md-sys-color-error)] transition-colors active:scale-95">
                        <span class="material-symbols-rounded text-lg">close</span>
                    </button>
                </div>

                {{-- Tabs --}}
                <div class="flex border-b border-[var(--md-sys-color-outline-variant)] px-6 bg-[var(--md-sys-color-surface-container-low)] gap-6 text-sm overflow-x-auto no-scrollbar">
                    <button wire:click="$set('modalTab', 'details')"
                            class="relative py-3 font-bold transition-colors whitespace-nowrap {{ $modalTab === 'details' ? 'text-[var(--md-sys-color-primary)]' : 'text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-surface)]' }}">
                        جزئیات تیکت
                        @if($modalTab === 'details')
                            <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-[var(--md-sys-color-primary)] rounded-t-full"></div>
                        @endif
                    </button>
                    @if($selectedTicket['action_result'] || $selectedTicket['completion_date'])
                        <button wire:click="$set('modalTab', 'response')"
                                class="relative py-3 font-bold transition-colors whitespace-nowrap {{ $modalTab === 'response' ? 'text-[var(--md-sys-color-primary)]' : 'text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-surface)]' }}">
                            پاسخ و پیگیری
                            @if($modalTab === 'response')
                                <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-[var(--md-sys-color-primary)] rounded-t-full"></div>
                            @endif
                        </button>
                    @endif
                </div>

                {{-- Body (Scrollable) --}}
                <div class="flex-1 overflow-y-auto p-6 bg-[var(--md-sys-color-surface)] custom-scrollbar">

                    @if($modalTab === 'details')
                        <div class="space-y-6 animate-[fade-in-up_0.3s_ease-out]">
                            {{-- Info Grid --}}
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                {{-- Status --}}
                                <div class="bg-[var(--md-sys-color-surface-container-lowest)] p-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/50">
                                    <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] font-bold tracking-wider mb-1.5 flex items-center gap-1">
                                        <span class="material-symbols-rounded text-[14px]">timelapse</span>
                                        وضعیت
                                    </p>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold {{ $presenter->getStatusClass($selectedTicket['status']) }} shadow-sm">
                                        {{ $presenter->getStatusLabel($selectedTicket['status']) }}
                                    </span>
                                </div>
                                {{-- Department --}}
                                <div class="bg-[var(--md-sys-color-surface-container-lowest)] p-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/50">
                                    <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] font-bold tracking-wider mb-1.5 flex items-center gap-1">
                                        <span class="material-symbols-rounded text-[14px]">domain</span>
                                        دپارتمان
                                    </p>
                                    <p class="text-xs font-bold text-[var(--md-sys-color-on-surface)] truncate">
                                        {{ $selectedTicket['department']['name'] ?? 'نامشخص' }}
                                    </p>
                                </div>
                                {{-- Category --}}
                                <div class="bg-[var(--md-sys-color-surface-container-lowest)] p-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/50">
                                    <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] font-bold tracking-wider mb-1.5 flex items-center gap-1">
                                        <span class="material-symbols-rounded text-[14px]">category</span>
                                        دسته‌بندی
                                    </p>
                                    <p class="text-xs font-bold text-[var(--md-sys-color-on-surface)] truncate">
                                        {{ $selectedTicket['category']['name'] ?? 'عمومی' }}
                                    </p>
                                </div>
                                {{-- Priority --}}
                                <div class="bg-[var(--md-sys-color-surface-container-lowest)] p-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/50">
                                    <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] font-bold tracking-wider mb-1.5 flex items-center gap-1">
                                        <span class="material-symbols-rounded text-[14px]">priority</span>
                                        اولویت
                                    </p>
                                    <p class="text-xs font-bold flex items-center gap-1 {{ $presenter->getPriorityClass($selectedTicket['priority']) }}">
                                        {{ $presenter->getPriorityLabel($selectedTicket['priority']) }}
                                        <span class="material-symbols-rounded text-[14px]">
                                            {{ $selectedTicket['priority'] === 'high' ? 'arrow_upward' : ($selectedTicket['priority'] === 'low' ? 'arrow_downward' : 'horizontal_rule') }}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            {{-- Description --}}
                            <div>
                                <h4 class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] font-bold tracking-wider mb-2 flex items-center gap-1.5">
                                    <span class="material-symbols-rounded text-[14px]">subject</span>
                                    شرح درخواست
                                </h4>
                                <div class="bg-[var(--md-sys-color-surface-container-low)] rounded-xl p-4 text-sm text-[var(--md-sys-color-on-surface)] leading-loose text-justify border border-[var(--md-sys-color-outline-variant)]/30 whitespace-pre-wrap">
                                    {{ $selectedTicket['description'] }}
                                </div>
                            </div>

                            {{-- Extra JSON --}}
                            @if(isset($selectedTicket['extra']) && is_array($selectedTicket['extra']) && !empty($selectedTicket['extra']) && count(array_filter($selectedTicket['extra'], fn($k) => $k !== 'satisfaction_comment', ARRAY_FILTER_USE_KEY)) > 0)
                                <div>
                                    <h4 class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] font-bold tracking-wider mb-2 flex items-center gap-1.5">
                                        <span class="material-symbols-rounded text-[14px]">data_object</span>
                                        اطلاعات تکمیلی
                                    </h4>
                                    <div class="bg-[var(--md-sys-color-surface-container-low)] rounded-xl p-4 border border-[var(--md-sys-color-outline-variant)]/30 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        @foreach($selectedTicket['extra'] as $key => $value)
                                            @if($key !== 'satisfaction_comment')
                                                <div class="flex items-start gap-2">
                                                    <span class="text-xs text-[var(--md-sys-color-on-surface-variant)] font-bold min-w-[80px]">{{ __($key) }}:</span>
                                                    <span class="text-xs text-[var(--md-sys-color-on-surface)] truncate" title="{{ is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value }}">
                                                        {{ is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value }}
                                                    </span>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- Request Files --}}
                            @if(!empty($selectedTicket['request_files']))
                                <div>
                                    <h4 class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] font-bold tracking-wider mb-2 flex items-center gap-1.5">
                                        <span class="material-symbols-rounded text-[14px]">attachment</span>
                                        فایل‌های ضمیمه شما
                                    </h4>
                                    <div class="flex flex-wrap gap-3">
                                        @foreach($selectedTicket['request_files'] as $file)
                                            <a href="{{ Storage::url($file['file']) }}" target="_blank"
                                               class="group flex flex-col items-center justify-center w-20 h-20 rounded-xl bg-[var(--md-sys-color-surface-container)] border border-[var(--md-sys-color-outline-variant)] hover:border-[var(--md-sys-color-primary)] transition-colors overflow-hidden relative shadow-sm hover:shadow-md">
                                                @if(Str::contains($file['file'], ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                                    <img src="{{ Storage::url($file['file']) }}" alt="Attachment"
                                                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                                @else
                                                    <span
                                                        class="material-symbols-rounded text-3xl text-[var(--md-sys-color-on-surface-variant)] group-hover:text-[var(--md-sys-color-primary)] transition-colors">description</span>
                                                @endif
                                                <div
                                                    class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                                    <span class="material-symbols-rounded text-white text-[20px]">open_in_new</span>
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if($modalTab === 'response')
                        <div class="space-y-6 animate-[fade-in-up_0.3s_ease-out]">
                            {{-- Assignee Info --}}
                            <div
                                class="flex items-center gap-4 !bg-[var(--md-sys-color-primary-container)] p-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30 shadow-sm">
                                <div
                                    class="w-12 h-12 rounded-lg text-[var(--md-sys-color-on-secondary-container)] flex items-center justify-center font-bold text-lg shadow-inner">
                                    {{ isset($selectedTicket['assignee']) ? mb_substr($selectedTicket['assignee']['name'], 0, 1) : '❗' }}
                                </div>
                                <div>
                                    <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] font-bold tracking-wider uppercase">
                                        مسئول رسیدگی
                                    </p>
                                    <p class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">
                                        {{ $selectedTicket['assignee']['name'] ?? 'در انتظار تخصیص به کارشناس' }}
                                    </p>
                                </div>
                                @if($selectedTicket['completion_date'])
                                    <div
                                        class="mr-auto text-left border-r border-[var(--md-sys-color-outline-variant)]/50 pr-4">
                                        <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] font-bold tracking-wider uppercase text-right">
                                            تاریخ تکمیل</p>
                                        <p class="text-xs font-medium text-[var(--md-sys-color-on-surface)] flex items-center gap-1 mt-0.5 justify-end"
                                           dir="ltr">
                                            <span
                                                class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-primary)]">event_available</span>
                                            {{$presenter->formatTimestamp($selectedTicket, 'completion_date') }}
                                        </p>
                                    </div>
                                @endif
                            </div>

                            {{-- Action Result --}}
                            <div
                                class="bg-[var(--md-sys-color-surface-container-low)] rounded-2xl p-5 border border-[var(--md-sys-color-outline-variant)]/30 shadow-sm relative overflow-hidden min-h-[120px]">
                                <div
                                    class="absolute top-0 right-0 w-1.5 h-full bg-[var(--md-sys-color-secondary)]"></div>
                                <h4 class="text-[var(--md-sys-color-on-surface)] font-bold mb-3 flex items-center gap-2">
                                    <span
                                        class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-secondary)]">quick_reference_all</span>
                                    نتیجه اقدامات:
                                </h4>
                                <div
                                    class="text-sm text-[var(--md-sys-color-on-surface-variant)] leading-relaxed text-justify whitespace-pre-wrap pl-2 border-r border-[var(--md-sys-color-outline-variant)]/30 pr-4 mt-2">
                                    @if($selectedTicket['action_result'])
                                        {{ $selectedTicket['action_result'] }}
                                    @else
                                        <span class="italic opacity-70">هنوز پاسخی ثبت نشده است...</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Assignee Files --}}
                            @if(!empty($selectedTicket['assignee_files']))
                                <div class="mt-4">
                                    <h5 class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)] mb-3 flex items-center gap-1.5">
                                        <span class="material-symbols-rounded text-[16px]">attachment</span>
                                        فایل‌های ضمیمه کارشناس:
                                    </h5>
                                    <div class="flex flex-wrap gap-3">
                                        @foreach($selectedTicket['assignee_files'] as $file)
                                            <a href="{{ Storage::url($file['file']) }}" target="_blank"
                                               class="group flex flex-col items-center justify-center w-20 h-20 rounded-xl bg-[var(--md-sys-color-surface-container)] border border-[var(--md-sys-color-outline-variant)] hover:border-[var(--md-sys-color-primary)] transition-colors overflow-hidden relative shadow-sm hover:shadow-md">
                                                @if(Str::contains($file['file'], ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                                    <img src="{{ Storage::url($file['file']) }}" alt="Attachment"
                                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                                @else
                                                    <span
                                                        class="material-symbols-rounded text-3xl text-[var(--md-sys-color-on-surface-variant)] group-hover:text-[var(--md-sys-color-primary)] transition-colors">description</span>
                                                @endif
                                                <div
                                                    class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                                    <span class="material-symbols-rounded text-white text-[20px]">file_download</span>
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- Rating Display (Read-Only) --}}
                            @if($selectedTicket['satisfaction_score'] > 0)
                                <div class="mt-6 border-t border-[var(--md-sys-color-outline-variant)]/50 pt-6">
                                    <h5 class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)] mb-3 text-center">
                                        ارزیابی ثبت شده شما</h5>
                                    <div class="flex justify-center gap-1 ltr-direction flex-row-reverse mb-3">
                                        @for($i = 5; $i >= 1; $i--)
                                            <span
                                                class="material-symbols-rounded text-3xl {{ $selectedTicket['satisfaction_score'] >= $i ? 'text-[#FFD700] font-variation-fill drop-shadow-sm' : 'text-[var(--md-sys-color-outline-variant)] opacity-50' }}">star</span>
                                        @endfor
                                    </div>
                                    @if(isset($selectedTicket['extra']['satisfaction_comment']) && $selectedTicket['extra']['satisfaction_comment'])
                                        <div
                                            class="bg-[var(--md-sys-color-surface-container)] rounded-xl p-4 text-xs text-[var(--md-sys-color-on-surface-variant)] italic text-center mx-auto max-w-lg shadow-inner">
                                            "{{ $selectedTicket['extra']['satisfaction_comment'] }}"
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    </template>
@endif
