<?php

namespace App\Traits\Filament;

use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Slider;
use Filament\Forms\Components\Slider\Enums\Behavior;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Auth;

trait FilamentPreferences
{
    public const RECORDS_PER_PAGE_OPTIONS = [10, 25, 50, 100];
    public const CACHE_TTL_OPTIONS = [300, 600, 900, 1800, 3600];
    public const CACHE_TTL_MINUTES = [5, 10, 15, 30, 60];

    public ?array $data = [];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Grid::make($this->getPreferencesColumns())
                    ->schema($this->getPreferenceFields()),
            ]);
    }

    public function getTogglesCount(): int
    {
        return count($this->getPreferenceFields());
    }

    public function mount(): void
    {
        $user = Auth::user();

        $defaults = collect($this->getPreferenceFields())
            ->filter(fn ($field) => $field instanceof Toggle && $field->getDefaultState() !== null)
            ->mapWithKeys(fn (Toggle $field) => [$field->getName() => $field->getDefaultState()])
            ->all();

        $this->form->fill(array_replace($defaults, $user?->extra['preferences'] ?? []));
    }

    public function save(): void
    {
        $user = Auth::user();

        if (!$user) {
            return;
        }

        $extra = $user->extra ?? [];
        $extra['preferences'] = array_merge($extra['preferences'] ?? [], $this->form->getState());

        $user->update(['extra' => $extra]);

        Notification::make()
            ->title('تغییرات اعمال شد')
            ->body('تنظیمات رابط کاربری با موفقیت به‌روزرسانی شد.')
            ->success()
            ->send();

        $this->redirect(request()->header('Referer'));
    }

    public function saveAction(): Action
    {
        return Action::make('save')
            ->label('ذخیره تنظیمات')
            ->icon('heroicon-o-check-circle')
            ->color('primary')
            ->submit('save');
    }

    protected function getPreferenceFields(): array
    {
        return [
            Toggle::make('sidebar_collapsible')
                ->label('نوار کناری تاشو')
                ->helperText('برای باز کردن فضای بیشتر، می‌توانید منوی کناری را جمع کنید.')
                ->onIcon('heroicon-o-bars-3-bottom-right')
                ->offIcon('heroicon-o-x-mark')
                ->reactive()
                ->disabled(fn (Get $get) => (bool) $get('nav_dock')),

            Toggle::make('sidebar_fully_collapsible')
                ->label('جمع شدن کامل')
                ->helperText('با این گزینه، منوی کناری هنگام جمع شدن کاملاً پنهان می‌شود. برای استفاده، «نوار کناری تاشو» را خاموش کنید.')
                ->onIcon('heroicon-o-arrows-pointing-in')
                ->offIcon('heroicon-o-arrows-pointing-out')
                ->disabled(fn (Get $get) => (bool) $get('nav_dock') || (bool) $get('sidebar_collapsible')),

            Toggle::make('breadcrumbs')
                ->label('نمایش مسیر راهنما')
                ->helperText('نام صفحه و بخش‌های بالادست آن را بالای صفحه نشان می‌دهد.')
                ->onIcon('heroicon-o-chevron-double-right')
                ->offIcon('heroicon-o-chevron-double-left'),

            Toggle::make('spa_enabled')
                ->label('جابه‌جایی سریع بین صفحه‌ها')
                ->helperText('برای باز کردن صفحهٔ بعدی، مرورگر فقط محتوای لازم را دوباره بارگذاری می‌کند.')
                ->onIcon('heroicon-o-bolt')
                ->offIcon('heroicon-o-bolt'),

            Toggle::make('collapsible_groups')
                ->label('گروه‌های تاشو')
                ->helperText('امکان باز و بسته کردن گروه‌های منو.')
                ->onIcon('heroicon-o-list-bullet')
                ->offIcon('heroicon-o-list-bullet'),

            Toggle::make('top_nav')
                ->label('منوی بالا')
                ->helperText('انتقال منوی اصلی به قسمت بالای صفحه.')
                ->onIcon('heroicon-o-window')
                ->offIcon('heroicon-o-window')
                ->reactive()
                ->disabled(fn (Get $get) => (bool) $get('nav_dock')),

            Toggle::make('topbar')
                ->label('نمایش نوار بالا')
                ->helperText('نمایش یا مخفی‌سازی کامل نوار بالای پنل.')
                ->onIcon('heroicon-o-bars-3')
                ->offIcon('heroicon-o-bars-3')
                ->reactive()
                ->default(true),

            Toggle::make('topbar_pinned')
                ->label('ثابت نگه‌داشتن نوار بالا')
                ->helperText('در رایانه، اگر این گزینه خاموش باشد، برای دیدن نوار بالا نشانگر ماوس را به بالای صفحه ببرید.')
                ->onIcon('heroicon-o-map-pin')
                ->offIcon('heroicon-o-eye')
                ->default(true)
                ->disabled(fn (Get $get) => !$get('topbar')),

            Toggle::make('nav_dock')
                ->label('منوی شناور پایین صفحه')
                ->helperText('در رایانه، منوی کناری به پایین صفحه منتقل می‌شود. برای استفاده، «منوی بالا» را خاموش کنید.')
                ->onIcon('heroicon-o-view-columns')
                ->offIcon('heroicon-o-bars-3')
                ->reactive()
                ->disabled(fn (Get $get) => (bool) $get('top_nav')),

            Toggle::make('user_menu_topbar')
                ->label('منوی کاربر در نوار بالا')
                ->helperText('نمایش منوی پروفایل کاربر در نوار بالای صفحه به جای منوی کناری.')
                ->onIcon('heroicon-o-user-circle')
                ->offIcon('heroicon-o-bars-4')
                ->disabled(fn (Get $get) => !$get('topbar')),

            Toggle::make('unsaved_changes_alerts')
                ->label('هشدار تغییرات ذخیره‌نشده')
                ->helperText('اگر فرم را ذخیره نکرده باشید، هنگام خروج از صفحه از شما تأیید می‌خواهد.')
                ->onIcon('heroicon-o-shield-exclamation')
                ->offIcon('heroicon-o-shield-check'),

            Toggle::make('show_list_tabs')
                ->label('نمایش زبانه‌های آمار و فیلتر')
                ->helperText('زبانه‌های آمار و فیلتر را بالای فهرست‌ها نشان می‌دهد.')
                ->onIcon('heroicon-o-chart-bar')
                ->offIcon('heroicon-o-chart-bar-square')
                ->default(true),

            Toggle::make('persian_dates')
                ->label('تقویم شمسی در فیلترها')
                ->helperText('نمایش تقویم شمسی به‌جای میلادی در فیلترهای بازه تاریخ.')
                ->onIcon('heroicon-o-calendar-days')
                ->offIcon('heroicon-o-calendar')
                ->default(true),

            Toggle::make('row_click_edit')
                ->label('باز کردن ویرایش با انتخاب ردیف')
                ->helperText('با روشن کردن این گزینه، انتخاب ردیف صفحهٔ ویرایش را باز می‌کند. اگر گزینه خاموش باشد یا اجازهٔ ویرایش نداشته باشید، جزئیات ردیف باز می‌شود.')
                ->onIcon('heroicon-o-pencil-square')
                ->offIcon('heroicon-o-eye')
                ->default(false),

            Toggle::make('persist_table_state')
                ->label('به‌خاطر سپردن تنظیمات جدول')
                ->helperText('تا وقتی در همین نشست هستید، با بازگشت به جدول، جست‌وجو، فیلترها و ترتیب ردیف‌ها حفظ می‌شوند.')
                ->onIcon('heroicon-o-bookmark')
                ->offIcon('heroicon-o-bookmark-slash')
                ->default(false),

            Slider::make('records_per_page')
                ->label('تعداد ردیف در هر صفحه')
                ->helperText('تعداد ردیف‌های هر صفحه را برای جدول‌های پنل مدیریت تعیین کنید.')
                ->range(minValue: 0, maxValue: count(self::RECORDS_PER_PAGE_OPTIONS) - 1)
                ->step(1)
                ->live()
                ->rtl()
                ->pips()
                ->pipsFormatter(RawJs::make('(' . json_encode(self::RECORDS_PER_PAGE_OPTIONS) . ')[$value]'))
                ->extraAttributes(['style' => 'transform: scale(0.8); transform-origin: center;'])
                ->behavior([Behavior::Tap, Behavior::Drag, Behavior::SmoothSteps])
                ->default(0)
                ->afterStateHydrated(fn (Slider $component, ?int $state) => $component->state(($index = array_search($state, self::RECORDS_PER_PAGE_OPTIONS, true)) !== false ? $index : 0))
                ->dehydrateStateUsing(fn (?int $state) => self::RECORDS_PER_PAGE_OPTIONS[$state] ?? 25)
                ->beforeContent(fn (Get $get): string => (self::RECORDS_PER_PAGE_OPTIONS[(int) ($get('records_per_page') ?? 0)] ?? 25) . ' ردیف'),

            Slider::make('cache_ttl')
                ->label('فاصلهٔ به‌روزرسانی آمار')
                ->helperText('هرچه این زمان بیشتر باشد، آمار ذخیره‌شده مدت بیشتری نمایش داده می‌شود و محاسبهٔ دوباره دیرتر انجام می‌شود.')
                ->range(minValue: 0, maxValue: count(self::CACHE_TTL_OPTIONS) - 1)
                ->step(1)
                ->live()
                ->rtl()
                ->pips()
                ->pipsFormatter(RawJs::make('(' . json_encode(self::CACHE_TTL_MINUTES) . ')[$value]'))
                ->extraAttributes(['style' => 'transform: scale(0.8); transform-origin: center;'])
                ->behavior([Behavior::Tap, Behavior::Drag, Behavior::SmoothSteps])
                ->default(0)
                ->afterStateHydrated(fn (Slider $component, ?int $state) => $component->state(($index = array_search($state, self::CACHE_TTL_OPTIONS, true)) !== false ? $index : 0))
                ->dehydrateStateUsing(fn (?int $state) => self::CACHE_TTL_OPTIONS[$state] ?? 300)
                ->beforeContent(fn (Get $get): string => (self::CACHE_TTL_MINUTES[(int) ($get('cache_ttl') ?? 0)] ?? 5) . ' دقیقه'),

            Toggle::make('screen_saver')
                ->label('محافظ صفحه')
                ->helperText('پس از مدتی که از پنل استفاده نکنید، محافظ صفحه نمایش داده می‌شود.')
                ->onIcon('heroicon-o-computer-desktop')
                ->offIcon('heroicon-o-computer-desktop')
                ->reactive()
                ->default(true),

            Slider::make('screen_saver_timeout')
                ->hiddenLabel()
                ->helperText('چند دقیقه بعد از آخرین فعالیت، محافظ صفحه باز شود؟')
                ->range(minValue: 1, maxValue: 60)
                ->step(1)
                ->live()
                ->rtl()
                ->pips()
                ->extraAttributes(['style' => 'transform: scale(0.8); transform-origin: center;'])
                ->behavior([Behavior::Tap, Behavior::Drag, Behavior::SmoothSteps])
                ->default(2)
                ->afterStateHydrated(fn (Slider $component, ?int $state) => $component->state($state ? (int) round($state / 60) : 2))
                ->dehydrateStateUsing(fn (?int $state) => $state ? $state * 60 : null)
                ->beforeContent(fn (Get $get): string => ((int) ($get('screen_saver_timeout') ?? 0)) . ' دقیقه')
                ->visible(fn (Get $get) => $get('screen_saver')),

        ];
    }

    protected function getPreferencesColumns(): int
    {
        return 2;
    }
}
