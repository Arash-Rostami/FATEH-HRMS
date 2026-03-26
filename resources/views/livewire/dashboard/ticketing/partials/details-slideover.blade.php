<x-dashboard.modal.slideover show="selectedTicket" maxWidth="max-w-4xl">
    @if($selectedTicket)
        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-5 border-b border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface-container-low)]">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center shadow-inner border border-[var(--md-sys-color-primary)]/20">
                    <span class="material-symbols-rounded text-[24px]">confirmation_number</span>
                </div>
                <div>
                    <h3 class="font-bold text-[var(--md-sys-color-on-surface)] text-base tracking-wide font-mono flex items-center gap-2 rtl:-scale-x-100 flex-row-reverse" dir="ltr">
                        {{ $this->getFormattedTicketId($selectedTicket) }}
                    </h3>
                    <p class="text-xs text-[var(--md-sys-color-on-surface-variant)] mt-1 font-medium">{{ $this->getFormattedTimeStamp($selectedTicket, 'created_at') }}</p>
                </div>
            </div>

            <button @click="$wire.set('selectedTicket', null)"
                    class="p-2 rounded-full text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-error-container)] hover:text-[var(--md-sys-color-on-error-container)] transition-colors focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-error)]">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        {{-- Inner Tabs --}}
        <div class="flex border-b border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface)]">
            <button wire:click="$set('modalTab', 'request')"
                    class="flex-1 py-4 text-sm font-bold text-center transition-colors relative flex items-center justify-center gap-2"
                    :class="$wire.modalTab === 'request' ? 'text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-primary)]/5' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-lowest)] hover:text-[var(--md-sys-color-on-surface)]'">
                <span class="material-symbols-rounded text-[20px]" :class="$wire.modalTab === 'request' ? 'font-variation-fill' : ''">description</span>
                جزئیات درخواست
                <div x-show="$wire.modalTab === 'request'" x-transition class="absolute bottom-0 left-0 right-0 h-0.5 bg-[var(--md-sys-color-primary)] rounded-t-full"></div>
            </button>
            <button wire:click="$set('modalTab', 'response')"
                    class="flex-1 py-4 text-sm font-bold text-center transition-colors relative flex items-center justify-center gap-2"
                    :class="$wire.modalTab === 'response' ? 'text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-primary)]/5' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-lowest)] hover:text-[var(--md-sys-color-on-surface)]'">
                <span class="material-symbols-rounded text-[20px]" :class="$wire.modalTab === 'response' ? 'font-variation-fill' : ''">forum</span>
                پاسخ و پیگیری
                <div x-show="$wire.modalTab === 'response'" x-transition class="absolute bottom-0 left-0 right-0 h-0.5 bg-[var(--md-sys-color-primary)] rounded-t-full"></div>
            </button>
        </div>

        {{-- Scrollable Body --}}
        <div class="p-6 md:p-8 overflow-y-auto custom-scrollbar flex-1 bg-[var(--md-sys-color-surface-container-lowest)]">
            @if($modalTab === 'request')
                <div class="space-y-6 animate-[fade-in-up_0.3s_ease-out]">
                    {{-- Status Badges Grid --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
                        <div class="bg-[var(--md-sys-color-surface)] p-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 flex flex-col items-center justify-center text-center gap-2 shadow-sm relative overflow-hidden group">
                            <div class="absolute inset-0 bg-gradient-to-b from-transparent to-[var(--md-sys-color-surface-container-low)] opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]/60">category</span>
                            <div class="flex flex-col relative z-10">
                                <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-wider font-bold mb-1">حوزه پشتیبانی</span>
                                <span class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">{{ $this->getRequestAreaLabel($selectedTicket['request_type'], $selectedTicket['request_area']) }}</span>
                            </div>
                        </div>
                        <div class="bg-[var(--md-sys-color-surface)] p-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 flex flex-col items-center justify-center text-center gap-2 shadow-sm relative overflow-hidden group">
                            <div class="absolute inset-0 bg-gradient-to-b from-transparent to-[var(--md-sys-color-surface-container-low)] opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            @php
                                $s = $selectedTicket['status'];
                                $col = $s==='open' ? 'text-[var(--md-sys-color-primary)]' : ($s==='in-progress'?'text-[var(--md-sys-color-tertiary)]':'text-[var(--md-sys-color-secondary)]');
                                $lbl = $s==='open' ? 'باز' : ($s==='in-progress'?'در حال بررسی':'بسته شده');
                                $icn = $s==='open' ? 'pending' : ($s==='in-progress'?'sync':'check_circle');
                            @endphp
                            <span class="material-symbols-rounded text-[24px] {{ $col }}/60 {{ $s==='in-progress' ? 'animate-spin' : '' }}">{{ $icn }}</span>
                            <div class="flex flex-col relative z-10">
                                <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-wider font-bold mb-1">وضعیت کنونی</span>
                                <span class="text-sm font-bold {{ $col }}">{{ $lbl }}</span>
                            </div>
                        </div>
                        <div class="bg-[var(--md-sys-color-surface)] p-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 flex flex-col items-center justify-center text-center gap-2 shadow-sm relative overflow-hidden group">
                            <div class="absolute inset-0 bg-gradient-to-b from-transparent to-[var(--md-sys-color-surface-container-low)] opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            @php
                                $p = $selectedTicket['priority'];
                                $pCol = $p==='low'?'text-[var(--md-sys-color-primary)]':($p==='medium'?'text-[var(--md-sys-color-secondary)]':'text-[var(--md-sys-color-error)]');
                                $pLbl = $p==='low'?'کم':($p==='medium'?'متوسط':'زیاد');
                                $pIcn = $p==='low'?'arrow_drop_down':($p==='medium'?'remove':'arrow_drop_up');
                            @endphp
                            <span class="material-symbols-rounded text-[24px] {{ $pCol }}/60">{{ $pIcn }}</span>
                            <div class="flex flex-col relative z-10">
                                <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-wider font-bold mb-1">اولویت رسیدگی</span>
                                <span class="text-sm font-bold {{ $pCol }}">{{ $pLbl }}</span>
                            </div>
                        </div>
                        <div class="bg-[var(--md-sys-color-surface)] p-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 flex flex-col items-center justify-center text-center gap-2 shadow-sm relative overflow-hidden group">
                            <div class="absolute inset-0 bg-gradient-to-b from-transparent to-[var(--md-sys-color-surface-container-low)] opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-on-surface-variant)]/60">calendar_month</span>
                            <div class="flex flex-col relative z-10">
                                <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-wider font-bold mb-1">تاریخ ایجاد</span>
                                <span class="text-[13px] font-bold text-[var(--md-sys-color-on-surface)] font-mono rtl:-scale-x-100 flex-row-reverse flex" dir="ltr">{{ \Carbon\Carbon::parse($selectedTicket['created_at'])->format('Y-m-d') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Subject & Desc --}}
                    <div class="bg-[var(--md-sys-color-surface)] rounded-3xl p-6 md:p-8 border border-[var(--md-sys-color-outline-variant)]/40 shadow-sm relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-2 h-full bg-gradient-to-b from-[var(--md-sys-color-primary)] to-[var(--md-sys-color-tertiary)]"></div>
                        <h4 class="text-lg text-[var(--md-sys-color-on-surface)] font-bold mb-4 flex items-center gap-3">
                            <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)] p-2 rounded-xl bg-[var(--md-sys-color-primary-container)]/50">subject</span>
                            {{ $selectedTicket['request_subject'] }}
                        </h4>
                        <div class="text-[15px] text-[var(--md-sys-color-on-surface-variant)] leading-loose text-justify whitespace-pre-wrap pl-4 border-r-2 border-[var(--md-sys-color-outline-variant)]/40 pr-6 mt-4">
                            {{ $selectedTicket['description'] }}
                        </div>
                    </div>

                    {{-- Requester Files --}}
                    @if(!empty($selectedTicket['requester_files']))
                        <div class="mt-8 bg-[var(--md-sys-color-surface-container-low)] p-6 rounded-3xl border border-[var(--md-sys-color-outline-variant)]/30">
                            <h5 class="text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-4 flex items-center gap-2 border-b border-[var(--md-sys-color-outline-variant)]/40 pb-3">
                                <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)]">attachment</span>
                                فایل‌های ضمیمه شما
                            </h5>
                            <div class="flex flex-wrap gap-4">
                                @foreach($selectedTicket['requester_files'] as $file)
                                    <a href="{{ asset($file['file']) }}" target="_blank" class="group flex flex-col items-center justify-center w-24 h-24 rounded-2xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] hover:border-[var(--md-sys-color-primary)] transition-all overflow-hidden relative shadow-sm hover:shadow-md hover:-translate-y-1">
                                        @if(Str::contains($file['file'], ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                            <img src="{{ asset($file['file']) }}" alt="Attachment" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                                        @else
                                            <span class="material-symbols-rounded text-4xl text-[var(--md-sys-color-primary)]/40 group-hover:text-[var(--md-sys-color-primary)] transition-colors">description</span>
                                        @endif
                                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center backdrop-blur-sm">
                                            <span class="material-symbols-rounded text-white text-[28px] transform scale-50 group-hover:scale-100 transition-transform duration-300">open_in_new</span>
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
                    <div class="flex items-center gap-5 bg-[var(--md-sys-color-surface)] p-5 md:p-6 rounded-3xl border border-[var(--md-sys-color-outline-variant)]/40 shadow-sm relative overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-l from-[var(--md-sys-color-secondary-container)]/20 to-transparent pointer-events-none"></div>
                        <div class="w-14 h-14 rounded-full bg-[var(--md-sys-color-secondary)] text-[var(--md-sys-color-on-secondary)] flex items-center justify-center font-bold text-xl shadow-lg relative z-10 ring-4 ring-[var(--md-sys-color-secondary-container)]/50">
                            {{ isset($selectedTicket['assignee']) ? mb_substr($selectedTicket['assignee']['name'], 0, 1) : '؟' }}
                        </div>
                        <div class="relative z-10 flex-1">
                            <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] font-bold tracking-widest uppercase mb-1">مسئول رسیدگی</p>
                            <p class="text-base font-bold text-[var(--md-sys-color-on-surface)]">
                                {{ $selectedTicket['assignee']['name'] ?? 'در انتظار تخصیص به کارشناس' }}
                            </p>
                        </div>
                        @if($selectedTicket['completion_date'])
                            <div class="relative z-10 text-left border-r border-[var(--md-sys-color-outline-variant)]/60 pr-5 pl-2">
                                <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] font-bold tracking-widest uppercase text-right mb-1">تاریخ تکمیل</p>
                                <p class="text-sm font-bold text-[var(--md-sys-color-on-surface)] flex items-center gap-1.5 justify-end" dir="ltr">
                                    <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-secondary)]">event_available</span>
                                    {{ $this->getFormattedTimeStamp($selectedTicket, 'completion_date') }}
                                </p>
                            </div>
                        @endif
                    </div>

                    {{-- Action Result --}}
                    <div class="bg-[var(--md-sys-color-surface)] rounded-3xl p-6 md:p-8 border border-[var(--md-sys-color-outline-variant)]/40 shadow-sm relative overflow-hidden min-h-[160px]">
                        <div class="absolute top-0 right-0 w-2 h-full bg-gradient-to-b from-[var(--md-sys-color-secondary)] to-[var(--md-sys-color-primary)]"></div>
                        <h4 class="text-lg text-[var(--md-sys-color-on-surface)] font-bold mb-4 flex items-center gap-3">
                            <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-secondary)] p-2 rounded-xl bg-[var(--md-sys-color-secondary-container)]/50">quick_reference_all</span>
                            نتیجه اقدامات کارشناس
                        </h4>
                        <div class="text-[15px] text-[var(--md-sys-color-on-surface-variant)] leading-loose text-justify whitespace-pre-wrap pl-4 border-r-2 border-[var(--md-sys-color-outline-variant)]/40 pr-6 mt-4">
                            @if($selectedTicket['action_result'])
                                {{ $selectedTicket['action_result'] }}
                            @else
                                <div class="flex items-center gap-3 text-[var(--md-sys-color-on-surface-variant)]/60 italic bg-[var(--md-sys-color-surface-container-lowest)] p-4 rounded-xl border border-dashed border-[var(--md-sys-color-outline-variant)]">
                                    <span class="material-symbols-rounded">hourglass_empty</span>
                                    هنوز پاسخی ثبت نشده است...
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Assignee Files --}}
                    @if(!empty($selectedTicket['assignee_files']))
                        <div class="mt-6 bg-[var(--md-sys-color-surface-container-low)] p-6 rounded-3xl border border-[var(--md-sys-color-outline-variant)]/30">
                            <h5 class="text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-4 flex items-center gap-2 border-b border-[var(--md-sys-color-outline-variant)]/40 pb-3">
                                <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-secondary)]">attachment</span>
                                فایل‌های ضمیمه کارشناس
                            </h5>
                            <div class="flex flex-wrap gap-4">
                                @foreach($selectedTicket['assignee_files'] as $file)
                                    <a href="{{ asset($file['file']) }}" target="_blank" class="group flex flex-col items-center justify-center w-24 h-24 rounded-2xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] hover:border-[var(--md-sys-color-secondary)] transition-all overflow-hidden relative shadow-sm hover:shadow-md hover:-translate-y-1">
                                        @if(Str::contains($file['file'], ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                            <img src="{{ asset($file['file']) }}" alt="Attachment" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                                        @else
                                            <span class="material-symbols-rounded text-4xl text-[var(--md-sys-color-secondary)]/40 group-hover:text-[var(--md-sys-color-secondary)] transition-colors">description</span>
                                        @endif
                                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center backdrop-blur-sm">
                                            <span class="material-symbols-rounded text-white text-[28px] transform scale-50 group-hover:scale-100 transition-transform duration-300">file_download</span>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Rating Display (Read-Only) --}}
                    @if($selectedTicket['satisfaction_score'] > 0)
                        <div class="mt-8 bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/40 p-6 rounded-3xl shadow-sm relative overflow-hidden">
                            <div class="absolute -right-10 -top-10 w-32 h-32 bg-[#FFD700] opacity-5 rounded-full blur-3xl"></div>
                            <h5 class="text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-4 text-center">ارزیابی ثبت شده شما</h5>
                            <div class="flex justify-center gap-2 ltr-direction flex-row-reverse mb-5">
                                @for($i = 5; $i >= 1; $i--)
                                    <span class="material-symbols-rounded text-4xl {{ $selectedTicket['satisfaction_score'] >= $i ? 'text-[#FFD700] font-variation-fill drop-shadow-md' : 'text-[var(--md-sys-color-outline-variant)]/40' }} transition-all">star</span>
                                @endfor
                            </div>
                            @if(isset($selectedTicket['extra']['satisfaction_comment']) && $selectedTicket['extra']['satisfaction_comment'])
                                <div class="bg-[var(--md-sys-color-surface-container-low)] rounded-2xl p-5 text-sm text-[var(--md-sys-color-on-surface-variant)] italic text-center mx-auto max-w-xl shadow-inner border border-[var(--md-sys-color-outline-variant)]/30 relative">
                                    <span class="material-symbols-rounded text-3xl text-[var(--md-sys-color-outline-variant)]/20 absolute -top-2 right-4 rotate-180">format_quote</span>
                                    <span class="relative z-10 leading-relaxed block px-4">
                                        {{ $selectedTicket['extra']['satisfaction_comment'] }}
                                    </span>
                                    <span class="material-symbols-rounded text-3xl text-[var(--md-sys-color-outline-variant)]/20 absolute -bottom-2 left-4">format_quote</span>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @endif
</x-dashboard.modal.slideover>
