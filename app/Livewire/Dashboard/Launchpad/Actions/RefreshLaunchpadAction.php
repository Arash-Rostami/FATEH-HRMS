<?php

namespace App\Livewire\Dashboard\Launchpad\Actions;

use App\Jobs\RefreshLaunchpadJob;
use App\Livewire\Dashboard\Launchpad\Forms\LaunchpadForm;
use App\Models\Launchpad;
use App\Models\User;
use App\Support\LaunchpadAccessPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;
use Throwable;

class RefreshLaunchpadAction
{
    private const BUDGET_SECONDS = 100;

    private const GOOGLE_TOKEN = 'https://oauth2.googleapis.com/token';

    private const GOOGLE_SCOPE = 'https://www.googleapis.com/auth/spreadsheets.readonly';

    private const BLOCKED = [['100.64.0.0', 10], ['192.0.0.0', 24], ['192.0.2.0', 24], ['198.18.0.0', 15], ['198.51.100.0', 24], ['203.0.113.0', 24], ['224.0.0.0', 4]];

    private const GID_TTL_HOURS = 12;

    private const MAX_GRID_ROWS = 50000;

    private const MAX_GRID_COLUMNS = 200;

    private array $resolved = [];

    private ?string $reason = null;

    private int $code = 0;

    private ?array $gidCache = null;

    private ?array $gidsOut = null;

    private ?int $attempts = null;

    public function sheetTabs(string $sheet): ?array
    {
        if (preg_match(LaunchpadForm::SHEET_PATTERN, $sheet) !== 1) {
            return null;
        }

        $this->attempts = 1;
        [$status, $page] = $this->call(self::publicHtmlviewUrl($sheet), 'GET', null, [], microtime(true) + (int) config('launchpad.refresh.verify_budget'), mode: 'html');
        $this->attempts = null;
        $map = $status === 'ok' ? self::sheetGids($page) : [];

        return $map === [] ? null : array_map('strval', array_keys($map));
    }

    public function plan(): int
    {
        $ids = Launchpad::query()->dueForRefresh()->limit((int) config('launchpad.refresh.limit'))->pluck('id');
        if ($ids->isEmpty()) {
            return 0;
        }

        foreach ($ids->values() as $index => $id) {
            RefreshLaunchpadJob::dispatch((int) $id)->delay(now()->addMinutes($index + 1));
        }

        Launchpad::query()->whereKey($ids->all())->whereNotNull('refresh_due_at')->toBase()->update(['refresh_due_at' => Launchpad::nextRefreshAt()]);

        return $ids->count();
    }

    public function refreshNow(int $id, User $user): string
    {
        $row = Launchpad::query()->find($id, ['id', 'user_id', 'kind', 'refresh_due_at', 'active', 'accessible_from', 'accessible_to', 'expire_at']);
        if ($row === null || !LaunchpadAccessPolicy::canManage($row, $user)) {
            throw new AuthorizationException;
        }

        if ($row->kind !== 'static' || $row->refresh_due_at === null) {
            return 'none';
        }

        if (!$row->isLive()) {
            return 'dead';
        }

        $queued = RateLimiter::attempt("launchpad-refresh:{$id}", 1, fn () => RefreshLaunchpadJob::dispatch($id), (int) config('launchpad.refresh.cooldown_minutes') * 60);

        return $queued ? 'queued' : 'cooldown';
    }

    public function execute(int $id): void
    {
        $row = Launchpad::query()->find($id, ['id', 'kind', 'folder', 'source', 'settings', 'active', 'accessible_from', 'accessible_to', 'expire_at']);
        $source = $row?->source;
        if ($row === null || $row->kind !== 'static' || !$row->isLive() || !is_array($source) || $row->directory() === null) {
            return;
        }

        $fingerprint = md5(json_encode($source));
        $cached = data_get((array) $row->settings, 'refresh.gids');
        $this->reason = null;
        $this->code = 0;
        $this->gidCache = $this->gidsOut = is_array($cached) ? $cached : null;

        [$status, $data] = $this->fetch($source);
        $tracked = ($source['auth'] ?? null) === 'google' && data_get($source, 'env.access') === 'public';
        $reason = $status === 'ok' || !$tracked ? null : $this->reason;
        if ($status !== 'ok') {
            Log::warning('launchpad refresh', ['item' => $id, 'status' => $status, 'reason' => $reason]);
        }

        $payload = json_encode(['updated_at' => now()->toIso8601String(), 'ok' => $status === 'ok', 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        DB::transaction(fn () => $this->store($id, $row->folder, $payload, $status, $reason, $this->gidsOut, $fingerprint));
    }

    public static function refreshMeta(string $status, ?string $reason, ?array $gids, string $at): array
    {
        return ['at' => $at, 'status' => $status, 'reason' => $status === 'ok' ? null : $reason] + ($gids === null ? [] : ['gids' => $gids]);
    }

    public static function backoff(int $attempt): int
    {
        return min(2, max(1, $attempt));
    }

    public static function contentTypeOk(string $type): bool
    {
        $type = strtolower(trim($type));

        return $type === '' || Str::startsWith($type, ['text/csv', 'text/plain', 'application/octet-stream']);
    }

    public static function verdict(string $raw, string $type, bool $page): ?array
    {
        $body = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
        $html = preg_match('/^\s*<(!doctype|html)\b/i', $body) === 1;

        return match (true) {
            $page => !$html || !mb_check_encoding($raw, 'UTF-8') || !str_contains($body, 'items.push(') ? ['invalid', 'map_failed'] : null,
            $html => ['denied', 'private_sheet'],
            !mb_check_encoding($raw, 'UTF-8'), !self::contentTypeOk($type) => ['invalid', 'bad_content'],
            default => null,
        };
    }

    public static function tabKey(string $name): string
    {
        return mb_strtolower((string) preg_replace('/[\s\x{200C}]+/u', '', strtr($name, ['ي' => 'ی', 'ك' => 'ک'])));
    }

    public static function tabGid(array $gids, string $tab): ?string
    {
        if (isset($gids[$tab])) {
            return (string) $gids[$tab];
        }

        foreach ($gids as $name => $gid) {
            if (self::tabKey((string) $name) === self::tabKey($tab)) {
                return (string) $gid;
            }
        }

        return null;
    }

    public static function gridTooLarge(string $csv): bool
    {
        return substr_count($csv, "\n") > self::MAX_GRID_ROWS;
    }

    public static function redirectReason(string $host): string
    {
        return preg_match('/(^|\.)google\.com$/Di', $host) === 1 ? 'private_sheet' : 'redirect_refused';
    }

    public static function gidSource(?array $cache, string $hash, array $tabs, ?Carbon $now = null): string
    {
        $map = $cache['map'] ?? null;
        if (($cache['sheetIdHash'] ?? null) !== $hash || !is_array($map)) {
            return 'none';
        }

        try {
            $at = Carbon::parse((string) ($cache['at'] ?? ''));
        } catch (Throwable) {
            return 'stale';
        }

        $fresh = filled($cache['at'] ?? null) && $at->gt(($now ?? now())->copy()->subHours(self::GID_TTL_HOURS)) && array_filter($tabs, fn (string $tab) => self::tabGid($map, $tab) === null) === [];

        return $fresh ? 'fresh' : 'stale';
    }

    private function stop(string $status, ?string $reason): array
    {
        $this->reason ??= $reason;

        return [$status, null];
    }

    private function store(int $id, ?string $folder, string $payload, string $status, ?string $reason, ?array $gids, string $fingerprint): void
    {
        $row = Launchpad::query()->whereKey($id)->lockForUpdate()->first(['id', 'folder', 'settings', 'refresh_due_at', 'source']);
        $directory = $row?->directory();
        $name = Launchpad::storedName((string) config('launchpad.refresh.file'));
        if ($row === null || $row->folder !== $folder || $row->refresh_due_at === null || md5(json_encode($row->source)) !== $fingerprint || $directory === null || $name === null) {
            return;
        }

        $disk = Storage::disk('public');
        $temp = "{$directory}/.{$name}.tmp";
        if (!$disk->put($temp, $payload) || !$disk->move($temp, "{$directory}/{$name}")) {
            $disk->delete($temp);

            throw new RuntimeException('write');
        }

        $row->update(['settings' => [...(array) $row->settings, 'refresh' => self::refreshMeta($status, $reason, $gids, now()->toIso8601String())]]);
    }

    private function fetch(array $source): array
    {
        $deadline = microtime(true) + self::BUDGET_SECONDS;
        $auth = (string) ($source['auth'] ?? 'none');

        if ($auth === 'google') {
            $env = (array) ($source['env'] ?? []);

            return match ($env['access'] ?? null) {
                'public' => $this->publicSheet($env, $deadline),
                'private' => $this->sheets($env, $deadline),
                default => ['invalid', null],
            };
        }

        $env = $this->env($auth, (array) ($source['env'] ?? []));
        $headers = [];

        if ($auth === 'header') {
            if (!is_string($env['value']) || $env['value'] === '') {
                return ['invalid', null];
            }

            $headers[] = match ($env['kind']) {
                'bearer' => ['Authorization', "Bearer {$env['value']}"],
                'key' => ['X-API-Key', $env['value']],
                default => [$env['name'], $env['value']],
            };
        } elseif ($auth === 'login') {
            [$status, $reply] = $this->call((string) $env['url'], 'POST', (string) $env['body'], [], $deadline);
            if ($status !== 'ok') {
                return [$status, null];
            }

            $token = null;
            foreach (filled($env['token']) ? [$env['token']] : (array) config('launchpad.refresh.token_paths') as $path) {
                $candidate = data_get($reply, $path);
                if (is_string($candidate) && $candidate !== '') {
                    $token = $candidate;
                    break;
                }
            }

            if ($token === null) {
                return ['invalid', null];
            }

            $headers[] = ['Authorization', "Bearer {$token}"];
        } elseif ($auth !== 'none') {
            return ['invalid', null];
        }

        return $this->call((string) ($source['url'] ?? ''), strtoupper((string) ($source['method'] ?? 'GET')), is_string($source['body'] ?? null) ? $source['body'] : null, $headers, $deadline);
    }

    public static function sheetsUrl(string $sheet, string $ranges): string
    {
        $query = collect(LaunchpadForm::rangeLines($ranges))->map(fn (string $line) => 'ranges=' . rawurlencode($line))->push('valueRenderOption=FORMATTED_VALUE')->implode('&');

        return "https://sheets.googleapis.com/v4/spreadsheets/{$sheet}/values:batchGet?{$query}";
    }

    public static function rangeParts(string $line): ?array
    {
        $cells = '[A-Za-z]{1,3}[1-9][0-9]{0,6}(?::[A-Za-z]{1,3}[1-9][0-9]{0,6})?';

        if (preg_match("/^(?:'((?:[^']|'')+)'|([^'!]+))!({$cells})$/Du", $line, $match) === 1) {
            return [$match[1] !== '' ? str_replace("''", "'", $match[1]) : $match[2], strtoupper($match[3])];
        }

        if (preg_match("/^({$cells})!(?:'((?:[^']|'')+)'|([^'!]+))$/Du", $line, $match) !== 1) {
            return null;
        }

        return [$match[2] !== '' ? str_replace("''", "'", $match[2]) : $match[3], strtoupper($match[1])];
    }

    public static function publicHtmlviewUrl(string $sheet): string
    {
        return "https://docs.google.com/spreadsheets/d/{$sheet}/htmlview";
    }

    public static function publicExportUrl(string $sheet, string $gid): ?string
    {
        return preg_match('/^\d{1,12}$/D', $gid) === 1 ? "https://docs.google.com/spreadsheets/d/{$sheet}/export?format=csv&gid={$gid}" : null;
    }

    public static function sheetGids(string $html): array
    {
        preg_match_all('/items\.push\(\{name:\s*("(?:[^"\\\\]|\\\\.)*")[^}]*?\bgid:\s*"(\d+)"/s', $html, $matches, PREG_SET_ORDER);

        $gids = [];
        foreach ($matches as [, $quoted, $gid]) {
            $name = json_decode(preg_replace_callback('/\\\\(?:x([0-9A-Fa-f]{2})|.)/s', fn (array $escape) => ($escape[1] ?? '') !== '' ? '\u00' . $escape[1] : $escape[0], $quoted));
            if (is_string($name)) {
                $gids[$name] ??= $gid;
            }
        }

        return $gids;
    }

    public static function isSheetsDownloadHost(string $host): bool
    {
        return preg_match('/^doc-[0-9a-z-]+-sheets\.googleusercontent\.com$/D', $host) === 1;
    }

    public static function sliceRange(array $grid, string $cells): array
    {
        if (preg_match('/^([A-Z]{1,3})([1-9][0-9]{0,6})(?::([A-Z]{1,3})([1-9][0-9]{0,6}))?$/D', strtoupper($cells), $m) !== 1) {
            return [];
        }

        $column = fn (string $letters): int => array_reduce(str_split($letters), fn (int $carry, string $letter) => $carry * 26 + ord($letter) - 64, 0) - 1;
        [$firstColumn, $lastColumn] = [$column($m[1]), $column($m[3] ?? $m[1])];
        [$firstRow, $lastRow] = [(int) $m[2], (int) ($m[4] ?? $m[2])];

        $rows = array_slice(array_values($grid), min($firstRow, $lastRow) - 1, abs($lastRow - $firstRow) + 1);

        return self::trimRows(array_map(fn (array $row) => array_slice($row, min($firstColumn, $lastColumn), abs($lastColumn - $firstColumn) + 1), $rows));
    }

    public static function csvRows(string $csv): array
    {
        return self::trimRows(self::csvGrid($csv));
    }

    private static function csvGrid(string $csv): array
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, preg_replace('/^\xEF\xBB\xBF/', '', $csv));
        rewind($handle);

        $rows = [];
        while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
            $rows[] = array_map(fn ($cell) => (string) $cell, array_slice($row, 0, self::MAX_GRID_COLUMNS));
        }

        fclose($handle);

        return $rows;
    }

    private static function trimRows(array $rows): array
    {
        $rows = array_map(function (array $cells): array {
            while ($cells !== [] && end($cells) === '') {
                array_pop($cells);
            }

            return $cells;
        }, $rows);

        while ($rows !== [] && end($rows) === []) {
            array_pop($rows);
        }

        return $rows;
    }

    private function publicSheet(array $env, float $deadline): array
    {
        $sheet = (string) ($env['sheet'] ?? '');
        $lines = LaunchpadForm::rangeLines((string) ($env['ranges'] ?? ''));
        $parts = array_map(self::rangeParts(...), $lines);
        if (preg_match(LaunchpadForm::SHEET_PATTERN, $sheet) !== 1 || $lines === [] || in_array(null, $parts, true)) {
            return $this->stop('invalid', 'range_invalid');
        }

        $tabs = array_values(array_unique(array_column($parts, 0)));
        $hash = hash('sha256', $sheet);
        $plan = self::gidSource($this->gidCache, $hash, $tabs);
        $cached = $plan === 'fresh';
        $bytes = 0;
        $gids = $cached ? $this->gidCache['map'] : null;

        if ($gids === null) {
            [$status, $gids] = $this->gidMap($sheet, $hash, $deadline, $bytes);
            if ($status !== 'ok' && $plan !== 'stale') {
                return [$status, null];
            }

            if ($status !== 'ok') {
                $gids = $this->gidCache['map'];
                $cached = true;
                $bytes = 0;
                $this->reason = null;
                $this->code = 0;
            }
        }

        [$status, $grids] = $this->exports($sheet, $tabs, $gids, $deadline, $bytes);
        if ($status !== 'ok' && $cached && in_array($this->code, [400, 404], true)) {
            $this->gidsOut = null;
            $this->reason = null;
            $bytes = 0;
            [$status, $gids] = $this->gidMap($sheet, $hash, $deadline, $bytes);
            if ($status === 'ok') {
                [$status, $grids] = $this->exports($sheet, $tabs, $gids, $deadline, $bytes);
            }
        }

        if ($status !== 'ok') {
            return [$status, null];
        }

        $valueRanges = [];
        foreach ($lines as $index => $line) {
            [$tab, $cells] = $parts[$index];
            $valueRanges[] = ['range' => $line, 'values' => self::sliceRange($grids[$tab], $cells)];
        }

        return ['ok', ['valueRanges' => $valueRanges]];
    }

    private function gidMap(string $sheet, string $hash, float $deadline, int &$bytes): array
    {
        [$status, $page] = $this->call(self::publicHtmlviewUrl($sheet), 'GET', null, [], $deadline, mode: 'html');
        if ($status !== 'ok') {
            return $this->stop($status, 'map_failed');
        }

        $bytes += strlen($page);
        $map = self::sheetGids($page);
        if ($map === []) {
            return $this->stop('invalid', 'map_failed');
        }

        $this->gidsOut = ['sheetIdHash' => $hash, 'map' => $map, 'at' => now()->toIso8601String()];

        return $bytes > (int) config('launchpad.refresh.max_bytes') ? $this->stop('invalid', 'too_large') : ['ok', $map];
    }

    private function exports(string $sheet, array $tabs, array $gids, float $deadline, int &$bytes): array
    {
        $limit = (int) config('launchpad.refresh.max_bytes');
        $grids = [];
        foreach ($tabs as $tab) {
            $gid = self::tabGid($gids, $tab);
            $url = $gid === null ? null : self::publicExportUrl($sheet, $gid);
            if ($url === null) {
                return $this->stop('invalid', $gid === null ? 'tab_missing' : 'export_failed');
            }

            [$status, $csv] = $this->call($url, 'GET', null, [], $deadline, mode: 'csv', hop: true);
            if ($status !== 'ok') {
                return $this->stop($status, 'export_failed');
            }

            $bytes += strlen($csv);
            if ($bytes > $limit) {
                return $this->stop('invalid', 'too_large');
            }

            if (self::gridTooLarge($csv)) {
                return $this->stop('invalid', 'too_large');
            }

            $grids[$tab] = self::csvGrid($csv);
        }

        return ['ok', $grids];
    }

    private function text(string $raw, string $type, bool $page = false): array
    {
        $verdict = self::verdict($raw, $type, $page);

        return $verdict === null ? ['ok', $raw] : $this->stop(...$verdict);
    }

    private function follow(string $location, float $deadline): array
    {
        $host = (string) parse_url($location, PHP_URL_HOST);
        if (!LaunchpadForm::urlAllowed($location) || !self::isSheetsDownloadHost($host) || str_contains($location, '#')) {
            return $this->stop('denied', self::redirectReason($host));
        }

        return $this->call($location, 'GET', null, [], $deadline, mode: 'csv');
    }

    private function sheets(array $env, float $deadline): array
    {
        $sheet = (string) ($env['sheet'] ?? '');
        $ranges = (string) ($env['ranges'] ?? '');
        $assertion = $this->assertion((string) ($env['email'] ?? ''), (string) ($env['key'] ?? ''));
        if ($assertion === null || preg_match(LaunchpadForm::SHEET_PATTERN, $sheet) !== 1 || LaunchpadForm::rangeLines($ranges) === []) {
            return ['invalid', null];
        }

        $form = http_build_query(['grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $assertion], '', '&');
        [$status, $reply] = $this->call(self::GOOGLE_TOKEN, 'POST', $form, [], $deadline, 'application/x-www-form-urlencoded');
        if ($status !== 'ok') {
            return [$status, null];
        }

        $token = data_get($reply, 'access_token');
        if (!is_string($token) || $token === '') {
            return ['invalid', null];
        }

        return $this->call(self::sheetsUrl($sheet, $ranges), 'GET', null, [['Authorization', "Bearer {$token}"]], $deadline);
    }

    private function assertion(string $email, string $pem): ?string
    {
        $key = preg_match(LaunchpadForm::PEM_PATTERN, $pem) === 1 ? @openssl_pkey_get_private($pem) : false;
        if ($key === false || preg_match(LaunchpadForm::SERVICE_EMAIL_PATTERN, $email) !== 1) {
            return null;
        }

        $now = time();
        $input = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])) . '.' . $this->base64Url(json_encode(['iss' => $email, 'scope' => self::GOOGLE_SCOPE, 'aud' => self::GOOGLE_TOKEN, 'iat' => $now - 60, 'exp' => $now + 3500], JSON_UNESCAPED_SLASHES));

        return openssl_sign($input, $signature, $key, OPENSSL_ALGO_SHA256) ? $input . '.' . $this->base64Url($signature) : null;
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function env(string $auth, array $stored): array
    {
        $env = [];
        foreach ((array) config("launchpad.refresh.auth.{$auth}.fields") as $field => $spec) {
            $value = $stored[$field] ?? null;
            $env[$field] = is_string($value) && $value !== '' ? $value : $spec['default'];
        }

        return $env;
    }

    private function call(string $url, string $method, ?string $body, array $headers, float $deadline, string $type = 'application/json', string $mode = 'json', bool $hop = false): array
    {
        $parts = parse_url($url);
        $host = is_array($parts) ? strtolower((string) ($parts['host'] ?? '')) : '';
        $lines = [];

        foreach ($headers as [$name, $value]) {
            if (preg_match(LaunchpadForm::HEADER_PATTERN, (string) $name) !== 1 || !is_string($value) || $value === '' || preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
                return ['invalid', null];
            }

            $lines[] = "{$name}: {$value}";
        }

        if (!LaunchpadForm::urlAllowed($url) || !in_array($method, ['GET', 'POST'], true)) {
            return ['invalid', null];
        }

        if (microtime(true) >= $deadline) {
            return $this->stop('unreachable', 'timeout');
        }

        $ips = $this->resolve($host);
        if ($ips === [] || microtime(true) >= $deadline) {
            return $this->stop('unreachable', $ips === [] ? null : 'timeout');
        }

        foreach ($ips as $ip) {
            if (!$this->isPublic($ip)) {
                return ['invalid', null];
            }
        }

        $lines[] = match ($mode) {
            'csv' => 'Accept: text/csv',
            'html' => 'Accept: text/html',
            default => 'Accept: application/json',
        };
        if ($method === 'POST') {
            $lines[] = "Content-Type: {$type}";
        }

        $attempts = max(1, $this->attempts ?? (int) config('launchpad.refresh.attempts'));
        for ($attempt = 1; ; $attempt++) {
            if (microtime(true) >= $deadline) {
                return $this->stop('unreachable', 'timeout');
            }

            $timeout = max(1, min((int) config('launchpad.refresh.timeout'), (int) ceil($deadline - microtime(true))));
            [$code, $raw, $errno, $over, $next, $replyType] = $this->send($url, $host, $ips[($attempt - 1) % count($ips)], $method, $body, $lines, $timeout);
            $retry = !$over && ($errno !== 0 || $code === 429 || $code >= 500);
            $pause = self::backoff($attempt);
            if (!$retry || $attempt >= $attempts || microtime(true) + $pause + (int) config('launchpad.refresh.timeout') >= $deadline) {
                break;
            }

            sleep($pause);
        }

        $this->code = $code;

        return match (true) {
            $over => $this->stop('invalid', 'too_large'),
            $errno !== 0, $code === 429, $code >= 500 => $this->stop('unreachable', null),
            $code === 401, $code === 403 => $this->stop('denied', 'private_sheet'),
            $hop && in_array($code, [301, 302, 303, 307, 308], true) => $this->follow($next, $deadline),
            $mode !== 'json' && $code >= 300 && $code < 400 => $this->stop('denied', 'private_sheet'),
            $code >= 200 && $code < 300 => match ($mode) {
                'csv' => $this->text($raw, $replyType),
                'html' => $this->text($raw, $replyType, true),
                default => $this->decode($raw),
            },
            default => $this->stop('invalid', null),
        };
    }

    private function send(string $url, string $host, string $ip, string $method, ?string $body, array $lines, int $timeout): array
    {
        $max = (int) config('launchpad.refresh.max_bytes');
        $raw = '';
        $over = false;
        $handle = curl_init($url);

        curl_setopt_array($handle, [
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_PROXY => '',
            CURLOPT_NOPROXY => '*',
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_RESOLVE => ["{$host}:443:{$ip}"],
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_CONNECTTIMEOUT => (int) config('launchpad.refresh.connect_timeout'),
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => $lines,
            CURLOPT_ENCODING => '',
            CURLOPT_WRITEFUNCTION => function ($handle, string $chunk) use (&$raw, &$over, $max): int {
                $raw .= $chunk;
                $over = strlen($raw) > $max;

                return $over ? 0 : strlen($chunk);
            },
        ]);

        if ($method === 'POST') {
            curl_setopt_array($handle, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => (string) $body]);
        }

        curl_exec($handle);

        return [(int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), $raw, curl_errno($handle), $over, (string) curl_getinfo($handle, CURLINFO_REDIRECT_URL), (string) curl_getinfo($handle, CURLINFO_CONTENT_TYPE)];
    }

    private function decode(string $raw): array
    {
        try {
            return ['ok', json_decode($raw, false, 64, JSON_THROW_ON_ERROR)];
        } catch (JsonException) {
            return ['invalid', null];
        }
    }

    private function resolve(string $host): array
    {
        if (!isset($this->resolved[$host])) {
            $records = @dns_get_record($host, DNS_A);
            $this->resolved[$host] = is_array($records) ? array_values(array_column($records, 'ip')) : [];
        }

        return $this->resolved[$host];
    }

    private function isPublic(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        foreach (self::BLOCKED as [$network, $bits]) {
            $mask = -1 << (32 - $bits);

            if ((ip2long($ip) & $mask) === (ip2long($network) & $mask)) {
                return false;
            }
        }

        return true;
    }
}
