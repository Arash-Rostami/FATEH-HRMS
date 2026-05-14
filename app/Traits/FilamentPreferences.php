<?php

namespace App\Traits;

use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Slider;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

trait FilamentPreferences
{
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

        $this->form->fill($user?->extra['preferences'] ?? []);
    }

    public function save(): void
    {
        $user = Auth::user();

        if (!$user) {
            return;
        }

        $extra = $user->extra ?? [];
        $extra['preferences'] = $this->form->getState();

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
                ->helperText('امکان جمع کردن منوی کناری برای فضای بیشتر.')
                ->onIcon('heroicon-o-bars-3-bottom-right')
                ->offIcon('heroicon-o-x-mark'),

            Toggle::make('sidebar_fully_collapsible')
                ->label('جمع شدن کامل')
                ->helperText('منوی کناری به طور کامل مخفی شود.')
                ->onIcon('heroicon-o-arrows-pointing-in')
                ->offIcon('heroicon-o-arrows-pointing-out'),

            Toggle::make('breadcrumbs')
                ->label('نمایش مسیر راهنما')
                ->helperText('نمایش موقعیت فعلی در بالای صفحات.')
                ->onIcon('heroicon-o-chevron-double-right')
                ->offIcon('heroicon-o-chevron-double-left'),

            Toggle::make('spa_enabled')
                ->label('حالت SPA')
                ->helperText('بارگذاری سریع‌تر صفحات بدون رفرش کامل مرورگر.')
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
                ->offIcon('heroicon-o-window'),

            Toggle::make('topbar')
                ->label('نمایش نوار بالا')
                ->helperText('نمایش یا مخفی‌سازی کامل نوار بالای پنل.')
                ->onIcon('heroicon-o-bars-3')
                ->offIcon('heroicon-o-bars-3'),

            Toggle::make('user_menu_topbar')
                ->label('منوی کاربر در نوار بالا')
                ->helperText('نمایش منوی پروفایل کاربر در نوار بالای صفحه به جای منوی کناری.')
                ->onIcon('heroicon-o-user-circle')
                ->offIcon('heroicon-o-bars-4'),

            Toggle::make('unsaved_changes_alerts')
                ->label('هشدار تغییرات ذخیره‌نشده')
                ->helperText('نمایش پیغام تایید هنگام خروج از صفحه در صورت وجود فرم‌های ذخیره‌نشده.')
                ->onIcon('heroicon-o-shield-exclamation')
                ->offIcon('heroicon-o-shield-check'),

            Toggle::make('screen_saver')
                ->label('محافظ صفحه')
                ->helperText('فعال‌سازی محافظ صفحه پس از مدتی عدم فعالیت.')
                ->onIcon('heroicon-o-computer-desktop')
                ->offIcon('heroicon-o-computer-desktop')
                ->reactive()
                ->default(true),

            Slider::make('screen_saver_timeout')
                ->label('زمان محافظ صفحه (ثانیه)')
                ->helperText('مدت زمان عدم فعالیت پیش از فعال شدن محافظ صفحه.')
                ->min(15)
                ->max(3600)
                ->step(15)
                ->default(60)
                ->visible(fn (Get $get) => $get('screen_saver')),
        ];
    }

    protected function getPreferencesColumns(): int
    {
        return 2;
    }
}
