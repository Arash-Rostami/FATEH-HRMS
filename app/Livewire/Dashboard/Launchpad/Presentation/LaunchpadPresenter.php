<?php

namespace App\Livewire\Dashboard\Launchpad\Presentation;

use App\Models\Department;
use App\Models\Launchpad;
use App\Models\User;
use App\Support\LaunchpadAccessPolicy;

class LaunchpadPresenter
{
    private ?array $departments = null;

    public function listRow(Launchpad $row, User $user, array $sites = []): array
    {
        $status = $row->status();
        $label = $this->statusLabel($row, $status);
        $this->departments ??= Department::getCachedOptions()->all();
        $block = LaunchpadAccessPolicy::workspaceBlock($row, $user);
        $canManage = LaunchpadAccessPolicy::canManage($row, $user);
        return [
            'id' => (int) $row->id,
            'title' => $row->title,
            'department' => $this->departments[$row->department_id] ?? $row->department_id,
            'owner' => $row->user?->name ?? '—',
            'audience' => $row->isPublic() ? 'همهٔ کاربران' : convertToPersian((string) count(is_array($row->audience) ? $row->audience : [])),
            'status' => $label,
            'description' => (string) $row->description,
            'access' => $row->accessible_from === null && $row->accessible_to === null ? '—' : $this->date($row->accessible_from) . ' تا ' . $this->date($row->accessible_to),
            'expiry' => $this->date($row->expire_at),
            'variant' => match ($status) {
                'live' => 'success',
                'soon' => 'warning',
                'expired' => 'danger',
                'scheduled' => 'info',
                default => 'neutral',
            },
            'can_manage' => $canManage,
            'live' => in_array($status, ['live', 'soon'], true),
            'refresh' => $canManage && $row->refresh_due_at !== null ? $this->refreshState($row) : null,
            'block' => $block,
            'pinnable' => $row->kind !== 'link' || isset($sites[(string) ($row->settings['site'] ?? '')]),
            'workspace_title' => match ($block) {
                null => 'افزودن به فضای کاری',
                'inactive' => 'برنامه غیرفعال است',
                'scheduled' => 'هنوز زمان دسترسی به برنامه نرسیده است',
                'expired' => 'مهلت دسترسی به برنامه تمام شده است',
                default => 'برنامه در دسترس شما نیست',
            },
        ];
    }

    public function details(Launchpad $row, bool $manager): array
    {
        $this->departments ??= Department::getCachedOptions()->all();
        $settings = (array) $row->settings;
        $static = $row->kind === 'static';
        $rows = [
            ['نام برنامه', $row->title],
            ['توضیحات', filled($row->description) ? $row->description : '—'],
            ['دپارتمان',$this->departments[$row->department_id] ?? $row->department_id],
            ['مالک', $row->user?->name ?? '—'],
            ['نوع برنامه', $static ? 'بارگذاری فایل' : 'میان‌بر به سامانه'],
        ];
        if ($static) {
            $rows[] = ['تعداد فایل‌ها', convertToPersian((string) (int) ($settings['files_count'] ?? 0))];
            $rows[] = ['حجم فایل‌ها', convertToPersian(number_format((int) ceil((int) ($settings['size'] ?? 0) / 1024))) . ' کیلوبایت'];
        }
        $rows[] = ['تاریخ ساخت', $this->date($row->created_at)];
        $rows[] = ['آخرین تغییر', $this->date($row->updated_at)];
        $access = [
            ['وضعیت', $this->statusLabel($row, $row->status())],
            ['مخاطبان', $row->isPublic() ? 'همهٔ کاربران' : convertToPersian((string) count(is_array($row->audience) ? $row->audience : [])) . ' نفر'],
            ['شروع دسترسی', $this->date($row->accessible_from)],
            ['پایان دسترسی', $this->date($row->accessible_to)],
            ['آخرین روز نگهداری برنامه', $this->date($row->expire_at)],
        ];

        return ['id' => (int) $row->id, 'manage' => $manager, 'rows' => $rows, 'access' => $access, 'source' =>$manager && $static && is_array($row->source) ? $this->sourceRows($row) : null];
    }

    public function capMeter(string $dept): array
    {
        $used = Launchpad::query()->where('department_id', $dept)->count();
        $cap = (int) config('launchpad.max_per_department');
        return ['used' => $used, 'cap' => $cap, 'pct' => $cap > 0 ? min(100, (int) round($used * 100 / $cap)) : 0, 'tone' => $this->tone($cap > 0 && $used >= $cap)];
    }

    public function capRules(): array
    {
        return [
            'ظرفیت' => 'هر دپارتمان حداکثر ' . convertToPersian((string) config('launchpad.max_per_department')) . ' برنامهٔ همزمان دارد؛ برنامهٔ غیرفعال و برنامه‌ای که مهلت دسترسی‌اش تمام شده هم جای خود را نگه می‌دارد و تنها حذف برنامه جا را آزاد می‌کند.',
            'جایگزینی' => 'برنامهٔ تازه با نام و دپارتمانِ برنامهٔ موجود، همان برنامه را جایگزین می‌کند و ظرفیت تازه‌ای نمی‌گیرد؛ فایل‌های قبلی حذف و مخاطبان و تاریخ‌های همان برنامه حفظ می‌شوند.',
            'نگهداری' => 'پایان دسترسی، برنامه را حذف نمی‌کند و جایش در ظرفیت می‌ماند؛ اما پایان «آخرین روز نگهداری» برنامه را از دسترس خارج و در پاک‌سازی خودکار حذف می‌کند.',
            'مخاطبان' => 'بدون انتخاب مخاطبان، فقط شما و مدیران برنامه را می‌بینند؛ با روشن کردن «برای همه کاربران»، همهٔ کاربران سامانه آن را می‌بینند. مخاطبان را بعداً می‌توانید از «جزئیات» و سپس «ویرایش» تغییر دهید.',
            'منبع داده' => 'اتصال API فقط برای دریافت خودکار اطلاعات است، نه برای ورود به سامانه؛ کلیدها و رمزهای آن رمزگذاری می‌شوند و پس از ذخیره دوباره نمایش داده نمی‌شوند.',
        ];
    }

    public function precheck(array $report): array
    {
        $reasons = ['name' => 'نام فایل خیلی بلند است یا علامت غیرمجاز دارد؛ نام کوتاه‌تر و ساده‌تری انتخاب کنید.', 'hidden' => 'نام فایل نباید با نقطه شروع شود.', 'executable' => 'این نوع فایل اجرایی پذیرفته نمی‌شود؛ فقط فایل‌های وب مجاز را انتخاب کنید.', 'extension' => 'این نوع فایل پذیرفته نمی‌شود؛ فهرست فایل‌های مجاز را در راهنما ببینید.', 'size' => 'حجم این فایل بیش از حد مجاز است؛ حجم آن را کم کنید.', 'duplicate' => 'فایلی با همین نام انتخاب شده است؛ نام‌ها باید متفاوت باشند، حتی اگر حروف بزرگ و کوچک فرق کنند.', 'content' => 'در ابتدای فایل کد PHP پیدا شد؛ نسخهٔ بدون کد PHP را از سازندهٔ برنامه بگیرید.'];
        $report['rejected'] = array_map(fn ($item) => ['name' => mb_substr((string) $item['name'], 0, 80), 'reason' => $reasons[$item['reason']] ?? 'نامعتبر'], array_slice($report['rejected'], 0, 20));
        $messages = ['empty' => 'فایلی انتخاب نشده است.', 'too_many' => 'تعداد فایل‌ها بیش از حد مجاز است.', 'too_big' => 'حجم همهٔ فایل‌ها با هم بیش از حد مجاز است؛ حجم فایل‌ها را کم کنید.', 'no_entry' => 'فایل اصلی index.html پیدا نشد؛ آن را همراه بقیهٔ فایل‌ها انتخاب کنید. نام فایل باید دقیقاً index.html باشد.'];
        $report['messages'] = array_values(array_map(fn ($key) => $messages[$key] ?? 'فایل‌ها آمادهٔ ذخیره نیستند؛ پیام‌های بررسی فایل را بخوانید.', $report['errors']));
        $report['pct'] =$report['max_bytes'] > 0 ? min(100, (int) round($report['bytes'] * 100 / $report['max_bytes'])) : 0;
        $report['tone'] = $this->tone(!$report['ok']);
        return $report;
    }

    private function statusLabel(Launchpad $row, string $status): string
    {
        return match ($status) {
            'inactive' => 'غیرفعال',
            'expired' => 'مهلت دسترسی تمام شده',
            'scheduled' => 'هنوز زمان دسترسی نرسیده',
            'soon' => convertToPersian((string) max(0, (int) today()->diffInDays($row->expire_at, false))) . ' روز تا پایان نگهداری',
            default => 'فعال',
        };
    }

    private function date(mixed $value): string
    {
        return convertToPersian(toJalaliSmart($value));
    }

    private function sourceRows(Launchpad $row): array
    {
        $source = $row->source;
        $auth = (string) ($source['auth'] ?? '');
        $type = $auth === 'google'
            ? config('launchpad.refresh.auth.google.label') . ' — ' . (data_get($source, 'env.access') === 'private' ? 'خصوصی' : 'عمومی')
            : (string) config("launchpad.refresh.auth.{$auth}.label", '—');
        $refresh = is_array($row->settings['refresh'] ?? null) ? $row->settings['refresh'] : [];
        $reason = $refresh['reason'] ?? null;
        $rows = [
            ['نوع', $type],
            ['وضعیت دریافت', config('launchpad.refresh.statuses.' . ($refresh['status'] ?? 'pending'), config('launchpad.refresh.statuses.pending'))['label']],
            ['زمان دریافت', $this->date($refresh['at'] ?? null)],
        ];
        if (is_string($reason) && config("launchpad.refresh.reasons.{$reason}") !== null) {
            $rows[] = ['علت', config("launchpad.refresh.reasons.{$reason}")];
        }
        if ($reason === 'tab_missing' && filled(data_get($refresh, 'gids.map'))) {
            $rows[] = ['برگه‌های موجود در فایل', implode('، ', array_map('strval', array_keys((array) data_get($refresh, 'gids.map'))))];
        }

        return $rows;
    }

    public function freshness(Launchpad $row, bool $manager): array
    {
        $refresh = $row->kind === 'static' && is_array($row->settings['refresh'] ?? null) ? $row->settings['refresh'] : [];
        if (!isset($refresh['status'])) {
            return [];
        }
        $ok = $refresh['status'] === 'ok';

        return [
            'ok' => $ok,
            'variant' => config("launchpad.refresh.statuses.{$refresh['status']}.variant", 'warning'),
            'text' => $ok ? 'آخرین به‌روزرسانی: ' . $this->date($refresh['at'] ?? null) : 'آخرین دریافت اطلاعات ناموفق بود؛ اطلاعات ممکن است قدیمی باشد',
            'reason' => $manager && !$ok ? $this->reasonText($refresh) : null,
            'manage' => $manager,
        ];
    }

    private function refreshState(Launchpad $row): array
    {
        $refresh = is_array($row->settings['refresh'] ?? null) ? $row->settings['refresh'] : [];
        $state = config('launchpad.refresh.statuses.' . ($refresh['status'] ?? 'pending')) ?? config('launchpad.refresh.statuses.pending');

        return [
            'status' => $state['label'],
            'variant' => $state['variant'],
            'at' => isset($refresh['at']) ? convertToPersian(toJalaliSmart($refresh['at'])) : null,
            'reason' => ($refresh['status'] ?? 'pending') === 'ok' ? null : $this->reasonText($refresh),
        ];
    }

    private function reasonText(array $refresh): ?string
    {
        $reason = $refresh['reason'] ?? null;

        return is_string($reason) ? config("launchpad.refresh.reasons.{$reason}") : null;
    }

    private function tone(bool $warning): array
    {
        return $warning
            ? ['text' => 'text-[var(--md-sys-color-error)]', 'fill' => 'bg-[var(--md-sys-color-error)]']
            : ['text' => 'text-[var(--md-sys-color-primary)]', 'fill' => 'bg-[var(--md-sys-color-primary)]'];
    }
}
