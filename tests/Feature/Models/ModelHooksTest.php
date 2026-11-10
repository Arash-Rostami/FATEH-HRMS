<?php

namespace Tests\Feature\Models;

use Tests\TestCase;
use App\Models\Correspondence;
use App\Models\Letterhead;

class ModelHooksTest extends TestCase
{
    private const HOOK_CALL = '/static::(creating|created|updating|updated|saving|saved|deleting|deleted|restoring|restored|forceDeleting|forceDeleted|retrieved|replicating)\(/';

    public function test_every_hooks_trait_is_booted_by_exactly_one_model_and_declared_last(): void
    {
        $files = glob(app_path('Models/Concerns/Hooks/Has*Hooks.php'));

        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $name = basename($file, '.php');
            $trait = "App\\Models\\Concerns\\Hooks\\{$name}";
            $short = substr($name, 3, -5);
            $model = 'App\\Models\\' . ($short === 'Dms' ? 'DMS' : $short);

            $this->assertTrue(trait_exists($trait), "{$trait} is not a trait");
            $this->assertTrue(class_exists($model), "{$trait} has no matching model {$model}");
            $this->assertTrue(method_exists($model, "boot{$name}"), "{$trait} must expose boot{$name}()");
            $this->assertSame($trait, array_key_last(class_uses($model)), "{$trait} must be the last trait of {$model}");
        }
    }

    public function test_no_model_registers_lifecycle_hooks_inline(): void
    {
        foreach (glob(app_path('Models/*.php')) as $file) {
            $this->assertDoesNotMatchRegularExpression(
                self::HOOK_CALL,
                file_get_contents($file),
                basename($file) . ' registers a lifecycle hook inline; move it to Concerns/Hooks'
            );
        }
    }

    public function test_shared_model_concerns_are_composed_by_their_expected_models(): void
    {
        $concerns = 'App\\Models\\Concerns\\';

        $expectations = [
            'App\\Models\\Task' => ['HasUrgencyState', 'HasBoardRanking', 'HasCycleStep', 'HasTaskApproval'],
            'App\\Models\\Ticket' => ['HasUrgencyState', 'HasDailyQuota'],
            'App\\Models\\Message' => ['HasThreadMessages'],
            'App\\Models\\ChannelMessage' => ['HasThreadMessages'],
            'App\\Models\\User' => ['HasBookingRows', 'HasExtraPreferences', 'HasPresence', 'HasTodaysDesk'],
            'App\\Models\\Project' => ['HasProjectFollowUps'],
            'App\\Models\\AiReport' => ['HasActive', 'HasSharedRecipients', 'HasVersionHistory', 'HasDailyQuota', 'HasGenerationLifecycle'],
            'App\\Models\\Correspondence' => ['HasDailyQuota', 'HasGenerationLifecycle', 'HasSharedRecipients', 'HasVersionHistory'],
            'App\\Models\\Letterhead' => ['HasActive', 'HasLetterheadNumbering'],
        ];

        $relationTraits = [
            'HasUser' => ['AiReport', 'Authority', 'ChannelMember', 'Comment', 'Correspondence', 'Credential', 'EnergyTest', 'Event', 'FAQ', 'Feed', 'Onboarding', 'Permission', 'Poll', 'Post', 'Profile', 'Reaction', 'ReleaseRequest', 'Reminder', 'Reply', 'Report', 'Reservation', 'Review', 'SkillUser', 'Suggestion'],
            'HasDepartment' => ['Authority', 'FAQ', 'Photo', 'Profile', 'Report', 'Review', 'TaskDetail'],
        ];

        foreach ($relationTraits as $trait => $models) {
            foreach ($models as $model) {
                $expectations["App\\Models\\{$model}"][] = $trait;
            }
        }

        foreach ($expectations as $model => $traits) {
            foreach ($traits as $trait) {
                $this->assertContains($concerns . $trait, class_uses($model), "{$model} must compose {$trait}");
            }
        }
    }

    public function test_correspondence_and_letterhead_keep_their_hook_traits_as_the_last_model_concerns(): void
    {
        $this->assertSame(
            'App\\Models\\Concerns\\Hooks\\HasCorrespondenceHooks',
            array_key_last(class_uses(Correspondence::class)),
        );
        $this->assertSame(
            'App\\Models\\Concerns\\Hooks\\HasLetterheadHooks',
            array_key_last(class_uses(Letterhead::class)),
        );
        $this->assertTrue(method_exists(Correspondence::class, 'bootHasCorrespondenceHooks'));
        $this->assertTrue(method_exists(Letterhead::class, 'bootHasLetterheadHooks'));
    }
}
