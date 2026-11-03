<?php

namespace Tests\Feature\Http;

use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class IconMissTest extends TestCase
{
    public function test_a_missing_icon_is_logged_once_per_day_with_its_page_and_always_answers_204(): void
    {
        $name = 'zz_' . bin2hex(random_bytes(4));
        Log::spy();

        $this->postJson('/icon-miss', ['name' => $name], ['Referer' => 'https://app.test/login?x=1'])->assertNoContent();
        $this->postJson('/icon-miss', ['name' => $name])->assertNoContent();

        Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message, array $context) => $context['icon'] === $name && $context['page'] === '/login');
    }

    public function test_anything_that_is_not_an_icon_name_is_rejected_and_never_logged(): void
    {
        Log::spy();

        foreach (['', 'Bad Name', '../etc/passwd', 'UPPER', str_repeat('a', 61), 'a-b', ['x'], 5] as $bad) {
            $this->postJson('/icon-miss', ['name' => $bad])->assertStatus(422);
        }

        Log::shouldNotHaveReceived('warning');
    }
}
