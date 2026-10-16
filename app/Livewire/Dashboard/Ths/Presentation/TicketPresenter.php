<?php

namespace App\Livewire\Dashboard\Ths\Presentation;

use App\Filament\Resources\ThsResource\Enums\TicketPriority;
use App\Livewire\Dashboard\Ths\Actions\AssignTicketAction;
use App\Livewire\Dashboard\Ths\Actions\ReleaseTicketAction;
use App\Livewire\Dashboard\Ths\Actions\RerouteTicketAction;
use App\Models\Department;
use App\Models\Reply;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Ticket\TicketInsightsService;
use App\Traits\RiskEscalationChip;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class TicketPresenter
{
    use RiskEscalationChip;

    private const STATUS_ORDER = ['open', 'in-progress', 'closed'];

    private const QUICK_REPLIES = ['در حال بررسی هستم', 'لطفاً اطلاعات بیشتری ارسال کنید', 'مشکل برطرف شد؛ لطفاً تأیید کنید'];

    private const BALL_TONES = [
        'mine' => ['bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]', 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]'],
        'wait' => ['bg-[var(--md-sys-color-surface-container-low)] text-[var(--md-sys-color-on-surface)]', 'bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)]'],
        'rate' => ['bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]', 'bg-[var(--md-sys-color-tertiary)] text-[var(--md-sys-color-on-tertiary)]'],
        'closed' => ['bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]', 'bg-[var(--md-sys-color-secondary)] text-[var(--md-sys-color-on-secondary)]'],
        'error' => ['bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]', 'bg-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-error)]'],
    ];

    private const SYSTEM_ENTRIES = [
        'assign' => ['person_add', 'bg-[color-mix(in_srgb,var(--md-sys-color-primary-container)_60%,transparent)] text-[var(--md-sys-color-on-primary-container)]'],
        'reroute' => ['alt_route', 'bg-[color-mix(in_srgb,var(--md-sys-color-tertiary-container)_70%,transparent)] text-[var(--md-sys-color-on-tertiary-container)]'],
        'release' => ['undo', 'bg-[color-mix(in_srgb,var(--md-sys-color-warning-container)_80%,transparent)] text-[var(--md-sys-color-on-warning-container)]'],
    ];

    public function formatId(?array $ticket): string
    {
        if (!$ticket) return '';

        $prefix = $ticket['extra']['target_department'] ?? 'T';

        return sprintf('%s-%s-%04d', strtoupper($prefix), Carbon::parse($ticket['created_at'])->format('ym'), (int) $ticket['id']);
    }

    public function priorityMeta(string $priority): ?array
    {
        return match ($priority) {
            'low'    => ['color' => 'text-[var(--md-sys-color-primary)]', 'bg' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]', 'icon' => 'low_priority', 'title' => TicketPriority::Low->getLabel()],
            'medium' => ['color' => 'text-[var(--md-sys-color-secondary)]', 'bg' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]', 'icon' => 'drag_handle', 'title' => TicketPriority::Medium->getLabel()],
            'high'   => ['color' => 'text-[var(--md-sys-color-error)]', 'bg' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]', 'icon' => 'priority_high', 'title' => TicketPriority::High->getLabel()],
            default  => null,
        };
    }

    public function statusMeta(string $status): ?array
    {
        return match ($status) {
            'open'        => ['icon' => 'pending', 'textColor' => 'text-[var(--md-sys-color-primary)]', 'bg' => 'bg-[var(--md-sys-color-primary-container)]', 'title' => 'باز', 'pulse' => true, 'spin' => false],
            'in-progress' => ['icon' => 'sync', 'textColor' => 'text-[var(--md-sys-color-tertiary)]', 'bg' => 'bg-[var(--md-sys-color-tertiary-container)]', 'title' => 'در حال بررسی', 'pulse' => false, 'spin' => true],
            'closed'      => ['icon' => 'check_circle', 'textColor' => 'text-[var(--md-sys-color-secondary)]', 'bg' => 'bg-[var(--md-sys-color-secondary-container)]', 'title' => 'بسته شده', 'pulse' => false, 'spin' => false],
            default       => null,
        };
    }

    public function formatTimestamp(?array $ticket, string $col): string
    {
        if (!$ticket || !isset($ticket[$col])) return 'نامشخص';
        $at = Carbon::parse($ticket[$col]);

        return $at->diffInDays(Carbon::now()) >= 30 ? toJalali($at, 'j F Y') : toJalaliRelative($at);
    }

    public function replyTime(?CarbonInterface $at): string
    {
        $at = $at?->copy()->setTimezone(config('app.timezone'));

        return $at ? toJalali($at, $at->isToday() ? 'H:i' : 'Y/m/d H:i') : '';
    }

    public function requestAreaLabel(?string $requestType, ?string $requestArea, ?string $department = null): string
    {
        return Ticket::getCustomRequestAreaLabel($requestType, $requestArea, $department) ?: '—';
    }

    public function requestTypeLabel(?string $requestType, ?string $department = null): string
    {
        return Ticket::getCustomRequestTypeLabel($requestType, $department) ?: '—';
    }

    public function typicalHint(array $map, ?string $type, ?string $area): ?string
    {
        $days = filled($area) ? app(TicketInsightsService::class)->typicalCloseDays($map, $type, $area) : null;

        return $days ? __('resources/ths/strings.insight.typical_create', ['days' => convertToPersian($days)]) : null;
    }

    public function requestAreaIcon(?string $area, ?string $department = null, string $fallback = 'location_on'): string
    {
        return $area ? Ticket::getCustomMaterialIconForArea($area, $department) : $fallback;
    }

    public function deadlineChip(\DateTimeInterface|string|null $deadline, \DateTimeInterface|string|null $completionDate, string $status): ?array
    {
        if (!$deadline) {
            return null;
        }

        $deadlineAt = Carbon::parse($deadline);
        $reference = $completionDate ? Carbon::parse($completionDate) : now();
        $diff = $reference->diff($deadlineAt);
        $duration = convertToPersian($diff->days) . ' روز و ' . convertToPersian($diff->h) . ' ساعت';
        $isLate = $reference->gt($deadlineAt);

        if ($completionDate) {
            return [
                'icon' => $isLate ? 'event_busy' : 'check_circle',
                'text' => $isLate ? "با تأخیر: {$duration}" : "پیش از موعد: {$duration}",
                'classes' => $this->riskToneClasses($isLate ? 'error' : 'success'),
            ];
        }

        if ($status === 'closed') {
            return null;
        }

        return [
            'icon' => $isLate ? 'event_busy' : 'schedule',
            'text' => $isLate ? "سررسید گذشته: {$duration}" : "تا سررسید: {$duration}",
            'classes' => $this->riskToneClasses($isLate ? 'error' : ($diff->days < 1 ? 'warning' : 'success')),
        ];
    }

    public function statusSteps(string $currentStatus, array $ticket = []): array
    {
        $currentIndex = array_search($currentStatus, self::STATUS_ORDER, true);
        $currentIndex = $currentIndex === false ? 0 : $currentIndex;
        $withdrawn = $this->isWithdrawn($ticket);

        $steps = [];

        foreach (self::STATUS_ORDER as $index => $status) {
            $meta = $this->statusMeta($status);
            $state = match (true) {
                $index < $currentIndex => 'done',
                $index === $currentIndex => $withdrawn ? 'error' : 'active',
                default => 'upcoming',
            };

            $steps[] = [
                'icon' => $withdrawn && $status === 'closed' ? 'cancel' : ($meta['icon'] ?? 'circle'),
                'label' => $withdrawn && $status === 'closed' ? 'لغو شده' : ($meta['title'] ?? $status),
                'state' => $state,
                'meta' => $this->stepMeta($status, $ticket),
            ];
        }

        return $steps;
    }

    public function statusTone(string $status): string
    {
        return match ($status) {
            'open' => 'primary',
            'in-progress' => 'tertiary',
            'closed' => 'secondary',
            default => 'neutral',
        };
    }

    public function quickReplies(): array
    {
        return self::QUICK_REPLIES;
    }

    public function departmentLabel(?string $code): string
    {
        return ($code ? Department::getCachedModels()->get($code)?->displayLabel() : null)
            ?? __('resources/ths/strings.fields.target_department_default');
    }

    public function replyKind(Reply|array $reply): string
    {
        $body = (string) (is_array($reply) ? ($reply['body'] ?? '') : $reply->body);

        return match (true) {
            str_starts_with($body, AssignTicketAction::REPLY_PREFIX) => 'assign',
            str_starts_with($body, RerouteTicketAction::REPLY_PREFIX) => 'reroute',
            str_starts_with($body, ReleaseTicketAction::REPLY_PREFIX) => 'release',
            default => 'reply',
        };
    }

    public function systemEntryMeta(string $kind): ?array
    {
        return isset(self::SYSTEM_ENTRIES[$kind])
            ? ['icon' => self::SYSTEM_ENTRIES[$kind][0], 'classes' => self::SYSTEM_ENTRIES[$kind][1]]
            : null;
    }

    public function deadlineRing(array $ticket): ?array
    {
        if (empty($ticket['completion_deadline']) || empty($ticket['created_at'])) {
            return null;
        }

        $created = Carbon::parse($ticket['created_at']);
        $deadline = Carbon::parse($ticket['completion_deadline']);
        $closed = ($ticket['status'] ?? null) === 'closed';
        $reference = !empty($ticket['completion_date']) ? Carbon::parse($ticket['completion_date']) : ($closed ? null : now());
        $late = $reference !== null && $reference->gt($deadline);
        $total = max(1, (int) $created->diffInSeconds($deadline, false));
        $percent = $closed || $reference === null
            ? 100
            : (int) round(min(100, max(0, (int) $created->diffInSeconds($reference, false) / $total * 100)));

        return [
            'percent' => $percent,
            'late' => $late,
            'label' => $closed ? ($late ? '!' : '✓') : convertToPersian($percent) . '٪',
            'color' => match (true) {
                $late => 'var(--md-sys-color-error)',
                $closed => 'var(--md-sys-color-success)',
                $percent >= 75 => 'var(--md-sys-color-warning)',
                default => 'var(--md-sys-color-primary)',
            },
        ];
    }

    public function ballState(array $ticket, ?User $user): array
    {
        $status = $ticket['status'] ?? 'open';
        $timeline = $ticket['timeline'] ?? [];
        $isRequester = $user !== null && (int) ($ticket['requester_id'] ?? 0) === $user->id;
        $isAssignee = $user !== null && (int) ($ticket['assigned_to'] ?? 0) === $user->id;
        $dept = $ticket['target_department_code'] ?? null;
        $canAssign = $user !== null && !$isRequester && (($dept && $user->isHeadOfDepartment($dept)) || $user->hasElevatedRole());

        return match (true) {
            $this->isWithdrawn($ticket) => $this->ball('error', 'cancel', 'لغو شد', 'این درخواست توسط درخواست‌کننده لغو شد.'),
            $status === 'closed' && $isRequester && !($ticket['satisfaction_score'] ?? 0) => $this->ball('rate', 'star', 'لطفاً ارزیابی کنید', 'نظر شما کیفیت پشتیبانی را بهتر می‌کند.', ['ثبت ارزیابی', 'rate']),
            $status === 'closed' => $this->ball('closed', 'check_circle', 'بسته شد', implode(' · ', array_filter([
                !empty($ticket['completion_date']) ? 'در ' . toJalali($ticket['completion_date'], 'j F Y') : null,
                $this->deadlineChip($ticket['completion_deadline'] ?? null, $ticket['completion_date'] ?? null, $status)['text'] ?? null,
            ]))),
            $status === 'in-progress' && $isAssignee => $this->ball('mine', 'sports_score', 'نوبت شماست — پاسخ دهید یا ببندید', $timeline['response'] ?? null, ['پاسخ', 'response']),
            $status === 'in-progress' => $this->ball('wait', 'person', 'در دست ' . ($ticket['assignee']['name'] ?? '—'), $timeline['response'] ?? null),
            $canAssign => $this->ball('mine', 'person_add', 'نوبت شماست — کارشناس را تخصیص دهید', 'تا زمان تخصیص، درخواست در صف واحد می‌ماند.', ['تخصیص', 'response']),
            default => $this->ball('wait', 'hourglass_top', 'در انتظار تخصیص توسط سرپرست واحد', $timeline['typical'] ?? null),
        };
    }

    private function ball(string $tone, string $icon, string $title, ?string $sub, ?array $cta = null): array
    {
        return [
            'tone' => $tone,
            'icon' => $icon,
            'title' => $title,
            'sub' => $sub ?: null,
            'cta' => $cta,
            'classes' => self::BALL_TONES[$tone][0],
            'iconClasses' => self::BALL_TONES[$tone][1],
        ];
    }

    private function isWithdrawn(array $ticket): bool
    {
        return ($ticket['status'] ?? null) === 'closed' && ($ticket['action_result'] ?? null) === Ticket::WITHDRAWN_RESULT;
    }

    private function stepMeta(string $status, array $ticket): ?string
    {
        return match ($status) {
            'open' => !empty($ticket['created_at']) ? toJalali($ticket['created_at'], 'j F Y') : null,
            'in-progress' => !empty($ticket['completion_deadline']) ? 'مهلت: ' . toJalali($ticket['completion_deadline'], 'j F Y') : null,
            default => !empty($ticket['completion_date']) ? toJalali($ticket['completion_date'], 'j F Y') : null,
        };
    }
}
