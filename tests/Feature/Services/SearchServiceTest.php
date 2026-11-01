<?php

namespace Tests\Feature\Services;

use App\Models\Ad;
use App\Models\Authority;
use App\Models\Channel;
use App\Models\ChannelMember;
use App\Models\ChannelMessage;
use App\Models\Credential;
use App\Models\Department;
use App\Models\DMS;
use App\Models\Event;
use App\Models\FAQ;
use App\Models\Feed;
use App\Models\Link;
use App\Models\Message;
use App\Models\Onboarding;
use App\Models\Photo;
use App\Models\Post;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Report;
use App\Models\Resource;
use App\Models\Skill;
use App\Models\Suggestion;
use App\Models\Task;
use App\Models\TaskDetail;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Search\ContentService;
use App\Services\Search\Contracts\SearchContext;
use App\Services\Search\NavigationService;
use App\Services\Search\Resources\AdResource;
use App\Services\Search\Resources\AuthorityResource;
use App\Services\Search\Resources\ChannelMessageResource;
use App\Services\Search\Resources\ChannelResource;
use App\Services\Search\Resources\CredentialResource;
use App\Services\Search\Resources\DmsResource;
use App\Services\Search\Resources\EventResource;
use App\Services\Search\Resources\FaqResource;
use App\Services\Search\Resources\FeedResource;
use App\Services\Search\Resources\GalleryResource;
use App\Services\Search\Resources\LinkResource;
use App\Services\Search\Resources\MessageResource;
use App\Services\Search\Resources\OnboardingResource;
use App\Services\Search\Resources\PeopleResource;
use App\Services\Search\Resources\PostResource;
use App\Services\Search\Resources\ProjectResource;
use App\Services\Search\Resources\ReportResource;
use App\Services\Search\Resources\ReservationResource;
use App\Services\Search\Resources\SkillSearchResource;
use App\Services\Search\Resources\SuggestionResource;
use App\Services\Search\Resources\TaskResource;
use App\Services\Search\Resources\TicketResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SearchServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Auth::logout();
        DB::rollBack();
        Cache::flush();
        parent::tearDown();
    }

    private function useMysql(): void
    {
        $env = base_path('.env');
        if (is_file($env)) {
            $vals = [];
            foreach (explode("\n", (string)file_get_contents($env)) as $line) {
                if (preg_match('/^\s*(DB_HOST|DB_PORT|DB_DATABASE|DB_USERNAME|DB_PASSWORD)\s*=\s*(.*)$/', $line, $m)) {
                    $vals[$m[1]] = trim(preg_replace('/\s+#.*$/', '', trim($m[2])), "\"' \t");
                }
            }
            $map = ['DB_HOST' => 'host', 'DB_PORT' => 'port', 'DB_DATABASE' => 'database', 'DB_USERNAME' => 'username', 'DB_PASSWORD' => 'password'];
            foreach ($map as $envKey => $cfgKey) {
                if (isset($vals[$envKey])) {
                    config(['database.connections.mysql.' . $cfgKey => $vals[$envKey]]);
                }
            }
        }
        DB::purge('mysql');
        config(['database.default' => 'mysql']);
    }

    private function token(string $prefix = 'zzsrch'): string
    {
        return $prefix . substr(uniqid(), -8);
    }

    private function departmentHead(): User
    {
        $code = 'T' . Str::upper(Str::random(3));
        Department::create(['code' => $code, 'name' => $code, 'description' => $code]);

        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => $code, 'position' => 'employee']);

        return $user;
    }

    private function runResource(string $class, string $query, bool $allHistory = false): array
    {
        $context = SearchContext::for($query, $allHistory);

        return $context ? app($class)->search($context) : [];
    }

    private function firstItem(array $result): array
    {
        return $result['items'][0];
    }

    private function assertItemShape(array $item, string $type, string $group, string $icon): void
    {
        $this->assertSame($type, $item['type']);
        $this->assertSame($group, $item['group']);
        $this->assertSame($icon, $item['icon']);
        $this->assertIsInt($item['id']);
        $this->assertIsString($item['title']);
        $this->assertIsString($item['subtitle']);
        $this->assertIsString($item['action']);
        $this->assertIsInt($item['score']);
        $this->assertGreaterThan(0, $item['score']);
    }

    public function test_navigation_service_returns_empty_for_query_under_two_chars(): void
    {
        $this->assertSame([], app(NavigationService::class)->search('a'));
        $this->assertSame([], app(NavigationService::class)->search(''));
    }

    public function test_navigation_service_ranks_exact_title_match_above_partial_keyword_match(): void
    {
        $exact = app(NavigationService::class)->search('تقویم');
        $partial = app(NavigationService::class)->search('تقو');

        $this->assertNotEmpty($exact);
        $this->assertSame('calendar', $exact[0]['id']);
        $this->assertNotEmpty($partial);
        $this->assertSame('calendar', $partial[0]['id']);
        $this->assertGreaterThan($partial[0]['score'], $exact[0]['score']);
    }

    public function test_navigation_service_normalizes_arabic_kaf_to_persian_keheh(): void
    {
        $results = app(NavigationService::class)->search('كالندر');

        $this->assertNotEmpty($results);
        $this->assertSame('calendar', $results[0]['id']);
    }

    public function test_navigation_service_emits_all_four_action_prefixes(): void
    {
        $this->assertStringStartsWith('tab:', collect(app(NavigationService::class)->search('خانه'))->first()['action'] ?? '');
        $this->assertStringStartsWith('route:', collect(app(NavigationService::class)->search('dms'))->first()['action'] ?? '');
        $this->assertStringStartsWith('url:', collect(app(NavigationService::class)->search('profile'))->first()['action'] ?? '');
        $this->assertStringStartsWith('event:', collect(app(NavigationService::class)->search('logout'))->first()['action'] ?? '');
    }

    public function test_content_service_returns_empty_for_query_under_two_chars(): void
    {
        $this->assertSame([], app(ContentService::class)->search('a'));
    }

    public function test_content_service_returns_empty_when_no_resource_matches(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->actingAs($user);

        $this->assertSame([], app(ContentService::class)->search($this->token('zznone')));
    }

    public function test_content_service_aggregates_and_ranks_across_resources(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->actingAs($user);
        $token = $this->token();

        $post = Post::factory()->create(['title' => $token, 'body' => 'x', 'user_id' => $user->id]);
        Task::factory()->create(['title' => 'partial ' . $token, 'description' => 'x', 'user_id' => $user->id, 'assigned_to' => $user->id]);

        $results = app(ContentService::class)->search($token);

        $this->assertNotEmpty($results);
        $types = collect($results)->pluck('type')->unique()->all();
        $this->assertContains('post', $types);
        $this->assertContains('task', $types);
        $this->assertSame('post', $results[0]['type']);
        $this->assertSame('url:/dashboard?tab=post&open=' . $post->id, $results[0]['action']);
    }

    public function test_post_search_resource_shapes_row_and_builds_tab_deep_link(): void
    {
        $post = Post::factory()->create(['title' => $this->token(), 'body' => 'body']);

        $result = $this->runResource(PostResource::class, $post->title);

        $this->assertNotEmpty($result);
        $item = $this->firstItem($result);
        $this->assertItemShape($item, 'post', 'پست و اعلانات', 'campaign');
        $this->assertSame('url:/dashboard?tab=post&open=' . $post->id, $item['action']);
        $this->assertSame($post->title, $item['title']);
    }

    public function test_feed_search_resource_title_falls_back_to_content_when_category_blank(): void
    {
        $withCategory = Feed::factory()->create(['category' => $this->token(), 'content' => 'irrelevant']);
        $result = $this->runResource(FeedResource::class, $withCategory->category);
        $this->assertSame($withCategory->category, $this->firstItem($result)['title']);

        $blank = Feed::factory()->create(['category' => '', 'content' => $this->token()]);
        $result = $this->runResource(FeedResource::class, $blank->content);
        $item = $this->firstItem($result);
        $this->assertStringStartsWith('url:/dashboard?tab=feed&open=', $item['action']);
        $this->assertNotSame('—', $item['title']);
    }

    public function test_event_search_resource_scope_gates_visibility_by_user_null_or_public(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        $this->actingAs($me);
        $token = $this->token();

        $own = Event::factory()->create(['user_id' => $me->id, 'title' => $token, 'private' => true]);
        $publicOther = Event::factory()->create(['user_id' => $other->id, 'title' => $token, 'private' => false]);
        $privateOther = Event::factory()->create(['user_id' => $other->id, 'title' => $token, 'private' => true]);
        $general = Event::factory()->create(['user_id' => null, 'title' => $token, 'private' => true]);

        $ids = collect($this->runResource(EventResource::class, $token)['items'] ?? [])->pluck('id')->all();

        $this->assertContains($own->id, $ids);
        $this->assertContains($publicOther->id, $ids);
        $this->assertContains($general->id, $ids);
        $this->assertNotContains($privateOther->id, $ids);
    }

    public function test_report_search_resource_scope_only_returns_active_rows(): void
    {
        $token = $this->token();
        $active = Report::factory()->create(['title' => $token, 'active' => 1]);
        $inactive = Report::factory()->create(['title' => $token, 'active' => 0]);

        $ids = collect($this->runResource(ReportResource::class, $token)['items'] ?? [])->pluck('id')->all();

        $this->assertContains($active->id, $ids);
        $this->assertNotContains($inactive->id, $ids);
    }

    public function test_feed_post_gallery_and_report_search_exclude_inactive_and_other_department_rows(): void
    {
        $me = $this->departmentHead();
        $mine = $me->profile->department_id;
        $this->actingAs($me);
        $token = $this->token();

        $rows = [
            FeedResource::class => [
                'own' => Feed::factory()->create(['content' => $token, 'category' => '', 'departments' => [$mine]]),
                'public' => Feed::factory()->create(['content' => $token, 'category' => '', 'departments' => null]),
                'foreign' => Feed::factory()->create(['content' => $token, 'category' => '', 'departments' => ['ZZ-SX']]),
                'inactive' => Feed::factory()->create(['content' => $token, 'category' => '', 'departments' => null, 'active' => false]),
            ],
            PostResource::class => [
                'own' => Post::factory()->create(['title' => $token, 'departments' => [$mine]]),
                'public' => Post::factory()->create(['title' => $token, 'departments' => null]),
                'foreign' => Post::factory()->create(['title' => $token, 'departments' => ['ZZ-SX']]),
                'inactive' => Post::factory()->create(['title' => $token, 'departments' => null, 'active' => false]),
            ],
            GalleryResource::class => [
                'own' => Photo::factory()->create(['title' => $token, 'department_id' => $mine, 'departments' => null]),
                'public' => Photo::factory()->create(['title' => $token, 'department_id' => null, 'departments' => null]),
                'foreign' => Photo::factory()->create(['title' => $token, 'department_id' => 'ZZ-SX', 'departments' => null]),
                'inactive' => Photo::factory()->create(['title' => $token, 'department_id' => null, 'departments' => null, 'active' => false]),
            ],
            ReportResource::class => [
                'own' => Report::factory()->create(['title' => $token, 'active' => true, 'departments' => [$mine]]),
                'public' => Report::factory()->create(['title' => $token, 'active' => true, 'departments' => null]),
                'foreign' => Report::factory()->create(['title' => $token, 'active' => true, 'departments' => ['ZZ-SX']]),
                'inactive' => Report::factory()->create(['title' => $token, 'active' => false, 'departments' => null]),
            ],
        ];

        foreach ($rows as $resource => $set) {
            $ids = collect($this->runResource($resource, $token)['items'] ?? [])->pluck('id')->all();

            $this->assertContains($set['own']->id, $ids, $resource);
            $this->assertContains($set['public']->id, $ids, $resource);
            $this->assertNotContains($set['foreign']->id, $ids, $resource);
            $this->assertNotContains($set['inactive']->id, $ids, $resource);
        }
    }

    public function test_gallery_search_resource_orders_by_event_date_desc_on_relevance_tie(): void
    {
        $token = $this->token();
        $older = Photo::factory()->create(['title' => $token, 'event_date' => '2020-01-01', 'department_id' => null, 'departments' => null]);
        $newer = Photo::factory()->create(['title' => $token, 'event_date' => '2024-06-15', 'department_id' => null, 'departments' => null]);

        $items = $this->runResource(GalleryResource::class, $token)['items'] ?? [];

        $this->assertCount(2, $items);
        $this->assertSame($newer->id, $items[0]['id']);
        $this->assertSame($older->id, $items[1]['id']);
        $this->assertSame('url:/dashboard?tab=gallery&open=' . $newer->id, $items[0]['action']);
    }

    public function test_faq_search_resource_shapes_row_and_builds_tab_deep_link(): void
    {
        $faq = FAQ::factory()->create(['question' => $this->token()]);

        $item = $this->firstItem($this->runResource(FaqResource::class, $faq->question));

        $this->assertItemShape($item, 'faq', 'پرسش‌های متداول', 'help');
        $this->assertSame('url:/dashboard?tab=faqs&open=' . $faq->id, $item['action']);
    }

    public function test_link_search_resource_scans_url_title_description_and_link_columns(): void
    {
        $link = Link::factory()->create(['url_title' => $this->token(), 'url_description' => 'desc', 'link' => 'internal']);

        $item = $this->firstItem($this->runResource(LinkResource::class, $link->url_title));

        $this->assertItemShape($item, 'link', 'لینک‌ها و ابزارها', 'open_in_new');
        $this->assertSame('url:/dashboard?tab=links&open=' . $link->id, $item['action']);
    }

    public function test_dms_search_resource_scope_visible_to_user_gates_rows(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $this->actingAs($me);
        $token = $this->token();

        $visible = DMS::factory()->create(['title' => $token, 'status' => 'live', 'owners' => ['ALL'], 'users' => []]);
        $hidden = DMS::factory()->create(['title' => $token, 'status' => 'live', 'owners' => ['ZZ-NODEPT'], 'users' => ['999999999']]);
        $archived = DMS::factory()->create(['title' => $token, 'status' => 'obsolete', 'owners' => ['ALL'], 'users' => []]);

        $ids = collect($this->runResource(DmsResource::class, $token)['items'] ?? [])->pluck('id')->all();

        $this->assertContains($visible->id, $ids);
        $this->assertNotContains($hidden->id, $ids);
        $this->assertNotContains($archived->id, $ids);
    }

    public function test_ticket_search_resource_scope_gates_to_requester_or_assignee(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        $this->actingAs($me);
        $token = $this->token();

        $own = Ticket::factory()->create(['requester_id' => $me->id, 'request_subject' => $token]);
        $assigned = Ticket::factory()->create(['requester_id' => $other->id, 'assigned_to' => $me->id, 'request_subject' => $token]);
        $foreign = Ticket::factory()->create(['requester_id' => $other->id, 'assigned_to' => $other->id, 'request_subject' => $token]);

        $ids = collect($this->runResource(TicketResource::class, $token)['items'] ?? [])->pluck('id')->all();

        $this->assertContains($own->id, $ids);
        $this->assertContains($assigned->id, $ids);
        $this->assertNotContains($foreign->id, $ids);
    }

    public function test_ticket_search_resource_finds_ticket_by_type_and_area_label_and_raw_key(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $this->actingAs($me);
        $token = 'zzlbl' . Str::lower(Str::random(6));
        Department::create([
            'code' => 'ZS' . random_int(1000, 9999),
            'name' => 'zs',
            'description' => 'zs',
            'ticket_options' => [['request_type' => 'support', 'area_key' => 'zz_srch_' . $token, 'area_label' => 'برچسب ' . $token]],
        ]);
        $ticket = Ticket::factory()->create(['requester_id' => $me->id, 'request_type' => 'support', 'request_area' => 'zz_srch_' . $token]);
        $typed = Ticket::factory()->create(['requester_id' => $me->id, 'request_type' => 'access', 'request_area' => 'vpn']);

        $byAreaLabel = collect($this->runResource(TicketResource::class, $token)['items'] ?? [])->pluck('id')->all();
        $byTypeLabel = collect($this->runResource(TicketResource::class, Ticket::$requestTypeOptions['access'])['items'] ?? [])->pluck('id')->all();

        $this->assertContains($ticket->id, $byAreaLabel);
        $this->assertContains($typed->id, $byTypeLabel);
    }

    public function test_ticket_search_resource_includes_open_department_tickets_for_department_head(): void
    {
        $head = $this->departmentHead();
        $other = User::factory()->create(['status' => 'active']);
        $this->actingAs($head);
        $token = $this->token();

        $openDeptTicket = Ticket::factory()->create([
            'requester_id' => $other->id,
            'assigned_to' => null,
            'status' => 'open',
            'request_subject' => $token,
            'extra' => ['target_department' => $head->profile->department_id],
        ]);

        $inProgressDeptTicket = Ticket::factory()->create([
            'requester_id' => $other->id,
            'assigned_to' => $other->id,
            'status' => 'in-progress',
            'request_subject' => $token,
            'extra' => ['target_department' => $head->profile->department_id],
        ]);

        $otherDeptTicket = Ticket::factory()->create([
            'requester_id' => $other->id,
            'assigned_to' => null,
            'status' => 'open',
            'request_subject' => $token,
            'extra' => ['target_department' => 'ZZ-UNRELATED'],
        ]);

        $ids = collect($this->runResource(TicketResource::class, $token)['items'] ?? [])->pluck('id')->all();

        $this->assertContains($openDeptTicket->id, $ids);
        $this->assertContains($inProgressDeptTicket->id, $ids, 'a head can open every ticket of its department whatever the status');
        $this->assertNotContains($otherDeptTicket->id, $ids, 'a ticket targeted at a different department must not surface');
    }

    public function test_ticket_search_resource_does_not_grant_department_visibility_to_a_non_head(): void
    {
        $code = 'T' . Str::upper(Str::random(3));
        Department::create(['code' => $code, 'name' => $code, 'description' => $code]);

        $head = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $head->id, 'department_id' => $code, 'position' => 'manager']);

        $rankAndFile = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $rankAndFile->id, 'department_id' => $code, 'position' => 'employee']);

        $this->actingAs($rankAndFile);
        $token = $this->token();

        $openDeptTicket = Ticket::factory()->create([
            'requester_id' => $head->id,
            'assigned_to' => null,
            'status' => 'open',
            'request_subject' => $token,
            'extra' => ['target_department' => $code],
        ]);

        $ids = collect($this->runResource(TicketResource::class, $token)['items'] ?? [])->pluck('id')->all();

        $this->assertNotContains($openDeptTicket->id, $ids);
    }

    public function test_ticket_search_resource_recency_window_excludes_rows_older_than_six_months_by_default(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $this->actingAs($me);
        $token = $this->token();

        $recent = Ticket::factory()->create(['requester_id' => $me->id, 'request_subject' => $token]);
        $old = Ticket::factory()->create(['requester_id' => $me->id, 'request_subject' => $token]);
        Ticket::whereKey($old->id)->update(['created_at' => now()->subMonths(8)]);

        $ids = collect($this->runResource(TicketResource::class, $token)['items'] ?? [])->pluck('id')->all();

        $this->assertContains($recent->id, $ids);
        $this->assertNotContains($old->id, $ids);
    }

    public function test_ticket_search_resource_all_history_expansion_returns_rows_past_the_recency_window(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $this->actingAs($me);
        $token = $this->token();

        $old = Ticket::factory()->create(['requester_id' => $me->id, 'request_subject' => $token]);
        Ticket::whereKey($old->id)->update(['created_at' => now()->subMonths(8)]);

        $ids = collect($this->runResource(TicketResource::class, $token, true)['items'] ?? [])->pluck('id')->all();
        $this->assertContains($old->id, $ids);

        $item = collect(app(ContentService::class)->search($token, true))->firstWhere('id', $old->id);
        $this->assertNotNull($item);
        $this->assertSame('ticket', $item['type']);
    }

    public function test_task_search_resource_scope_gates_to_owner_or_assignee(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        $this->actingAs($me);
        $token = $this->token();

        $own = Task::factory()->create(['user_id' => $me->id, 'assigned_to' => $other->id, 'title' => $token]);
        $assigned = Task::factory()->create(['user_id' => $other->id, 'assigned_to' => $me->id, 'title' => $token]);
        $foreign = Task::factory()->create(['user_id' => $other->id, 'assigned_to' => $other->id, 'title' => $token]);

        $ids = collect($this->runResource(TaskResource::class, $token)['items'] ?? [])->pluck('id')->all();

        $this->assertContains($own->id, $ids);
        $this->assertContains($assigned->id, $ids);
        $this->assertNotContains($foreign->id, $ids);
    }

    public function test_task_search_resource_scope_includes_tasks_visible_via_project_membership(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        $this->actingAs($me);
        $token = $this->token();

        $memberProject = Project::factory()->create(['owner_id' => $other->id, 'member_ids' => [$me->id]]);
        $foreignProject = Project::factory()->create(['owner_id' => $other->id]);

        $viaProject = Task::factory()->create(['user_id' => $other->id, 'assigned_to' => $other->id, 'project_id' => $memberProject->id, 'title' => $token]);
        $foreign = Task::factory()->create(['user_id' => $other->id, 'assigned_to' => $other->id, 'project_id' => $foreignProject->id, 'title' => $token]);

        $ids = collect($this->runResource(TaskResource::class, $token)['items'] ?? [])->pluck('id')->all();

        $this->assertContains($viaProject->id, $ids, 'a task under a project the user is a member of, but neither owns nor is assigned to, must still be findable');
        $this->assertNotContains($foreign->id, $ids);
    }

    public function test_task_search_resource_shapes_urgency_progress_and_collaborator_avatars(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $collaborator = User::factory()->create(['status' => 'active']);
        $this->actingAs($me);
        $token = $this->token();

        $task = Task::factory()->create([
            'user_id' => $me->id,
            'assigned_to' => $me->id,
            'title' => $token,
            'status' => 'in-progress',
            'deadline' => now()->subDays(2),
        ]);
        TaskDetail::factory()->create(['task_id' => $task->id, 'collaborators' => [$collaborator->id]]);

        $item = $this->firstItem($this->runResource(TaskResource::class, $token));

        $this->assertSame('overdue', $item['urgency_state']['kind']);
        $this->assertIsInt($item['progress_percent']);
        $this->assertCount(1, $item['collaborator_avatars']);
        $this->assertSame($collaborator->id, $item['collaborator_avatars'][0]['id']);
        $this->assertArrayHasKey('avatar_url', $item['collaborator_avatars'][0]);
    }

    public function test_task_search_resource_query_count_stays_constant_regardless_of_task_count(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $this->actingAs($me);
        $token = $this->token();

        $this->runResource(TaskResource::class, $this->token());

        $single = Task::factory()->create(['user_id' => $me->id, 'assigned_to' => $me->id, 'title' => $token]);
        TaskDetail::factory()->create(['task_id' => $single->id]);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->runResource(TaskResource::class, $token);
        DB::disableQueryLog();
        $singleQueryCount = count(DB::getQueryLog());

        collect(range(1, 4))->each(function () use ($me, $token) {
            $task = Task::factory()->create(['user_id' => $me->id, 'assigned_to' => $me->id, 'title' => $token]);
            TaskDetail::factory()->create(['task_id' => $task->id]);
        });

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->runResource(TaskResource::class, $token);
        DB::disableQueryLog();
        $manyQueryCount = count(DB::getQueryLog());

        $this->assertSame($singleQueryCount, $manyQueryCount, 'resolving N tasks must issue the same number of queries as resolving one');
    }

    public function test_project_search_resource_scope_gates_to_owner_member_or_foreign(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        $this->actingAs($me);
        $token = $this->token();

        $owned = Project::factory()->create(['owner_id' => $me->id, 'name' => $token]);
        $memberOf = Project::factory()->create(['owner_id' => $other->id, 'member_ids' => [$me->id], 'name' => $token]);
        $foreign = Project::factory()->create(['owner_id' => $other->id, 'name' => $token]);

        $ids = collect($this->runResource(ProjectResource::class, $token)['items'] ?? [])->pluck('id')->all();

        $this->assertContains($owned->id, $ids);
        $this->assertContains($memberOf->id, $ids);
        $this->assertNotContains($foreign->id, $ids);
    }

    public function test_project_search_resource_shapes_row_and_routes_to_projects(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $this->actingAs($me);
        $project = Project::factory()->create(['owner_id' => $me->id, 'name' => $this->token()]);

        $item = $this->firstItem($this->runResource(ProjectResource::class, $project->name));

        $this->assertItemShape($item, 'project', 'پروژه‌ها', 'workspaces');
        $this->assertSame('url:/projects?open=' . $project->id, $item['action']);
    }

    public function test_suggestion_search_resource_is_org_wide_and_routes_to_suggestion(): void
    {
        $suggestion = Suggestion::factory()->create(['title' => $this->token()]);

        $item = $this->firstItem($this->runResource(SuggestionResource::class, $suggestion->title));

        $this->assertItemShape($item, 'suggestion', 'پیشنهادات', 'lightbulb');
        $this->assertSame('url:/suggestion?open=' . $suggestion->id, $item['action']);
    }

    public function test_ad_search_resource_scope_only_returns_active_rows(): void
    {
        $token = $this->token();
        $active = Ad::factory()->create(['position' => $token, 'active' => 1]);
        $inactive = Ad::factory()->create(['position' => $token, 'active' => 0]);

        $ids = collect($this->runResource(AdResource::class, $token)['items'] ?? [])->pluck('id')->all();

        $this->assertContains($active->id, $ids);
        $this->assertNotContains($inactive->id, $ids);
    }

    public function test_reservation_search_resource_scope_only_returns_active_status(): void
    {
        $token = $this->token();
        $active = Resource::factory()->create(['name' => $token, 'status' => 'active']);
        $inactive = Resource::factory()->create(['name' => $token, 'status' => 'inactive']);

        $ids = collect($this->runResource(ReservationResource::class, $token)['items'] ?? [])->pluck('id')->all();

        $this->assertContains($active->id, $ids);
        $this->assertNotContains($inactive->id, $ids);
    }

    public function test_channel_search_resource_scope_gates_to_member_channels(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $owner = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);
        $this->actingAs($me);
        $token = $this->token();

        $member = Channel::factory()->create(['name' => $token, 'owner_id' => $owner->id]);
        ChannelMember::factory()->create(['channel_id' => $member->id, 'user_id' => $me->id]);
        $foreign = Channel::factory()->create(['name' => $token, 'owner_id' => $stranger->id]);

        $ids = collect($this->runResource(ChannelResource::class, $token)['items'] ?? [])->pluck('id')->all();

        $this->assertContains($member->id, $ids);
        $this->assertNotContains($foreign->id, $ids);
    }

    public function test_channel_message_search_resource_finds_any_fellow_members_message_action_and_title(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        $owner = User::factory()->create(['status' => 'active']);
        $this->actingAs($me);
        $token = $this->token();

        $channel = Channel::factory()->create(['owner_id' => $owner->id]);
        ChannelMember::factory()->create(['channel_id' => $channel->id, 'user_id' => $me->id]);
        ChannelMember::factory()->create(['channel_id' => $channel->id, 'user_id' => $other->id]);

        $mine = ChannelMessage::factory()->create(['channel_id' => $channel->id, 'sender_id' => $me->id, 'body' => $token]);
        $theirs = ChannelMessage::factory()->create(['channel_id' => $channel->id, 'sender_id' => $other->id, 'body' => $token]);

        $items = $this->runResource(ChannelMessageResource::class, $token)['items'] ?? [];
        $ids = collect($items)->pluck('id')->all();

        $this->assertContains($mine->id, $ids, 'a message I sent in a channel I belong to must be findable');
        $this->assertContains($theirs->id, $ids, "a fellow member's message in the same channel must also be findable — search must match what the in-chat search already shows");

        $mineItem = collect($items)->firstWhere('id', $mine->id);
        $this->assertSame('channel-message', $mineItem['type']);
        $this->assertSame('url:/channels?open=' . $channel->id . '&focus_msg=' . $mine->id, $mineItem['action']);
        $this->assertNotSame('—', $mineItem['title']);
    }

    public function test_channel_message_search_resource_excludes_messages_from_channels_i_am_not_a_member_of(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);
        $owner = User::factory()->create(['status' => 'active']);
        $this->actingAs($me);
        $token = $this->token();

        $foreignChannel = Channel::factory()->create(['owner_id' => $owner->id]);
        ChannelMember::factory()->create(['channel_id' => $foreignChannel->id, 'user_id' => $stranger->id]);

        $foreignMessage = ChannelMessage::factory()->create(['channel_id' => $foreignChannel->id, 'sender_id' => $stranger->id, 'body' => $token]);

        $ids = collect($this->runResource(ChannelMessageResource::class, $token)['items'] ?? [])->pluck('id')->all();

        $this->assertNotContains($foreignMessage->id, $ids, 'a message in a channel I am not a member of must never be findable');
    }

    public function test_people_search_resource_excludes_the_authenticated_user(): void
    {
        $token = $this->token();
        $me = User::factory()->create(['status' => 'active', 'name' => $token]);
        $other = User::factory()->create(['status' => 'active', 'name' => $token]);
        $this->actingAs($me);

        $ids = collect($this->runResource(PeopleResource::class, $token)['items'] ?? [])->pluck('id')->all();

        $this->assertContains($other->id, $ids);
        $this->assertNotContains($me->id, $ids);
        $item = collect($this->runResource(PeopleResource::class, $token)['items'])->firstWhere('id', $other->id);
        $this->assertSame('url:/contacts?open=' . $other->id, $item['action']);
    }

    public function test_people_search_resource_is_unbounded_and_finds_old_rows_without_all_history(): void
    {
        $token = $this->token();
        $me = User::factory()->create(['status' => 'active', 'name' => $token]);
        $other = User::factory()->create(['status' => 'active', 'name' => $token]);
        User::whereKey($other->id)->update(['created_at' => now()->subYears(2)]);
        $this->actingAs($me);

        $ids = collect($this->runResource(PeopleResource::class, $token)['items'] ?? [])->pluck('id')->all();

        $this->assertContains($other->id, $ids);
        $this->assertNotContains($me->id, $ids);
    }

    public function test_credential_search_resource_scope_gates_to_owner_credentials(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $this->actingAs($me);
        $token = $this->token();

        $own = Credential::factory()->create(['user_id' => $me->id, 'app_name' => $token]);
        $foreign = Credential::factory()->create(['user_id' => User::factory()->create(['status' => 'active'])->id, 'app_name' => $token]);

        $ids = collect($this->runResource(CredentialResource::class, $token)['items'] ?? [])->pluck('id')->all();

        $this->assertContains($own->id, $ids);
        $this->assertNotContains($foreign->id, $ids);
        $item = collect($this->runResource(CredentialResource::class, $token)['items'])->firstWhere('id', $own->id);
        $this->assertSame('url:/profile?tab=credentials&open=' . $own->id, $item['action']);
    }

    public function test_onboarding_search_resource_scopes_to_own_and_global_active_records(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        $this->actingAs($me);
        $token = $this->token();

        $own = Onboarding::factory()->create(['welcome' => $token, 'is_active' => 1, 'user_id' => $me->id]);
        $global = Onboarding::factory()->create(['welcome' => $token, 'is_active' => 1, 'user_id' => null]);
        $foreign = Onboarding::factory()->create(['welcome' => $token, 'is_active' => 1, 'user_id' => $other->id]);
        $inactive = Onboarding::factory()->create(['welcome' => $token, 'is_active' => 0, 'user_id' => $me->id]);

        $items = $this->runResource(OnboardingResource::class, $token)['items'] ?? [];
        $ids = collect($items)->pluck('id')->all();

        $this->assertContains($own->id, $ids);
        $this->assertContains($global->id, $ids);
        $this->assertNotContains($foreign->id, $ids);
        $this->assertNotContains($inactive->id, $ids);
        $item = collect($items)->firstWhere('id', $own->id);
        $this->assertSame('url:/profile?tab=onboarding', $item['action']);
        $this->assertNotSame('—', $item['title']);
    }

    public function test_message_search_resource_scope_action_and_title(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);
        $this->actingAs($me);
        $token = $this->token();

        $sent = Message::factory()->create(['sender_id' => $me->id, 'recipient_id' => $other->id, 'body' => $token]);
        $received = Message::factory()->create(['sender_id' => $other->id, 'recipient_id' => $me->id, 'body' => $token]);
        $foreign = Message::factory()->create(['sender_id' => $other->id, 'recipient_id' => $stranger->id, 'body' => $token]);

        $items = $this->runResource(MessageResource::class, $token)['items'] ?? [];
        $ids = collect($items)->pluck('id')->all();

        $this->assertContains($sent->id, $ids);
        $this->assertContains($received->id, $ids);
        $this->assertNotContains($foreign->id, $ids);

        $sentItem = collect($items)->firstWhere('id', $sent->id);
        $this->assertSame('url:/contacts?open=' . $other->id . '&focus_msg=' . $sent->id, $sentItem['action']);
        $this->assertNotSame('—', $sentItem['title']);
    }

    public function test_authority_search_resource_finds_row_via_details_json_and_routes_to_authority(): void
    {
        $token = $this->token();
        $authority = Authority::factory()->create(['sub_duty' => 1, 'details' => ['duty' => $token]]);

        $item = $this->firstItem($this->runResource(AuthorityResource::class, $token));

        $this->assertItemShape($item, 'authority', 'اختیارات', 'verified_user');
        $this->assertSame(superClean($token, 120), $item['subtitle']);
        $this->assertSame('url:/authority?open=' . $authority->id, $item['action']);
    }

    public function test_skill_search_resource_scope_returns_only_active_catalog_rows(): void
    {
        $token = $this->token();
        $active = Skill::create(['name' => $token, 'is_active' => true, 'is_ghost' => false]);
        $draft = Skill::create(['name' => $token . '-draft', 'is_active' => false, 'is_ghost' => false]);
        $ghost = Skill::create(['name' => $token . '-ghost', 'is_active' => false, 'is_ghost' => true]);

        $ids = collect($this->runResource(SkillSearchResource::class, $token)['items'] ?? [])->pluck('id')->all();

        $this->assertContains($active->id, $ids);
        $this->assertNotContains($draft->id, $ids);
        $this->assertNotContains($ghost->id, $ids);
    }

    public function test_skill_search_resource_action_deep_links_to_status_board_with_skill_param(): void
    {
        $token = $this->token();
        $skill = Skill::create(['name' => $token, 'is_active' => true, 'is_ghost' => false]);

        $item = $this->firstItem($this->runResource(SkillSearchResource::class, $token));

        $this->assertItemShape($item, 'skill', 'مهارت‌ها', 'bolt');
        $this->assertSame('url:/dashboard?tab=status&skill=' . $skill->id, $item['action']);
    }

    public function test_content_search_skips_typeless_resources_whose_target_module_is_off_and_returns_them_when_on(): void
    {
        $me = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        $this->actingAs($me);
        $token = $this->token();

        Event::factory()->create(['user_id' => $me->id, 'title' => $token, 'private' => false]);
        Ticket::factory()->create(['requester_id' => $me->id, 'request_subject' => $token]);
        Message::factory()->create(['sender_id' => $me->id, 'recipient_id' => $other->id, 'body' => $token]);
        Skill::create(['name' => $token, 'is_active' => true, 'is_ghost' => false]);
        Credential::factory()->create(['user_id' => $me->id, 'app_name' => $token]);

        $cases = [
            [EventResource::class, 'calendar'],
            [TicketResource::class, 'ths'],
            [MessageResource::class, 'contact'],
            [SkillSearchResource::class, 'status'],
            [CredentialResource::class, 'credentials'],
        ];

        try {
            foreach ($cases as [$class, $module]) {
                config(['app.off' => [$module]]);
                $this->assertSame([], $this->runResource($class, $token), $module);

                config(['app.off' => []]);
                $this->assertNotEmpty($this->runResource($class, $token), $module);
            }
        } finally {
            config(['app.off' => []]);
        }
    }

    public function test_navigation_search_hides_profile_tab_items_when_profile_is_off(): void
    {
        $service = app(NavigationService::class);
        $items = (new \ReflectionMethod($service, 'getSearchableItems'))->getClosure($service)();
        $profileIds = collect($items)->filter(fn ($item) => str_contains($item['action'], '/profile'))->pluck('id')->all();

        $this->assertNotEmpty($profileIds);

        try {
            config(['app.off' => ['profile']]);

            $found = collect($items)->flatMap(fn ($item) => array_column($service->search($item['title']), 'id'))->unique()->all();

            $this->assertEmpty(array_intersect($profileIds, $found));
        } finally {
            config(['app.off' => []]);
        }
    }

    public function test_navigation_search_hides_every_off_module_item_and_keeps_tools(): void
    {
        $ids = fn (string $query) => array_column(app(NavigationService::class)->search($query), 'id');

        try {
            config(['app.off' => ['calendar', 'gallery', 'reservation', 'skills']]);

            $this->assertNotContains('calendar', $ids('تقویم'));
            $this->assertNotContains('gallery', $ids('گالری'));
            $this->assertNotContains('reservation', $ids('رزرو'));
            $this->assertNotContains('skills', $ids('مهارت'));
            $this->assertContains('calculator', $ids('ماشین حساب'));
            $this->assertContains('logout', $ids('logout'));

            config(['app.off' => []]);

            $this->assertContains('calendar', $ids('تقویم'));
            $this->assertContains('gallery', $ids('گالری'));
        } finally {
            config(['app.off' => []]);
        }
    }
}