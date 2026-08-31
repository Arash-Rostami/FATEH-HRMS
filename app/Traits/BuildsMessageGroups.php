<?php

namespace App\Traits;

use Carbon\Carbon;

trait BuildsMessageGroups
{
    use BuildsChatBubbles;

    public function messageGroup(string $date, array $messages, int $authId, int $editTimeLimit, array $readersMap = [], array $mentionMap = []): array
    {
        $label = Carbon::parse($date)->isToday()
            ? 'امروز'
            : (Carbon::parse($date)->isYesterday()
                ? 'دیروز'
                : toJalali($date, 'j F Y'));

        return [
            'date' => $date,
            'label' => $label,
            'messages' => $this->messages($messages, $authId, $editTimeLimit, $readersMap, $mentionMap),
        ];
    }

    public function messages(array $messages, int $authId, int $editTimeLimit, array $readersMap = [], array $mentionMap = []): array
    {
        $total = count($messages);

        $mentionNames = array_keys($mentionMap);
        usort($mentionNames, fn($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        $mentionPattern = $mentionNames
            ? '/(?<![\w@])@(' . implode('|', array_map(fn($n) => preg_quote(e($n), '/'), $mentionNames)) . ')(?![\p{L}\p{N}_])/u'
            : '';

        return array_map(function (array $msg, int $i) use ($messages, $total, $authId, $editTimeLimit, $readersMap, $mentionPattern, $mentionMap) {
            $senderId = (int)($msg['sender_id'] ?? 0);
            $isMine = $senderId === $authId;
            $prev = $i > 0 ? $messages[$i - 1] : null;
            $next = $i < $total - 1 ? $messages[$i + 1] : null;
            $isFirst = !$prev || (int)($prev['sender_id'] ?? 0) !== $senderId;
            $isLast = !$next || (int)($next['sender_id'] ?? 0) !== $senderId;
            $createdAt = Carbon::parse($msg['created_at'] ?? now());

            $readers = [];
            $readCount = 0;
            $totalMembers = 0;
            if ($isMine && $isLast && !empty($readersMap)) {
                foreach ($readersMap as $uid => $meta) {
                    if ($uid === $senderId) {
                        continue;
                    }
                    $totalMembers++;
                    $cursor = (int)($meta['cursor'] ?? 0);
                    if ($cursor >= (int)($msg['id'] ?? 0)) {
                        $readCount++;
                        $readers[] = ['avatar' => $meta['avatar'] ?? null, 'name' => $meta['name'] ?? '—'];
                    }
                }
            }

            $linked = $this->linkify(e($msg['body'] ?? ''));
            $mentionsYou = false;
            if ($mentionPattern !== '') {
                [$html, $hits] = $this->mentionify($linked, $mentionPattern);
                foreach ($hits as $name) {
                    if (in_array($authId, $mentionMap[$name] ?? [], true)) {
                        $mentionsYou = true;
                        break;
                    }
                }
            } else {
                $html = $linked;
            }

            return [
                'id' => (int)($msg['id'] ?? 0),
                'body' => $msg['body'] ?? '',
                'body_html' => nl2br($html, false),
                'created_at' => $msg['created_at'] ?? null,
                'time' => $createdAt->isToday() ? toJalali($createdAt->toDateTimeString(), 'H:i') : toJalali($createdAt->toDateTimeString(), 'Y/m/d H:i'),
                'datetime' => $msg['created_at'] ?? '',
                'is_mine' => $isMine,
                'is_first' => $isFirst,
                'is_last' => $isLast,
                'is_edited' => (bool)($msg['is_edited'] ?? false),
                'is_deleted' => !empty($msg['deleted_at']),
                'sender_name' => $msg['sender']['name'] ?? 'ناشناس',
                'sender_avatar' => $msg['sender']['avatar'] ?? null,
                'can_edit' => $isMine && empty($msg['deleted_at']) && $createdAt->diffInSeconds(now()) <= $editTimeLimit,
                'can_delete' => $isMine && empty($msg['deleted_at']) && $createdAt->diffInSeconds(now()) <= $editTimeLimit,
                'attachments' => $this->attachments($msg['attachments'] ?? []),
                'gap_class' => $isFirst ? 'mt-4' : 'mt-1',
                'bubble_radius' => $this->bubbleRadius($isMine, $isFirst, $isLast),
                'reply_to' => $this->replyPreview($msg['reply_to'] ?? null),
                'animation_delay' => $i * 0.04,
                'readers' => $readers,
                'read_count' => $readCount,
                'is_read' => $readCount > 0,
                'is_read_by_all' => $totalMembers > 0 && $readCount === $totalMembers,
                'mentions_you' => $mentionsYou,
            ];
        }, array_values($messages), array_keys($messages));
    }

    public function readerSummary(array $msg): array
    {
        $shown = array_slice($msg['readers'], 0, 3);
        return [
            'names' => array_map(fn($r) => $r['name'] ?? '—', $msg['readers']),
            'shown' => $shown,
            'extra' => max(0, $msg['read_count'] - count($shown)),
        ];
    }

    private function mentionify(string $linked, string $pattern): array
    {
        $parts = preg_split('/(<a\s[^>]*>.*?<\/a>)/us', $linked, -1, PREG_SPLIT_DELIM_CAPTURE);
        $html = '';
        $hits = [];
        foreach ($parts as $i => $part) {
            if ($i % 2 === 1) {
                $html .= $part;
                continue;
            }
            $html .= preg_replace_callback($pattern, function (array $m) use (&$hits): string {
                $hits[] = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                return '<span class="mention">@' . $m[1] . '</span>';
            }, $part);
        }
        return [$html, $hits];
    }
}
