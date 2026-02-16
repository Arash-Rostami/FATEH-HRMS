<?php

namespace App\Livewire\Dashboard\Navbar;

use Livewire\Component;

class CommandPalette extends Component
{
    public string $query = '';
    public array $results = [];

    public function render()
    {
        return view('livewire.dashboard.navbar.command-palette');
    }

    public function updatedQuery()
    {
        if (strlen($this->query) < 2) {
            $this->results = [];
            return;
        }

        $this->results = collect([
            ['icon' => 'person', 'title' => 'پروفایل کاربری', 'subtitle' => 'مشاهده و ویرایش اطلاعات شخصی', 'action' => 'profile'],
            ['icon' => 'dashboard', 'title' => 'داشبورد اصلی', 'subtitle' => 'بازگشت به صفحه اصلی', 'action' => 'dashboard'],
            ['icon' => 'settings', 'title' => 'تنظیمات سیستم', 'subtitle' => 'مدیریت پیکربندی‌ها', 'action' => 'settings'],
            ['icon' => 'logout', 'title' => 'خروج از حساب', 'subtitle' => 'پایان نشست کاربری', 'action' => 'logout'],
        ])->filter(function ($item) {
            return str_contains($item['title'], $this->query);
        })->values()->toArray();
    }
}
