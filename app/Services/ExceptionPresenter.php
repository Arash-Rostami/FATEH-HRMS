<?php

namespace App\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ExceptionPresenter
{
    public static function present(Throwable $e): array
    {
        $reference = static::makeReference();

        Log::error("[{$reference}] " . $e->getMessage(), [
            'reference' => $reference,
            'exception' => $e,
        ]);

        [$title, $body] = static::classify($e);

        return [
            'title' => $title,
            'body' => $body . "\n\n" . __('errors/strings.notifications.reference_line', ['ref' => $reference]),
            'reference' => $reference,
        ];
    }

    protected static function makeReference(): string
    {
        return 'ERR-' . now()->format('ymd-His') . '-' . Str::upper(Str::random(4));
    }

    protected static function classify(Throwable $e): array
    {
        if ($e instanceof QueryException) {
            return static::classifyQueryException($e);
        }

        if ($e instanceof ModelNotFoundException) {
            return [
                __('errors/strings.notifications.not_found_title'),
                __('errors/strings.notifications.not_found_body'),
            ];
        }

        if ($e instanceof AuthorizationException) {
            return [
                __('errors/strings.notifications.forbidden_title'),
                __('errors/strings.notifications.forbidden_body'),
            ];
        }

        if ($e instanceof TokenMismatchException) {
            return [
                __('errors/strings.notifications.session_expired_title'),
                __('errors/strings.notifications.session_expired_body'),
            ];
        }

        if ($e instanceof PostTooLargeException) {
            return [
                __('errors/strings.notifications.payload_too_large_title'),
                __('errors/strings.notifications.payload_too_large_body'),
            ];
        }

        if ($e instanceof HttpExceptionInterface) {
            return match ($e->getStatusCode()) {
                419 => [
                    __('errors/strings.notifications.session_expired_title'),
                    __('errors/strings.notifications.session_expired_body'),
                ],
                404 => [
                    __('errors/strings.notifications.not_found_title'),
                    __('errors/strings.notifications.not_found_body'),
                ],
                403 => [
                    __('errors/strings.notifications.forbidden_title'),
                    __('errors/strings.notifications.forbidden_body'),
                ],
                default => [
                    __('errors/strings.notifications.generic_title'),
                    __('errors/strings.notifications.generic_body'),
                ],
            };
        }

        return [
            __('errors/strings.notifications.generic_title'),
            __('errors/strings.notifications.generic_body'),
        ];
    }

    protected static function classifyQueryException(QueryException $e): array
    {
        $driverCode = (int) ($e->errorInfo[1] ?? 0);

        return match ($driverCode) {
            1264 => [
                __('errors/strings.notifications.number_too_large_title'),
                __('errors/strings.notifications.number_too_large_body'),
            ],
            1406 => [
                __('errors/strings.notifications.value_too_long_title'),
                __('errors/strings.notifications.value_too_long_body'),
            ],
            1265, 1366 => [
                __('errors/strings.notifications.invalid_format_title'),
                __('errors/strings.notifications.invalid_format_body'),
            ],
            1048 => [
                __('errors/strings.notifications.missing_required_title'),
                __('errors/strings.notifications.missing_required_body'),
            ],
            1062 => [
                __('errors/strings.notifications.duplicate_title'),
                __('errors/strings.notifications.duplicate_body'),
            ],
            1451 => [
                __('errors/strings.notifications.fk_in_use_title'),
                __('errors/strings.notifications.fk_in_use_body'),
            ],
            1452 => [
                __('errors/strings.notifications.fk_invalid_reference_title'),
                __('errors/strings.notifications.fk_invalid_reference_body'),
            ],
            1213, 1205 => [
                __('errors/strings.notifications.busy_retry_title'),
                __('errors/strings.notifications.busy_retry_body'),
            ],
            2002, 2006, 2013 => [
                __('errors/strings.notifications.connection_lost_title'),
                __('errors/strings.notifications.connection_lost_body'),
            ],
            1146, 1054, 1049 => [
                __('errors/strings.notifications.system_config_title'),
                __('errors/strings.notifications.system_config_body'),
            ],
            default => [
                __('errors/strings.notifications.database_title'),
                __('errors/strings.notifications.database_body'),
            ],
        };
    }
}