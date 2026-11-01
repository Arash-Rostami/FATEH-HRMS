<?php

namespace Tests\Feature\Services\ProjectTask;

use App\Services\ProjectTask\ToggleReplyReactionAction;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\ProjectTask\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ToggleReplyReactionActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    private function useMysql(): void
    {
        $env = base_path('.env');
        if (is_file($env)) {
            $vals = [];
            foreach (explode("\n", (string) file_get_contents($env)) as $line) {
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

    private function projectWithReply(): array
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        $reply = app(ActivityLogger::class)->comment($task, $owner, 'hello team');

        return [$project, $reply->id, $owner];
    }

    private function taskWithReply(): array
    {
        $owner = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => null]);
        $reply = app(ActivityLogger::class)->comment($task, $owner, 'hello there');

        return [$task, $reply->id, $owner];
    }

    public function test_toggle_adds_a_reaction(): void
    {
        [, $replyId, $user] = $this->projectWithReply();

        $reactions = app(ToggleReplyReactionAction::class)->execute($replyId, '👍', $user->id);

        $this->assertCount(1, $reactions);
        $this->assertSame($user->id, (int) $reactions[0]['user_id']);
        $this->assertSame('👍', $reactions[0]['emoji']);
    }

    public function test_toggle_again_removes_the_same_reaction(): void
    {
        [, $replyId, $user] = $this->projectWithReply();
        $action = app(ToggleReplyReactionAction::class);

        $action->execute($replyId, '👍', $user->id);
        $reactions = $action->execute($replyId, '👍', $user->id);

        $this->assertCount(0, $reactions);
    }

    public function test_two_different_users_can_react_with_the_same_emoji(): void
    {
        [$project, $replyId, $owner] = $this->projectWithReply();
        $member = User::factory()->create(['status' => 'active']);
        $project->update(['member_ids' => [$member->id]]);
        $action = app(ToggleReplyReactionAction::class);

        $action->execute($replyId, '👍', $owner->id);
        $reactions = $action->execute($replyId, '👍', $member->id);

        $this->assertCount(2, $reactions);
    }

    public function test_allows_the_full_picker_emoji_set_not_just_the_original_five(): void
    {
        [, $replyId, $user] = $this->projectWithReply();

        $reactions = app(ToggleReplyReactionAction::class)->execute($replyId, '😮', $user->id);

        $this->assertCount(1, $reactions);
        $this->assertSame('😮', $reactions[0]['emoji']);
    }

    public function test_disallowed_emoji_is_rejected(): void
    {
        [, $replyId, $user] = $this->projectWithReply();

        $this->expectException(HttpException::class);

        app(ToggleReplyReactionAction::class)->execute($replyId, '💩', $user->id);
    }

    public function test_arbitrary_string_is_rejected_not_persisted(): void
    {
        [, $replyId, $user] = $this->projectWithReply();

        try {
            app(ToggleReplyReactionAction::class)->execute($replyId, "'; alert(1); //", $user->id);
            $this->fail('Expected HttpException was not thrown');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }

        $this->assertEmpty(\App\Models\Reply::find($replyId)->reactions, 'a rejected emoji must never reach persistence');
    }

    public function test_a_stranger_with_no_visibility_into_the_project_cannot_react(): void
    {
        [, $replyId] = $this->projectWithReply();
        $stranger = User::factory()->create(['status' => 'active']);

        $this->expectException(HttpException::class);

        app(ToggleReplyReactionAction::class)->execute($replyId, '👍', $stranger->id);
    }

    public function test_reaction_toggle_bumps_the_project_heartbeat(): void
    {
        [$project, $replyId, $user] = $this->projectWithReply();
        $before = \App\Services\ProjectTask\ProjectHeartbeat::version($project->id);

        app(ToggleReplyReactionAction::class)->execute($replyId, '👍', $user->id);

        $this->assertNotSame($before, \App\Services\ProjectTask\ProjectHeartbeat::version($project->id));
    }

    public function test_toggle_adds_a_reaction_to_a_task_level_reply(): void
    {
        [$task, $replyId, $owner] = $this->taskWithReply();

        $reactions = app(ToggleReplyReactionAction::class)->execute($replyId, '🎉', $owner->id);

        $this->assertCount(1, $reactions);
        $this->assertSame('🎉', $reactions[0]['emoji']);
        $this->assertNotNull($task);
    }

    public function test_a_collaborator_can_react_to_a_task_level_reply(): void
    {
        [$task, $replyId, $owner] = $this->taskWithReply();
        $collaborator = User::factory()->create(['status' => 'active']);
        $task->detail()->create(['collaborators' => [$collaborator->id]]);

        $reactions = app(ToggleReplyReactionAction::class)->execute($replyId, '👀', $collaborator->id);

        $this->assertCount(1, $reactions);
    }

    public function test_an_unrelated_user_cannot_react_to_a_task_level_reply(): void
    {
        [, $replyId] = $this->taskWithReply();
        $stranger = User::factory()->create(['status' => 'active']);

        $this->expectException(HttpException::class);

        app(ToggleReplyReactionAction::class)->execute($replyId, '👀', $stranger->id);
    }
}
