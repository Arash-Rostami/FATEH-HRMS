<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SecureLetterLink;
use App\Jobs\SendCorrespondenceJob;
use App\Models\Correspondence;
use App\Services\Correspondence\CorrespondencePdf;
use App\Support\FileNamer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CorrespondenceLinkController extends Controller
{
    private const HEADERS = SecureLetterLink::HEADERS;

    private const VOIDED = [
        Correspondence::STATUS_VOID,
        Correspondence::STATUS_SUPERSEDED,
    ];

    public function show(Request $request, string $id, string $token): Response
    {
        if ($request->isMethod('HEAD')) {
            return $this->preview($id, $token);
        }

        $opened = $this->gate($id, $token);
        if ($opened instanceof Response) {
            return $opened;
        }
        if ($opened['void']) {
            return $this->voided($opened['letter']);
        }

        $letter = $opened['letter']->loadMissing('letterhead');

        return $this->page($letter, false, $token);
    }

    public function pdf(Request $request, string $id, string $token): Response
    {
        $opened = $this->find($id, $token);
        if ($opened instanceof Response) {
            return $opened;
        }
        if ($opened['void']) {
            return $this->voided($opened['letter']);
        }

        $letter = $this->ready((int) $id);
        if ($letter === null) {
            return $this->missing();
        }

        if ($request->isMethod('HEAD')) {
            return response('', 200, [...self::HEADERS, 'Content-Type' => 'application/pdf']);
        }

        try {
            $relative = CorrespondencePdf::path($letter);
        } catch (RuntimeException) {
            return $this->missing();
        }

        $stamped = $this->stamp($id, $token);
        if ($stamped instanceof Response) {
            return $stamped;
        }
        if ($stamped['void']) {
            return $this->voided($stamped['letter']);
        }

        $file = CorrespondencePdf::absolute($relative);
        if ($file === null) {
            return $this->missing();
        }

        return response()->download($file, FileNamer::download('pdf', $letter->number), self::HEADERS);
    }

    private function gate(string $id, string $token): array|Response
    {
        $found = $this->find($id, $token);
        if ($found instanceof Response || $found['void']) {
            return $found;
        }

        return $this->stamp($id, $token, true);
    }

    private function stamp(string $id, string $token, bool $page = false): array|Response
    {
        $columns = $page ? [...CorrespondencePdf::renderColumns(), 'events'] : ['id', 'status', 'classification', 'parties', 'events'];

        return DB::transaction(function () use ($id, $token, $columns): array|Response {
            $letter = Correspondence::query()->whereKey((int) $id)->lockForUpdate()->first($columns);
            $again = $letter === null ? null : $this->match($letter, $token);
            if ($letter === null || $again === null || $this->expired($again['party'])) {
                return $this->missing();
            }
            if (in_array($letter->status, self::VOIDED, true)) {
                return ['letter' => $letter, 'void' => true];
            }
            if ($this->blocked($letter)) {
                return $this->missing();
            }

            $parties = is_array($letter->parties) ? $letter->parties : [];
            $index = $again['index'];
            $party = $parties[$index];
            $now = now()->toIso8601String();
            $party['open_count'] = min(1000000, (int) ($party['open_count'] ?? 0) + 1);
            $party['last_opened_at'] = $now;
            $events = is_array($letter->events) ? $letter->events : [];
            if (blank($party['opened_at'] ?? null)) {
                $party['opened_at'] = $now;
                if (count($events) < 900) {
                    $events[] = [
                        'at' => $now,
                        'user_id' => null,
                        'action' => 'opened',
                        'target' => SendCorrespondenceJob::label($party, (string) ($party['email'] ?? '')),
                        'text' => null,
                    ];
                }
            }
            $parties[$index] = $party;
            SendCorrespondenceJob::writeQuiet($letter, ['parties' => $parties, 'events' => $events]);

            return ['letter' => $letter, 'void' => false];
        });
    }

    private function find(string $id, string $token): array|Response
    {
        if (preg_match('/^[1-9][0-9]{0,9}$/', $id) !== 1 || preg_match('/^[A-Za-z0-9_-]{43}$/', $token) !== 1) {
            return $this->missing();
        }

        $letter = Correspondence::query()->whereKey((int) $id)->first(['id', 'status', 'classification', 'parties']);
        $match = $letter === null ? null : $this->match($letter, $token);
        if ($letter === null || $match === null || $this->expired($match['party'])) {
            return $this->missing();
        }
        if (in_array($letter->status, self::VOIDED, true)) {
            return ['letter' => $letter, 'void' => true];
        }
        if ($this->blocked($letter)) {
            return $this->missing();
        }

        return ['letter' => $letter, 'void' => false];
    }

    private function match(Correspondence $letter, string $token): ?array
    {
        $digest = hash('sha256', $token);
        $found = null;
        foreach (is_array($letter->parties) ? $letter->parties : [] as $index => $party) {
            if (!is_array($party)) {
                continue;
            }
            foreach (['token_hash' => 'expires_at', 'token_hash_next' => 'expires_at_next'] as $hash => $expiry) {
                $stored = (string) ($party[$hash] ?? '');
                if ($stored !== '' && hash_equals($stored, $digest)) {
                    $found = ['index' => $index, 'party' => [...$party, 'expires_at' => $party[$expiry] ?? null]];
                }
            }
        }

        return $found;
    }

    private function expired(array $party): bool
    {
        $at = $party['expires_at'] ?? null;
        if ($at === null || $at === '') {
            return false;
        }

        try {
            return now()->greaterThan(Carbon::parse((string) $at));
        } catch (Throwable) {
            return true;
        }
    }

    private function blocked(Correspondence $letter): bool
    {
        return $letter->status !== Correspondence::STATUS_APPROVED
            || $letter->classification !== Correspondence::CLASSIFICATION_NORMAL;
    }

    private function voided(?Correspondence $letter): Response
    {
        if ($letter?->status !== Correspondence::STATUS_SUPERSEDED) {
            return $this->page(null, true, '');
        }

        $number = Correspondence::query()
            ->where('supersedes_id', $letter->id)
            ->where('status', Correspondence::STATUS_APPROVED)
            ->where('classification', Correspondence::CLASSIFICATION_NORMAL)
            ->orderByDesc('id')
            ->value('number');

        return $this->page(null, true, '', is_string($number) ? (convertToPersian($number) ?? '') : '');
    }

    private function page(?Correspondence $letter, bool $void, string $token, ?string $superseded = null): Response
    {
        $hidden = $void || $letter === null;

        return response()->view('correspondence.link', [
            'void' => $void,
            'superseded' => $superseded,
            'letter' => $hidden ? null : $letter,
            'pdfUrl' => $hidden ? '' : route('correspondence.link.pdf', ['id' => $letter->id, 'token' => $token]),
            'number' => $hidden ? '' : (convertToPersian($letter->number) ?? ''),
            'date' => $hidden ? '' : (convertToPersian($letter->letter_date) ?? ''),
        ], 200)->withHeaders(self::HEADERS);
    }

    private function preview(string $id, string $token): Response
    {
        $opened = $this->find($id, $token);
        if ($opened instanceof Response) {
            return $opened;
        }
        if ($opened['void']) {
            return $this->voided($opened['letter']);
        }

        $letter = $this->ready((int) $id);

        return $letter === null ? $this->missing() : $this->page($letter, false, $token);
    }

    private function ready(int $id): ?Correspondence
    {
        return Correspondence::query()->with('letterhead')->whereKey($id)->first(CorrespondencePdf::renderColumns());
    }

    private function missing(): Response
    {
        return response('', 404);
    }
}
