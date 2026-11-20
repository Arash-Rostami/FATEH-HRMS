<?php

namespace Tests\Feature\Traits\Filament;

use App\Traits\Filament\HandlesActionExceptions;
use Closure;
use Filament\Support\Exceptions\Cancel;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class ActionExceptionsParentStub
{
    public ?Closure $mounted = null;

    public function callMountedAction(array $arguments = []): mixed
    {
        return ($this->mounted)($arguments);
    }

    public function getMountedAction(): ?object
    {
        return null;
    }

    public function unmountAction(): void
    {
    }
}

class HandlesActionExceptionsTest extends TestCase
{
    private function host(Closure $mounted): object
    {
        $host = new class extends ActionExceptionsParentStub {
            use HandlesActionExceptions;
        };
        $host->mounted = $mounted;

        return $host;
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

    public function test_successful_action_result_passes_through_untouched(): void
    {
        $result = $this->host(fn (array $arguments) => $arguments)->callMountedAction(['a' => 1]);

        $this->assertSame(['a' => 1], $result);
        $this->assertSame([], $this->sent());
    }

    public function test_failing_action_becomes_a_notification_instead_of_an_exception(): void
    {
        $result = $this->host(fn () => throw new RuntimeException('SQLSTATE secret-internal-detail'))->callMountedAction();

        $this->assertNull($result);
        $notifications = $this->sent();
        $this->assertCount(1, $notifications);
        $this->assertSame('danger', $notifications[0]['status']);
        $this->assertSame('persistent', $notifications[0]['duration']);
        $this->assertMatchesRegularExpression('/ERR-\d{6}-\d{6}-[A-Z0-9]{4}/', $notifications[0]['body']);
        $this->assertStringNotContainsString('secret-internal-detail', $notifications[0]['body']);
    }

    public function test_halt_cancel_and_validation_exceptions_are_rethrown_not_reported(): void
    {
        $control = [
            Halt::class => new Halt,
            Cancel::class => new Cancel,
            ValidationException::class => ValidationException::withMessages(['mountedActions.0.data.field' => 'invalid']),
        ];

        foreach ($control as $class => $exception) {
            try {
                $this->host(fn () => throw $exception)->callMountedAction();
                $this->fail("{$class} was swallowed.");
            } catch (Halt|Cancel|ValidationException $caught) {
                $this->assertSame($exception, $caught);
            }
        }

        $this->assertSame([], $this->sent());
    }
}
