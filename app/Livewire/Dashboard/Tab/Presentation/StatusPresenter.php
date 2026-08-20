<?php

namespace App\Livewire\Dashboard\Tab\Presentation;

use App\Models\SkillUser;
use App\Models\User;
use Illuminate\Support\Collection;

class StatusPresenter
{
    public function orgTree(Collection $users): array
    {
        $byDept = $users->groupBy(fn (User $u) => $u->profile?->department?->code ?? '__none__');

        $apex = collect();
        $byCode = [];

        foreach ($byDept as $code => $members) {
            $sorted = $members->sortBy(fn (User $u) => $u->rank());
            $apex = $apex->merge($sorted->filter(fn (User $u) => $u->rank() === 1)->values());

            $remaining = $sorted->filter(fn (User $u) => $u->rank() !== 1)->values();

            if ($remaining->isEmpty()) {
                continue;
            }

            $department = $remaining->first()?->profile?->department;
            $rest = $remaining->slice(1)->values();

            $byCode[$code] = [
                'code' => $code,
                'label' => $department?->displayLabel() ?? 'سایر',
                'department' => $department,
                'head' => $remaining->first(),
                'count' => $remaining->count(),
                'level' => $department?->level ?? 1,
                'subordinateTo' => $department?->subordinate_to,
                'allRemaining' => $rest,
                ...$this->groupByUnit($rest),
                'children' => [],
            ];
        }

        return [
            'apex' => $apex->values(),
            ...$this->buildDepartmentBands($byCode),
            'byCode' => $byCode,
        ];
    }

    public function directReports(array $tree, User $me): Collection
    {
        $myRank = $me->rank();

        if ($myRank === 1) {
            return collect($tree['byCode'])
                ->map(fn (array $d) => $d['head'])
                ->filter()
                ->values();
        }

        $dept = $tree['byCode'][$me->profile?->department?->code ?? '__none__'] ?? null;

        if ($dept === null) {
            return collect();
        }

        if ($dept['head']?->id === $me->id) {
            return $dept['allRemaining'];
        }

        return $dept['allRemaining']->filter(fn (User $u) => $u->rank() > $myRank)->values();
    }

    public function chartData(array $orgTree, Collection $users): array
    {
        $apex = $orgTree['apex'];
        $level1 = $orgTree['level1'];
        $count = count($level1);
        $mid = intdiv($count, 2);
        $bestMid = $mid;
        $minDist = $count;

        for ($i = 1; $i < $count; $i++) {
            if ($level1[$i - 1]['count'] === 1 && $level1[$i]['count'] === 1 && abs($i - $mid) < $minDist) {
                $bestMid = $i;
                $minDist = abs($i - $mid);
            }
        }

        $depts = [...array_slice($level1, 0, $bestMid), ...$orgTree['level2'], ...array_slice($level1, $bestMid)];
        $hasApex = $apex->isNotEmpty();
        $hasDepts = !empty($depts);

        $stats = [
            ['icon' => 'groups', 'label' => 'همکاران', 'value' => $users->count()],
            ['icon' => 'corporate_fare', 'label' => 'دپارتمان', 'value' => count($orgTree['byCode'])],
            ['icon' => 'stars', 'label' => 'رأس سازمان', 'value' => $apex->count()],
        ];

        return [
            'apex' => $apex,
            'depts' => $depts,
            'hasApex' => $hasApex,
            'hasDepts' => $hasDepts,
            'stats' => $stats,
        ];
    }

    public function nodeData(User $user, string $tier = 'member'): array
    {
        $p = presence($user->presence);
        $position = $user->profile?->displayPosition ?? 'کارشناس';

        $rankMeta = match ($user->profile?->position) {
            'chairman', 'ceo' => [
                'icon' => 'stars',
                'chip' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] ring-1 ring-[var(--md-sys-color-primary)]/25',
            ],
            'c-manager' => [
                'icon' => 'manage_accounts',
                'chip' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] ring-1 ring-[var(--md-sys-color-tertiary)]/25',
            ],
            'manager', 'supervisor', 'senior' => [
                'icon' => 'supervisor_account',
                'chip' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] ring-1 ring-[var(--md-sys-color-secondary)]/25',
            ],
            default => [
                'icon' => 'person',
                'chip' => 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)] ring-1 ring-[var(--md-sys-color-outline-variant)]/50',
            ],
        };

        $tierConfig = match ($tier) {
            'apex' => [
                'width' => 'w-52 min-w-[13rem]',
                'avatar' => 'w-[4.25rem] h-[4.25rem]',
                'surface' => 'bg-gradient-to-b from-[var(--md-sys-color-primary-container)]/35 via-[var(--md-sys-color-surface-container-lowest)] to-[var(--md-sys-color-surface-container-lowest)]',
                'border' => 'border-[var(--md-sys-color-primary)]/45 hover:border-[var(--md-sys-color-primary)]',
                'accent' => 'bg-gradient-to-r from-[var(--md-sys-color-primary)] via-[var(--md-sys-color-tertiary)] to-[var(--md-sys-color-primary)]',
                'shadow' => 'shadow-[0_8px_28px_-8px_color-mix(in_srgb,var(--md-sys-color-primary)_28%,transparent)]',
                'ring' => 'ring-[var(--md-sys-color-primary)]/30',
                'spacing' => '',
            ],
            'head' => [
                'width' => 'w-44 min-w-[11rem]',
                'avatar' => 'w-12 h-12',
                'surface' => 'bg-[var(--md-sys-color-surface-container-lowest)]',
                'border' => 'border-[var(--md-sys-color-secondary)]/40 hover:border-[var(--md-sys-color-secondary)]',
                'accent' => 'bg-[var(--md-sys-color-secondary)]',
                'shadow' => 'shadow-[0_5px_18px_-5px_color-mix(in_srgb,var(--md-sys-color-secondary)_20%,transparent)]',
                'ring' => 'ring-[var(--md-sys-color-secondary)]/25',
                'spacing' => '',
            ],
            'tier2' => [
                'width' => 'w-40 min-w-[10rem]',
                'avatar' => 'w-10 h-10',
                'surface' => 'bg-[var(--md-sys-color-surface)]',
                'border' => 'border-[var(--md-sys-color-outline-variant)]/65 hover:border-[var(--md-sys-color-outline)]',
                'accent' => 'bg-transparent',
                'shadow' => 'shadow-[0_2px_10px_-3px_rgba(0,0,0,0.06)]',
                'ring' => 'ring-[var(--md-sys-color-outline-variant)]/40',
                'spacing' => 'mt-5',
            ],
            default => [
                'width' => 'w-40 min-w-[10rem]',
                'avatar' => 'w-10 h-10',
                'surface' => 'bg-[var(--md-sys-color-surface)]',
                'border' => 'border-[var(--md-sys-color-outline-variant)]/65 hover:border-[var(--md-sys-color-outline)]',
                'accent' => 'bg-transparent',
                'shadow' => 'shadow-[0_2px_10px_-3px_rgba(0,0,0,0.06)]',
                'ring' => 'ring-[var(--md-sys-color-outline-variant)]/40',
                'spacing' => '',
            ],
        };

        $presenceBg = match ($p->color()) {
            'green', 'emerald', 'success' => 'bg-emerald-500',
            'amber', 'yellow', 'warning' => 'bg-amber-500',
            'red', 'rose', 'danger' => 'bg-rose-500',
            'blue', 'sky', 'info' => 'bg-sky-500',
            default => 'bg-slate-400',
        };

        return [
            'p' => $p,
            'obscured' => $p->isObscured(),
            'img' => $user->getProfileImageUrl() ?? $user->getInitialsAvatarUrl(),
            'position' => $position,
            'deptName' => $user->profile?->department?->displayLabel(),
            'rankMeta' => $rankMeta,
            'tierConfig' => $tierConfig,
            'presenceBg' => $presenceBg,
            'aboutPayload' => $this->aboutPayload($user),
        ];
    }

    public function gridData(User $user, ?int $skillId): array
    {
        $p = presence($user->presence);
        $obscured = $p->isObscured();
        $sms = $user->sms_number;
        $ext = $user->getTodaysDeskExtension();
        $reserved = $user->getTodaysReservationsLabel();
        $summary = $user->getTodaysStatusSummary();

        $skillTier = ($skillId !== null && $user->skill_tier_endorsements_count !== null)
            ? (new SkillUser([
                'endorsements_count' => $user->skill_tier_endorsements_count,
                'last_used_at' => $user->skill_tier_last_used_at,
            ]))->stateTier()
            : null;

        $deptName = $user->profile?->department?->displayLabel();
        $details = $user->profile?->detailsMap();
        $orgTitle = collect([$deptName, $details?->get('unit'), $details?->get('section')])->filter()->implode(' › ');

        return [
            'p' => $p,
            'obscured' => $obscured,
            'sms' => $sms,
            'ext' => $ext,
            'reserved' => $reserved,
            'summary' => $summary,
            'skillTier' => $skillTier,
            'hasBar' => $obscured || $sms || $ext || $reserved || $skillTier !== null,
            'hasCall' => !$obscured && ($sms || $ext),
            'orgTitle' => $orgTitle,
            'aboutPayload' => $this->aboutPayload($user),
        ];
    }

    private function groupByUnit(Collection $members): array
    {
        $direct = collect();
        $units = [];

        foreach ($members->groupBy(fn (User $u) => $u->profile?->detailsMap()->get('unit') ?: '') as $unitName => $unitMembers) {
            if ($unitName === '') {
                $direct = $unitMembers->values();
                continue;
            }

            $sectionDirect = collect();
            $sections = [];

            foreach ($unitMembers->groupBy(fn (User $u) => $u->profile?->detailsMap()->get('section') ?: '') as $sectionName => $sectionMembers) {
                if ($sectionName === '') {
                    $sectionDirect = $sectionMembers->values();
                    continue;
                }

                $sections[] = ['name' => $sectionName, 'members' => $sectionMembers->values()];
            }

            usort($sections, fn (array $a, array $b) => strcmp($a['name'], $b['name']));

            $units[] = ['name' => $unitName, 'members' => $sectionDirect, 'sections' => $sections];
        }

        usort($units, fn (array $a, array $b) => strcmp($a['name'], $b['name']));

        return ['members' => $direct, 'units' => $units];
    }

    private function buildDepartmentBands(array $byCode): array
    {
        $childrenOf = [];

        foreach ($byCode as $code => $node) {
            if ($node['level'] !== 0 && $this->hasVisibleParent($node, $byCode)) {
                $childrenOf[$node['subordinateTo']][] = $code;
            }
        }

        $sort = fn (array $a, array $b) => ($a['head']?->rank() ?? PHP_INT_MAX) <=> ($b['head']?->rank() ?? PHP_INT_MAX)
            ?: strcmp($a['label'], $b['label']);

        $build = function (string $code, array $seen = []) use (&$build, $byCode, $childrenOf, $sort): array {
            $node = $byCode[$code];

            if (isset($seen[$code])) {
                $node['children'] = [];
                return $node;
            }

            $seen[$code] = true;
            $children = $childrenOf[$code] ?? [];
            usort($children, fn (string $a, string $b) => $sort($byCode[$a], $byCode[$b]));
            $node['children'] = array_map(fn (string $childCode) => $build($childCode, $seen), $children);

            return $node;
        };

        $roots = ['level1' => [], 'level2' => []];

        foreach ($byCode as $code => $node) {
            if ($this->hasVisibleParent($node, $byCode) || !in_array($node['level'], [1, 2], true)) {
                continue;
            }

            $roots["level{$node['level']}"][] = $build($code);
        }

        usort($roots['level1'], $sort);
        usort($roots['level2'], $sort);

        return $roots;
    }

    private function hasVisibleParent(array $node, array $byCode): bool
    {
        $parentCode = $node['subordinateTo'];

        return $parentCode !== null
            && $parentCode !== $node['code']
            && isset($byCode[$parentCode])
            && $byCode[$parentCode]['level'] !== 0;
    }

    private function aboutPayload(User $user): array
    {
        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'position' => $user->profile?->displayPosition ?? 'کارشناس',
                'image' => $user->getProfileImageUrl() ?? $user->getInitialsAvatarUrl(),
                'department' => $user->profile?->department?->displayLabel(),
                'division' => $user->profile?->detailsMap()->get('unit'),
                'section' => $user->profile?->detailsMap()->get('section'),
                'favoriteColors' => $user->profile?->favorite_colors ?? [],
            ],
            'aboutMe' => $this->flattenAboutMe($user->profile?->about_me ?? []),
        ];
    }

    private function flattenAboutMe(array $aboutMe): array
    {
        return collect($aboutMe)
            ->map(fn ($v) => is_array($v) ? implode(', ', array_map(fn ($x) => is_array($x) ? implode(', ', $x) : $x, $v)) : $v)
            ->toArray();
    }
}
