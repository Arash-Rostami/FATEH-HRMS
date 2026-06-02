<?php

namespace App\Livewire\Dashboard\Suggestion\Presentation;

use App\Models\Review;
use App\Models\Suggestion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class SuggestionPresenter
{
    private const CEO_DEPT_ID = 'MA';
    private const REVIEW_DEADLINE_DAYS = 5;

    private const DEFAULT_FEEDBACK = 'unknown';
    private const WORKFLOW_STEPS = [
        1 => ['ثبت پیشنهاد', 'ثبت اولیه توسط کاربر و تعیین واحدهای ذی‌نفع', 'lightbulb'],
        2 => ['نظر تیم / مدیر مستقیم', 'تأیید خودکار پس از ۴۸ ساعت در صورت عدم پاسخ', 'group'],
        3 => ['بررسی هم‌زمان ذی‌نفعان', 'ارسال به سرپرستان واحدها — عدم پاسخ به منزله نیمه موافق', 'corporate_fare'],
        4 => ['تجمیع و ارسال به مدیریت ارشد', 'جمع‌بندی خودکار نظرات و آماده‌سازی برای تصمیم نهایی', 'summarize'],
        5 => ['تصمیم مدیریت ارشد سازمان', 'تأیید، رد یا درخواست تکمیل مجدد', 'gavel'],
        6 => ['ارجاع برای اقدام', 'صدور دستور اجرا و ارسال به واحدهای مربوطه', 'forward_to_inbox'],
        7 => ['پایان و بایگانی', 'فرآیند با موفقیت تکمیل و پیشنهاد بایگانی شد', 'inventory_2'],
    ];
    private const STAGE_META = [
        'pending' => ['surface-variant', 'on-surface-variant', 'hourglass_empty'],
        'team_remarks' => ['secondary-container', 'on-secondary-container', 'group'],
        'dept_remarks' => ['secondary-container', 'on-secondary-container', 'corporate_fare'],
        'awaiting_decision' => ['primary-container', 'on-primary-container', 'gavel'],
        'accepted' => ['tertiary-container', 'on-tertiary-container', 'check_circle'],
        'rejected' => ['error-container', 'on-error-container', 'cancel'],
        'under_review' => ['primary-container', 'on-primary-container', 'find_in_page'],
        'closed' => ['surface-variant', 'on-surface-variant', 'lock'],
    ];
    private const DEFAULT_STAGE_META = ['surface-variant', 'on-surface-variant', 'info'];
    private const PRIORITY_META = [
        'low' => ['surface-variant', 'on-surface-variant', 'arrow_drop_down', 'کم'],
        'medium' => ['secondary-container', 'on-secondary-container', 'commit', 'متوسط'],
        'high' => ['error-container', 'on-error-container', 'arrow_drop_up', 'زیاد'],
    ];
    private const DEFAULT_PRIORITY = 'low';
    private const FEEDBACK_STYLES = [
        'agree' => ['primary-container', 'on-primary-container', 'thumb_up'],
        'disagree' => ['error-container', 'on-error-container', 'thumb_down'],
        'neutral' => ['surface-variant', 'on-surface-variant', 'thumbs_up_down'],
        'unknown' => ['surface-variant', 'on-surface-variant', 'help'],
        'incomplete' => ['tertiary-container', 'on-tertiary-container', 'warning'],
    ];

    private ?Collection $reviews = null;
    private ?array $reviewsMap = null;
    private ?Review $ceoReview = null;
    private ?array $referrals = null;
    private ?array $stepMapCache = null;

    public function __construct(protected Suggestion $suggestion) {}

    public function attachmentUrl(): ?string
    {
        return $this->suggestion->attachment ? Storage::url($this->suggestion->attachment) : null;
    }

    public function currentStep(): int
    {
        return $this->stepMap()[$this->suggestion->stage] ?? 1;
    }

    public function deadlineConfig(): array
    {
        $deadline = $this->suggestion->created_at->addDays(self::REVIEW_DEADLINE_DAYS);
        $isPast = $deadline->isPast();

        return [
            'date' => $deadline,
            'passed' => $isPast,
            'formatted' => ($isPast ? ' ⚠' : '') . toJalali($deadline, 'j F Y'),
            ];
    }

    public function departmentStatuses(): array
    {
        $referrals = $this->referralDepts();
        $reviewsMap = $this->getReviewsMap();

        return array_map(
            fn($dept) => $this->buildDepartmentStatus($dept, $referrals, $reviewsMap),
            $this->suggestion->departments ?? []
        );
    }

    public function feedbackLabels(): array { return Review::FEEDBACKS; }

    public function feedbackStyle(string $type): array
    {
        return $this->resolveMeta(self::FEEDBACK_STYLES, $type, self::FEEDBACK_STYLES[self::DEFAULT_FEEDBACK]);
    }

    public function hasReferral(): bool { return !empty($this->referralDepts()); }

    public function infoRows(): array
    {
        $s = $this->suggestion;
        $deadline = $this->deadlineConfig();
        $userDep = $s->user?->profile?->department?->description ?? $s->user?->profile?->department_id ?? '-';
        $totalDeps = count((array) ($s->departments ?? []));
        $respondedDeps = $this->getValidFeedbackCount();

        return [
            $this->buildInfoRow('person', 'ثبت‌کننده', $s->user?->name ?? '—'),
            $this->buildInfoRow('corporate_fare', 'واحد ثبت‌کننده', $userDep),
            $this->buildInfoRow('calendar_today', 'تاریخ ثبت', toJalali($s->created_at, 'j F Y')),
            $this->buildInfoRow('schedule', 'مهلت بررسی', $deadline['formatted'], $deadline['passed'] ? 'warning' : 'text'),
            $this->buildInfoRow('edit_note', 'تکمیل شخصی', $this->isSelfFill() ? 'بله' : 'خیر', 'bool'),
            $this->buildInfoRow('how_to_reg', 'پاسخ‌های دریافتی', $this->formatResponseCount($totalDeps, $respondedDeps), $this->resolveResponseCountType($totalDeps)),
            $this->buildInfoRow('gavel', 'ارجاع به مدیریت ارشد', $this->sentToCeo() ? 'بله' : 'خیر', 'bool'),
            $this->buildInfoRow('forward_to_inbox', 'ارجاع برای اقدام', $this->formatReferralText(), $this->hasReferral() ? 'text' : 'bool'),
        ];
    }

    public function isSelfFill(): bool { return (bool) $this->suggestion->self_fill; }

    public function priorityConfig(): array
    {
        $priority = $this->suggestion->priority ?? self::DEFAULT_PRIORITY;
        return $this->resolveMeta(self::PRIORITY_META, $priority, self::PRIORITY_META[self::DEFAULT_PRIORITY]);
    }

    public function purposeLabels(): array { return Suggestion::PURPOSES; }

    public function referralDepts(): array
    {
        return $this->referrals ??= $this->ceoReview()?->referralDepartments()?->pluck('description')->all()
            ?? $this->ceoReview()?->referral
            ?? [];
    }

    public function requiresMyAction(): bool { return Suggestion::requiresAttentionFor($this->suggestion, auth()->user()); }

    public function reviewItems(): array
    {
        $referrals = $this->referralDepts();
        return array_map(fn($review) => $this->buildReviewItem($review, $referrals), $this->getReviews()->all());
    }

    public function ruleLabels(): array { return Suggestion::RULES; }

    public function sentToCeo(): bool
    {
        return in_array($this->suggestion->stage, ['awaiting_decision', 'accepted', 'rejected', 'under_review', 'closed'], true);
    }

    public function showDeptDetails($suggestion, $item)
    {
        $deps = $suggestion->departments ?? [];
        $count = $suggestion->departments_count ?? count($deps);
        $fallback = $suggestion->user?->profile?->department ?? '—';
        $glue = ' ⭑ ';

        return match ($item) {
            'truncated_deps' => $count ? implode($glue, array_slice($deps, 0, 2)) : $fallback,
            'all_deps' => $count ? implode($glue, $deps) : $fallback,
            'deps_count' => $count,
            default => null,
        };
    }

    public function stageBadgeClasses(): string
    {
        return match ($this->suggestion->stage) {
            'team_remarks' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
            'dept_remarks' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
            'awaiting_decision' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] border border-[var(--md-sys-color-primary)]',
            'accepted' => 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]',
            'rejected' => 'bg-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-error)]',
            'under_review' => 'bg-amber-100 text-amber-800 border border-amber-300 dark:bg-amber-900/30 dark:text-amber-200 dark:border-amber-700',
            'closed' => 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] border-2 border-[var(--md-sys-color-outline)]',
            default => 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]',
        };
    }

    public function stageConfig(): array
    {
        [$bg, $on, $icon] = $this->resolveMeta(self::STAGE_META, $this->suggestion->stage, self::DEFAULT_STAGE_META);
        return [
            'bg' => $bg, 'on' => $on, 'icon' => $icon,
            'badge_icon' => Suggestion::STAGE_ICONS[$this->suggestion->stage] ?? '',
            'label' => Suggestion::STAGES[$this->suggestion->stage] ?? $this->suggestion->stage,
        ];
    }

    public function stats(): array
    {
        return [
            $this->buildStat('موافق', 'thumb_up', 'primary', $this->suggestion->agree_count),
            $this->buildStat('مخالف', 'thumb_down', 'error', $this->suggestion->disagree_count),
            $this->buildStat('نیمه موافق', 'thumbs_up_down', 'on-surface-variant', $this->suggestion->neutral_count),
        ];
    }

    public function suggestion(): Suggestion { return $this->suggestion; }

    public function workflowItems(): array
    {
        $currentStep = $this->currentStep();
        $isRejected = $this->suggestion->stage === 'rejected';
        $userDept = $this->suggestion->user?->profile?->department_id;
        $userReview = $userDept ? ($this->getReviewsMap()[$userDept] ?? null) : null;
        $blocked = $userReview && !in_array($userReview->feedback, ['agree', 'disagree', 'neutral'], true);

        $effectiveStep = fn(int $step): int => match(true) {
            $blocked && $step === 4 => 3,
            $blocked && $step === 2 => 2,
            default => $currentStep,
        };

        return array_map(
            fn(int $step, array $config) => [
                'step' => $step, 'title' => $config[0], 'description' => $config[1], 'base_icon' => $config[2],
                ...$this->buildStepItem($step, $effectiveStep($step), $isRejected, $config[2])
            ],
            array_keys(self::WORKFLOW_STEPS),
            self::WORKFLOW_STEPS
        );
    }

    public static function workflowSteps(): array
    {
        return array_map(
            fn(int $step, array $config): array => [
                'step'        => $step,
                'title'       => $config[0],
                'description' => $config[1],
                'icon'        => $config[2],
            ],
            array_keys(self::WORKFLOW_STEPS),
            self::WORKFLOW_STEPS,
        );
    }

    protected function ceoReview(): ?Review
    {
        return $this->ceoReview ??= $this->getReviewByDeptId(self::CEO_DEPT_ID);
    }

    protected function resolveStepState(int $step, int $currentStep, bool $isRejected): string
    {
        return match(true) {
            $isRejected && $step === 5 => 'rejected',
            $step < $currentStep => 'done',
            $step === $currentStep => 'active',
            default => 'pending',
        };
    }
    protected function stepMap(): array
    {
        if ($this->stepMapCache !== null) return $this->stepMapCache;

        $this->stepMapCache = [
            'pending' => 1, 'team_remarks' => 2, 'dept_remarks' => 3,
            'awaiting_decision' => 4, 'accepted' => 5, 'rejected' => 5,
            'under_review' => 5, 'closed' => 8,
        ];

        if ($this->suggestion->stage === 'accepted' && $this->hasReferral()) {
            $this->stepMapCache['accepted'] = 6;
        }

        return $this->stepMapCache;
    }

    private function buildDepartmentStatus(string $dept, array $referrals, array $reviewsMap): array
    {
        $review = $reviewsMap[$dept] ?? null;
        $feedback = $review?->feedback ?? self::DEFAULT_FEEDBACK;
        $style = $this->feedbackStyle($feedback);

        return [
            'dept'        => $dept,
            'name'        => $review?->department?->description ?? $dept,
            'feedback'    => $feedback,
            'style'       => $style,
            'bg_class'    => 'bg-[var(--md-sys-color-' . $style[0] . ')]',
            'text_class'  => 'text-[var(--md-sys-color-' . $style[1] . ')]',
            'is_action'   => in_array($dept, $referrals, true),
            'is_complete' => $review?->complete ?? false,
            'has_review'  => $review !== null,
        ];
    }


    private function buildInfoRow(string $icon, string $label, string $value, string $type = 'text'): array
    {
        return compact('icon', 'label', 'value', 'type');
    }

    private function buildReviewItem(Review $review, array $referrals): array
    {
        $isCeo = $review->department_id === self::CEO_DEPT_ID;
        $style = $this->feedbackStyle($review->feedback);

        return [
            'review'           => $review,
            'style'            => $style,
            'bg_class'         => 'bg-[color-mix(in_srgb,var(--md-sys-color-' . $style[0] . ')_15%,transparent)]',
            'icon_bg_class'    => 'bg-[color-mix(in_srgb,var(--md-sys-color-' . $style[0] . ')_40%,transparent)]',
            'icon_text_class'  => 'text-[var(--md-sys-color-' . $style[1] . ')]',
            'badge_bg_class'   => 'bg-[var(--md-sys-color-' . $style[0] . ')]',
            'badge_text_class' => 'text-[var(--md-sys-color-' . $style[1] . ')]',
            'is_ma'            => $isCeo,
            'is_action'        => in_array($review->department_id, $referrals, true),
            'label'            => $isCeo ? 'مدیریت ارشد' : ($review->department?->description ?? $review->department_id),
            'feedback_label'   => Review::FEEDBACKS[$review->feedback] ?? $review->feedback,
        ];
    }

    private function buildStat(string $label, string $icon, string $colorContext, ?int $count): array
    {
        return [
            'count' => $count ?? 0,
            'label' => $label,
            'color' => 'text-[var(--md-sys-color-' . $colorContext . ')]',
            'icon' => $icon,
        ];
    }

    private function buildStepItem(int $step, int $currentStep, bool $isRejected, string $baseIcon): array
    {
        $state = $this->resolveStepState($step, $currentStep, $isRejected);
        return ['state' => $state, ...$this->resolveStepStyles($state, $baseIcon)];
    }

    private function formatReferralText(): string
    {
        return $this->hasReferral() ? implode('، ', $this->referralDepts()) : 'خیر';
    }

    private function formatResponseCount(int $total, int $responded): string
    {
        return $total > 0 ? "{$responded} / {$total} " : '0';
    }

    private function getReviewByDeptId(?string $id): ?Review
    {
        return $id ? ($this->getReviewsMap()[$id] ?? null) : null;
    }

    private function getReviews(): Collection
    {
        return $this->reviews ??= $this->suggestion->reviews;
    }

    private function getReviewsMap(): array
    {
        return $this->reviewsMap ??= $this->getReviews()->keyBy('department_id')->all();
    }

    private function getValidFeedbackCount(): int
    {
        return $this->getReviews()
            ->where('department_id', '!=', self::CEO_DEPT_ID)
            ->whereIn('feedback', ['agree', 'disagree', 'neutral'])
            ->count();
    }

    private function resolveMeta(array $metaMap, string $key, array $default): array
    {
        return $metaMap[$key] ?? $default;
    }

    private function resolveResponseCountType(int $total): string
    {
        return $total === 0 ? 'zero' : 'text';
    }

    private function resolveStepStyles(string $state, string $baseIcon): array
    {
        $config = $this->stepStyleConfig()[$state] ?? $this->stepStyleConfig()['pending'];
        return [
            'display_icon' => match($state) { 'done' => 'check', 'rejected' => 'cancel', default => $baseIcon },
            'badge_label' => match($state) { 'active' => 'فعال', 'done' => 'انجام شد', 'rejected' => 'رد شده', default => 'در انتظار' },
            ...$config,
        ];
    }

    private function stepStyleConfig(): array
    {
        $iconBase = 'w-10 h-10 rounded-xl flex items-center justify-center border-2 transition-all duration-300 bg-[var(--md-sys-color-surface)]';
        $cardBase = 'flex-1 rounded-xl p-4 transition-all duration-300 border';
        $titleBase = 'text-sm font-bold tracking-wide';

        return [
            'active' => [
                'badge_classes' => 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] animate-pulse',
                'icon_classes' => "{$iconBase} border-[var(--md-sys-color-primary)] text-[var(--md-sys-color-primary)] shadow-md shadow-[var(--md-sys-color-primary)]/20 scale-105",
                'card_classes' => "{$cardBase} bg-[var(--md-sys-color-primary-container)] border-r-4 border-[var(--md-sys-color-primary)]",
                'title_classes' => "{$titleBase} text-[var(--md-sys-color-on-primary-container)]",
            ],
            'done' => [
                'badge_classes' => 'bg-[var(--md-sys-color-tertiary)] text-[var(--md-sys-color-on-tertiary)]',
                'icon_classes' => "{$iconBase} border-[var(--md-sys-color-tertiary)] bg-[var(--md-sys-color-tertiary)] text-[var(--md-sys-color-on-tertiary)]",
                'card_classes' => "{$cardBase} bg-[var(--md-sys-color-surface-variant)] border-r-4 border-[var(--md-sys-color-tertiary)]",
                'title_classes' => "{$titleBase} text-[var(--md-sys-color-on-surface)]",
            ],
            'rejected' => [
                'badge_classes' => 'bg-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-error)]',
                'icon_classes' => "{$iconBase} border-[var(--md-sys-color-error)] bg-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-error)]",
                'card_classes' => "{$cardBase} bg-[color-mix(in_srgb,var(--md-sys-color-error)_8%,transparent)] border-r-4 border-[var(--md-sys-color-error)]",
                'title_classes' => "{$titleBase} text-[var(--md-sys-color-on-error)]",
            ],
            'pending' => [
                'badge_classes' => 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]',
                'icon_classes' => "{$iconBase} border-[var(--md-sys-color-outline)] text-[var(--md-sys-color-on-surface-variant)] group-hover:border-[var(--md-sys-color-primary)]",
                'card_classes' => "{$cardBase} bg-[var(--md-sys-color-surface)] border-[var(--md-sys-color-outline-variant)] hover:border-[var(--md-sys-color-outline)]",
                'title_classes' => "{$titleBase} text-[var(--md-sys-color-on-surface)]",
            ],
        ];
    }
}
