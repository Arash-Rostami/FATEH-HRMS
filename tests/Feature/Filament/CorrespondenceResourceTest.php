<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\CorrespondenceResource;
use App\Filament\Resources\CorrespondenceResource\Pages\ListCorrespondences;
use App\Filament\Resources\CorrespondenceResource\Pages\ViewCorrespondence;
use App\Models\Correspondence;
use App\Models\Permission;
use App\Models\User;
use App\Support\CorrespondenceAccessPolicy;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class CorrespondenceResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        app()->setLocale('fa');
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

    private function developer(): User
    {
        $user = User::factory()->create(['role' => 'developer', 'status' => 'active']);
        Permission::create(['user_id' => $user->id, 'is_super_admin' => true]);

        return $user;
    }

    private function letter(User $owner, string $subject, string $classification, array $extra = []): Correspondence
    {
        return Correspondence::forceCreate($extra + [
            'user_id' => $owner->id,
            'direction' => Correspondence::DIRECTION_OUT,
            'type' => 'letter',
            'status' => Correspondence::STATUS_APPROVED,
            'classification' => $classification,
            'subject' => $subject,
            'spec' => ['blocks' => [['type' => 'text', 'text' => 'body']]],
            'parties' => [],
            'recipients' => [],
            'events' => [],
        ]);
    }

    public function test_admin_resource_is_read_only_and_masks_non_normal_subjects(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $normal = $this->letter($owner, 'Public subject', Correspondence::CLASSIFICATION_NORMAL);
        $secret = $this->letter($owner, 'private-secret-subject', Correspondence::CLASSIFICATION_SECRET);

        $this->assertFalse(CorrespondenceResource::canCreate());
        $this->assertNotContains('spec', Correspondence::ADMIN_COLUMNS);
        $this->assertNotContains('parties', Correspondence::ADMIN_COLUMNS);
        $this->assertNotContains('events', Correspondence::ADMIN_COLUMNS);

        Livewire::actingAs($admin)
            ->test(ListCorrespondences::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$normal, $secret])
            ->assertSee('Public subject')
            ->assertDontSee('private-secret-subject')
            ->assertSee(__('resources/correspondence/strings.admin.masked'));
    }

    public function test_only_list_and_view_pages_exist_and_the_table_query_selects_the_admin_columns(): void
    {
        $this->assertSame(['index', 'view'], array_keys(CorrespondenceResource::getPages()));
        foreach (['id', 'user_id', 'status', 'classification', 'subject', 'number', 'direction', 'is_template', 'created_at'] as $column) {
            $this->assertContains($column, Correspondence::ADMIN_COLUMNS);
        }
    }

    public function test_direction_and_status_filters_narrow_the_list(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $out = $this->letter($owner, 'out-letter', Correspondence::CLASSIFICATION_NORMAL);
        $in = $this->letter($owner, 'in-letter', Correspondence::CLASSIFICATION_NORMAL, ['direction' => Correspondence::DIRECTION_IN, 'type' => 'incoming']);
        $draft = $this->letter($owner, 'draft-letter', Correspondence::CLASSIFICATION_NORMAL, ['status' => Correspondence::STATUS_DRAFT]);

        Livewire::actingAs($admin)->test(ListCorrespondences::class)
            ->filterTable('direction', true)
            ->assertCanSeeTableRecords([$out, $draft])
            ->assertCanNotSeeTableRecords([$in]);

        Livewire::actingAs($admin)->test(ListCorrespondences::class)
            ->filterTable('direction', false)
            ->assertCanSeeTableRecords([$in])
            ->assertCanNotSeeTableRecords([$out, $draft]);

        Livewire::actingAs($admin)->test(ListCorrespondences::class)
            ->filterTable('status', Correspondence::STATUS_DRAFT)
            ->assertCanSeeTableRecords([$draft])
            ->assertCanNotSeeTableRecords([$out, $in]);
    }

    public function test_the_stale_draft_filter_keeps_only_old_non_template_drafts(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $days = (int) config('ai.correspondence.stale_draft_days');
        $old = $this->letter($owner, 'old-draft', Correspondence::CLASSIFICATION_NORMAL, ['status' => Correspondence::STATUS_DRAFT, 'created_at' => now()->subDays($days + 2)]);
        $fresh = $this->letter($owner, 'fresh-draft', Correspondence::CLASSIFICATION_NORMAL, ['status' => Correspondence::STATUS_DRAFT]);
        $template = $this->letter($owner, 'old-template', Correspondence::CLASSIFICATION_NORMAL, ['status' => Correspondence::STATUS_DRAFT, 'is_template' => true, 'created_at' => now()->subDays($days + 2)]);
        $approved = $this->letter($owner, 'old-approved', Correspondence::CLASSIFICATION_NORMAL, ['created_at' => now()->subDays($days + 2)]);

        Livewire::actingAs($admin)->test(ListCorrespondences::class)
            ->filterTable('stale_drafts', ['isActive' => true])
            ->assertCanSeeTableRecords([$old])
            ->assertCanNotSeeTableRecords([$fresh, $template, $approved]);
    }

    public function test_view_page_masks_non_normal_subjects_and_never_exposes_link_token_hashes(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $party = ['user_id' => null, 'role' => 'fyi', 'name' => 'Outside Person', 'email' => 'outside@example.test', 'delivery' => 'link', 'sent_at' => now()->toIso8601String(), 'token_hash' => 'LEAKEDHASH0123456789abcdef', 'token_hash_next' => 'LEAKEDNEXT0123456789abcdef'];
        $normal = $this->letter($owner, 'Visible Subject Line', Correspondence::CLASSIFICATION_NORMAL, ['parties' => [$party], 'events' => [['at' => now()->toIso8601String(), 'user_id' => $owner->id, 'action' => 'created']]]);
        $secret = $this->letter($owner, 'private-secret-subject', Correspondence::CLASSIFICATION_SECRET);

        Livewire::actingAs($admin)->test(ViewCorrespondence::class, ['record' => $normal->getKey()])
            ->assertSuccessful()
            ->assertSee('Visible Subject Line')
            ->assertSee('Outside Person')
            ->assertDontSee('LEAKEDHASH0123456789abcdef')
            ->assertDontSee('LEAKEDNEXT0123456789abcdef');

        Livewire::actingAs($admin)->test(ViewCorrespondence::class, ['record' => $secret->getKey()])
            ->assertSuccessful()
            ->assertDontSee('private-secret-subject')
            ->assertSee(__('resources/correspondence/strings.admin.masked'));
    }

    public function test_view_page_preview_shows_signed_signature_pictures_to_a_non_party_admin_but_hides_confidential_letters(): void
    {
        $admin = $this->developer();
        $owner = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $normal = $this->letter($owner, 'Preview Subject', Correspondence::CLASSIFICATION_NORMAL);
        $secret = $this->letter($owner, 'Preview Secret', Correspondence::CLASSIFICATION_CONFIDENTIAL);

        foreach ([$normal, $secret] as $letter) {
            $letter->forceFill(['parties' => [['role' => 'approver', 'name' => 'Signer', 'signature_path' => "correspondence/{$letter->id}/signature-5.png"]]])->save();
        }

        $this->assertFalse(CorrespondenceAccessPolicy::canView($admin, $normal));

        Livewire::actingAs($admin)->test(ViewCorrespondence::class, ['record' => $normal->getKey()])
            ->assertSuccessful()
            ->assertSee('signature=', false);

        Livewire::actingAs($admin)->test(ViewCorrespondence::class, ['record' => $secret->getKey()])
            ->assertSuccessful()
            ->assertDontSee('signature=', false)
            ->assertDontSee('signature-5.png', false);
    }
}
