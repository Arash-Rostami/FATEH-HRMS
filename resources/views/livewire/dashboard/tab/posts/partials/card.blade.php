                    <article
                        class="group relative flex flex-col bg-[var(--md-sys-color-surface-container-low)] rounded-[24px] overflow-hidden border border-[var(--md-sys-color-outline-variant)]/30 transition-all duration-300 hover:bg-[var(--md-sys-color-surface-container)] hover:shadow-lg hover:-translate-y-1"
                        wire:key="post-{{ $post->id }}"
                    >
                        {{-- Image --}}
                        <div class="relative h-48 overflow-hidden cursor-pointer"
                             wire:click="selectPost({{ $post->id }})">
                            <img
                                src="{{ $post->image }}"
                                alt="{{ superClean($post->title, 200) }}"
                                loading="lazy"
                                class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                            >
                            <div
                                class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors duration-300"></div>
                        </div>

                        {{-- Body --}}
                        <div class="p-5 flex flex-col flex-grow">
                            <div class="flex items-center justify-between mb-3">
                                    <span
                                        class="text-[10px] font-bold px-2 py-1 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] tracking-wide">
                                        اخبار
                                    </span>
                                <span class="text-[11px] text-[var(--md-sys-color-outline)] font-mono dir-ltr">
                                        {{ $post->created_at->diffForHumans() }}
                                    </span>
                            </div>

                            <h4
                                class="text-lg font-bold text-[var(--md-sys-color-on-surface)] mb-2 line-clamp-1 cursor-pointer hover:text-[var(--md-sys-color-primary)] transition-colors"
                                wire:click="selectPost({{ $post->id }})"
                            >
                                {{ superClean($post->title, 100) }}
                            </h4>

                            <p class="text-sm text-[var(--md-sys-color-on-surface-variant)] line-clamp-3 mb-5 flex-grow leading-relaxed">
                                {{ superClean($post->body, 100) }}
                            </p>

                            {{-- Actions --}}
                            <div
                                class="pt-4 mt-auto border-t border-[var(--md-sys-color-outline-variant)]/20 flex items-center justify-between">
                                <button
                                    @click="$dispatch('select-post', { id: {{ $post->id }} })"
                                    class="text-xs font-bold text-[var(--md-sys-color-primary)] flex items-center gap-1.5 hover:gap-2.5 transition-all bg-[var(--md-sys-color-surface)]/50 hover:bg-[var(--md-sys-color-secondary-container)]/30 px-3 py-1.5 rounded-full"
                                >
                                    <span>ادامه مطلب</span>
                                    <span class="material-symbols-rounded text-[16px] flip-rtl">arrow_left_alt</span>
                                </button>
                            </div>
                        </div>
                    </article>
