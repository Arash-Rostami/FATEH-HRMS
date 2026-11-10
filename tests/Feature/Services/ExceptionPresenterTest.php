<?php

namespace Tests\Feature\Services;

use App\Services\ExceptionPresenter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use League\Flysystem\UnableToRetrieveMetadata;
use PDOException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class ExceptionPresenterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->setLocale('fa');
    }

    private function queryException(int $driverCode): QueryException
    {
        $previous = new PDOException("SQLSTATE[HY000]: Driver error {$driverCode}");
        $previous->errorInfo = ['HY000', $driverCode, 'driver message'];

        return new QueryException('mysql', 'select * from `projects`', [], $previous);
    }

    public function test_reference_matches_the_error_tracking_format(): void
    {
        $presented = ExceptionPresenter::present(new RuntimeException('boom'));

        $this->assertMatchesRegularExpression('/^ERR-\d{6}-\d{6}-[A-Z0-9]{4}$/', $presented['reference']);
        $this->assertSame(['title', 'body', 'reference'], array_keys($presented));
        $this->assertStringContainsString('کد پیگیری: ' . $presented['reference'], $presented['body']);
    }

    public function test_reference_is_logged_with_the_exception(): void
    {
        $exception = new RuntimeException('boom');
        $reference = null;

        Log::shouldReceive('error')->once()->andReturnUsing(
            function (string $message, array $context) use ($exception, &$reference): void {
                $reference = $context['reference'];
                $this->assertStringContainsString("[{$reference}]", $message);
                $this->assertStringContainsString('boom', $message);
                $this->assertSame($exception, $context['exception']);
            }
        );

        $presented = ExceptionPresenter::present($exception);

        $this->assertSame($reference, $presented['reference']);
    }

    public function test_framework_benign_exceptions_are_presented_without_logging(): void
    {
        Log::shouldReceive('error')->never();

        $tokenMismatch = ExceptionPresenter::present(new TokenMismatchException());
        $validation = ExceptionPresenter::present(ValidationException::withMessages(['assigned_to' => 'blocked']));

        $this->assertSame('زمان اعتبار نشست شما تمام شد', $tokenMismatch['title']);
        $this->assertStringNotContainsString('کد پیگیری', $tokenMismatch['body']);
        $this->assertSame('انجام این کار ممکن نیست', $validation['title']);
        $this->assertSame('blocked', $validation['body']);
    }

    public function test_reported_exceptions_keep_the_reference_line_in_the_body(): void
    {
        $presented = ExceptionPresenter::present(new RuntimeException('boom'));

        $this->assertStringContainsString('کد پیگیری: ' . $presented['reference'], $presented['body']);
    }

    public function test_generic_fallback_presents_the_system_error_title_and_body(): void
    {
        $presented = ExceptionPresenter::present(new RuntimeException('boom'));

        $this->assertSame('خطایی رخ داد', $presented['title']);
        $this->assertStringContainsString('هنگام پردازش درخواست خطایی رخ داد', $presented['body']);
    }

    public function test_model_not_found_presents_the_not_found_title(): void
    {
        $presented = ExceptionPresenter::present(new ModelNotFoundException());

        $this->assertSame('اطلاعات پیدا نشد', $presented['title']);
        $this->assertStringContainsString('اطلاعات مورد نظر پیدا نشد', $presented['body']);
    }

    public function test_authorization_exception_presents_the_forbidden_title(): void
    {
        $presented = ExceptionPresenter::present(new AuthorizationException());

        $this->assertSame('اجازهٔ انجام این کار را ندارید', $presented['title']);
        $this->assertStringContainsString('اجازهٔ انجام این کار را نمی‌دهد', $presented['body']);
    }

    public function test_filesystem_exception_presents_the_upload_incomplete_title(): void
    {
        $presented = ExceptionPresenter::present(UnableToRetrieveMetadata::mimeType('livewire-tmp/missing.mp4'));

        $this->assertSame('بارگذاری فایل کامل نشد', $presented['title']);
        $this->assertStringContainsString('فایل را از کادر بارگذاری حذف کنید', $presented['body']);
    }

    public function test_query_exception_driver_codes_map_to_distinct_titles(): void
    {
        $expected = [
            1264 => 'عدد بزرگ‌تر از حد مجاز است',
            1406 => 'متن طولانی‌تر از حد مجاز است',
            1265 => 'قالب اطلاعات معتبر نیست',
            1366 => 'قالب اطلاعات معتبر نیست',
            1048 => 'اطلاعات لازم کامل نیست',
            1062 => 'اطلاعات تکراری است',
            1451 => 'این مورد در حال استفاده است',
            1452 => 'مورد انتخاب‌شده معتبر نیست',
            1213 => 'سامانه مشغول است',
            1205 => 'سامانه مشغول است',
            2002 => 'ارتباط برقرار نشد',
            2006 => 'ارتباط برقرار نشد',
            2013 => 'ارتباط برقرار نشد',
            1146 => 'تنظیمات سامانه مشکل دارد',
            1054 => 'تنظیمات سامانه مشکل دارد',
            1049 => 'تنظیمات سامانه مشکل دارد',
            9999 => 'اطلاعات ذخیره نشد',
        ];

        $titles = [];
        foreach ($expected as $driverCode => $title) {
            $titles[$driverCode] = ExceptionPresenter::present($this->queryException($driverCode))['title'];
            $this->assertSame($title, $titles[$driverCode]);
        }

        $distinct = array_intersect_key($titles, array_flip([1264, 1406, 1048, 1062, 1451, 1452, 1213, 2002]));
        $this->assertSame($distinct, array_unique($distinct));
    }

    public function test_duplicate_entry_presents_the_duplicate_body_and_reference_line(): void
    {
        $presented = ExceptionPresenter::present($this->queryException(1062));

        $this->assertStringContainsString('موردی با این مشخصات قبلاً ثبت شده است.', $presented['body']);
        $this->assertStringContainsString('کد پیگیری: ' . $presented['reference'], $presented['body']);
    }

    public function test_http_exception_interface_classifies_by_status_code(): void
    {
        $cases = [
            [new NotFoundHttpException(), 'اطلاعات پیدا نشد'],
            [new AccessDeniedHttpException(), 'اجازهٔ انجام این کار را ندارید'],
            [new HttpException(419), 'زمان اعتبار نشست شما تمام شد'],
            [new HttpException(503), 'خطایی رخ داد'],
        ];

        foreach ($cases as [$exception, $title]) {
            $this->assertSame($title, ExceptionPresenter::present($exception)['title']);
        }
    }

    public function test_bootstrapped_renderable_presents_non_livewire_500s_and_passes_http_exceptions_through(): void
    {
        config(['app.debug' => false]);

        Route::get('/-exception-presenter-probe', fn () => throw new RuntimeException('boom'));

        $this->get('/-exception-presenter-probe')
            ->assertStatus(500)
            ->assertViewIs('errors.500')
            ->assertViewHas('heading', 'خطایی رخ داد')
            ->assertViewHas('trace_id', fn (string $reference) => (bool) preg_match('/^ERR-\d{6}-\d{6}-[A-Z0-9]{4}$/', $reference))
            ->assertViewHas('message');

        $this->get('/-exception-presenter-missing-route')->assertStatus(404);

        Route::get('admin/-exception-presenter-probe', fn () => throw new RuntimeException('boom'));

        $this->withHeaders(['x-livewire' => 'true'])
            ->get('admin/-exception-presenter-probe')
            ->assertStatus(500)
            ->assertDontSee('خطای سیستمی', false);
    }
}