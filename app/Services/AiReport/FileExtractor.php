<?php

namespace App\Services\AiReport;

use Illuminate\Support\Facades\Storage;
use OpenSpout\Reader\XLSX\Reader;

final class FileExtractor
{
    public const MIMES = [
        'application/pdf' => ['inline', 'pdf'],
        'image/jpeg' => ['inline', 'jpg'],
        'image/png' => ['inline', 'png'],
        'image/webp' => ['inline', 'webp'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx', 'docx'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx', 'xlsx'],
        'text/plain' => ['text', 'txt'],
        'text/csv' => ['text', 'csv'],
        'application/csv' => ['text', 'csv'],
        'text/markdown' => ['text', 'md'],
        'text/x-markdown' => ['text', 'md'],
        'application/zip' => ['zip', 'zip'],
        'application/x-zip-compressed' => ['zip', 'zip'],
    ];

    private const TRUNCATION = "[\u{2026} \u{0627}\u{062F}\u{0627}\u{0645}\u{0647}\u{0654} \u{0641}\u{0627}\u{06CC}\u{0644} \u{0628}\u{0647} \u{062F}\u{0644}\u{06CC}\u{0644} \u{062D}\u{062C}\u{0645} \u{0632}\u{06CC}\u{0627}\u{062F} \u{0628}\u{0631}\u{06CC}\u{062F}\u{0647} \u{0634}\u{062F}]";

    public function extract(string $disk, string $directory): array
    {
        $out = ['text' => '', 'inline' => [], 'notes' => [], 'skipped' => []];
        $storage = Storage::disk($disk);
        $used = 0;
        $inlineBytes = 0;
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        foreach ($storage->files($directory) as $relative) {
            $name = basename($relative);
            try {
                $this->consume($storage->path($relative), $name, $out, $used, $inlineBytes, $finfo);
            } catch (\Throwable) {
                $this->skip($out, $name, 'corrupt');
            }
        }

        return $out;
    }

    private function consume(string $path, string $name, array &$out, int &$used, int &$inlineBytes, \finfo $finfo): void
    {
        $size = filesize($path) ?: 0;
        if ($size === 0) {
            $this->skip($out, $name, 'empty');

            return;
        }

        $mime = $finfo->file($path) ?: 'application/octet-stream';
        $kind = self::MIMES[$mime][0] ?? 'unsupported';
        if ($kind === 'unsupported' && $this->legacyEncryptedOffice($path, $name)) {
            $kind = 'encrypted';
        }
        if ($kind === 'zip') {
            $kind = $this->zipKind($path);
        }

        match ($kind) {
            'inline' => $this->inline($path, $name, $mime, $size, $out, $inlineBytes),
            'docx' => $this->docx($path, $name, $out, $used),
            'xlsx' => $this->xlsx($path, $name, $out, $used),
            'text' => $this->text($path, $name, $size, $out, $used),
            'encrypted' => $this->skip($out, $name, 'encrypted'),
            'corrupt' => $this->skip($out, $name, 'corrupt'),
            default => $this->skip($out, $name, 'unsupported'),
        };
    }

    private function zipKind(string $path): string
    {
        $zip = new \ZipArchive();
        $code = $zip->open($path);
        if (in_array($code, [\ZipArchive::ER_NOPASSWD, \ZipArchive::ER_WRONGPASSWD, \ZipArchive::ER_ENCRNOTSUPP], true)) {
            return 'encrypted';
        }
        if ($code !== true) {
            return 'corrupt';
        }
        $kind = 'unsupported';
        if ($zip->locateName('word/document.xml') !== false) {
            $kind = 'docx';
        } elseif ($zip->locateName('xl/workbook.xml') !== false) {
            $kind = 'xlsx';
        }
        $zip->close();

        return $kind;
    }

    private function inline(string $path, string $name, string $mime, int $size, array &$out, int &$inlineBytes): void
    {
        $perFile = (int) config('ai.report.upload.inline_kb') * 1024;
        $total = (int) config('ai.report.upload.inline_total_kb') * 1024;
        if ($size > $perFile || $inlineBytes + $size > $total) {
            $this->skip($out, $name, 'too_large');

            return;
        }
        $raw = file_get_contents($path);
        if ($raw === false || $raw === '') {
            $this->skip($out, $name, 'empty');

            return;
        }
        $out['inline'][] = ['mime' => $mime, 'data' => base64_encode($raw), 'name' => $this->reportedName($name)];
        $inlineBytes += $size;
    }

    private function docx(string $path, string $name, array &$out, int &$used): void
    {
        $zip = new \ZipArchive();
        $code = $zip->open($path);
        if (in_array($code, [\ZipArchive::ER_NOPASSWD, \ZipArchive::ER_WRONGPASSWD, \ZipArchive::ER_ENCRNOTSUPP], true)) {
            $this->skip($out, $name, 'encrypted');

            return;
        }
        if ($code !== true || $this->zipOversized($zip, 'docx_zip_kb') || $zip->locateName('word/document.xml') === false) {
            if ($code === true) {
                $zip->close();
            }
            $this->skip($out, $name, 'corrupt');

            return;
        }
        $stat = $zip->statName('word/document.xml');
        if (is_array($stat) && ($stat['encryption_method'] ?? 0) !== 0) {
            $zip->close();
            $this->skip($out, $name, 'encrypted');

            return;
        }
        $cap = (int) config('ai.report.extract.docx_xml_kb') * 1024;
        $xml = $zip->getFromName('word/document.xml', $cap);
        $zip->close();
        if (!is_string($xml) || $xml === '') {
            $this->skip($out, $name, 'corrupt');

            return;
        }
        $capped = strlen($xml) === $cap;
        if (!mb_check_encoding($xml, 'UTF-8')) {
            $xml = mb_scrub($xml, 'UTF-8');
        }
        $text = html_entity_decode(strip_tags(str_replace(['</w:p>', '</w:tr>'], "\n", $xml)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace("/[^\S\n]+/u", ' ', $text) ?? $text);
        $this->pushText($out, $name, $text, $capped, $used);
    }

    private function xlsx(string $path, string $name, array &$out, int &$used): void
    {
        if ($this->officeEncrypted($path, 'xl/workbook.xml')) {
            $this->skip($out, $name, 'encrypted');

            return;
        }
        $zip = new \ZipArchive();
        $opened = $zip->open($path) === true;
        $oversized = !$opened || $this->zipOversized($zip, 'xlsx_xml_kb');
        if ($opened) {
            $zip->close();
        }
        if ($oversized) {
            $this->skip($out, $name, 'corrupt');

            return;
        }
        $reader = new Reader();
        $reader->open($path);
        $chunks = [];
        $sheets = 0;
        $capped = false;
        $maxSheets = (int) config('ai.report.extract.xlsx_sheets');
        $maxRows = (int) config('ai.report.extract.xlsx_rows');
        $maxRaw = (int) config('ai.report.extract.xlsx_raw_rows');
        $maxCols = (int) config('ai.report.extract.xlsx_cols');
        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                if (!$sheet->isVisible()) {
                    continue;
                }
                if ($sheets >= $maxSheets) {
                    $capped = true;
                    break;
                }
                $sheets++;
                $title = str_replace(["\n", "\r", "\t"], ' ', $sheet->getName());
                $lines = ['## ' . $title];
                $rows = 0;
                $raw = 0;
                foreach ($sheet->getRowIterator() as $row) {
                    if (++$raw > $maxRaw || $rows >= $maxRows) {
                        $capped = true;
                        break;
                    }
                    $values = $row->toArray();
                    if (count($values) > $maxCols) {
                        $capped = true;
                        $values = array_slice($values, 0, $maxCols);
                    }
                    $cells = array_map($this->cell(...), $values);
                    if (implode('', $cells) === '') {
                        continue;
                    }
                    $lines[] = implode("\t", $cells);
                    $rows++;
                }
                if (count($lines) > 1) {
                    $chunks[] = implode("\n", $lines);
                }
            }
        } finally {
            $reader->close();
        }
        $this->pushText($out, $name, implode("\n", $chunks), $capped, $used);
    }

    private function text(string $path, string $name, int $size, array &$out, int &$used): void
    {
        $perFile = (int) config('ai.report.extract.text_per_file');
        $raw = file_get_contents($path, false, null, 0, $perFile * (int) config('ai.report.extract.text_read_factor'));
        if ($raw === false) {
            $this->skip($out, $name, 'corrupt');

            return;
        }
        $capped = $size > strlen($raw);
        if (!mb_check_encoding($raw, 'UTF-8')) {
            if (!$capped) {
                $this->skip($out, $name, 'corrupt');

                return;
            }
            $raw = mb_scrub($raw, 'UTF-8');
        }
        $text = mb_substr($raw, 0, $perFile);
        $this->pushText($out, $name, $text, $capped || mb_strlen($raw) > $perFile, $used);
    }

    private function pushText(array &$out, string $name, string $text, bool $capped, int &$used): void
    {
        $perFile = (int) config('ai.report.extract.text_per_file');
        $text = trim($text);
        if ($text === '') {
            $this->skip($out, $name, 'empty');

            return;
        }
        if (mb_strlen($text) > $perFile) {
            $text = mb_substr($text, 0, $perFile);
            $capped = true;
        }
        $room = (int) config('ai.report.extract.text_total') - $used;
        if ($room <= 0) {
            $this->skip($out, $name, 'too_large');

            return;
        }
        if (mb_strlen($text) > $room) {
            $text = mb_substr($text, 0, $room);
            $capped = true;
        }
        if ($capped) {
            $text .= "\n" . self::TRUNCATION;
            $out['notes'][] = 'فایل «' . $this->reportedName($name) . '» به دلیل حجم زیاد ادامه‌اش بریده شد.';
        }
        $used += mb_strlen($text);
        $safeName = str_replace(['<<<', '>>>'], ['«', '»'], $this->reportedName($name));
        $safeBody = str_replace(['<<<', '>>>'], ['«', '»'], $text);
        $fenced = "<<<FILE {$safeName}>>>\n{$safeBody}\n<<<END FILE>>>";
        $out['text'] .= ($out['text'] === '' ? '' : "\n") . $fenced;
    }

    private function cell(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            $value = $value->format('Y-m-d H:i') . ' (' . toJalali(\Carbon\Carbon::instance($value), 'j F Y') . ')';
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '0';
        } elseif (is_int($value)) {
            $value = (string) $value;
        } elseif (is_float($value)) {
            $value = rtrim(rtrim(number_format($value, 10, '.', ''), '0'), '.');
        } elseif (!is_string($value)) {
            return '';
        }

        return mb_substr(trim($value), 0, (int) config('ai.report.spec.cell'));
    }

    private function zipOversized(\ZipArchive $zip, string $capKey): bool
    {
        if ($zip->numFiles > (int) config('ai.report.extract.zip_entries')) {
            return true;
        }
        $cap = (int) config("ai.report.extract.{$capKey}") * 1024;
        $sum = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $sum += (int) ($zip->statIndex($i)['size'] ?? 0);
            if ($sum > $cap) {
                return true;
            }
        }

        return false;
    }

    private function legacyEncryptedOffice(string $path, string $name): bool
    {
        if (!in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), ['docx', 'xlsx'], true)) {
            return false;
        }

        return file_get_contents($path, false, null, 0, 4) === "\xD0\xCF\x11\xE0";
    }

    private function officeEncrypted(string $path, string $inner): bool
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return false;
        }
        $stat = $zip->statName($inner);
        $zip->close();

        return is_array($stat) && ($stat['encryption_method'] ?? 0) !== 0;
    }

    private function reportedName(string $name): string
    {
        return preg_replace('/^\d+-/', '', $name) ?? $name;
    }

    private function skip(array &$out, string $name, string $reason): void
    {
        $out['skipped'][] = ['name' => $this->reportedName($name), 'reason' => $reason];
    }
}
