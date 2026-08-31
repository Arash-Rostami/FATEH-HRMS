<?php

namespace App\Livewire\Dashboard\Profile\Presentation;

use App\Filament\Resources\ProfileResource\Enums\Position;
use App\Models\SkillUser;
use App\Models\User;
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
            'gender', 'marital_status', 'id_card_number', 'degree', 'field', 'birthdate',
            'cellphone', 'address', 'department_id', 'insurance', 'emergency_phone',
        ];

        $filled = collect($fields)->filter(fn($f) => $profile->{$f} !== null && $profile->{$f} !== '')->count();

        return (int)round(($filled / count($fields)) * 100);
    }

    public function missingFieldLabels(User $user): array
    {
        $labels = [
            'gender' => 'جنسیت', 'marital_status' => 'وضعیت تاهل', 'id_card_number' => 'شماره ملی',
            'degree' => 'مدرک تحصیلی', 'field' => 'رشته تحصیلی', 'birthdate' => 'تاریخ تولد',
            'cellphone' => 'تلفن همراه', 'address' => 'آدرس', 'department_id' => 'واحد',
            'insurance' => 'شماره بیمه', 'emergency_phone' => 'تلفن ضروری',
        ];

        $profile = $user->profile;
        if (!$profile) return array_values($labels);

        return collect($labels)
            ->reject(fn($label, $field) => $profile->{$field} !== null && $profile->{$field} !== '')
            ->values()
            ->all();
    }

    public function departmentName(User $user): string
    {
        return $user->profile?->department?->displayLabel() ?? 'واحد عمومی';
    }

    public function divisionName(User $user): ?string
    {
        $unit = $user->profile?->detailsMap()->get('unit');
        return filled($unit) ? (string)$unit : null;
    }

    public function sectionName(User $user): ?string
    {
        $section = $user->profile?->detailsMap()->get('section');
        return filled($section) ? (string)$section : null;
    }

    public function lastSeen(User $user): string
    {
        if (!$user->last_seen) return 'هم‌اکنون';

        return toJalaliRelative($user->last_seen);
    }

    public function memberSince(User $user): string
    {
        $date = $user->profile?->start_date ?? $user->created_at;
        if (!$date) return 'تازه';


        $diff = now()->diff($date);
        $parts = [];

        if ($diff->y > 0) $parts[] = convertToPersian($diff->y) . ' سال';

        if ($diff->m > 0) $parts[] = convertToPersian($diff->m) . ' ماه';


        if (empty($parts)) {
            if ($diff->d > 0) return convertToPersian($diff->d) . ' روز';

            return 'تازه';
        }

        return implode(' و ', $parts);
    }


    public function bioSnippet(User $user): ?string
    {
        $aboutMe = $user->profile?->about_me;

        $aboutText = is_array($aboutMe)
            ? collect($aboutMe)->map(fn($v) => is_array($v) ? implode(', ', $v) : (string)$v)->implode(' | ')
            : (string)$aboutMe;
        if (blank($aboutText)) return null;

        return $aboutText;
    }

    public function position(User $user): string
    {
        $displayTitle = $user->profile?->detailsMap()->get('display_title');

        return filled($displayTitle)
            ? (string) $displayTitle
            : (Position::tryFrom($user->profile?->position ?? '')?->getLabel() ?? Position::Employee->getLabel());
    }

    public function newBadgeVisible(): bool
    {
        return now()->lt(SkillUser::newBadgeUntil());
    }

    public function tabs(): array
    {
        return [
            'info' => ['label' => 'اطلاعات فردی', 'icon' => 'person', 'sub' => 'مشخصات و تماس', 'title' => 'ویرایش اطلاعات فردی', 'component' => 'dashboard.profile.info', 'key' => 'tab-info', 'lazy' => false],
            'details' => ['label' => 'اطلاعات تکمیلی', 'icon' => 'list_alt', 'sub' => 'سوابق و جزئیات', 'title' => 'اطلاعات تکمیلی پرسنلی', 'component' => 'dashboard.profile.details', 'key' => 'tab-details', 'lazy' => 'on-load'],
            'skills' => ['label' => 'استعدادها', 'icon' => 'military_tech', 'sub' => 'مهارت‌ها و تخصص‌ها', 'title' => 'استعدادها و مهارت‌ها', 'component' => 'dashboard.profile.skills', 'key' => 'tab-skills', 'lazy' => 'on-load', 'isNew' => $this->newBadgeVisible()],
            'about' => ['label' => 'درباره من', 'icon' => 'psychology', 'sub' => 'بیوگرافی و علایق', 'title' => 'درباره من', 'component' => 'dashboard.profile.about', 'key' => 'tab-about', 'lazy' => 'on-load'],
            'documents' => ['label' => 'مدارک و اسناد', 'icon' => 'cloud_upload', 'sub' => 'آپلود فایل‌ها', 'title' => 'مدیریت مدارک و مستندات', 'component' => 'dashboard.profile.documents', 'key' => 'tab-docs', 'lazy' => 'on-load'],
            'credentials' => ['label' => 'دسترسی و امنیتی', 'icon' => 'vpn_key', 'sub' => 'مجوزها و رمزها', 'title' => 'مشاهده دسترسی‌ها', 'component' => 'dashboard.profile.credentials', 'key' => 'tab-creds', 'lazy' => 'on-load'],
            'onboarding' => ['label' => 'آنبوردینگ', 'icon' => 'apartment', 'sub' => 'آشنایی با شرکت', 'title' => 'آنبوردینگ (آشنایی با شرکت)', 'component' => 'dashboard.profile.onboarding', 'key' => 'tab-onboarding', 'lazy' => 'on-load'],
        ];
    }
}
