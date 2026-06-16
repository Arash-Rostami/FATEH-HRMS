<?php

namespace App\Livewire\Dashboard\Profile\Presentation;

use App\Filament\Resources\ProfileResource\Enums\Position;
use App\Models\User;
use Carbon\Carbon;

class ProfilePresenter
{
    public function avatarUrl(User $user): ?string
    {
        return $user->getProfileImageUrl() ?? $user->getInitialsAvatarUrl();
    }

    public function completion(User $user): int
    {
        $profile = $user->profile;
        if (!$profile) return 0;

        $fields = [
            'personnel_id', 'gender', 'employment_type', 'marital_status',
            'id_card_number', 'degree', 'field', 'birthdate',
            'cellphone', 'address', 'department_id', 'position',
            'insurance', 'emergency_phone', 'start_date',
        ];

        $filled = collect($fields)->filter(fn($f) => $profile->{$f} !== null && $profile->{$f} !== '')->count();

        return (int)round(($filled / count($fields)) * 100);
    }

    public function departmentName(User $user): string
    {
        return $user->profile?->department?->description ?? $user->profile?->department?->name ??'واحد عمومی';
    }

    public function lastSeen(User $user): string
    {
        if (!$user->last_seen) {
            return 'هم‌اکنون';
        }
        return Carbon::parse($user->last_seen)->diffForHumans();
    }

    public function memberSince(User $user): string
    {
        if (!$user->created_at) {
            return 'نامشخص';
        }
        return Carbon::parse($user->created_at)->diffForHumans(null, true);
    }

    public function position(User $user): string
    {
        return Position::tryFrom($user->profile?->position ?? '')
            ?->getLabel() ?? Position::Employee->getLabel();
    }

    public function tabs(): array
    {
        return [
            'info' => ['label' => 'اطلاعات فردی', 'icon' => 'person', 'sub' => 'مشخصات و تماس', 'title' => 'ویرایش اطلاعات فردی', 'component' => 'dashboard.profile.info', 'key' => 'tab-info', 'lazy' => false],
            'about' => ['label' => 'درباره من', 'icon' => 'psychology', 'sub' => 'بیوگرافی و علایق', 'title' => 'درباره من', 'component' => 'dashboard.profile.about', 'key' => 'tab-about', 'lazy' => true],
            'details' => ['label' => 'اطلاعات تکمیلی', 'icon' => 'list_alt', 'sub' => 'سوابق و جزئیات', 'title' => 'اطلاعات تکمیلی پرسنلی', 'component' => 'dashboard.profile.details', 'key' => 'tab-details', 'lazy' => true],
            'documents' => ['label' => 'مدارک و اسناد', 'icon' => 'cloud_upload', 'sub' => 'آپلود فایل‌ها', 'title' => 'مدیریت مدارک و مستندات', 'component' => 'dashboard.profile.documents', 'key' => 'tab-docs', 'lazy' => true],
            'credentials' => ['label' => 'دسترسی و امنیتی', 'icon' => 'vpn_key', 'sub' => 'مجوزها و رمزها', 'title' => 'مشاهده دسترسی‌ها', 'component' => 'dashboard.profile.credentials', 'key' => 'tab-creds', 'lazy' => true],
            'onboarding' => ['label' => 'آنبوردینگ', 'icon' => 'apartment', 'sub' => 'آشنایی با شرکت', 'title' => 'آنبوردینگ (آشنایی با شرکت)', 'component' => 'dashboard.profile.onboarding', 'key' => 'tab-onboarding', 'lazy' => true],
        ];
    }
}
