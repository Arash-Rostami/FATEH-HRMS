<?php

namespace App\Services\Search;

use App\Models\Ad;
use App\Models\Authority;
use App\Models\Credential;
use App\Models\DMS;
use App\Models\Event;
use App\Models\FAQ;
use App\Models\Feed;
use App\Models\Link;
use App\Models\Message;
use App\Models\Onboarding;
use App\Models\Photo;
use App\Models\Post;
use App\Models\Report;
use App\Models\Resource;
use App\Models\Suggestion;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ContentService
{
    /** Rows returned per module group (MySQL already ranked + limited to this). */
    protected int $groupLimit = 5;

    public function search(string $query): array
    {
        $query = $this->normalize($query);

        if (Str::length($query) < 2) {
            return [];
        }

        $tokens = array_values(array_filter(explode(' ', $query), fn ($t) => $t !== ''));

        if ($tokens === []) {
            return [];
        }

        return collect($this->resources())
            ->map(fn (array $resource) => $this->searchResource($resource, $query, $tokens))
            ->filter(fn (array $group) => $group['items'] !== [])
            ->sortByDesc('score')
            ->flatMap(fn (array $group) => $group['items'])
            ->values()
            ->toArray();
    }

    protected function searchResource(array $resource, string $query, array $tokens): array
    {
        /** @var Builder $builder */
        $builder = $resource['model']::query();

        // 1. Per-model visibility scope — wrapped in its OWN group so a scope that
        //    Result: WHERE (scope) AND (token1) AND (token2) ...
        if ($scope = $resource['scope'] ?? null) {
            $builder->where(function (Builder $q) use ($scope) {
                $scope($q);
            });
        }

        // 2. Coarse filter — every token must appear in at least one column.
        foreach ($tokens as $token) {
            $builder->where(function (Builder $q) use ($resource, $token) {
                foreach ($resource['columns'] as $column) {
                    $q->orWhere($column, 'like', '%' . $token . '%');
                }
            });
        }

        // 3. MySQL-side relevance ranking + trimming.
        [$relevanceSql, $bindings] = $this->relevanceExpression($resource, $query, $tokens);

        $rows = $builder
            ->selectRaw('*, (' . $relevanceSql . ') as search_relevance', $bindings)
            ->orderByDesc('search_relevance')
            ->orderByDesc($resource['orderBy'] ?? 'id')
            ->limit($this->groupLimit)
            ->get();

        $items = $rows->map(fn ($row) => $this->shapeRow($row, $resource))->all();

        return [
            'type'  => $resource['type'],
            'group' => $resource['group'],
            'icon'  => $resource['icon'],
            'score' => (int) ($rows->max('search_relevance') ?? 0),
            'items' => $items,
        ];
    }

    /**
     * Relevance score expression + bindings.
     * exact title 120 · title prefix 40 · token in title 40 · token in any column 8
     */
    protected function relevanceExpression(array $resource, string $query, array $tokens): array
    {
        $titleColumn = is_string($resource['titleField'])
            ? $resource['titleField']
            : $resource['columns'][0];

        // Fold casing + Persian ي/ك on a backticked column (MySQL).
        $norm  = fn (string $column) => "REPLACE(REPLACE(LOWER(`{$column}`), 'ي', 'ی'), 'ك', 'ک')";
        $title = $norm($titleColumn);

        $parts    = [];
        $bindings = [];

        $parts[]    = "CASE WHEN {$title} = ? THEN 120 ELSE 0 END";
        $bindings[] = $query;

        $parts[]    = "CASE WHEN {$title} LIKE ? THEN 40 ELSE 0 END";
        $bindings[] = $query . '%';

        foreach ($tokens as $token) {
            $parts[]    = "CASE WHEN {$title} LIKE ? THEN 40 ELSE 0 END";
            $bindings[] = '%' . $token . '%';

            foreach ($resource['columns'] as $column) {
                $parts[]    = 'CASE WHEN ' . $norm($column) . ' LIKE ? THEN 8 ELSE 0 END';
                $bindings[] = '%' . $token . '%';
            }
        }

        return [implode(' + ', $parts), $bindings];
    }

    protected function shapeRow($row, array $resource): array
    {
        $title = $this->resolveField($row, $resource['titleField']);

        return [
            'id'       => $row->getKey(),
            'title'    => $title !== '' ? superClean($title, 80) : '—',
            'subtitle' => $this->makeSubtitle($row, $resource),
            'icon'     => $resource['icon'],
            'group'    => $resource['group'],
            'type'     => $resource['type'],
            'action'   => ($resource['action'])($row),
            'score'    => (int) ($row->search_relevance ?? 0),
        ];
    }

    protected function makeSubtitle($row, array $resource): string
    {
        $field = $resource['subtitleField'] ?? null;

        if (! $field) {
            return $resource['group'];
        }

        return superClean($this->resolveField($row, $field), 120) ?: $resource['group'];
    }

    protected function resolveField($row, string|callable $field): string
    {
        if (is_callable($field)) {
            return (string) $field($row);
        }

        return (string) ($row->{$field} ?? '');
    }

    protected function normalize(string $text): string
    {
        $text = Str::lower(trim($text));
        $text = strtr($text, ['ي' => 'ی', 'ك' => 'ک']);

        return preg_replace('/\s+/u', ' ', $text);
    }

    /**
     * Searchable resource registry — one entry per data-bearing dashboard module.
     *
     * PERMISSIONS: each `scope` MUST replicate that module's own listing query
     * (scopes marked "verified" were copied from live code; confirm the rest).
     * `null` = the resource is org-wide (no per-user restriction).
     */
    protected function resources(): array
    {
        $tab   = fn (string $tab, int $id) => 'url:' . route('dashboard', ['tab' => $tab, 'open' => $id], false);
        $route = fn (string $name, int $id) => 'url:' . route($name, ['open' => $id], false);
        $url   = fn (string $path) => 'url:' . $path;
        $me    = (int) auth()->id();

        return [
            // ============ TAB modules (/dashboard?tab=...) ============
            [
                'type' => 'post', 'group' => 'پست و اعلانات', 'icon' => 'campaign',
                'model' => Post::class, 'columns' => ['title', 'body'],
                'titleField' => 'title', 'subtitleField' => 'body',
                'scope' => null,
                'action' => fn ($r) => $tab('post', $r->getKey()),
            ],
            [
                'type' => 'feed', 'group' => 'اخبار و فیدها', 'icon' => 'rss_feed',
                'model' => Feed::class, 'columns' => ['content', 'category'],
                'titleField' => fn ($r) => $r->category ?: superClean((string) $r->content, 40),
                'subtitleField' => 'content',
                'scope' => null,
                'action' => fn ($r) => $tab('feed', $r->getKey()),
            ],
            [
                'type' => 'event', 'group' => 'تقویم و رویدادها', 'icon' => 'calendar_month',
                'model' => Event::class, 'columns' => ['title', 'description'],
                'titleField' => 'title', 'subtitleField' => 'description', 'orderBy' => 'date',
                'scope' => fn (Builder $q) => $q->where('user_id', $me),
                'action' => fn ($r) => $tab('calendar', $r->getKey()),
            ],
            [
                'type' => 'report', 'group' => 'گزارشات', 'icon' => 'show_chart',
                'model' => Report::class, 'columns' => ['title', 'description'],
                'titleField' => 'title', 'subtitleField' => 'description',
                'scope' => fn (Builder $q) => $q->where('active', 1),
                'action' => fn ($r) => $tab('reports', $r->getKey()),
            ],
            [
                'type' => 'gallery', 'group' => 'گالری تصاویر', 'icon' => 'photo_library',
                'model' => Photo::class, 'columns' => ['title', 'description'],
                'titleField' => 'title', 'subtitleField' => 'description', 'orderBy' => 'event_date',
                'scope' => null,
                'action' => fn ($r) => $tab('gallery', $r->getKey()),
            ],
            [
                'type' => 'faq', 'group' => 'پرسش‌های متداول', 'icon' => 'help',
                'model' => FAQ::class, 'columns' => ['question', 'answer', 'category'],
                'titleField' => 'question', 'subtitleField' => 'answer',
                'scope' => null,
                'action' => fn ($r) => $tab('faqs', $r->getKey()),
            ],
            [
                'type' => 'link', 'group' => 'لینک‌ها و ابزارها', 'icon' => 'open_in_new',
                'model' => Link::class, 'columns' => ['url_title', 'url_description', 'link'],
                'titleField' => 'url_title', 'subtitleField' => 'url_description',
                'scope' => null,
                'action' => fn ($r) => $tab('links', $r->getKey()),
            ],

            // ============ PAGE modules (own route ?open=...) ============
            [
                'type' => 'dms', 'group' => 'مدیریت اسناد', 'icon' => 'folder_managed',
                'model' => DMS::class, 'columns' => ['title', 'code'],
                'titleField' => 'title', 'subtitleField' => 'code',
                'scope' => fn (Builder $q) => $q->visibleToUser(),
                'action' => fn ($r) => $route('dms', $r->getKey()),
            ],
            [
                'type' => 'ticket',
                'group' => 'سیستم تیکت',
                'icon' => 'support_agent',
                'model' => Ticket::class,
                'columns' => ['request_subject', 'description', 'request_type'],
                'titleField' => 'request_subject',
                'subtitleField' => 'description',
                'scope' => fn (Builder $q) => $q->where(fn (Builder $query) =>
                $query->where('requester_id', $me)->orWhere('assigned_to', $me)
                ),
                'action' => fn ($r) => $route('ths', $r->getKey()),
            ],
            [
                'type' => 'task',
                'group' => 'تسک بورد',
                'icon' => 'view_kanban',
                'model' => Task::class,
                'columns' => ['title', 'description'],
                'titleField' => 'title',
                'subtitleField' => 'description',
                'scope' => fn (Builder $q) => $q->where(fn (Builder $query) =>
                $query->where('user_id', $me)->orWhere('assigned_to', $me)
                ),
                'action' => fn ($r) => $route('tasks', $r->getKey()),
            ],
            [
                'type' => 'suggestion', 'group' => 'پیشنهادات', 'icon' => 'lightbulb',
                'model' => Suggestion::class, 'columns' => ['title', 'description', 'purpose'],
                'titleField' => 'title', 'subtitleField' => 'description',
                'scope' => fn (Builder $q) => $q->where('user_id', $me),
                'action' => fn ($r) => $route('suggestion', $r->getKey()),
            ],
            [
                'type' => 'ad', 'group' => 'فرصت‌های شغلی', 'icon' => 'work',
                'model' => Ad::class, 'columns' => ['position', 'skill', 'experience'],
                'titleField' => 'position', 'subtitleField' => 'skill',
                'scope' => fn (Builder $q) => $q->where('active', 1),
                'action' => fn ($r) => $route('ads', $r->getKey()),
            ],
            [
                'type' => 'authority', 'group' => 'اختیارات', 'icon' => 'verified_user',
                'model' => Authority::class, 'columns' => ['sub_duty', 'details'],
                'titleField' => 'sub_duty', 'subtitleField' => 'details',
                'scope' => null, // confirm
                'action' => fn ($r) => $route('authority', $r->getKey()),
            ],
            [
                'type' => 'reservation', 'group' => 'رزرو فضا و منابع', 'icon' => 'meeting_room',
                'model' => Resource::class, 'columns' => ['name', 'type'],
                'titleField' => 'name', 'subtitleField' => 'type',
                'scope' => fn (Builder $q) => $q->where('status', 'active'),
                'action' => fn ($r) => $route('reservation', $r->getKey()),
            ],

            // ============ Users & profile-area modules ============
            [
                'type' => 'people', 'group' => 'همکاران', 'icon' => 'badge',
                'model' => User::class, 'columns' => ['name', 'email'],
                'titleField' => 'name', 'subtitleField' => 'email',
                'scope' => fn (Builder $q) => $q->whereKeyNot($me),
                'action' => fn ($r) => $route('contact', $r->getKey()),
            ],
            [
                'type' => 'credential', 'group' => 'دسترسی‌های سازمانی', 'icon' => 'vpn_key',
                'model' => Credential::class, 'columns' => ['app_name', 'username', 'note', 'link'],
                'titleField' => 'app_name', 'subtitleField' => 'username',
                'scope' => fn (Builder $q) => $q->where('user_id', $me),
                'action' => fn ($r) => $url('/profile?tab=credentials&open=' . $r->getKey()),
            ],
            [
                'type' => 'onboarding', 'group' => 'آنبوردینگ', 'icon' => 'apartment',
                'model' => Onboarding::class, 'columns' => ['welcome', 'mission', 'vision'],
                'titleField' => fn ($r) => superClean((string) $r->welcome, 40) ?: 'آنبوردینگ',
                'subtitleField' => 'mission',
                'scope' => fn (Builder $q) => $q->where('is_active', 1),
                'action' => fn ($r) => $url('/profile?tab=onboarding'),
            ],
            [
                'type' => 'message', 'group' => 'پیام‌رسان داخلی', 'icon' => 'perm_contact_calendar',
                'model' => Message::class, 'columns' => ['body'],
                'titleField' => fn ($r) => superClean((string) $r->body, 40) ?: 'پیام',
                'subtitleField' => 'body',
                'scope' => fn (Builder $q) => $q->where(fn (Builder $query) =>
                $query->where('sender_id', $me)->orWhere('recipient_id', $me)
                ),
                // open the conversation with the OTHER party (works from either side)
                'action' => fn ($r) => $route(
                    'contact',
                    (int) $r->sender_id === (int) auth()->id() ? (int) $r->recipient_id : (int) $r->sender_id
                ),
            ],
        ];
    }
}
