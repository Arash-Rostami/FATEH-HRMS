<?php

use App\Http\Middleware\EnsureUserModulePermission;
use App\Http\Middleware\UpdateLastSeen;
use App\Services\ExceptionPresenter;
use Filament\Support\Exceptions\Cancel;
use Filament\Support\Exceptions\Halt;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: fn () => Route::middleware('web')->group(base_path('routes/cache.php')),
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['module' => EnsureUserModulePermission::class]);
        $middleware->appendToGroup('web', [UpdateLastSeen::class]);

        $middleware->trustProxies(at: env('TRUSTED_PROXIES'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (\ErrorException $e) {
            if (str_contains($e->getMessage(), 'Utime failed')) {
                return false;
            }
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            $silent = $e instanceof ValidationException
                || $e instanceof AuthenticationException
                || $e instanceof Halt
                || $e instanceof Cancel;

            if ($silent) {
                return null;
            }

            $isLivewire = $request->hasHeader('x-livewire');

            if (config('app.debug')) {
                return null;
            }

            if (! $isLivewire && ($e instanceof HttpExceptionInterface || $e instanceof ModelNotFoundException)) {
                return null;
            }

            if ($isLivewire && $request->is('admin*')) {
                return null;
            }

            $presented = ExceptionPresenter::present($e);
            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

            if ($isLivewire) {
                return response()->json([
                    'message' => "{$presented['title']}\n{$presented['body']}",
                ], $status);
            }

            return response()->view('errors.500', [
                'trace_id' => $presented['reference'],
                'heading' => $presented['title'],
                'message' => $presented['body'],
            ], 500);
        });
    })->create();
