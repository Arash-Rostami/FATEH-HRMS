@if($selectedTicket)
    <div x-data="{ isClosing: false }" x-cloak>
        @teleport('body')
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6"
             x-show="!isClosing"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 backdrop-blur-none"
             x-transition:enter-end="opacity-100 backdrop-blur-sm"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 backdrop-blur-sm"
             x-transition:leave-end="opacity-0 backdrop-blur-none"
             class="bg-[var(--md-sys-color-scrim)]/40 backdrop-blur-sm"
             dir="rtl">

            {{-- Modal Backdrop Click --}}
            <div class="absolute inset-0" @click="isClosing = true; setTimeout(() => $wire.set('selectedTicket', null), 300)"></div>

            {{-- Modal Content --}}
            <div class="relative w-full max-w-4xl bg-[var(--md-sys-color-surface-container-lowest)] rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
                 x-show="!isClosing"
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 translate-y-8 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-8 scale-95">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container-low)]">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center">
                            <span class="material-symbols-rounded text-xl">confirmation_number</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-[var(--md-sys-color-on-surface)] text-sm tracking-wide font-mono flex items-center gap-2 rtl:-scale-x-100 flex-row-reverse" dir="ltr">
                                {{ $this->getFormattedTicketId($selectedTicket) }}
                            </h3>
                            <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] mt-0.5">{{ $this->getFormattedTimeStamp($selectedTicket, 'created_at') }}</p>
                        </div>
                    </div>

                    <button @click="isClosing = true; setTimeout(() => $wire.set('selectedTicket', null), 300)"
                            class="p-2 rounded-full text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)] hover:text-[var(--md-sys-color-on-surface)] transition-colors focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)]">
                        <span class="material-symbols-rounded">close</span>
                    </button>
                </div>

                {{-- Inner Tabs --}}
                <div class="flex border-b border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface)]">
                    <button wire:click="$set('modalTab', 'request')"
                            class="flex-1 py-3 text-sm font-medium text-center transition-colors relative flex items-center justify-center gap-2"
                            :class="$wire.modalTab === 'request' ? 'text-[var(--md-sys-color-primary)]' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-lowest)] hover:text-[var(--md-sys-color-on-surface)]'">
                        <span class="material-symbols-rounded text-[18px]">description</span>
                        جزئیات درخواست
                        <div x-show="$wire.modalTab === 'request'" x-transition class="absolute bottom-0 left-0 right-0 h-0.5 bg-[var(--md-sys-color-primary)] rounded-t-full"></div>
                    </button>
                    <button wire:click="$set('modalTab', 'response')"
                            class="flex-1 py-3 text-sm font-medium text-center transition-colors relative flex items-center justify-center gap-2"
                            :class="$wire.modalTab === 'response' ? 'text-[var(--md-sys-color-primary)]' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-lowest)] hover:text-[var(--md-sys-color-on-surface)]'">
                        <span class="material-symbols-rounded text-[18px]">forum</span>
                        پاسخ و پیگیری
                        <div x-show="$wire.modalTab === 'response'" x-transition class="absolute bottom-0 left-0 right-0 h-0.5 bg-[var(--md-sys-color-primary)] rounded-t-full"></div>
                    </button>
                </div>

                {{-- Scrollable Body --}}
                <div class="p-6 overflow-y-auto custom-scrollbar flex-1 bg-[var(--md-sys-color-surface-container-lowest)]">
                    @if($modalTab === 'request')
                        <div class="space-y-6 animate-[fade-in-up_0.3s_ease-out]">
                            {{-- Status Badges Grid --}}
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                                <div class="bg-[var(--md-sys-color-surface-container-low)] p-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 flex flex-col items-center justify-center text-center gap-1 shadow-sm">
                                    <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-wider font-bold">حوزه</span>
                                    <span class="text-xs font-medium text-[var(--md-sys-color-on-surface)]">{{ $this->getRequestAreaLabel($selectedTicket['request_type'], $selectedTicket['request_area']) }}</span>
                                </div>
                                <div class="bg-[var(--md-sys-color-surface-container-low)] p-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 flex flex-col items-center justify-center text-center gap-1 shadow-sm">
                                    <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-wider font-bold">وضعیت</span>
                                    @php
                                        $s = $selectedTicket['status'];
                                        $col = $s==='open' ? 'text-[var(--md-sys-color-primary)]' : ($s==='in-progress'?'text-[var(--md-sys-color-tertiary)]':'text-[var(--md-sys-color-secondary)]');
                                        $lbl = $s==='open' ? 'باز' : ($s==='in-progress'?'در حال بررسی':'بسته شده');
                                    @endphp
                                    <span class="text-xs font-bold {{ $col }}">{{ $lbl }}</span>
                                </div>
                                <div class="bg-[var(--md-sys-color-surface-container-low)] p-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 flex flex-col items-center justify-center text-center gap-1 shadow-sm">
                                    <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-wider font-bold">اولویت</span>
                                    @php
                                        $p = $selectedTicket['priority'];
                                        $pCol = $p==='low'?'text-[var(--md-sys-color-primary)]':($p==='medium'?'text-[var(--md-sys-color-secondary)]':'text-[var(--md-sys-color-error)]');
                                        $pLbl = $p==='low'?'کم':($p==='medium'?'متوسط':'زیاد');
                                    @endphp
                                    <span class="text-xs font-bold {{ $pCol }}">{{ $pLbl }}</span>
                                </div>
                                <div class="bg-[var(--md-sys-color-surface-container-low)] p-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 flex flex-col items-center justify-center text-center gap-1 shadow-sm">
                                    <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-wider font-bold">تاریخ ایجاد</span>
                                    <span class="text-[11px] font-medium text-[var(--md-sys-color-on-surface)] font-mono rtl:-scale-x-100 flex-row-reverse flex" dir="ltr">{{ \Carbon\Carbon::parse($selectedTicket['created_at'])->format('Y-m-d') }}</span>
                                </div>
                            </div>

                            {{-- Subject & Desc --}}
                            <div class="bg-[var(--md-sys-color-surface-container-low)] rounded-2xl p-5 border border-[var(--md-sys-color-outline-variant)]/30 shadow-sm relative overflow-hidden">
                                <div class="absolute top-0 right-0 w-1.5 h-full bg-[var(--md-sys-color-primary)]"></div>
                                <h4 class="text-[var(--md-sys-color-on-surface)] font-bold mb-3 flex items-center gap-2">
                                    <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">subject</span>
                                    {{ $selectedTicket['request_subject'] }}
                                </h4>
                                <div class="text-sm text-[var(--md-sys-color-on-surface-variant)] leading-relaxed text-justify whitespace-pre-wrap pl-2 border-r border-[var(--md-sys-color-outline-variant)]/30 pr-4 mt-2">
                                    {{ $selectedTicket['description'] }}
                                </div>
                            </div>

                            {{-- Requester Files --}}
                            @if(!empty($selectedTicket['requester_files']))
                                <div class="mt-4">
                                    <h5 class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)] mb-3 flex items-center gap-1.5">
                                        <span class="material-symbols-rounded text-[16px]">attachment</span>
                                        فایل‌های ضمیمه شما:
                                    </h5>
                                    <div class="flex flex-wrap gap-3">
                                        @foreach($selectedTicket['requester_files'] as $file)
                                            <a href="{{ asset($file['file']) }}" target="_blank" class="group flex flex-col items-center justify-center w-20 h-20 rounded-xl bg-[var(--md-sys-color-surface-container)] border border-[var(--md-sys-color-outline-variant)] hover:border-[var(--md-sys-color-primary)] transition-colors overflow-hidden relative shadow-sm hover:shadow-md">
                                                @if(Str::contains($file['file'], ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                                    <img src="{{ asset($file['file']) }}" alt="Attachment" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                                @else
                                                    <span class="material-symbols-rounded text-3xl text-[var(--md-sys-color-on-surface-variant)] group-hover:text-[var(--md-sys-color-primary)] transition-colors">description</span>
                                                @endif
                                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center backdrop-blur-[2px]">
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
                            <div class="flex items-center gap-4 bg-[var(--md-sys-color-surface-container-low)] p-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30 shadow-sm">
                                <div class="w-12 h-12 rounded-full bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] flex items-center justify-center font-bold text-lg shadow-inner">
                                    {{ isset($selectedTicket['assignee']) ? mb_substr($selectedTicket['assignee']['name'], 0, 1) : '؟' }}
                                </div>
                                <div>
                                    <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] font-bold tracking-wider uppercase">مسئول رسیدگی</p>
                                    <p class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">
                                        {{ $selectedTicket['assignee']['name'] ?? 'در انتظار تخصیص به کارشناس' }}
                                    </p>
                                </div>
                                @if($selectedTicket['completion_date'])
                                    <div class="mr-auto text-left border-r border-[var(--md-sys-color-outline-variant)]/50 pr-4">
                                        <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] font-bold tracking-wider uppercase text-right">تاریخ تکمیل</p>
                                        <p class="text-xs font-medium text-[var(--md-sys-color-on-surface)] flex items-center gap-1 mt-0.5 justify-end" dir="ltr">
                                            <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-primary)]">event_available</span>
                                            {{ $this->getFormattedTimeStamp($selectedTicket, 'completion_date') }}
                                        </p>
                                    </div>
                                @endif
                            </div>

                            {{-- Action Result --}}
                            <div class="bg-[var(--md-sys-color-surface-container-low)] rounded-2xl p-5 border border-[var(--md-sys-color-outline-variant)]/30 shadow-sm relative overflow-hidden min-h-[120px]">
                                <div class="absolute top-0 right-0 w-1.5 h-full bg-[var(--md-sys-color-secondary)]"></div>
                                <h4 class="text-[var(--md-sys-color-on-surface)] font-bold mb-3 flex items-center gap-2">
                                    <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-secondary)]">quick_reference_all</span>
                                    نتیجه اقدامات:
                                </h4>
                                <div class="text-sm text-[var(--md-sys-color-on-surface-variant)] leading-relaxed text-justify whitespace-pre-wrap pl-2 border-r border-[var(--md-sys-color-outline-variant)]/30 pr-4 mt-2">
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
                                            <a href="{{ asset($file['file']) }}" target="_blank" class="group flex flex-col items-center justify-center w-20 h-20 rounded-xl bg-[var(--md-sys-color-surface-container)] border border-[var(--md-sys-color-outline-variant)] hover:border-[var(--md-sys-color-primary)] transition-colors overflow-hidden relative shadow-sm hover:shadow-md">
                                                @if(Str::contains($file['file'], ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                                    <img src="{{ asset($file['file']) }}" alt="Attachment" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                                @else
                                                    <span class="material-symbols-rounded text-3xl text-[var(--md-sys-color-on-surface-variant)] group-hover:text-[var(--md-sys-color-primary)] transition-colors">description</span>
                                                @endif
                                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center backdrop-blur-[2px]">
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
                                    <h5 class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)] mb-3 text-center">ارزیابی ثبت شده شما</h5>
                                    <div class="flex justify-center gap-1 ltr-direction flex-row-reverse mb-3">
                                        @for($i = 5; $i >= 1; $i--)
                                            <span class="material-symbols-rounded text-3xl {{ $selectedTicket['satisfaction_score'] >= $i ? 'text-[#FFD700] font-variation-fill drop-shadow-sm' : 'text-[var(--md-sys-color-outline-variant)] opacity-50' }}">star</span>
                                        @endfor
                                    </div>
                                    @if(isset($selectedTicket['extra']['satisfaction_comment']) && $selectedTicket['extra']['satisfaction_comment'])
                                        <div class="bg-[var(--md-sys-color-surface-container)] rounded-xl p-4 text-xs text-[var(--md-sys-color-on-surface-variant)] italic text-center mx-auto max-w-lg shadow-inner">
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
        @endteleport
    </div>
@endif
