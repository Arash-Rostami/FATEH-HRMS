<?php

namespace Tests\Feature\Models;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PushSubscriptionModelTest extends TestCase
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

    private function data(string $suffix, bool $contact = true, bool $channel = false): array
    {
        return [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/' . $suffix,
            'keys' => ['p256dh' => 'key-' . $suffix, 'auth' => 'auth-' . $suffix],
            'scopes' => ['contact' => $contact, 'channel' => $channel],
        ];
    }

    public function test_sync_upserts_by_endpoint_hash(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $first = PushSubscription::sync($user, $this->data('a'));
        $second = PushSubscription::sync($user, $this->data('a', false, true));

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, PushSubscription::where('user_id', $user->id)->count());
        $this->assertFalse($second->fresh()->contact);
        $this->assertTrue($second->fresh()->channel);
    }

    public function test_sync_transfers_ownership_to_the_current_user(): void
    {
        $old = User::factory()->create(['status' => 'active']);
        $new = User::factory()->create(['status' => 'active']);

        PushSubscription::sync($old, $this->data('shared'));
        $row = PushSubscription::sync($new, $this->data('shared'));

        $this->assertSame($new->id, $row->fresh()->user_id);
        $this->assertSame(0, PushSubscription::where('user_id', $old->id)->count());
    }

    public function test_another_account_without_the_browser_keys_can_neither_take_over_nor_delete_a_row(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $intruder = User::factory()->create(['status' => 'active']);
        $row = PushSubscription::sync($owner, $this->data('mine'));

        $forged = $this->data('mine');
        $forged['keys'] = ['p256dh' => 'other', 'auth' => 'other'];

        $this->assertNull(PushSubscription::sync($intruder, $forged));
        $this->assertSame($owner->id, $row->fresh()->user_id);
        $this->assertSame('key-mine', $row->fresh()->p256dh);

        $forged['scopes'] = ['contact' => false, 'channel' => false];

        $this->assertNull(PushSubscription::sync($intruder, $forged));
        $this->assertNotNull(PushSubscription::find($row->id));
    }

    public function test_sync_with_both_scopes_off_deletes_the_row(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        PushSubscription::sync($user, $this->data('gone'));

        $result = PushSubscription::sync($user, $this->data('gone', false, false));

        $this->assertNull($result);
        $this->assertSame(0, PushSubscription::where('user_id', $user->id)->count());
    }

    public function test_sync_prunes_to_the_five_newest_rows_per_user(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        foreach (range(1, 7) as $i) {
            $row = PushSubscription::sync($user, $this->data("n{$i}"));
            $row->forceFill(['updated_at' => now()->addSeconds($i)])->saveQuietly();
        }

        $kept = PushSubscription::where('user_id', $user->id)->pluck('endpoint')->all();

        $this->assertCount(5, $kept);
        $this->assertContains('https://fcm.googleapis.com/fcm/send/n7', $kept);
        $this->assertNotContains('https://fcm.googleapis.com/fcm/send/n1', $kept);
    }
}
