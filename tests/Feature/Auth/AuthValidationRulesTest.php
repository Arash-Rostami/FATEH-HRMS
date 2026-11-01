<?php

namespace Tests\Feature\Auth;

use App\Traits\AuthValidationRules;
use ReflectionMethod;
use Tests\TestCase;

class AuthValidationRulesTest extends TestCase
{
    private function invokeProtected(object $proxy, string $method, array $args = []): mixed
    {
        $reflection = new ReflectionMethod($proxy, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($proxy, ...$args);
    }

    public function test_email_rules_default_without_unique(): void
    {
        $proxy = new class { use AuthValidationRules; };

        $result = $this->invokeProtected($proxy, 'emailRules', [false]);

        $this->assertSame(['required', 'string', 'email', 'max:255'], $result);
    }

    public function test_email_rules_with_unique_appends_unique_rule(): void
    {
        $proxy = new class { use AuthValidationRules; };

        $result = $this->invokeProtected($proxy, 'emailRules', [true]);

        $this->assertContains('unique:users,email', $result);
        $this->assertCount(5, $result);
        $this->assertSame('unique:users,email', $result[4]);
    }

    public function test_password_rules_default_without_confirmed(): void
    {
        $proxy = new class { use AuthValidationRules; };

        $result = $this->invokeProtected($proxy, 'passwordRules', [false]);

        $this->assertSame(['required', 'string', 'min:8'], $result);
    }

    public function test_password_rules_with_confirmed_appends_confirmed_rule(): void
    {
        $proxy = new class { use AuthValidationRules; };

        $result = $this->invokeProtected($proxy, 'passwordRules', [true]);

        $this->assertSame(['required', 'string', 'min:8', 'confirmed'], $result);
    }

    public function test_validation_messages_returns_expected_persian_keys(): void
    {
        $proxy = new class { use AuthValidationRules; };

        $result = $this->invokeProtected($proxy, 'validationMessages');

        $this->assertCount(9, $result);

        $this->assertArrayHasKey('email.required', $result);
        $this->assertArrayHasKey('email.email', $result);
        $this->assertArrayHasKey('email.unique', $result);
        $this->assertArrayHasKey('password.required', $result);
        $this->assertArrayHasKey('password.min', $result);
        $this->assertArrayHasKey('password.confirmed', $result);
        $this->assertArrayHasKey('name.required', $result);
        $this->assertArrayHasKey('name.string', $result);
        $this->assertArrayHasKey('name.max', $result);

        $this->assertSame('لطفاً آدرس ایمیل خود را وارد کنید.', $result['email.required']);
    }
}