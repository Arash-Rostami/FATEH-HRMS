<?php

namespace App\Livewire\Dashboard\Profile\Presentation;

use App\Filament\Resources\ProfileResource\Enums\Position;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

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
        $date = $user->profile?->start_date ?? $user->created_at;
        if (!$date) {
            return 'نامشخص';
        }

        $diff = now()->diff(Carbon::parse($date));
        $parts = [];

        if ($diff->y > 0) {
            $parts[] = $this->toPersianDigits($diff->y) . ' سال';
        }

        if ($diff->m > 0) {
            $parts[] = $this->toPersianDigits($diff->m) . ' ماه';
        }

        if (empty($parts)) {
            if ($diff->d > 0) {
                return $this->toPersianDigits($diff->d) . ' روز';
            }
            return 'تازه‌وارد';
        }

        return implode(' و ', $parts);
    }

    public function position(User $user): string
    {
        return Position::tryFrom($user->profile?->position ?? '')
            ?->getLabel() ?? Position::Employee->getLabel();
    }

    public function roleBadge(User $user): ?string
    {
        return match ($user->role) {
            'developer' => 'توسعه‌دهنده',
            'admin'     => 'مدیر سیستم',
            default     => null,
        };
    }

    public function bioSnippet(User $user, int $limit = 60): ?string
    {
        $aboutMe = $user->profile?->about_me;

        $aboutText = is_array($aboutMe) ? collect($aboutMe)->implode(' ') : (string) $aboutMe;

        if (blank($aboutText)) {
            return null;
        }

        return Str::limit($aboutText, $limit);
    }

    public function joinDate(User $user, string $format = 'Y/m/d'): ?string
    {
        $date = $user->profile?->start_date ?? $user->created_at;

        if (!$date) return null;

        if (class_exists(\Morilog\Jalali\Jalalian::class)) {
            return $this->toPersianDigits(\Morilog\Jalali\Jalalian::fromCarbon(Carbon::parse($date))->format($format));
        }

        return $this->toPersianDigits(Carbon::parse($date)->format($format));
    }

    protected function toPersianDigits(string|int $value): string
    {
        return str_replace(
            range(0, 9),
            ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'],
            (string) $value
        );
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
