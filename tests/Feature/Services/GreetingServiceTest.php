<?php

namespace Tests\Feature\Services;

use App\Services\GreetingService;
use Carbon\Carbon;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class GreetingServiceTest extends TestCase
{
    private GreetingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GreetingService();
    }

    private function timeOfDay(?Carbon $now = null): string
    {
        return (new ReflectionMethod(GreetingService::class, 'getTimeOfDay'))
            ->invoke($this->service, $now);
    }

    private function dayOfWeek(?Carbon $now = null): string
    {
        return (new ReflectionMethod(GreetingService::class, 'getDayOfWeek'))
            ->invoke($this->service, $now);
    }

    private function carbonAt(int $dow, int $hour): Carbon
    {
        return Carbon::create(2020, 1, 1, 0, 0, 0)
            ->next($dow)
            ->setTime($hour, 0, 0);
    }

    private function messages(): array
    {
        return (new ReflectionClass(GreetingService::class))->getConstant('MESSAGES');
    }

    private function shortMessages(): array
    {
        return (new ReflectionClass(GreetingService::class))->getConstant('SHORT_MESSAGES');
    }

    public function test_get_time_of_day_morning_branch_covers_hours_4_through_11(): void
    {
        $this->assertSame('morning', $this->timeOfDay($this->carbonAt(Carbon::SATURDAY, 4)));
        $this->assertSame('morning', $this->timeOfDay($this->carbonAt(Carbon::SATURDAY, 11)));
    }

    public function test_get_time_of_day_afternoon_branch_covers_hours_12_through_16(): void
    {
        $this->assertSame('afternoon', $this->timeOfDay($this->carbonAt(Carbon::SATURDAY, 12)));
        $this->assertSame('afternoon', $this->timeOfDay($this->carbonAt(Carbon::SATURDAY, 16)));
    }

    public function test_get_time_of_day_evening_branch_covers_hours_17_through_20(): void
    {
        $this->assertSame('evening', $this->timeOfDay($this->carbonAt(Carbon::SATURDAY, 17)));
        $this->assertSame('evening', $this->timeOfDay($this->carbonAt(Carbon::SATURDAY, 20)));
    }

    public function test_get_time_of_day_night_branch_covers_hours_21_through_3(): void
    {
        $this->assertSame('night', $this->timeOfDay($this->carbonAt(Carbon::SATURDAY, 21)));
        $this->assertSame('night', $this->timeOfDay($this->carbonAt(Carbon::SATURDAY, 23)));
        $this->assertSame('night', $this->timeOfDay($this->carbonAt(Carbon::SATURDAY, 0)));
        $this->assertSame('night', $this->timeOfDay($this->carbonAt(Carbon::SATURDAY, 3)));
    }

    public function test_get_time_of_day_with_null_carbon_mirrors_current_hour(): void
    {
        $now = Carbon::now();
        $expected = match (true) {
            $now->hour >= 4 && $now->hour < 12 => 'morning',
            $now->hour >= 12 && $now->hour < 17 => 'afternoon',
            $now->hour >= 17 && $now->hour < 21 => 'evening',
            default => 'night',
        };
        $this->assertSame($expected, $this->timeOfDay(null));
    }

    public function test_get_day_of_week_maps_each_weekday(): void
    {
        $this->assertSame('saturday', $this->dayOfWeek($this->carbonAt(Carbon::SATURDAY, 10)));
        $this->assertSame('sunday', $this->dayOfWeek($this->carbonAt(Carbon::SUNDAY, 10)));
        $this->assertSame('monday', $this->dayOfWeek($this->carbonAt(Carbon::MONDAY, 10)));
        $this->assertSame('tuesday', $this->dayOfWeek($this->carbonAt(Carbon::TUESDAY, 10)));
        $this->assertSame('wednesday', $this->dayOfWeek($this->carbonAt(Carbon::WEDNESDAY, 10)));
        $this->assertSame('thursday', $this->dayOfWeek($this->carbonAt(Carbon::THURSDAY, 10)));
        $this->assertSame('friday', $this->dayOfWeek($this->carbonAt(Carbon::FRIDAY, 10)));
    }

    public function test_get_day_of_week_rolls_back_one_day_for_hours_before_4(): void
    {
        $this->assertSame('friday', $this->dayOfWeek($this->carbonAt(Carbon::SATURDAY, 0)));
        $this->assertSame('friday', $this->dayOfWeek($this->carbonAt(Carbon::SATURDAY, 3)));
        $this->assertSame('saturday', $this->dayOfWeek($this->carbonAt(Carbon::SUNDAY, 2)));
        $this->assertSame('thursday', $this->dayOfWeek($this->carbonAt(Carbon::FRIDAY, 1)));
    }

    public function test_get_day_of_week_does_not_roll_back_at_hour_4(): void
    {
        $this->assertSame('saturday', $this->dayOfWeek($this->carbonAt(Carbon::SATURDAY, 4)));
    }

    public function test_get_day_of_week_defaults_to_today_when_carbon_is_null(): void
    {
        $now = Carbon::now()->copy();
        if ($now->hour < 4) {
            $now->subDay();
        }
        $expected = match ($now->dayOfWeek) {
            Carbon::SATURDAY => 'saturday',
            Carbon::SUNDAY => 'sunday',
            Carbon::MONDAY => 'monday',
            Carbon::TUESDAY => 'tuesday',
            Carbon::WEDNESDAY => 'wednesday',
            Carbon::THURSDAY => 'thursday',
            Carbon::FRIDAY => 'friday',
        };
        $this->assertSame($expected, $this->dayOfWeek(null));
    }

    public function test_messages_const_has_all_28_time_day_combinations_each_with_5_entries(): void
    {
        $times = ['morning', 'afternoon', 'evening', 'night'];
        $days = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $messages = $this->messages();

        $this->assertCount(28, $messages);

        foreach ($times as $time) {
            foreach ($days as $day) {
                $key = "{$time}_{$day}";
                $this->assertArrayHasKey($key, $messages, "Missing key {$key}");
                $this->assertCount(5, $messages[$key], "Key {$key} should have 5 messages");
                foreach ($messages[$key] as $message) {
                    $this->assertIsString($message);
                    $this->assertNotSame('', $message);
                }
            }
        }
    }

    public function test_short_messages_const_has_4_time_keys_each_with_7_templates_containing_name_token(): void
    {
        $times = ['morning', 'afternoon', 'evening', 'night'];
        $short = $this->shortMessages();

        $this->assertCount(4, $short);

        foreach ($times as $time) {
            $this->assertArrayHasKey($time, $short, "Missing short key {$time}");
            $this->assertCount(7, $short[$time], "Short key {$time} should have 7 templates");
            foreach ($short[$time] as $template) {
                $this->assertStringContainsString('{name}', $template);
            }
        }
    }

    public function test_get_greeting_returns_a_message_from_the_expected_time_day_bucket(): void
    {
        $now = Carbon::now();
        $time = $this->timeOfDay($now);
        $day = $this->dayOfWeek($now);
        $key = "{$time}_{$day}";

        $bucket = $this->messages()[$key];
        $result = $this->service->getGreeting();

        $this->assertContains($result, $bucket);
        $this->assertIsString($result);
        $this->assertNotSame('', $result);
    }

    public function test_get_greeting_returns_non_empty_string_for_current_day(): void
    {
        $this->assertNotSame('', $this->service->getGreeting());
        $this->assertIsString($this->service->getGreeting());
    }

    public function test_get_short_greeting_substitutes_name_into_a_template_from_the_current_time_bucket(): void
    {
        $time = $this->timeOfDay(Carbon::now());
        $bucket = $this->shortMessages()[$time];

        $result = $this->service->getShortGreeting('آرش');

        $this->assertStringNotContainsString('{name}', $result);
        $this->assertStringContainsString('آرش', $result);
        $expected = array_map(fn (string $t): string => str_replace('{name}', 'آرش', $t), $bucket);
        $this->assertContains($result, $expected);
    }

    public function test_get_short_greeting_with_empty_name_strips_placeholder_and_returns_template(): void
    {
        $time = $this->timeOfDay(Carbon::now());
        $bucket = $this->shortMessages()[$time];

        $result = $this->service->getShortGreeting('');

        $this->assertStringNotContainsString('{name}', $result);
        $expected = array_map(fn (string $t): string => str_replace('{name}', '', $t), $bucket);
        $this->assertContains($result, $expected);
    }

    public function test_get_short_greeting_preserves_ascii_name_substitution(): void
    {
        $result = $this->service->getShortGreeting('John');

        $this->assertStringContainsString('John', $result);
        $this->assertStringNotContainsString('{name}', $result);
    }

    public function test_get_greeting_and_get_short_greeting_are_consistent_across_repeated_calls(): void
    {
        $now = Carbon::now();
        $bucket = $this->messages()["{$this->timeOfDay($now)}_{$this->dayOfWeek($now)}"];

        for ($i = 0; $i < 5; $i++) {
            $greeting = $this->service->getGreeting();
            $short = $this->service->getShortGreeting('نام');
            $this->assertContains($greeting, $bucket);
            $this->assertStringNotContainsString('{name}', $short);
        }
    }

    public function test_get_greeting_with_rolled_back_hour_still_finds_a_valid_bucket(): void
    {
        $now = Carbon::now()->setTime(2, 0, 0);
        $time = $this->timeOfDay($now);
        $day = $this->dayOfWeek($now);
        $key = "{$time}_{$day}";

        $this->assertArrayHasKey($key, $this->messages());
    }
}