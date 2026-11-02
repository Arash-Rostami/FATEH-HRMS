<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\ForgotPassword;
use App\Models\User;
use App\Notifications\PasswordResetNotification as ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        $this->app->setLocale('fa');
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

    public function test_valid_email_sends_reset_link_and_flashes_status()
    {
        Notification::fake();

        $user = User::factory()->create(['status' => 'active']);

        Livewire::test(ForgotPassword::class)
            ->set('email', $user->email)
            ->call('send')
            ->assertSee('لینک بازیابی رمز عبور به ایمیل شما ارسال شد')
            ->assertHasNoErrors();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_after_sending_the_button_is_replaced_by_the_message_and_cannot_resubmit()
    {
        Notification::fake();

        $user = User::factory()->create(['status' => 'active']);

        Livewire::test(ForgotPassword::class)
            ->set('email', $user->email)
            ->call('send')
            ->assertSet('sent', true)
            ->assertDontSee('ارسال لینک بازیابی<', false)
            ->call('send');

        Notification::assertSentToTimes($user, ResetPassword::class, 1);
    }

    public function test_reset_mail_uses_the_organization_template_with_a_working_link()
    {
        $user = User::factory()->create(['status' => 'active']);

        $mail = (new ResetPassword('tok123'))->toMail($user);
        $html = $mail->render();

        $this->assertStringContainsString('تغییر رمز عبور', $html);
        $this->assertStringContainsString('/reset-password/tok123?email=' . urlencode($user->email), $html);
        $this->assertStringContainsString(config('app.organization_name'), $html);
    }

    public function test_admin_panel_reset_uses_the_same_notification_with_its_own_url_and_persian_toast()
    {
        $user = User::factory()->create(['status' => 'active']);

        $notification = app(\Filament\Auth\Notifications\ResetPassword::class, ['token' => 'adm1']);
        $this->assertInstanceOf(ResetPassword::class, $notification);

        $notification->url = 'http://localhost/admin/password-reset/reset?token=adm1';
        $this->assertStringContainsString('/admin/password-reset/reset?token=adm1', $notification->toMail($user)->render());

        $this->assertSame('لینک بازیابی رمز عبور به ایمیل شما ارسال شد.', __('passwords.sent'));
    }

    public function test_unknown_email_adds_error_and_does_not_flash()
    {
        Livewire::test(ForgotPassword::class)
            ->set('email', 'nobody@example.test')
            ->call('send')
            ->assertHasErrors(['email'])
            ->assertSessionMissing('statusSwitcher');
    }

    public function test_validation_requires_email()
    {
        Livewire::test(ForgotPassword::class)
            ->set('email', '')
            ->call('send')
            ->assertHasErrors(['email']);
    }

    public function test_invalid_email_format_adds_error()
    {
        Livewire::test(ForgotPassword::class)
            ->set('email', 'not-an-email')
            ->call('send')
            ->assertHasErrors(['email']);
    }
}