<div class="flex h-screen w-full overflow-hidden bg-[var(--md-sys-color-background)] transition-colors duration-200"
     wire:loading.flex>

    <!-- Sidebar – standard enterprise navigation (collapsible on mobile) -->
    <aside class="hidden w-72 flex-col border-r border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] p-6 md:flex">

        <!-- Logo / Brand -->
        <div class="mb-10 flex items-center gap-3">
            <div class="h-10 w-10 animate-pulse rounded-2xl bg-[var(--md-sys-color-primary-container)]"></div>
            <div class="h-6 w-36 animate-pulse rounded bg-[var(--md-sys-color-on-surface-variant)] opacity-20"></div>
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
                <div class="h-10 w-10 animate-pulse rounded-2xl bg-[var(--md-sys-color-surface-variant)]"></div>
                <div class="flex-1 space-y-2">
                    <div class="h-3 w-28 animate-pulse rounded bg-[var(--md-sys-color-on-surface-variant)] opacity-40"></div>
                    <div class="h-2 w-20 animate-pulse rounded bg-[var(--md-sys-color-on-surface-variant)] opacity-30"></div>
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
                    <div class="h-5 w-5 animate-pulse rounded bg-[var(--md-sys-color-on-surface-variant)]"></div>
                </div>

                <!-- Page title / breadcrumb area -->
                <div class="flex flex-col gap-1">
                    <div class="h-6 w-52 animate-pulse rounded bg-[var(--md-sys-color-on-surface)] opacity-10"></div>
                    <div class="h-3 w-36 animate-pulse rounded bg-[var(--md-sys-color-on-surface)] opacity-10"></div>
                </div>
            </div>

            <!-- Right side actions (search + icons) -->
            <div class="flex items-center gap-3">
                <!-- Search bar – common in enterprise apps -->
                <div class="hidden h-10 w-72 items-center gap-3 rounded-3xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] px-4 md:flex">
                    <div class="h-4 w-4 animate-pulse rounded bg-[var(--md-sys-color-on-surface-variant)]"></div>
                    <div class="flex-1 h-3.5 animate-pulse rounded bg-[var(--md-sys-color-on-surface-variant)] opacity-20"></div>
                </div>

                <!-- Notification icon -->
                <div class="h-10 w-10 animate-pulse rounded-2xl bg-[var(--md-sys-color-surface-variant)]"></div>

                <!-- User avatar -->
                <div class="h-10 w-10 animate-pulse rounded-2xl bg-[var(--md-sys-color-surface-variant)]"></div>
            </div>
        </header>

        <!-- Scrollable content area -->
        <div class="flex-1 overflow-y-auto p-6 md:p-8">

            <!-- KPI / Metric cards – 4-column responsive grid (standard for any enterprise dashboard) -->
            <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-3xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] p-6 shadow-sm">
                    <div class="mb-4 h-10 w-10 animate-pulse rounded-2xl bg-[var(--md-sys-color-tertiary-container)]"></div>
                    <div class="mb-2 h-8 w-20 animate-pulse rounded bg-[var(--md-sys-color-on-surface)] opacity-20"></div>
                    <div class="h-4 w-28 animate-pulse rounded bg-[var(--md-sys-color-on-surface-variant)] opacity-30"></div>
                </div>
                <div class="rounded-3xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] p-6 shadow-sm">
                    <div class="mb-4 h-10 w-10 animate-pulse rounded-2xl bg-[var(--md-sys-color-secondary-container)]"></div>
                    <div class="mb-2 h-8 w-20 animate-pulse rounded bg-[var(--md-sys-color-on-surface)] opacity-20"></div>
                    <div class="h-4 w-28 animate-pulse rounded bg-[var(--md-sys-color-on-surface-variant)] opacity-30"></div>
                </div>
                <div class="rounded-3xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] p-6 shadow-sm">
                    <div class="mb-4 h-10 w-10 animate-pulse rounded-2xl bg-[var(--md-sys-color-primary-container)]"></div>
                    <div class="mb-2 h-8 w-20 animate-pulse rounded bg-[var(--md-sys-color-on-surface)] opacity-20"></div>
                    <div class="h-4 w-28 animate-pulse rounded bg-[var(--md-sys-color-on-surface-variant)] opacity-30"></div>
                </div>
                <div class="rounded-3xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] p-6 shadow-sm">
                    <div class="mb-4 h-10 w-10 animate-pulse rounded-2xl bg-[var(--md-sys-color-surface-variant)]"></div>
                    <div class="mb-2 h-8 w-20 animate-pulse rounded bg-[var(--md-sys-color-on-surface)] opacity-20"></div>
                    <div class="h-4 w-28 animate-pulse rounded bg-[var(--md-sys-color-on-surface-variant)] opacity-30"></div>
                </div>
            </div>

            <!-- Two-column widget area (chart + side panel) – balanced and reusable -->
            <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-12">

                <!-- Tabs chart / visualization card -->
                <div class="lg:col-span-8 rounded-3xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] p-6">
                    <div class="mb-6 flex items-center justify-between">
                        <div class="h-6 w-40 animate-pulse rounded bg-[var(--md-sys-color-on-surface)] opacity-20"></div>
                        <div class="h-9 w-28 animate-pulse rounded-2xl bg-[var(--md-sys-color-surface-variant)]"></div>
                    </div>
                    <!-- Neutral bar / trend placeholder (works for analytics, sales, usage, etc.) -->
                    <div class="flex h-72 items-end justify-between gap-3 px-2">
                        <div class="h-[35%] w-full animate-pulse rounded-t-2xl bg-[var(--md-sys-color-primary)] opacity-20"></div>
                        <div class="h-[65%] w-full animate-pulse rounded-t-2xl bg-[var(--md-sys-color-primary)] opacity-40"></div>
                        <div class="h-[45%] w-full animate-pulse rounded-t-2xl bg-[var(--md-sys-color-primary)] opacity-25"></div>
                        <div class="h-[80%] w-full animate-pulse rounded-t-2xl bg-[var(--md-sys-color-primary)] opacity-55"></div>
                        <div class="h-[55%] w-full animate-pulse rounded-t-2xl bg-[var(--md-sys-color-primary)] opacity-35"></div>
                        <div class="h-[85%] w-full animate-pulse rounded-t-2xl bg-[var(--md-sys-color-primary)] opacity-75"></div>
                        <div class="h-[40%] w-full animate-pulse rounded-t-2xl bg-[var(--md-sys-color-primary)] opacity-25"></div>
                    </div>
                </div>

                <!-- Side panel (activity / recent items / quick view) -->
                <div class="lg:col-span-4 rounded-3xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] p-6">
                    <div class="mb-6 h-6 w-32 animate-pulse rounded bg-[var(--md-sys-color-on-surface)] opacity-20"></div>
                    <div class="space-y-6">
                        <div class="flex gap-4">
                            <div class="h-10 w-10 shrink-0 animate-pulse rounded-2xl bg-[var(--md-sys-color-surface-variant)]"></div>
                            <div class="flex-1 space-y-3">
                                <div class="h-4 w-3/4 animate-pulse rounded bg-[var(--md-sys-color-surface-variant)]"></div>
                                <div class="h-3 w-1/2 animate-pulse rounded bg-[var(--md-sys-color-surface-variant)] opacity-50"></div>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="h-10 w-10 shrink-0 animate-pulse rounded-2xl bg-[var(--md-sys-color-surface-variant)]"></div>
                            <div class="flex-1 space-y-3">
                                <div class="h-4 w-full animate-pulse rounded bg-[var(--md-sys-color-surface-variant)]"></div>
                                <div class="h-3 w-2/3 animate-pulse rounded bg-[var(--md-sys-color-surface-variant)] opacity-50"></div>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="h-10 w-10 shrink-0 animate-pulse rounded-2xl bg-[var(--md-sys-color-surface-variant)]"></div>
                            <div class="flex-1 space-y-3">
                                <div class="h-4 w-5/6 animate-pulse rounded bg-[var(--md-sys-color-surface-variant)]"></div>
                                <div class="h-3 w-1/3 animate-pulse rounded bg-[var(--md-sys-color-surface-variant)] opacity-50"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data table / list view – full width, standard enterprise table -->
            <div class="rounded-3xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] overflow-hidden">
                <div class="border-b border-[var(--md-sys-color-outline-variant)] p-6">
                    <div class="flex items-center justify-between">
                        <div class="h-6 w-40 animate-pulse rounded bg-[var(--md-sys-color-on-surface)] opacity-20"></div>
                        <div class="flex gap-3">
                            <div class="h-9 w-28 animate-pulse rounded-2xl bg-[var(--md-sys-color-surface-variant)]"></div>
                            <div class="h-9 w-9 animate-pulse rounded-2xl bg-[var(--md-sys-color-surface-variant)]"></div>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="space-y-6">
                        <!-- Table row (repeatable) -->
                        <div class="flex items-center justify-between gap-6">
                            <div class="h-4 w-10 animate-pulse rounded bg-[var(--md-sys-color-surface-variant)]"></div>
                            <div class="h-4 flex-1 animate-pulse rounded bg-[var(--md-sys-color-surface-variant)] opacity-60"></div>
                            <div class="h-4 w-36 animate-pulse rounded bg-[var(--md-sys-color-surface-variant)] opacity-40"></div>
                            <div class="h-4 w-28 animate-pulse rounded bg-[var(--md-sys-color-surface-variant)] opacity-40"></div>
                            <div class="h-8 w-20 animate-pulse rounded-3xl bg-[var(--md-sys-color-surface-variant)]"></div>
                        </div>
                        <div class="flex items-center justify-between gap-6">
                            <div class="h-4 w-10 animate-pulse rounded bg-[var(--md-sys-color-surface-variant)]"></div>
                            <div class="h-4 flex-1 animate-pulse rounded bg-[var(--md-sys-color-surface-variant)] opacity-60"></div>
                            <div class="h-4 w-36 animate-pulse rounded bg-[var(--md-sys-color-surface-variant)] opacity-40"></div>
                            <div class="h-4 w-28 animate-pulse rounded bg-[var(--md-sys-color-surface-variant)] opacity-40"></div>
                            <div class="h-8 w-20 animate-pulse rounded-3xl bg-[var(--md-sys-color-surface-variant)]"></div>
                        </div>
                        <div class="flex items-center justify-between gap-6">
                            <div class="h-4 w-10 animate-pulse rounded bg-[var(--md-sys-color-surface-variant)]"></div>
                            <div class="h-4 flex-1 animate-pulse rounded bg-[var(--md-sys-color-surface-variant)] opacity-60"></div>
                            <div class="h-4 w-36 animate-pulse rounded bg-[var(--md-sys-color-surface-variant)] opacity-40"></div>
                            <div class="h-4 w-28 animate-pulse rounded bg-[var(--md-sys-color-surface-variant)] opacity-40"></div>
                            <div class="h-8 w-20 animate-pulse rounded-3xl bg-[var(--md-sys-color-surface-variant)]"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
