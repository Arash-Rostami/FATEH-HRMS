@if($selectedTicket)
<div x-data="{
        docId: '',
    }"
    @ths-modal.window="
        docId = $event.detail.id;
    "
    @ths-modal-closed.window="
        setTimeout(() => $wire.set('selectedTicket', null), 300);
    "
>
    <x-dashboard.modal.general
        eventName="ths-modal"
        maxWidth="4xl"
        icon="confirmation_number"
        title="{{ $this->getFormattedTicketId($selectedTicket) }}"
        subtext="{{ $this->getFormattedTimeStamp($selectedTicket, 'created_at') }}"
    >
        {{-- Inner Tabs - Inspired by Profile Index Design --}}
        <div class="flex border-b border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface)] sticky top-0 z-20 px-2 sm:px-4 pt-2 gap-2" role="tablist">
            <button wire:click="$set('modalTab', 'request')"
                    class="group relative flex items-center justify-center gap-2 px-4 py-3 text-sm font-semibold transition-all duration-200 outline-none rounded-t-xl
                    {{ $modalTab === 'request'
                        ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_-4px_12px_rgba(0,0,0,0.1)]'
                        : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] hover:text-[var(--md-sys-color-on-surface)]' }}"
                    >
                <span class="material-symbols-rounded text-[18px] {{ $modalTab === 'request' ? '' : 'opacity-70 group-hover:opacity-100' }}">description</span>
                جزئیات درخواست
            </button>
            <button wire:click="$set('modalTab', 'response')"
                    class="group relative flex items-center justify-center gap-2 px-4 py-3 text-sm font-semibold transition-all duration-200 outline-none rounded-t-xl
                    {{ $modalTab === 'response'
                        ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_-4px_12px_rgba(0,0,0,0.1)]'
                        : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] hover:text-[var(--md-sys-color-on-surface)]' }}"
                    >
                <span class="material-symbols-rounded text-[18px] {{ $modalTab === 'response' ? '' : 'opacity-70 group-hover:opacity-100' }}">forum</span>
                پاسخ و پیگیری
            </button>
        </div>

        {{-- Scrollable Body --}}
        <div class="p-6">
            @if($modalTab === 'request')
                <div class="space-y-6 animate-[fade-in-up_0.3s_ease-out]">
                    {{-- Status Badges Grid - Redesigned like Profile Metrics --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                        <div class="bg-[var(--md-sys-color-surface-container-low)]/50 p-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30 flex flex-col items-center justify-center text-center shadow-sm relative overflow-hidden group">
                            <div class="absolute inset-0 bg-gradient-to-br from-[var(--md-sys-color-primary-container)]/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-wider font-bold mb-1">حوزه</span>
                            <span class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">{{ $this->getRequestAreaLabel($selectedTicket['request_type'], $selectedTicket['request_area']) }}</span>
                        </div>

                        <div class="bg-[var(--md-sys-color-surface-container-low)]/50 p-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30 flex flex-col items-center justify-center text-center shadow-sm relative overflow-hidden group">
                            <div class="absolute inset-0 bg-gradient-to-br from-[var(--md-sys-color-primary-container)]/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-wider font-bold mb-1">وضعیت</span>
                            @php
                                $s = $selectedTicket['status'];
                                $col = $s==='open' ? 'text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-primary-container)]/30' : ($s==='in-progress'?'text-[var(--md-sys-color-tertiary)] bg-[var(--md-sys-color-tertiary-container)]/30':'text-[var(--md-sys-color-secondary)] bg-[var(--md-sys-color-secondary-container)]/30');
                                $lbl = $s==='open' ? 'باز' : ($s==='in-progress'?'در حال بررسی':'بسته شده');
                            @endphp
                            <span class="text-xs font-bold px-3 py-1 rounded-full {{ $col }}">{{ $lbl }}</span>
                        </div>

                        <div class="bg-[var(--md-sys-color-surface-container-low)]/50 p-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30 flex flex-col items-center justify-center text-center shadow-sm relative overflow-hidden group">
                            <div class="absolute inset-0 bg-gradient-to-br from-[var(--md-sys-color-primary-container)]/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-wider font-bold mb-1">اولویت</span>
                            @php
                                $p = $selectedTicket['priority'];
                                $pCol = $p==='low'?'text-[var(--md-sys-color-primary)]':($p==='medium'?'text-[var(--md-sys-color-secondary)]':'text-[var(--md-sys-color-error)]');
                                $pLbl = $p==='low'?'کم':($p==='medium'?'متوسط':'زیاد');
                            @endphp
                            <span class="text-sm font-bold {{ $pCol }}">{{ $pLbl }}</span>
                        </div>

                        <div class="bg-[var(--md-sys-color-surface-container-low)]/50 p-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30 flex flex-col items-center justify-center text-center shadow-sm relative overflow-hidden group">
                            <div class="absolute inset-0 bg-gradient-to-br from-[var(--md-sys-color-primary-container)]/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-wider font-bold mb-1">تاریخ ایجاد</span>
                            <span class="text-sm font-medium text-[var(--md-sys-color-on-surface)] font-mono rtl:-scale-x-100 flex-row-reverse flex" dir="ltr">{{ \Carbon\Carbon::parse($selectedTicket['created_at'])->format('Y-m-d') }}</span>
                        </div>
                    </div>

                    {{-- Subject & Desc --}}
                    <div class="bg-[var(--md-sys-color-surface-container-lowest)] rounded-2xl p-6 border border-[var(--md-sys-color-outline-variant)]/50 shadow-sm relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-2 h-full bg-[var(--md-sys-color-primary)]"></div>
                        <h4 class="text-[var(--md-sys-color-on-surface)] font-bold text-lg mb-4 flex items-center gap-2">
                            <span class="material-symbols-rounded text-2xl text-[var(--md-sys-color-primary)]">subject</span>
                            {{ $selectedTicket['request_subject'] }}
                        </h4>
                        <div class="text-sm text-[var(--md-sys-color-on-surface-variant)] leading-relaxed text-justify whitespace-pre-wrap pl-2 border-r-2 border-[var(--md-sys-color-outline-variant)]/30 pr-4 mt-2">
                            {{ $selectedTicket['description'] }}
                        </div>
                    </div>

                    {{-- Requester Files --}}
                    @if(!empty($selectedTicket['requester_files']))
                        <div class="mt-6">
                            <h5 class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)] mb-4 flex items-center gap-1.5 uppercase tracking-wider">
                                <span class="material-symbols-rounded text-[18px]">attachment</span>
                                فایل‌های ضمیمه شما
                            </h5>
                            <div class="flex flex-wrap gap-4">
                                @foreach($selectedTicket['requester_files'] as $file)
                                    <a href="{{ asset($file['file']) }}" target="_blank" class="group flex flex-col items-center justify-center w-24 h-24 rounded-2xl bg-[var(--md-sys-color-surface-container)] border border-[var(--md-sys-color-outline-variant)] hover:border-[var(--md-sys-color-primary)] transition-colors overflow-hidden relative shadow-sm hover:shadow-md">
                                        @if(Str::contains($file['file'], ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                            <img src="{{ asset($file['file']) }}" alt="Attachment" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                        @else
                                            <span class="material-symbols-rounded text-4xl text-[var(--md-sys-color-on-surface-variant)] group-hover:text-[var(--md-sys-color-primary)] transition-colors">description</span>
                                        @endif
                                        <div class="absolute inset-0 bg-[var(--md-sys-color-scrim)]/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center backdrop-blur-[2px]">
                                            <span class="material-symbols-rounded text-[var(--md-sys-color-surface)] text-[24px]">open_in_new</span>
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
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 bg-[var(--md-sys-color-surface-container-low)]/50 p-5 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30 shadow-sm relative overflow-hidden">
                         <div class="absolute inset-0 bg-gradient-to-l from-[var(--md-sys-color-secondary-container)]/10 to-transparent pointer-events-none"></div>
                        <div class="w-14 h-14 rounded-full bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] flex items-center justify-center font-bold text-xl shadow-inner border border-[var(--md-sys-color-secondary)]/20 z-10">
                            {{ isset($selectedTicket['assignee']) ? mb_substr($selectedTicket['assignee']['name'], 0, 1) : '؟' }}
                        </div>
                        <div class="z-10">
                            <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] font-bold tracking-wider uppercase mb-1">مسئول رسیدگی</p>
                            <p class="text-base font-bold text-[var(--md-sys-color-on-surface)]">
                                {{ $selectedTicket['assignee']['name'] ?? 'در انتظار تخصیص به کارشناس' }}
                            </p>
                        </div>
                        @if($selectedTicket['completion_date'])
                            <div class="mr-auto text-left sm:border-r border-[var(--md-sys-color-outline-variant)]/50 sm:pr-6 z-10 w-full sm:w-auto mt-4 sm:mt-0">
                                <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] font-bold tracking-wider uppercase text-right sm:text-left mb-1">تاریخ تکمیل</p>
                                <p class="text-sm font-medium text-[var(--md-sys-color-on-surface)] flex items-center gap-1 justify-end sm:justify-start" dir="ltr">
                                    <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">event_available</span>
                                    {{ $this->getFormattedTimeStamp($selectedTicket, 'completion_date') }}
                                </p>
                            </div>
                        @endif
                    </div>

                    {{-- Action Result --}}
                    <div class="bg-[var(--md-sys-color-surface-container-lowest)] rounded-2xl p-6 border border-[var(--md-sys-color-outline-variant)]/50 shadow-sm relative overflow-hidden min-h-[140px]">
                        <div class="absolute top-0 right-0 w-2 h-full bg-[var(--md-sys-color-secondary)]"></div>
                        <h4 class="text-[var(--md-sys-color-on-surface)] font-bold text-lg mb-4 flex items-center gap-2">
                            <span class="material-symbols-rounded text-2xl text-[var(--md-sys-color-secondary)]">quick_reference_all</span>
                            نتیجه اقدامات
                        </h4>
                        <div class="text-sm text-[var(--md-sys-color-on-surface-variant)] leading-relaxed text-justify whitespace-pre-wrap pl-2 border-r-2 border-[var(--md-sys-color-outline-variant)]/30 pr-4 mt-2">
                            @if($selectedTicket['action_result'])
                                {{ $selectedTicket['action_result'] }}
                            @else
                                <span class="italic opacity-70">هنوز پاسخی ثبت نشده است...</span>
                            @endif
                        </div>
                    </div>

                    {{-- Assignee Files --}}
                    @if(!empty($selectedTicket['assignee_files']))
                        <div class="mt-6">
                            <h5 class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)] mb-4 flex items-center gap-1.5 uppercase tracking-wider">
                                <span class="material-symbols-rounded text-[18px]">attachment</span>
                                فایل‌های ضمیمه کارشناس
                            </h5>
                            <div class="flex flex-wrap gap-4">
                                @foreach($selectedTicket['assignee_files'] as $file)
                                    <a href="{{ asset($file['file']) }}" target="_blank" class="group flex flex-col items-center justify-center w-24 h-24 rounded-2xl bg-[var(--md-sys-color-surface-container)] border border-[var(--md-sys-color-outline-variant)] hover:border-[var(--md-sys-color-primary)] transition-colors overflow-hidden relative shadow-sm hover:shadow-md">
                                        @if(Str::contains($file['file'], ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                            <img src="{{ asset($file['file']) }}" alt="Attachment" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                        @else
                                            <span class="material-symbols-rounded text-4xl text-[var(--md-sys-color-on-surface-variant)] group-hover:text-[var(--md-sys-color-primary)] transition-colors">description</span>
                                        @endif
                                        <div class="absolute inset-0 bg-[var(--md-sys-color-scrim)]/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center backdrop-blur-[2px]">
                                            <span class="material-symbols-rounded text-[var(--md-sys-color-surface)] text-[24px]">file_download</span>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Rating Display (Read-Only) --}}
                    @if($selectedTicket['satisfaction_score'] > 0)
                        <div class="mt-8 bg-[var(--md-sys-color-surface-container-low)] rounded-2xl p-6 border border-[var(--md-sys-color-outline-variant)]/30">
                            <h5 class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)] mb-4 text-center uppercase tracking-wider">ارزیابی ثبت شده شما</h5>
                            <div class="flex justify-center gap-2 ltr-direction flex-row-reverse mb-4">
                                @for($i = 5; $i >= 1; $i--)
                                    <span class="material-symbols-rounded text-4xl {{ $selectedTicket['satisfaction_score'] >= $i ? 'text-[#FFD700] font-variation-fill drop-shadow-md' : 'text-[var(--md-sys-color-outline-variant)] opacity-50' }}">star</span>
                                @endfor
                            </div>
                            @if(isset($selectedTicket['extra']['satisfaction_comment']) && $selectedTicket['extra']['satisfaction_comment'])
                                <div class="bg-[var(--md-sys-color-surface-container)]/80 rounded-xl p-5 text-sm text-[var(--md-sys-color-on-surface-variant)] italic text-center mx-auto max-w-lg shadow-inner border border-[var(--md-sys-color-outline-variant)]/20 leading-relaxed">
                                    "{{ $selectedTicket['extra']['satisfaction_comment'] }}"
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </x-dashboard.modal.general>
</div>
@endif
