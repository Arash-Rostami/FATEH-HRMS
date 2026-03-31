<div class="space-y-6" dir="rtl">
    <div class="bg-[var(--md-sys-color-surface-container-low)] rounded-2xl p-6 border border-[var(--md-sys-color-outline-variant)] shadow-sm">
        <h3 class="text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-4 flex items-center gap-2">
            <span class="material-symbols-rounded text-[var(--md-sys-color-tertiary)] text-[20px]">psychology</span>
            درباره من
        </h3>

        <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] mb-6 leading-relaxed">
            اطلاعات زیر در بخش "درباره من" در پروفایل شما برای سایر همکاران نمایش داده خواهد شد. این اطلاعات به همکاران جدید کمک می‌کند تا با شما بهتر آشنا شوند.
        </p>

        <form wire:submit.prevent="save" class="space-y-5">
            <div>
                <x-dashboard.form.textarea
                    model="bio"
                    label="من همانم که در ایام حیات..."
                    placeholder="روایت خلاصه‌ای از تولد، تحصیل، کار، مهارت و چندتا چیز دیگر که با من است..."
                    rows="4"
                    icon="auto_stories"
                />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <x-dashboard.form.input
                    model="movies"
                    label="فیلم و سریال"
                    placeholder="مثلا: معمولا فیلم زیاد میبینم مخصوصا ژانر تاریخی..."
                    icon="movie"
                />

                <x-dashboard.form.input
                    model="music"
                    label="موسیقی و پادکست"
                    placeholder="مثلا: به موسیقی و پادکست هم علاقه دارم..."
                    icon="headphones"
                />

                <x-dashboard.form.input
                    model="hobbies"
                    label="با اینا خستگیمو در می‌کنم"
                    placeholder="رنگ، غذا، سرگرمی و هنرهای مورد علاقه..."
                    icon="palette"
                />

                <x-dashboard.form.input
                    model="food"
                    label="غذا و خوراکی"
                    placeholder="مثلا: خیلی به میزان کالری غذا توجه میکنم..."
                    icon="restaurant"
                />

                <x-dashboard.form.input
                    model="sports"
                    label="ورزش و تفریح"
                    placeholder="مثلا: سایکل توریست هستم..."
                    icon="directions_bike"
                />
            </div>

            <div class="flex items-center justify-end pt-4 border-t border-[var(--md-sys-color-outline-variant)]">
                <button type="submit"
                        class="px-6 py-2.5 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] rounded-xl text-sm font-bold hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] hover:shadow-md transition-all duration-300 flex items-center gap-2">
                    <span class="material-symbols-rounded text-lg">save</span>
                    ذخیره اطلاعات
                </button>
            </div>
        </form>
    </div>
</div>
