<div class="flex h-screen w-full overflow-hidden bg-[var(--md-sys-color-background)] transition-colors duration-200"
     wire:loading.flex>

    <!-- Sidebar – standard enterprise navigation (collapsible on mobile) -->
    <aside class="hidden w-72 flex-col border-r border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] p-6 md:flex">

        <!-- Logo / Brand -->
        <div class="mb-10 flex items-center gap-3">
            <x-ui.loaders.skeleton.bar width="w-10" height="h-10" class="!rounded-2xl !bg-[var(--md-sys-color-primary-container)]"/>
            <x-ui.loaders.skeleton.bar width="w-36" height="h-6" class="!rounded !bg-[var(--md-sys-color-on-surface-variant)] opacity-20"/>
        </div>

        <!-- Navigation -->
        <div class="flex flex-1 flex-col gap-2">

            <!-- Active item -->
            <div class="flex items-center gap-4 rounded-2xl bg-[var(--md-sys-color-surface-variant)] p-4">
                <div class="h-5 w-5 rounded bg-[var(--md-sys-color-on-surface-variant)]"></div>
                <div class="h-4 w-28 rounded bg-[var(--md-sys-color-on-surface-variant)]"></div>
            </div>

            <!-- Other nav items (generic lengths for any enterprise app) -->
            <div class="space-y-6 pt-4">
                <div class="flex items-center gap-4 px-4 opacity-30">
                    <div class="h-5 w-5 rounded bg-[var(--md-sys-color-on-surface-variant)]"></div>
                    <div class="h-4 w-32 rounded bg-[var(--md-sys-color-on-surface-variant)]"></div>
                </div>
                <div class="flex items-center gap-4 px-4 opacity-30">
                    <div class="h-5 w-5 rounded bg-[var(--md-sys-color-on-surface-variant)]"></div>
                    <div class="h-4 w-24 rounded bg-[var(--md-sys-color-on-surface-variant)]"></div>
                </div>
                <div class="flex items-center gap-4 px-4 opacity-30">
                    <div class="h-5 w-5 rounded bg-[var(--md-sys-color-on-surface-variant)]"></div>
                    <div class="h-4 w-36 rounded bg-[var(--md-sys-color-on-surface-variant)]"></div>
                </div>
                <div class="flex items-center gap-4 px-4 opacity-30">
                    <div class="h-5 w-5 rounded bg-[var(--md-sys-color-on-surface-variant)]"></div>
                    <div class="h-4 w-20 rounded bg-[var(--md-sys-color-on-surface-variant)]"></div>
                </div>
                <div class="flex items-center gap-4 px-4 opacity-30">
                    <div class="h-5 w-5 rounded bg-[var(--md-sys-color-on-surface-variant)]"></div>
                    <div class="h-4 w-28 rounded bg-[var(--md-sys-color-on-surface-variant)]"></div>
                </div>
            </div>
        </div>

        <!-- Bottom user profile -->
        <div class="mt-auto border-t border-[var(--md-sys-color-outline-variant)] pt-6">
            <div class="flex items-center gap-3">
                <x-ui.loaders.skeleton.bar width="w-10" height="h-10" class="!rounded-2xl"/>
                <div class="flex-1 space-y-2">
                    <x-ui.loaders.skeleton.bar width="w-28" height="h-3" class="!rounded !bg-[var(--md-sys-color-on-surface-variant)] opacity-40"/>
                    <x-ui.loaders.skeleton.bar width="w-20" height="h-2" class="!rounded !bg-[var(--md-sys-color-on-surface-variant)] opacity-30"/>
                </div>
            </div>
        </div>
    </aside>

    <!-- Tabs content area -->
    <main class="relative flex flex-1 flex-col overflow-hidden">

        <!-- Top header – industry standard with mobile toggle + title + actions -->
        <header class="sticky top-0 z-10 flex h-16 w-full items-center justify-between border-b border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)]/80 px-6 md:px-8">

            <div class="flex items-center gap-4">
                <!-- Mobile sidebar toggle -->
                <div class="flex h-9 w-9 items-center justify-center rounded-2xl bg-[var(--md-sys-color-surface-variant)] md:hidden">
                    <x-ui.loaders.skeleton.bar width="w-5" height="h-5" class="!rounded !bg-[var(--md-sys-color-on-surface-variant)]"/>
                </div>

                <!-- Page title / breadcrumb area -->
                <div class="flex flex-col gap-1">
                    <x-ui.loaders.skeleton.bar width="w-52" height="h-6" class="!rounded !bg-[var(--md-sys-color-on-surface)] opacity-10"/>
                    <x-ui.loaders.skeleton.bar width="w-36" height="h-3" class="!rounded !bg-[var(--md-sys-color-on-surface)] opacity-10"/>
                </div>
            </div>

            <!-- Right side actions (search + icons) -->
            <div class="flex items-center gap-3">
                <!-- Search bar – common in enterprise apps -->
                <div class="hidden h-10 w-72 items-center gap-3 rounded-3xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] px-4 md:flex">
                    <x-ui.loaders.skeleton.bar width="w-4" height="h-4" class="!rounded !bg-[var(--md-sys-color-on-surface-variant)]"/>
                    <x-ui.loaders.skeleton.bar width="flex-1" height="h-3.5" class="!rounded !bg-[var(--md-sys-color-on-surface-variant)] opacity-20"/>
                </div>

                <!-- Notification icon -->
                <x-ui.loaders.skeleton.bar width="w-10" height="h-10" class="!rounded-2xl"/>

                <!-- User avatar -->
                <x-ui.loaders.skeleton.bar width="w-10" height="h-10" class="!rounded-2xl"/>
            </div>
        </header>

        <!-- Scrollable content area -->
        <div class="flex-1 overflow-y-auto p-6 md:p-8">

            <!-- KPI / Metric cards – 4-column responsive grid (standard for any enterprise dashboard) -->
            <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-3xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] p-6 shadow-sm">
                    <x-ui.loaders.skeleton.bar width="w-10" height="h-10" class="mb-4 !rounded-2xl !bg-[var(--md-sys-color-tertiary-container)]"/>
                    <x-ui.loaders.skeleton.bar width="w-20" height="h-8" class="mb-2 !rounded !bg-[var(--md-sys-color-on-surface)] opacity-20"/>
                    <x-ui.loaders.skeleton.bar width="w-28" height="h-4" class="!rounded !bg-[var(--md-sys-color-on-surface-variant)] opacity-30"/>
                </div>
                <div class="rounded-3xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] p-6 shadow-sm">
                    <x-ui.loaders.skeleton.bar width="w-10" height="h-10" class="mb-4 !rounded-2xl !bg-[var(--md-sys-color-secondary-container)]"/>
                    <x-ui.loaders.skeleton.bar width="w-20" height="h-8" class="mb-2 !rounded !bg-[var(--md-sys-color-on-surface)] opacity-20"/>
                    <x-ui.loaders.skeleton.bar width="w-28" height="h-4" class="!rounded !bg-[var(--md-sys-color-on-surface-variant)] opacity-30"/>
                </div>
                <div class="rounded-3xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] p-6 shadow-sm">
                    <x-ui.loaders.skeleton.bar width="w-10" height="h-10" class="mb-4 !rounded-2xl !bg-[var(--md-sys-color-primary-container)]"/>
                    <x-ui.loaders.skeleton.bar width="w-20" height="h-8" class="mb-2 !rounded !bg-[var(--md-sys-color-on-surface)] opacity-20"/>
                    <x-ui.loaders.skeleton.bar width="w-28" height="h-4" class="!rounded !bg-[var(--md-sys-color-on-surface-variant)] opacity-30"/>
                </div>
                <div class="rounded-3xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] p-6 shadow-sm">
                    <x-ui.loaders.skeleton.bar width="w-10" height="h-10" class="mb-4 !rounded-2xl"/>
                    <x-ui.loaders.skeleton.bar width="w-20" height="h-8" class="mb-2 !rounded !bg-[var(--md-sys-color-on-surface)] opacity-20"/>
                    <x-ui.loaders.skeleton.bar width="w-28" height="h-4" class="!rounded !bg-[var(--md-sys-color-on-surface-variant)] opacity-30"/>
                </div>
            </div>

            <!-- Two-column widget area (chart + side panel) – balanced and reusable -->
            <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-12">

                <!-- Tabs chart / visualization card -->
                <div class="lg:col-span-8 rounded-3xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] p-6">
                    <div class="mb-6 flex items-center justify-between">
                        <x-ui.loaders.skeleton.bar width="w-40" height="h-6" class="!rounded !bg-[var(--md-sys-color-on-surface)] opacity-20"/>
                        <x-ui.loaders.skeleton.bar width="w-28" height="h-9" class="!rounded-2xl"/>
                    </div>
                    <!-- Neutral bar / trend placeholder (works for analytics, sales, usage, etc.) -->
                    <div class="flex h-72 items-end justify-between gap-3 px-2">
                        <x-ui.loaders.skeleton.bar width="w-full" height="h-[35%]" class="!rounded-t-2xl !rounded-b-none !bg-[var(--md-sys-color-primary)] opacity-20"/>
                        <x-ui.loaders.skeleton.bar width="w-full" height="h-[65%]" class="!rounded-t-2xl !rounded-b-none !bg-[var(--md-sys-color-primary)] opacity-40"/>
                        <x-ui.loaders.skeleton.bar width="w-full" height="h-[45%]" class="!rounded-t-2xl !rounded-b-none !bg-[var(--md-sys-color-primary)] opacity-25"/>
                        <x-ui.loaders.skeleton.bar width="w-full" height="h-[80%]" class="!rounded-t-2xl !rounded-b-none !bg-[var(--md-sys-color-primary)] opacity-55"/>
                        <x-ui.loaders.skeleton.bar width="w-full" height="h-[55%]" class="!rounded-t-2xl !rounded-b-none !bg-[var(--md-sys-color-primary)] opacity-35"/>
                        <x-ui.loaders.skeleton.bar width="w-full" height="h-[85%]" class="!rounded-t-2xl !rounded-b-none !bg-[var(--md-sys-color-primary)] opacity-75"/>
                        <x-ui.loaders.skeleton.bar width="w-full" height="h-[40%]" class="!rounded-t-2xl !rounded-b-none !bg-[var(--md-sys-color-primary)] opacity-25"/>
                    </div>
                </div>

                <!-- Side panel (activity / recent items / quick view) -->
                <div class="lg:col-span-4 rounded-3xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] p-6">
                    <x-ui.loaders.skeleton.bar width="w-32" height="h-6" class="mb-6 !rounded !bg-[var(--md-sys-color-on-surface)] opacity-20"/>
                    <div class="space-y-6">
                        <div class="flex gap-4">
                            <x-ui.loaders.skeleton.bar width="w-10" height="h-10" class="shrink-0 !rounded-2xl"/>
                            <div class="flex-1 space-y-3">
                                <x-ui.loaders.skeleton.bar width="w-3/4" height="h-4" class="!rounded"/>
                                <x-ui.loaders.skeleton.bar width="w-1/2" height="h-3" class="!rounded opacity-50"/>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <x-ui.loaders.skeleton.bar width="w-10" height="h-10" class="shrink-0 !rounded-2xl"/>
                            <div class="flex-1 space-y-3">
                                <x-ui.loaders.skeleton.bar width="w-full" height="h-4" class="!rounded"/>
                                <x-ui.loaders.skeleton.bar width="w-2/3" height="h-3" class="!rounded opacity-50"/>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <x-ui.loaders.skeleton.bar width="w-10" height="h-10" class="shrink-0 !rounded-2xl"/>
                            <div class="flex-1 space-y-3">
                                <x-ui.loaders.skeleton.bar width="w-5/6" height="h-4" class="!rounded"/>
                                <x-ui.loaders.skeleton.bar width="w-1/3" height="h-3" class="!rounded opacity-50"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data table / list view – full width, standard enterprise table -->
            <div class="rounded-3xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] overflow-hidden">
                <div class="border-b border-[var(--md-sys-color-outline-variant)] p-6">
                    <div class="flex items-center justify-between">
                        <x-ui.loaders.skeleton.bar width="w-40" height="h-6" class="!rounded !bg-[var(--md-sys-color-on-surface)] opacity-20"/>
                        <div class="flex gap-3">
                            <x-ui.loaders.skeleton.bar width="w-28" height="h-9" class="!rounded-2xl"/>
                            <x-ui.loaders.skeleton.bar width="w-9" height="h-9" class="!rounded-2xl"/>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="space-y-6">
                        <!-- Table row (repeatable) -->
                        <div class="flex items-center justify-between gap-6">
                            <x-ui.loaders.skeleton.bar width="w-10" height="h-4" class="!rounded"/>
                            <x-ui.loaders.skeleton.bar width="flex-1" height="h-4" class="!rounded opacity-60"/>
                            <x-ui.loaders.skeleton.bar width="w-36" height="h-4" class="!rounded opacity-40"/>
                            <x-ui.loaders.skeleton.bar width="w-28" height="h-4" class="!rounded opacity-40"/>
                            <x-ui.loaders.skeleton.bar width="w-20" height="h-8" class="!rounded-3xl"/>
                        </div>
                        <div class="flex items-center justify-between gap-6">
                            <x-ui.loaders.skeleton.bar width="w-10" height="h-4" class="!rounded"/>
                            <x-ui.loaders.skeleton.bar width="flex-1" height="h-4" class="!rounded opacity-60"/>
                            <x-ui.loaders.skeleton.bar width="w-36" height="h-4" class="!rounded opacity-40"/>
                            <x-ui.loaders.skeleton.bar width="w-28" height="h-4" class="!rounded opacity-40"/>
                            <x-ui.loaders.skeleton.bar width="w-20" height="h-8" class="!rounded-3xl"/>
                        </div>
                        <div class="flex items-center justify-between gap-6">
                            <x-ui.loaders.skeleton.bar width="w-10" height="h-4" class="!rounded"/>
                            <x-ui.loaders.skeleton.bar width="flex-1" height="h-4" class="!rounded opacity-60"/>
                            <x-ui.loaders.skeleton.bar width="w-36" height="h-4" class="!rounded opacity-40"/>
                            <x-ui.loaders.skeleton.bar width="w-28" height="h-4" class="!rounded opacity-40"/>
                            <x-ui.loaders.skeleton.bar width="w-20" height="h-8" class="!rounded-3xl"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>