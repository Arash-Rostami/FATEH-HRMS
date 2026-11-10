<?php

namespace Tests\Feature\Traits\Filament;

use App\Traits\Filament\HandlesSaveExceptions;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

class HandlesSaveExceptionsTest extends TestCase
{
    private function host(): object
    {
        return new class {
            use HandlesSaveExceptions {
                reportSaveException as public;
                notifySaveException as public;
            }
        };
    }

    private function sent(): array
    {
        return session()->get('filament.notifications', []);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->setLocale('fa');
        Log::spy();
        session()->forget('filament.notifications');
    }

    public function test_report_save_exception_halts_and_requests_a_transaction_rollback(): void
    {
        try {
            $this->host()->reportSaveException(new RuntimeException('boom'));
            $this->fail('Halt was not thrown.');
        } catch (Halt $halt) {
            $this->assertTrue($halt->shouldRollbackDatabaseTransaction());
        }
    }

    public function test_report_save_exception_sends_a_persistent_danger_notification_with_reference(): void
    {
        try {
            $this->host()->reportSaveException(new RuntimeException('boom'));
        } catch (Halt) {
        }

        $notifications = $this->sent();

        $this->assertCount(1, $notifications);
        $this->assertSame('danger', $notifications[0]['status']);
        $this->assertSame('persistent', $notifications[0]['duration']);
        $this->assertMatchesRegularExpression('/ERR-\d{6}-\d{6}-[A-Z0-9]{4}/', $notifications[0]['body']);
    }

    public function test_notification_never_leaks_the_raw_exception_text(): void
    {
        $this->host()->notifySaveException(new RuntimeException('SQLSTATE secret-internal-detail'));

        $notification = $this->sent()[0];

        $this->assertStringNotContainsString('secret-internal-detail', $notification['title']);
        $this->assertStringNotContainsString('secret-internal-detail', $notification['body']);
    }

    public function test_notify_save_exception_reports_without_halting(): void
    {
        $this->host()->notifySaveException(new RuntimeException('boom'));

        $this->assertCount(1, $this->sent());
    }
}
