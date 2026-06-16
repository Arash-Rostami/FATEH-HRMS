<?php

namespace App\Presenters;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

class ProfileHeaderPresenter
{
    /**
     * Cache evaluated properties to optimize multiple calls in Blade.
     */
    protected array $cache = [];

    /**
     * Create a new ProfileHeaderPresenter instance.
     *
     * @param User $user The user model to present.
     */
    public function __construct(
        public readonly User $user
    ) {
        // Eager load relationships needed for the header to avoid N+1 queries
        $this->user->loadMissing(['profile.department']);
    }

    /**
     * Get the user's full name.
     */
    public function name(): string
    {
        return $this->user->name ?? 'کاربر ناشناس';
    }

    /**
     * Get the user's avatar image URL or fallback initials.
     */
    public function avatarImage(): ?string
    {
        return $this->user->getProfileImageUrl() ?? $this->user->getInitialsAvatarUrl();
    }

    /**
     * Get the user's job position or role label.
     */
    public function position(): string
    {
        return $this->user->profile?->position_label ?? 'کارمند';
    }

    /**
     * Get the user's department name.
     */
    public function departmentName(): string
    {
        return $this->user->profile?->department?->name ?? 'بدون دپارتمان';
    }

    /**
     * Get a formatted string representing the duration since the user joined.
     * Examples: "۲ سال و ۴ ماه", "۳ ماه", "تازه‌وارد"
     */
    public function memberSince(): string
    {
        $date = $this->getStartDate();

        if (!$date) {
            return 'تازه‌وارد';
        }

        $diff = now()->diff($date);
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

    /**
     * Calculate profile completion percentage based on filled fields.
     * Returns an integer between 0 and 100.
     */
    public function completion(): int
    {
        $fields = [
            $this->user->name,
            $this->user->email,
        ];

        $profile = $this->user->profile;

        if ($profile) {
            array_push($fields,
                $profile->gender,
                $profile->employment_type,
                $profile->marital_status,
                $profile->birthdate,
                $profile->cellphone,
                $profile->landline,
                $profile->address,
                $profile->degree,
                $profile->field,
                $profile->department_id,
                $profile->position,
                $profile->about_me,
                $profile->start_date,
                $profile->emergency_phone,
                $profile->image,
                $profile->personnel_id
            );
        }

        $filled = collect($fields)->filter(fn($field) => !blank($field))->count();
        $total = count($fields);

        return (int) round(($filled / $total) * 100);
    }

    /**
     * Get a short snippet of the user's bio/about me.
     *
     * @param int $limit Maximum number of characters.
     */
    public function bioSnippet(int $limit = 60): ?string
    {
        $aboutMe = $this->user->profile?->about_me;

        $aboutText = is_array($aboutMe) ? collect($aboutMe)->implode(' ') : (string) $aboutMe;

        if (blank($aboutText)) {
            return null;
        }

        return Str::limit($aboutText, $limit);
    }

    /**
     * Get user's system role/access badge nicely formatted.
     */
    public function roleBadge(): string
    {
        return match ($this->user->role) {
            'developer' => 'توسعه‌دهنده',
            'admin'     => 'مدیر سیستم',
            default     => 'کاربر عادی',
        };
    }

    /**
     * Get the exact join date formatted. Uses Jalali if available.
     *
     * @param string $format Date format (default 'Y/m/d').
     */
    public function joinDate(string $format = 'Y/m/d'): ?string
    {
        $date = $this->getStartDate();

        if (!$date) return null;

        if (class_exists(\Morilog\Jalali\Jalalian::class)) {
            return $this->toPersianDigits(\Morilog\Jalali\Jalalian::fromCarbon($date)->format($format));
        }

        return $this->toPersianDigits($date->format($format));
    }

    /**
     * Helper to get the start date of the user.
     */
    protected function getStartDate(): ?CarbonInterface
    {
        return $this->user->profile?->start_date ?? $this->user->created_at;
    }

    /**
     * Convert standard digits to Persian digits.
     */
    protected function toPersianDigits(string|int $value): string
    {
        return str_replace(
            range(0, 9),
            ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'],
            (string) $value
        );
    }

    /**
     * Dynamically access presenter methods as properties.
     * Allows elegant and cached usage in Blade like: {{ $presenter->completion }}
     */
    public function __get(string $name): mixed
    {
        if (array_key_exists($name, $this->cache)) {
            return $this->cache[$name];
        }

        if (method_exists($this, $name)) {
            return $this->cache[$name] = $this->{$name}();
        }

        return null;
    }
}
