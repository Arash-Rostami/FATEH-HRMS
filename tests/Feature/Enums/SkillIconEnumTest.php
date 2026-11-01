<?php

namespace Tests\Feature\Enums;

use App\Enums\SkillIcon;
use Tests\TestCase;

class SkillIconEnumTest extends TestCase
{
    private const CASE_VALUES = [
        'Code' => 'code',
        'Terminal' => 'terminal',
        'DataObject' => 'data_object',
        'Database' => 'database',
        'Cloud' => 'cloud',
        'Security' => 'security',
        'BugReport' => 'bug_report',
        'IntegrationInstructions' => 'integration_instructions',
        'Hub' => 'hub',
        'Memory' => 'memory',
        'DeveloperBoard' => 'developer_board',
        'Api' => 'api',
        'DesignServices' => 'design_services',
        'Brush' => 'brush',
        'Palette' => 'palette',
        'PhotoCamera' => 'photo_camera',
        'Videocam' => 'videocam',
        'Movie' => 'movie',
        'MusicNote' => 'music_note',
        'Edit' => 'edit',
        'Language' => 'language',
        'Translate' => 'translate',
        'RecordVoiceOver' => 'record_voice_over',
        'Campaign' => 'campaign',
        'Forum' => 'forum',
        'Groups' => 'groups',
        'Handshake' => 'handshake',
        'Diversity3' => 'diversity_3',
        'Calculate' => 'calculate',
        'Science' => 'science',
        'Biotech' => 'biotech',
        'Psychology' => 'psychology',
        'School' => 'school',
        'MenuBook' => 'menu_book',
        'AutoStories' => 'auto_stories',
        'Lightbulb' => 'lightbulb',
        'BusinessCenter' => 'business_center',
        'TrendingUp' => 'trending_up',
        'Monitoring' => 'monitoring',
        'Insights' => 'insights',
        'Assessment' => 'assessment',
        'Gavel' => 'gavel',
        'AccountBalance' => 'account_balance',
        'Paid' => 'paid',
        'Engineering' => 'engineering',
        'Construction' => 'construction',
        'PrecisionManufacturing' => 'precision_manufacturing',
        'Build' => 'build',
        'Settings' => 'settings',
        'Plumbing' => 'plumbing',
        'ElectricalServices' => 'electrical_services',
        'SportsSoccer' => 'sports_soccer',
        'FitnessCenter' => 'fitness_center',
        'DirectionsRun' => 'directions_run',
        'SelfImprovement' => 'self_improvement',
        'SupportAgent' => 'support_agent',
        'HeadsetMic' => 'headset_mic',
        'MedicalServices' => 'medical_services',
        'Restaurant' => 'restaurant',
        'LocalShipping' => 'local_shipping',
        'Flight' => 'flight',
        'DirectionsCar' => 'directions_car',
        'WorkspacePremium' => 'workspace_premium',
        'Star' => 'star',
        'MilitaryTech' => 'military_tech',
        'RocketLaunch' => 'rocket_launch',
        'Bolt' => 'bolt',
    ];

    public function test_cases_count_matches_expected_set(): void
    {
        $this->assertSame(count(self::CASE_VALUES), count(SkillIcon::cases()));
    }

    public function test_cases_are_returned_in_declared_order_with_expected_names(): void
    {
        $this->assertSame(array_keys(self::CASE_VALUES), array_map(fn ($c) => $c->name, SkillIcon::cases()));
    }

    public function test_every_case_value_is_unique_and_backed_string_matching_contract(): void
    {
        $values = array_map(fn (SkillIcon $c) => $c->value, SkillIcon::cases());

        $this->assertSame(array_values(self::CASE_VALUES), $values);
        $this->assertSame(count($values), count(array_unique($values)));
    }

    public function test_default_returns_workspace_premium(): void
    {
        $this->assertSame(SkillIcon::WorkspacePremium, SkillIcon::default());
        $this->assertSame('workspace_premium', SkillIcon::default()->value);
    }

    public function test_try_from_returns_case_for_valid_value_and_null_for_invalid_value(): void
    {
        foreach (self::CASE_VALUES as $value) {
            $this->assertSame($value, SkillIcon::tryFrom($value)->value);
        }

        $this->assertNull(SkillIcon::tryFrom('workspace_premiumx'));
        $this->assertNull(SkillIcon::tryFrom(''));
        $this->assertNull(SkillIcon::tryFrom('WorkspacePremium'));
    }

    public function test_from_returns_case_for_valid_value_and_throws_for_invalid_value(): void
    {
        $first = array_key_first(self::CASE_VALUES);
        $value = self::CASE_VALUES[$first];

        $this->assertSame($value, SkillIcon::from($value)->value);

        $threw = false;
        try {
            SkillIcon::from('workspace_premiumx');
        } catch (\ValueError) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected from() to throw ValueError for an invalid value.');
    }
}