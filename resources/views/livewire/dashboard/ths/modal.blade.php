@if($selectedTicket)
    <template x-teleport="body">
        <div x-data="{
                max: false,
                isClosing: false,
                closeModal() {
                    this.isClosing = true;
                    setTimeout(() => $wire.set('selectedTicket', null), 300);
                }
             }"
             @keydown.window.escape="closeModal()"
             x-cloak>

            <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6"
                 x-show="!isClosing"
                 dir="rtl">

                <div class="absolute inset-0 bg-[var(--md-sys-color-primary)]/60 transition-opacity duration-300"
                     x-show="!isClosing"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     @click="closeModal()"></div>

                <div class="relative w-full bg-[var(--md-sys-color-on-primary)] shadow-2xl overflow-hidden flex flex-col transition-all duration-300 ring-1 ring-[var(--md-sys-color-outline-variant)]/20"
                     :class="max ? 'h-full max-w-none max-h-none rounded-none' : 'max-w-4xl max-h-[90vh] rounded-3xl'"
                     x-show="!isClosing"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     role="dialog"
                     aria-modal="true">

                    <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container-low)] shrink-0">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center shadow-sm">
                                <span class="material-symbols-rounded text-xl">confirmation_number</span>
                            </div>
                            <h3 title="📆 {{ $presenter->formatTimestamp($selectedTicket, 'created_at') }}"
                                dir="ltr"
                                class="cursor-help font-bold text-[var(--md-sys-color-on-surface)] text-sm tracking-wide font-mono flex items-center gap-2 flex-row-reverse">
                                {{ $presenter->formatId($selectedTicket)}}
                            </h3>
                            <button type="button"
                                    @click="copyText('{{ $presenter->formatId($selectedTicket) }}', 'شناسهٔ تیکت کپی شد.')"
                                    title="کپی شناسه"
                                    class="w-6 h-6 flex items-center justify-center rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)] hover:text-[var(--md-sys-color-primary)] transition-colors">
                                <span class="material-symbols-rounded text-[14px]">content_copy</span>
                            </button>
                        </div>

                        <div class="flex items-center gap-2">
                            <button @click="max = !max"
                                    :title="max ? 'کوچک کردن' : 'بزرگ کردن'"
                                    :class="{ 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]': max, 'text-[var(--md-sys-color-on-surface-variant)]': !max }"
                                    class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-[var(--md-sys-color-surface-container-highest)] hover:text-[var(--md-sys-color-on-surface)] transition-all focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)]">
                                <span class="material-symbols-rounded text-[20px]" x-text="max ? 'close_fullscreen' : 'open_in_full'"></span>
                            </button>
                            <button @click="closeModal()"
                                    title="بستن"
                                    class="w-10 h-10 flex items-center justify-center rounded-xl text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-error-container)] hover:text-[var(--md-sys-color-on-error-container)] transition-all focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-error)]">
                                <span class="material-symbols-rounded text-[20px]">close</span>
                            </button>
                        </div>
                    </div>

                    <div class="px-6 py-3 border-b border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface-container-lowest)] shrink-0 flex justify-center md:justify-start">
                        <div class="w-full md:w-2/3 lg:w-1/2">
                            <x-ui.buttons.tab-selector
                                :activeTab="$modalTab"
                                action="$set('modalTab', "
                                class="!w-full mb-0 shadow-sm"
                                buttonBaseClass="flex-1 relative px-4 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 z-10 flex items-center justify-center gap-2"
                                :tabs="[
                                    ['id' => 'request','label' => 'جزئیات ','icon' => 'description'],
                                    ['id' => 'response', 'label' => 'پاسخ و پیگیری','icon' => 'forum']
                                ]"
                            />
                        </div>
                    </div>

                    <div class="p-6 overflow-y-auto overscroll-contain custom-scrollbar flex-1 bg-[var(--md-sys-color-surface-container-lowest)]">
                        @if($modalTab === 'request')
                            <div class="space-y-6"
                                 x-data
                                 x-show="true"
                                 x-transition:enter="transition ease-out duration-300 delay-100"
                                 x-transition:enter-start="opacity-0 translate-y-2"
                                 x-transition:enter-end="opacity-100 translate-y-0">

                                @php
                                    $sm = $presenter->statusMeta($selectedTicket['status']);
                                    $pm = $presenter->priorityMeta($selectedTicket['priority']);
                                @endphp
                                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                                    <div class="rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] p-4 flex items-center gap-3 shadow-sm hover:shadow-md transition-shadow">
                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">
                                            <span class="material-symbols-rounded text-[18px]">{{ $presenter->requestAreaIcon($selectedTicket['request_area']) }}</span>
                                        </div>
                                        <div class="min-w-0 flex flex-col gap-1">
                                            <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-wider font-bold">حوزه</span>
                                            <span class="text-[13px] font-bold text-[var(--md-sys-color-on-surface)] truncate">{{ $presenter->requestAreaLabel($selectedTicket['request_type'], $selectedTicket['request_area']) }}</span>
                                        </div>
                                    </div>

                                    <div class="rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] p-4 flex items-center gap-3 shadow-sm hover:shadow-md transition-shadow">
                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 {{ $sm['bg'] ?? 'bg-[var(--md-sys-color-surface-container-highest)]' }} {{ $sm['textColor'] ?? '' }}">
                                            <span class="material-symbols-rounded text-[18px]">{{ $sm['icon'] ?? 'help' }}</span>
                                        </div>
                                        <div class="min-w-0 flex flex-col gap-1">
                                            <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-wider font-bold">وضعیت</span>
                                            <span class="text-[13px] font-bold {{ $sm['textColor'] ?? 'text-[var(--md-sys-color-on-surface)]' }} truncate">{{ $sm['title'] ?? '—' }}</span>
                                        </div>
                                    </div>

                                    <div class="rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] p-4 flex items-center gap-3 shadow-sm hover:shadow-md transition-shadow">
                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 {{ $pm['bg'] ?? 'bg-[var(--md-sys-color-surface-container-highest)]' }}">
                                            <span class="material-symbols-rounded text-[18px]">{{ $pm['icon'] ?? 'flag' }}</span>
                                        </div>
                                        <div class="min-w-0 flex flex-col gap-1">
                                            <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-wider font-bold">اولویت</span>
                                            <span class="text-[13px] font-bold {{ $pm['color'] ?? 'text-[var(--md-sys-color-on-surface)]' }} truncate">{{ $pm['title'] ?? '—' }}</span>
                                        </div>
                                    </div>

                                    <div class="rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] p-4 flex items-center gap-3 shadow-sm hover:shadow-md transition-shadow">
                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)]">
                                            <span class="material-symbols-rounded text-[18px]">calendar_month</span>
                                        </div>
                                        <div class="min-w-0 flex flex-col gap-1">
                                            <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-wider font-bold">تاریخ ایجاد</span>
                                            <span dir="ltr" class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] flex-row-reverse flex justify-end">{{ toJalali($selectedTicket['created_at'], 'j F Y') }}</span>
                                        </div>
                                    </div>
                                </div>

                                @php $steps = $presenter->statusSteps($selectedTicket['status']); @endphp
                                <div class="rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] shadow-sm p-6 mb-6">
                                    <div class="flex items-start justify-between">
                                        @foreach($steps as $i => $step)
                                            <div class="flex flex-col items-center gap-2 {{ $i === 0 || $i === count($steps) - 1 ? '' : 'flex-1' }}">
                                                <div class="flex items-center w-full">
                                                    @if($i > 0)
                                                        <div class="flex-1 h-[3px] rounded-full {{ $step['state'] !== 'upcoming' ? 'bg-[var(--md-sys-color-primary)]' : 'bg-[var(--md-sys-color-outline-variant)]/50' }}"></div>
                                                    @endif
                                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 shadow-sm {{ match($step['state']) {
                                                        'done' => 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]',
                                                        'active' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-tertiary)] ring-4 ring-[var(--md-sys-color-tertiary)]/20',
                                                        default => 'bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] opacity-70',
                                                    } }}">
                                                        <span class="material-symbols-rounded text-[18px]">{{ $step['icon'] }}</span>
                                                    </div>
                                                    @if($i < count($steps) - 1)
                                                        <div class="flex-1 h-[3px] rounded-full {{ $steps[$i + 1]['state'] !== 'upcoming' ? 'bg-[var(--md-sys-color-primary)]' : 'bg-[var(--md-sys-color-outline-variant)]/50' }}"></div>
                                                    @endif
                                                </div>
                                                <span class="text-[11px] font-bold whitespace-nowrap {{ $step['state'] === 'upcoming' ? 'text-[var(--md-sys-color-on-surface-variant)] opacity-70' : 'text-[var(--md-sys-color-on-surface)]' }}">{{ $step['label'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="bg-[var(--md-sys-color-surface-container-low)] rounded-2xl p-6 border border-[var(--md-sys-color-outline-variant)]/40 shadow-sm relative overflow-hidden">
                                    <div class="absolute top-0 right-0 w-1.5 h-full bg-[var(--md-sys-color-primary)]"></div>
                                    <h4 class="text-[var(--md-sys-color-on-surface)] text-base font-bold mb-4 flex items-center gap-2">
                                        <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)]">subject</span>
                                        {{ $selectedTicket['request_subject'] }}
                                    </h4>
                                    <div class="text-sm text-[var(--md-sys-color-on-surface-variant)] leading-relaxed text-justify whitespace-pre-wrap pl-2 border-r-2 border-[var(--md-sys-color-outline-variant)]/30 pr-4">
                                        {{ $selectedTicket['description'] }}
                                    </div>
                                </div>

                                @if(!empty($selectedTicket['requester_files']))
                                    <div class="mt-6">
                                        <h5 class="text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-4 flex items-center gap-2">
                                            <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">attachment</span>
                                            فایل‌های ضمیمه شما
                                        </h5>
                                        <div class="flex flex-wrap gap-4">
                                            @foreach($selectedTicket['requester_files'] as $file)
                                                @if(Str::contains($file['file'], ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                                    <a href="{{ $file['file_url'] }}"
                                                       data-fancybox="ticket-{{ $selectedTicket['id'] }}-requester-files"
                                                       class="group flex flex-col items-center justify-center w-24 h-24 rounded-2xl bg-[var(--md-sys-color-surface-container)] border border-[var(--md-sys-color-outline-variant)]/50 hover:border-[var(--md-sys-color-primary)] transition-all overflow-hidden relative shadow-sm hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)]">
                                                        <img src="{{ $file['file_url'] }}"
                                                             alt="Attachment"
                                                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center backdrop-blur-[2px]">
                                                            <span class="material-symbols-rounded text-white text-[24px]">zoom_in</span>
                                                        </div>
                                                    </a>
                                                @else
                                                    <a href="{{ $file['file_url'] }}"
                                                       target="_blank"
                                                       class="group flex flex-col items-center justify-center w-24 h-24 rounded-2xl bg-[var(--md-sys-color-surface-container)] border border-[var(--md-sys-color-outline-variant)]/50 hover:border-[var(--md-sys-color-primary)] transition-all overflow-hidden relative shadow-sm hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)]">
                                                        <span class="material-symbols-rounded text-4xl text-[var(--md-sys-color-on-surface-variant)] group-hover:text-[var(--md-sys-color-primary)] transition-colors duration-300">description</span>
                                                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center backdrop-blur-[2px]">
                                                            <span class="material-symbols-rounded text-white text-[24px]">open_in_new</span>
                                                        </div>
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if($modalTab === 'response')
                            <div class="space-y-6"
                                 x-data
                                 x-show="true"
                                 x-transition:enter="transition ease-out duration-300 delay-100"
                                 x-transition:enter-start="opacity-0 translate-y-2"
                                 x-transition:enter-end="opacity-100 translate-y-0">

                                @if($selectedTicket['completion_date'])
                                    <div class="flex justify-end text-left border-b border-[var(--md-sys-color-outline-variant)]/40 pb-4 mb-2">
                                        <div class="bg-[var(--md-sys-color-surface-container-low)] px-4 py-2 rounded-xl border border-[var(--md-sys-color-outline-variant)]/30 shadow-sm">
                                            <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] font-bold tracking-wider uppercase text-right">تاریخ تکمیل</p>
                                            <p class="text-sm font-bold text-[var(--md-sys-color-on-surface)] flex items-center gap-1.5 mt-1 justify-end" dir="ltr">
                                                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">event_available</span>
                                                {{ toJalali($selectedTicket['completion_date'], 'j F Y') }}
                                            </p>
                                        </div>
                                    </div>
                                @endif

                                <livewire:dashboard.ths.workspace :ticket-id="$selectedTicket['id']" :key="'ticket-workspace-'.$selectedTicket['id']" />

                                @if($selectedTicket['satisfaction_score'] > 0)
                                    <div class="mt-8 border-t border-[var(--md-sys-color-outline-variant)]/40 pt-8">
                                        <div class="bg-[var(--md-sys-color-surface-container-low)] rounded-2xl p-6 border border-[var(--md-sys-color-outline-variant)]/30 shadow-sm max-w-2xl mx-auto">
                                            <h5 class="text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-4 text-center">ارزیابی ثبت شده شما</h5>

                                            <div class="flex justify-center gap-2 ltr-direction flex-row-reverse mb-4">
                                                @for($i = 5; $i >= 1; $i--)
                                                    <span class="material-symbols-rounded text-4xl transition-transform hover:scale-110 {{ $selectedTicket['satisfaction_score'] >= $i ? 'text-[#FFB300] font-variation-fill drop-shadow-md' : 'text-[var(--md-sys-color-outline-variant)]/40' }}">star</span>
                                                @endfor
                                            </div>

                                            @if(isset($selectedTicket['extra']['satisfaction_comment']) && $selectedTicket['extra']['satisfaction_comment'])
                                                <div class="bg-[var(--md-sys-color-surface-container-highest)] rounded-xl p-4 text-sm text-[var(--md-sys-color-on-surface)] italic text-center shadow-inner relative mt-4">
                                                    <span class="material-symbols-rounded absolute top-2 right-2 text-[var(--md-sys-color-on-surface-variant)] opacity-20 text-3xl">format_quote</span>
                                                    <p class="relative z-10 leading-relaxed">{{ $selectedTicket['extra']['satisfaction_comment'] }}</p>
                                                </div>
                                            @endif
                                        </div>
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
