<?php

namespace Tests\Feature\Models;

use App\Enums\SkillTier;
use App\Models\SkillUser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SkillUserModelTest extends TestCase
{
    public function test_active_window_days_constant_is_ninety(): void
    {
        $this->assertSame(90, SkillUser::ACTIVE_WINDOW_DAYS);
    }

    public function test_endorsement_threshold_constant_is_one(): void
    {
        $this->assertSame(1, SkillUser::ENDORSEMENT_THRESHOLD);
    }

    public function test_endorsement_saturation_cap_constant_is_four(): void
    {
        $this->assertSame(4, SkillUser::ENDORSEMENT_SATURATION_CAP);
    }

    public function test_new_badge_until_constant_is_expected_date(): void
    {
        $this->assertSame('2026-09-02', SkillUser::NEW_BADGE_UNTIL);
    }

    public function test_new_badge_until_parses_constant_into_carbon(): void
    {
        $this->assertTrue(SkillUser::newBadgeUntil()->equalTo(Carbon::parse('2026-09-02')));
    }

    public function test_has_endorser_returns_true_when_user_id_present(): void
    {
        $skillUser = new SkillUser(['endorsers' => [5, 9]]);

        $this->assertTrue($skillUser->hasEndorser(9));
    }

    public function test_has_endorser_returns_false_when_user_id_absent(): void
    {
        $skillUser = new SkillUser(['endorsers' => [5, 9]]);

        $this->assertFalse($skillUser->hasEndorser(1));
    }

    public function test_has_endorser_returns_false_when_endorsers_null(): void
    {
        $skillUser = new SkillUser();

        $this->assertFalse($skillUser->hasEndorser(1));
    }

    public function test_is_active_returns_true_within_window(): void
    {
        $skillUser = new SkillUser(['last_used_at' => now()->subDays(SkillUser::ACTIVE_WINDOW_DAYS - 1)]);

        $this->assertTrue($skillUser->isActive(SkillUser::ACTIVE_WINDOW_DAYS));
    }

    public function test_is_active_returns_false_outside_window(): void
    {
        $skillUser = new SkillUser(['last_used_at' => now()->subDays(SkillUser::ACTIVE_WINDOW_DAYS + 1)]);

        $this->assertFalse($skillUser->isActive(SkillUser::ACTIVE_WINDOW_DAYS));
    }

    public function test_is_active_returns_false_when_last_used_at_null(): void
    {
        $skillUser = new SkillUser();

        $this->assertFalse($skillUser->isActive(SkillUser::ACTIVE_WINDOW_DAYS));
    }

    public function test_is_endorsed_returns_true_at_or_above_threshold(): void
    {
        $skillUser = new SkillUser(['endorsements_count' => SkillUser::ENDORSEMENT_THRESHOLD]);

        $this->assertTrue($skillUser->isEndorsed(SkillUser::ENDORSEMENT_THRESHOLD));
    }

    public function test_is_endorsed_returns_false_below_threshold(): void
    {
        $skillUser = new SkillUser(['endorsements_count' => 0]);

        $this->assertFalse($skillUser->isEndorsed(SkillUser::ENDORSEMENT_THRESHOLD));
    }

    public function test_state_tier_returns_endorsed_when_saturation_cap_met(): void
    {
        $skillUser = new SkillUser([
            'endorsements_count' => SkillUser::ENDORSEMENT_SATURATION_CAP,
            'last_used_at' => now()->subDays(SkillUser::ACTIVE_WINDOW_DAYS + 1),
        ]);

        $this->assertSame(SkillTier::Endorsed, $skillUser->stateTier());
    }

    public function test_state_tier_returns_active_when_endorsed_below_saturation_cap_but_recently_used(): void
    {
        $skillUser = new SkillUser([
            'endorsements_count' => SkillUser::ENDORSEMENT_THRESHOLD,
            'last_used_at' => now()->subDay(),
        ]);

        $this->assertSame(SkillTier::Active, $skillUser->stateTier());
    }

    public function test_state_tier_returns_active_when_used_within_window_but_not_endorsed(): void
    {
        $skillUser = new SkillUser([
            'endorsements_count' => 0,
            'last_used_at' => now()->subDay(),
        ]);

        $this->assertSame(SkillTier::Active, $skillUser->stateTier());
    }

    public function test_state_tier_returns_unused_when_neither_endorsed_nor_recently_used(): void
    {
        $skillUser = new SkillUser([
            'endorsements_count' => 0,
            'last_used_at' => null,
        ]);

        $this->assertSame(SkillTier::Unused, $skillUser->stateTier());
    }

    public function test_state_tier_prefers_endorsed_over_active_when_both_conditions_hold(): void
    {
        $skillUser = new SkillUser([
            'endorsements_count' => SkillUser::ENDORSEMENT_SATURATION_CAP,
            'last_used_at' => now()->subDay(),
        ]);

        $this->assertSame(SkillTier::Endorsed, $skillUser->stateTier());
    }

    public function test_is_dormant_true_when_endorsed_tier_but_not_recently_active(): void
    {
        $skillUser = new SkillUser([
            'endorsements_count' => SkillUser::ENDORSEMENT_SATURATION_CAP,
            'last_used_at' => now()->subDays(SkillUser::ACTIVE_WINDOW_DAYS + 1),
        ]);

        $this->assertTrue($skillUser->isDormant());
    }

    public function test_is_dormant_false_when_endorsed_and_recently_active(): void
    {
        $skillUser = new SkillUser([
            'endorsements_count' => SkillUser::ENDORSEMENT_SATURATION_CAP,
            'last_used_at' => now()->subDay(),
        ]);

        $this->assertFalse($skillUser->isDormant());
    }

    public function test_is_dormant_false_when_not_endorsed_tier(): void
    {
        $skillUser = new SkillUser([
            'endorsements_count' => 0,
            'last_used_at' => null,
        ]);

        $this->assertFalse($skillUser->isDormant());
    }

    public function test_is_sole_endorsement_true_at_exactly_one(): void
    {
        $this->assertTrue((new SkillUser(['endorsements_count' => 1]))->isSoleEndorsement());
    }

    public function test_is_sole_endorsement_false_at_zero_and_at_multiple(): void
    {
        $this->assertFalse((new SkillUser(['endorsements_count' => 0]))->isSoleEndorsement());
        $this->assertFalse((new SkillUser(['endorsements_count' => 2]))->isSoleEndorsement());
    }

    public function test_endorsement_label_variants_by_count(): void
    {
        $this->assertSame('بدون تأیید', (new SkillUser(['endorsements_count' => 0]))->endorsementLabel());
        $this->assertSame('تأیید یک همکار', (new SkillUser(['endorsements_count' => 1]))->endorsementLabel());
        $this->assertSame('تأیید 2 همکار (از 4)', (new SkillUser(['endorsements_count' => 2]))->endorsementLabel());
        $this->assertSame('تأیید 4 همکار', (new SkillUser(['endorsements_count' => SkillUser::ENDORSEMENT_SATURATION_CAP]))->endorsementLabel());
        $this->assertSame('تأیید 7 همکار', (new SkillUser(['endorsements_count' => 7]))->endorsementLabel());
    }

    public function test_notify_queues_a_dedupe_keyed_entry_with_default_success_type(): void
    {
        $userId = 900001;
        Cache::forget("skill_notices:{$userId}");

        SkillUser::notify($userId, 'dedupe-a', 'Body text');

        $notices = Cache::get("skill_notices:{$userId}", []);
        $this->assertArrayHasKey('dedupe-a', $notices);
        $this->assertSame('Body text', $notices['dedupe-a']['body']);
        $this->assertSame('success', $notices['dedupe-a']['type']);

        Cache::forget("skill_notices:{$userId}");
    }

    public function test_notify_overwrites_prior_entry_sharing_the_same_dedupe_key(): void
    {
        $userId = 900002;
        Cache::forget("skill_notices:{$userId}");

        SkillUser::notify($userId, 'dedupe-b', 'First body', 'success');
        SkillUser::notify($userId, 'dedupe-b', 'Second body', 'warning');

        $notices = Cache::get("skill_notices:{$userId}", []);
        $this->assertCount(1, $notices);
        $this->assertSame('Second body', $notices['dedupe-b']['body']);
        $this->assertSame('warning', $notices['dedupe-b']['type']);

        Cache::forget("skill_notices:{$userId}");
    }
}
