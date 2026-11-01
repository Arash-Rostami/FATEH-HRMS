<?php

namespace Tests\Feature\View;

use App\Models\Profile;
use App\Models\User;
use App\View\Composers\OccasionComposer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OccasionComposerTest extends TestCase
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
        Carbon::setTestNow();
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

    private function compose(): array
    {
        $view = view('components.dashboard.modal.occasion');
        (new OccasionComposer)->compose($view);

        return $view->getData();
    }

    public function test_composes_a_birthday_greeting_and_marks_it_shown_until_midnight(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));
        $user = User::factory()->create(['status' => 'active', 'name' => 'آرش']);
        Profile::factory()->create(['user_id' => $user->id, 'birthdate' => today()]);
        Auth::login($user);

        $data = $this->compose();

        $this->assertSame('birthday', $data['occasionType']);
        $this->assertStringContainsString('آرش', $data['title']);
        $this->assertNotSame('', $data['message']);
        $this->assertTrue(Cache::has('birthdate_' . $user->id));

        Carbon::setTestNow(Carbon::today()->setTime(23, 59, 59)->addSecond());
        $this->assertFalse(Cache::has('birthdate_' . $user->id));
    }

    public function test_birthday_greeting_stays_suppressed_for_the_rest_of_the_same_day(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'birthdate' => today()]);
        Auth::login($user);

        $this->assertSame('birthday', $this->compose()['occasionType']);

        Carbon::setTestNow(Carbon::today()->setTime(20, 30));
        $this->assertNull($this->compose()['occasionType']);
    }

    public function test_composes_an_anniversary_greeting_and_marks_the_start_date_key(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'start_date' => today()]);
        Auth::login($user);

        $data = $this->compose();

        $this->assertSame('anniversary', $data['occasionType']);
        $this->assertTrue(Cache::has('start_date_' . $user->id));
    }
}