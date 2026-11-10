<?php

namespace Tests\Feature\Support;

use App\Livewire\Dashboard\Correspondence\Forms\LetterForm;
use App\Livewire\Dashboard\Correspondence\Presentation\CorrespondencePresenter;
use App\Models\Correspondence;
use App\Models\User;
use App\Support\CorrespondenceAccessPolicy;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CorrespondenceAccessPolicyTest extends TestCase
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

    private function user(array $attributes = []): User
    {
        return User::factory()->create(['role' => 'user', 'status' => 'active', ...$attributes]);
    }

    private function letter(User $author, array $overrides = []): Correspondence
    {
        return (new Correspondence())->forceFill(array_replace([
            'user_id' => $author->id,
            'direction' => Correspondence::DIRECTION_OUT,
            'status' => Correspondence::STATUS_SHARED,
            'classification' => Correspondence::CLASSIFICATION_NORMAL,
            'parties' => [],
            'recipients' => [],
        ], $overrides));
    }

    public function test_view_and_edit_matrix_covers_authors_parties_recipients_statuses_and_role_does_not_bypass_it(): void
    {
        $author = $this->user();
        $approver = $this->user();
        $fyi = $this->user();
        $stranger = $this->user();
        $admin = $this->user(['role' => 'admin']);
        $inactive = $this->user(['status' => 'inactive']);
        $shared = $this->letter($author, [
            'parties' => [
                ['user_id' => $approver->id, 'role' => LetterForm::ROLE_APPROVER],
                ['user_id' => $fyi->id, 'role' => LetterForm::ROLE_FYI],
            ],
            'recipients' => [$approver->id, $fyi->id],
        ]);

        $this->assertTrue(CorrespondenceAccessPolicy::canView($author, $shared));
        $this->assertTrue(CorrespondenceAccessPolicy::canView($approver, $shared));
        $this->assertTrue(CorrespondenceAccessPolicy::canView($fyi, $shared));
        $this->assertFalse(CorrespondenceAccessPolicy::canView($stranger, $shared));
        $this->assertFalse(CorrespondenceAccessPolicy::canView($admin, $shared));
        $this->assertFalse(CorrespondenceAccessPolicy::canView($inactive, $shared));
        $this->assertFalse(CorrespondenceAccessPolicy::canView(null, $shared));

        foreach ([...Correspondence::DRAFT_LIKE, Correspondence::STATUS_SAVED] as $status) {
            $hidden = $this->letter($author, ['status' => $status, 'recipients' => [$fyi->id]]);
            $this->assertTrue(CorrespondenceAccessPolicy::canView($author, $hidden));
            $this->assertFalse(CorrespondenceAccessPolicy::canView($fyi, $hidden));
        }

        $this->assertTrue(CorrespondenceAccessPolicy::canEdit($author, $this->letter($author, ['status' => Correspondence::STATUS_DRAFT])));
        $this->assertTrue(CorrespondenceAccessPolicy::canEdit($author, $this->letter($author, ['status' => Correspondence::STATUS_SAVED])));
        $this->assertTrue(CorrespondenceAccessPolicy::canEdit($author, $this->letter($author, ['status' => Correspondence::STATUS_REJECTED])));
        $this->assertFalse(CorrespondenceAccessPolicy::canEdit($author, $shared));
        $this->assertTrue(CorrespondenceAccessPolicy::canEdit($approver, $shared));
        $this->assertFalse(CorrespondenceAccessPolicy::canEdit($fyi, $shared));
        $this->assertFalse(CorrespondenceAccessPolicy::canEdit($admin, $shared));
    }

    public function test_confidential_parties_can_view_but_the_registry_gets_only_the_status_allowlist(): void
    {
        $author = $this->user();
        $party = $this->user();
        $stranger = $this->user();
        $registry = $this->user(['extra' => ['admin' => ['correspondence_registry' => true]]]);
        $secret = $this->letter($author, [
            'classification' => Correspondence::CLASSIFICATION_SECRET,
            'parties' => [['user_id' => $party->id, 'role' => LetterForm::ROLE_FYI]],
        ]);

        $this->assertTrue(CorrespondenceAccessPolicy::canView($party, $secret));
        $this->assertFalse(CorrespondenceAccessPolicy::canView($stranger, $secret));
        $this->assertFalse(CorrespondenceAccessPolicy::canView($registry, $secret));

        foreach (CorrespondencePresenter::REGISTRY_STATUSES as $status) {
            $row = $this->letter($author, ['status' => $status, 'classification' => Correspondence::CLASSIFICATION_SECRET]);
            $this->assertTrue(CorrespondenceAccessPolicy::canRegistry($registry, $row), "registry should see metadata for {$status}");
            $this->assertFalse(CorrespondenceAccessPolicy::canRegistry($author, $row));
        }

        $draft = $this->letter($author, ['status' => Correspondence::STATUS_DRAFT]);
        $this->assertFalse(CorrespondenceAccessPolicy::canRegistry($registry, $draft));
        $this->assertTrue(CorrespondenceAccessPolicy::canSteward($registry, $draft));
        $this->assertFalse(CorrespondenceAccessPolicy::canSteward($this->user(['status' => 'inactive']), $secret));
    }

    public function test_deleted_rows_and_partial_rows_are_handled_without_leaking_access(): void
    {
        $author = $this->user();
        $party = $this->user();
        $letter = $this->letter($author, ['parties' => [['user_id' => $party->id, 'role' => LetterForm::ROLE_APPROVER]]]);

        $this->assertSame($letter->parties[0], CorrespondenceAccessPolicy::partyOf($letter, $party));
        $this->assertNull(CorrespondenceAccessPolicy::partyOf($letter, null));
        $this->assertTrue(CorrespondenceAccessPolicy::canView($party, $letter));

        $deleted = $this->letter($author, [
            'parties' => [['user_id' => $party->id, 'role' => LetterForm::ROLE_APPROVER]],
            'deleted_at' => now(),
        ]);
        $this->assertFalse(CorrespondenceAccessPolicy::canView($party, $deleted));
        $this->assertFalse(CorrespondenceAccessPolicy::canRegistry($this->user(['extra' => ['admin' => ['correspondence_registry' => true]]]), $deleted));
        $this->assertFalse(CorrespondenceAccessPolicy::canEdit($author, $this->letter($author, ['status' => Correspondence::STATUS_DRAFT, 'deleted_at' => now()])));
    }

    public function test_the_reserved_withdrawn_status_is_no_longer_hidden_from_recipients_or_registry(): void
    {
        $author = $this->user();
        $fyi = $this->user();
        $registry = $this->user(['extra' => ['admin' => ['correspondence_registry' => true]]]);
        $withdrawn = $this->letter($author, ['status' => Correspondence::STATUS_WITHDRAWN, 'recipients' => [$fyi->id], 'parties' => [['user_id' => $fyi->id, 'role' => LetterForm::ROLE_FYI]]]);

        $this->assertTrue(CorrespondenceAccessPolicy::canView($fyi, $withdrawn));
        $this->assertNotContains(Correspondence::STATUS_WITHDRAWN, CorrespondencePresenter::REGISTRY_STATUSES);
        $this->assertFalse(CorrespondenceAccessPolicy::canRegistry($registry, $this->letter($author, ['status' => Correspondence::STATUS_WITHDRAWN])));
    }

    public function test_edit_rights_for_parallel_approvers_ended_letters_and_rejected_drafts(): void
    {
        $author = $this->user();
        $first = $this->user();
        $second = $this->user();
        $fyi = $this->user();
        $parties = [
            ['user_id' => $first->id, 'role' => LetterForm::ROLE_APPROVER],
            ['user_id' => $second->id, 'role' => LetterForm::ROLE_APPROVER],
            ['user_id' => $fyi->id, 'role' => LetterForm::ROLE_FYI],
        ];
        $parallel = $this->letter($author, ['route_mode' => 'parallel', 'parties' => $parties, 'recipients' => [$first->id, $second->id, $fyi->id]]);

        $this->assertTrue(CorrespondenceAccessPolicy::canEdit($first, $parallel));
        $this->assertTrue(CorrespondenceAccessPolicy::canEdit($second, $parallel));
        $this->assertFalse(CorrespondenceAccessPolicy::canEdit($fyi, $parallel));

        foreach ([Correspondence::STATUS_APPROVED, Correspondence::STATUS_VOID, Correspondence::STATUS_SUPERSEDED, Correspondence::STATUS_REJECTED] as $status) {
            $ended = $this->letter($author, ['status' => $status, 'parties' => $parties, 'recipients' => [$first->id]]);
            $this->assertFalse(CorrespondenceAccessPolicy::canEdit($first, $ended), "approver must not edit a {$status} letter");
        }

        foreach ([Correspondence::STATUS_APPROVED, Correspondence::STATUS_VOID, Correspondence::STATUS_SHARED] as $status) {
            $this->assertFalse(CorrespondenceAccessPolicy::canEdit($author, $this->letter($author, ['status' => $status])));
        }
    }

    public function test_registry_flag_needs_an_active_user_and_a_truthy_stored_value(): void
    {
        $this->assertTrue(CorrespondenceAccessPolicy::isRegistry($this->user(['extra' => ['admin' => ['correspondence_registry' => true]]])));
        $this->assertTrue(CorrespondenceAccessPolicy::isRegistry($this->user(['extra' => ['admin' => ['correspondence_registry' => '1']]])));
        $this->assertFalse(CorrespondenceAccessPolicy::isRegistry($this->user(['extra' => ['admin' => ['correspondence_registry' => 'false']]])));
        $this->assertFalse(CorrespondenceAccessPolicy::isRegistry($this->user(['extra' => ['admin' => ['correspondence_registry' => false]]])));
        $this->assertFalse(CorrespondenceAccessPolicy::isRegistry($this->user()));
        $this->assertFalse(CorrespondenceAccessPolicy::isRegistry($this->user(['status' => 'inactive', 'extra' => ['admin' => ['correspondence_registry' => true]]])));
        $this->assertFalse(CorrespondenceAccessPolicy::isRegistry(null));
    }

    public function test_registry_access_is_metadata_only_and_ends_once_the_clerk_can_really_view(): void
    {
        $author = $this->user();
        $clerk = $this->user(['extra' => ['admin' => ['correspondence_registry' => true]]]);
        $plain = $this->user();
        $letter = $this->letter($author, ['status' => Correspondence::STATUS_APPROVED]);
        $asParty = $this->letter($author, ['status' => Correspondence::STATUS_APPROVED, 'parties' => [['user_id' => $clerk->id, 'role' => LetterForm::ROLE_FYI]]]);

        $this->assertTrue(CorrespondenceAccessPolicy::canRegistry($clerk, $letter));
        $this->assertFalse(CorrespondenceAccessPolicy::canView($clerk, $letter));
        $this->assertFalse(CorrespondenceAccessPolicy::canRegistry($clerk, $asParty));
        $this->assertTrue(CorrespondenceAccessPolicy::canView($clerk, $asParty));
        $this->assertFalse(CorrespondenceAccessPolicy::canRegistry($plain, $letter));
        $this->assertFalse(CorrespondenceAccessPolicy::canRegistry(null, $letter));
    }

    public function test_steward_is_the_author_or_a_clerk_on_normal_or_viewable_letters_only(): void
    {
        $author = $this->user();
        $clerk = $this->user(['extra' => ['admin' => ['correspondence_registry' => true]]]);
        $stranger = $this->user();
        $admin = $this->user(['role' => 'admin']);
        $normal = $this->letter($author, ['status' => Correspondence::STATUS_APPROVED]);
        $secret = $this->letter($author, ['status' => Correspondence::STATUS_APPROVED, 'classification' => Correspondence::CLASSIFICATION_CONFIDENTIAL]);
        $secretAsParty = $this->letter($author, ['status' => Correspondence::STATUS_APPROVED, 'classification' => Correspondence::CLASSIFICATION_CONFIDENTIAL, 'parties' => [['user_id' => $clerk->id, 'role' => LetterForm::ROLE_FYI]]]);

        $this->assertTrue(CorrespondenceAccessPolicy::canSteward($author, $secret));
        $this->assertTrue(CorrespondenceAccessPolicy::canSteward($clerk, $normal));
        $this->assertFalse(CorrespondenceAccessPolicy::canSteward($clerk, $secret));
        $this->assertTrue(CorrespondenceAccessPolicy::canSteward($clerk, $secretAsParty));
        $this->assertFalse(CorrespondenceAccessPolicy::canSteward($stranger, $normal));
        $this->assertFalse(CorrespondenceAccessPolicy::canSteward($admin, $normal));
        $this->assertFalse(CorrespondenceAccessPolicy::canSteward(null, $normal));
    }

    public function test_author_helpers_compare_ids_and_party_lookup_ignores_malformed_rows(): void
    {
        $author = $this->user();
        $other = $this->user();
        $letter = $this->letter($author, ['parties' => ['junk', ['user_id' => null, 'role' => LetterForm::ROLE_FYI], ['user_id' => $other->id, 'role' => LetterForm::ROLE_FYI]]]);

        $this->assertTrue(CorrespondenceAccessPolicy::isAuthor($author, $letter));
        $this->assertFalse(CorrespondenceAccessPolicy::isAuthor($other, $letter));
        $this->assertFalse(CorrespondenceAccessPolicy::isAuthor(null, $letter));
        $this->assertSame(LetterForm::ROLE_FYI, CorrespondenceAccessPolicy::partyOf($letter, $other)['role']);
        $this->assertNull(CorrespondenceAccessPolicy::partyOf($letter, $author));
    }
}
