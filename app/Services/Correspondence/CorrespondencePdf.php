<?php

namespace App\Services\Correspondence;

use App\Livewire\Dashboard\Correspondence\Presentation\CorrespondencePresenter;
use App\Models\Correspondence;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use RuntimeException;
use Throwable;

class CorrespondencePdf
{
    public const MAX_BYTES = 20971520;

    private const RENDERER_VERSION = '2';

    private const MAX_HTML = 2000000;

    private const MAX_IMAGE = 5242880;

    private const FONT_FILE = 'Yekan.ttf';

    public static function renderColumns(): array
    {
        return [
            'id', 'status', 'classification', 'sign_mode', 'type', 'subject', 'number', 'letter_date',
            'letterhead_id', 'content_hash', 'pdf_path', 'pdf_hash', 'spec', 'meta', 'parties',
        ];
    }

    public static function absolute(string $relative): ?string
    {
        if ($relative === '' || str_contains($relative, '..')) {
            return null;
        }

        $root = realpath(self::disk()->path(''));
        $file = realpath(self::disk()->path($relative));
        if ($root === false || $file === false || !is_file($file)) {
            return null;
        }
        $root = str_replace('\\', '/', $root);
        $file = str_replace('\\', '/', $file);

        return str_starts_with($file, $root . '/') ? $file : null;
    }

    private static function disk(): Filesystem
    {
        return Storage::disk('local');
    }

    public static function path(Correspondence $letter): string
    {
        $letter = self::complete($letter);
        if ($letter->status !== Correspondence::STATUS_APPROVED) {
            throw new RuntimeException('not_approved');
        }

        $letter->loadMissing('letterhead');
        $hash = self::hashOf($letter);
        $relative = 'correspondence/' . $letter->id . '/letter-' . $hash . '.pdf';
        $disk = self::disk();

        if (self::cached($letter, $hash, $relative)) {
            return $relative;
        }

        if (!class_exists(Mpdf::class) || !class_exists(ConfigVariables::class) || !class_exists(FontVariables::class)) {
            throw new RuntimeException('pdf_unavailable');
        }

        $font = resource_path('assets/fonts/' . self::FONT_FILE);
        if (!is_file($font)) {
            throw new RuntimeException('font_missing');
        }

        $previous = null;
        $part = null;
        $lock = Cache::lock('correspondence-pdf:' . $letter->id, 120);
        $mpdf = null;

        try {
            $lock->block(20);
            $fresh = Correspondence::query()->with('letterhead')->whereKey($letter->getKey())->first(self::renderColumns());
            if ($fresh === null) {
                throw new RuntimeException('pdf_failed');
            }
            if ($fresh->status !== Correspondence::STATUS_APPROVED) {
                throw new RuntimeException('not_approved');
            }
            $letter = $fresh;
            $hash = self::hashOf($letter);
            $relative = 'correspondence/' . $letter->id . '/letter-' . $hash . '.pdf';
            $previous = is_string($letter->pdf_path) ? $letter->pdf_path : null;
            if (self::cached($letter, $hash, $relative)) {
                return $relative;
            }

            $bag = (new CorrespondencePresenter())->paperData($letter, 'pdf');
            $html = self::localHtml(view('livewire.dashboard.correspondence.paper', [
                'letter' => $letter,
                'mode' => 'pdf',
                'd' => $bag,
            ])->render());
            [$html, $css] = self::pullTag($html, 'style');
            [$html, $header] = self::pullDiv($html, 'paper-head');
            [$html, $footer] = self::pullDiv($html, 'paper-foot');
            $header = self::paint($header);
            $footer = self::paint($footer);
            $stripped = preg_replace('/@page\s*\{[^{}]*\}/', '', $css);
            if ($stripped === null) {
                throw new RuntimeException('pdf_failed');
            }
            $css = $stripped;

            $config = is_array($bag['config'] ?? null) ? $bag['config'] : [];
            $margins = is_array($bag['margins'] ?? null) ? $bag['margins'] : [];
            $size = (string) ($bag['size'] ?? 'a4');
            $top = max((float) ($margins['top'] ?? 0) * 10, (float) ($config['header_height_mm'] ?? 0));
            $bottom = max((float) ($margins['bottom'] ?? 0) * 10, (float) ($config['footer_height_mm'] ?? 0));

            $fontDirs = (new ConfigVariables())->getDefaults()['fontDir'];
            $fontData = (new FontVariables())->getDefaults()['fontdata'];
            $face = [
                'R' => self::FONT_FILE,
                'useOTL' => 0xFF,
                'useKashida' => 75,
            ];

            $registry = CorrespondenceFonts::pdfData();
            $default = strtolower(str_replace([' ', '-'], '', CorrespondenceFonts::pick($bag['style']['font'] ?? null, 'fa')));
            $default = isset($registry[$default]) ? $default : 'nazanin';

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => strtoupper($size),
                'margin_top' => $top,
                'margin_bottom' => $bottom,
                'margin_left' => (float) ($margins['left'] ?? 0) * 10,
                'margin_right' => (float) ($margins['right'] ?? 0) * 10,
                'margin_header' => 0,
                'margin_footer' => 0,
                'tempDir' => self::tempDir(),
                'fontDir' => array_merge($fontDirs, [resource_path('assets/fonts')]),
                'fontdata' => $fontData + ['yekan' => $face] + $registry,
                'default_font' => $default,
                'autoScriptToLang' => true,
                'autoLangToFont' => false,
                'curlAllowUnsafeSslRequests' => false,
                'curlFollowLocation' => false,
                'curlTimeout' => 1,
                'debug' => false,
            ]);
            $mpdf->SetDirectionality('rtl');
            $mpdf->showImageErrors = false;

            if (($config['repeat_header'] ?? false) === true && $header !== '') {
                $mpdf->SetHTMLHeader($header);
            } elseif ($header !== '') {
                $html = $header . $html;
            }

            $numbers = '';
            if (($bag['page_numbers'] ?? false) === true) {
                $pageNo = ($config['page_number_format'] ?? '') === 'page_only' ? '{PAGENO}' : '{PAGENO} / {nbpg}';
                $numbers = '<div style="text-align:center;font-family:' . $default . ';font-size:9pt;">' . $pageNo . '</div>';
            }

            if (($config['repeat_footer'] ?? false) === true && $footer !== '') {
                $mpdf->SetHTMLFooter($footer . $numbers);
            } else {
                if ($footer !== '') {
                    $html .= $footer;
                }
                if ($numbers !== '') {
                    $mpdf->SetHTMLFooter($numbers);
                }
            }

            $mark = trim(html_entity_decode(strip_tags((string) ($bag['watermark'] ?? '')), ENT_QUOTES, 'UTF-8'));
            if ($mark !== '') {
                $mpdf->SetWatermarkText(mb_substr($mark, 0, 80), (float) ($config['watermark_opacity'] ?? 0));
                $mpdf->showWatermarkText = true;
            }

            $directory = self::directory($disk->path('correspondence/' . $letter->id));
            self::pruneParts($directory);
            $part = $relative . '.part.' . bin2hex(random_bytes(4));
            if ($css !== '') {
                $mpdf->WriteHTML($css, HTMLParserMode::HEADER_CSS);
            }
            $mpdf->WriteHTML('<div dir="rtl">' . $html . '</div>', HTMLParserMode::HTML_BODY);
            $mpdf->Output($disk->path($part), 'F');
            unset($mpdf);

            $bytes = filesize($disk->path($part));
            if ($bytes === false || $bytes > self::MAX_BYTES) {
                throw new RuntimeException('too_large');
            }

            $final = $disk->path($relative);
            $partPath = $disk->path($part);
            if (!is_file($final)) {
                if (!rename($partPath, $final)) {
                    throw new RuntimeException('pdf_failed');
                }
            } elseif (is_file($partPath)) {
                unlink($partPath);
            }

            $letter->newQuery()->whereKey($letter->getKey())->toBase()->update(['pdf_path' => $relative, 'pdf_hash' => $hash]);

            if ($previous !== null && $previous !== $relative && self::owned($letter, $previous)) {
                try {
                    $disk->delete($previous);
                } catch (Throwable) {
                }
            }

            return $relative;
        } catch (LockTimeoutException) {
            throw new RuntimeException('pdf_failed');
        } catch (RuntimeException $e) {
            self::cleanup($part);
            if (in_array($e->getMessage(), ['not_approved', 'pdf_unavailable', 'font_missing', 'too_large', 'pdf_failed'], true)) {
                throw $e;
            }

            throw new RuntimeException('pdf_failed');
        } catch (Throwable) {
            self::cleanup($part);

            throw new RuntimeException('pdf_failed');
        } finally {
            $lock->release();
        }
    }

    private static function hashOf(Correspondence $letter): string
    {
        return hash('sha256', (string) $letter->content_hash . self::signature($letter) . self::RENDERER_VERSION);
    }

    private static function signature(Correspondence $letter): string
    {
        $meta = is_array($letter->meta) ? $letter->meta : [];
        $snapshot = is_array($meta['snapshot'] ?? null) ? $meta['snapshot'] : [];
        unset($snapshot['taken_at']);

        if ($snapshot === []) {
            $head = $letter->letterhead;
            $snapshot = [
                'id' => $head?->id,
                'size' => $head?->size,
                'margins' => $head?->margins,
                'style' => $head?->style,
                'config' => $head?->config,
                'fields_box' => $head?->fields_box,
                'header_image' => $head?->header_image,
                'footer_image' => $head?->footer_image,
                'stamp_image' => $head?->stamp_image,
                'watermark' => $head?->watermark,
                'page_numbers' => $head?->page_numbers,
            ];
        }

        $payload = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return hash('sha256', $payload === false ? '' : $payload);
    }

    private static function localHtml(string $html): string
    {
        if (strlen($html) > self::MAX_HTML) {
            throw new RuntimeException('too_large');
        }

        $html = self::mustReplace('#<script\b[^>]*>.*?</script>#is', '', $html);
        $html = self::mustReplaceCallback(
            '#(<style\b[^>]*>)(.*?)(</style>)#is',
            fn (array $match): string => $match[1] . self::cssUrls(self::stripRemoteCss($match[2])) . $match[3],
            $html
        );
        $html = self::mustReplaceCallback(
            '#\bstyle\s*=\s*([\'"])(.*?)\1#is',
            fn (array $match): string => 'style=' . $match[1] . self::cssUrls(self::stripRemoteCss($match[2])) . $match[1],
            $html
        );
        $html = self::mustReplaceCallback(
            '#\bsrc\s*=\s*([\'"])(.*?)\1#is',
            function (array $match): string {
                $path = str_replace('\\', '/', $match[2]);

                return self::allowedImage($path) ? 'src=' . $match[1] . $path . $match[1] : '';
            },
            $html
        );

        return $html;
    }

    private static function paint(string $html): string
    {
        if ($html === '' || strlen($html) > self::MAX_HTML) {
            return $html;
        }

        return self::mustReplaceCallback(
            '#background-image:\s*url\(\s*[\'"]?([^\'")]+)[\'"]?\s*\)#i',
            function (array $match): string {
                $path = str_replace('\\', '/', $match[1]);
                if (!self::allowedImage($path)) {
                    return 'background-image:none';
                }

                return "background-image:url('" . htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . "');background-image-resize:4";
            },
            $html
        );
    }

    private static function stripRemoteCss(string $css): string
    {
        if (strlen($css) > self::MAX_HTML) {
            throw new RuntimeException('pdf_failed');
        }

        $css = self::mustReplace('#@import[^;]+;#i', '', $css);

        return self::mustReplace('#url\(\s*[\'"]?\s*https?:[^)]+\)#i', 'none', $css);
    }

    private static function cssUrls(string $css): string
    {
        return self::mustReplaceCallback(
            '#url\(\s*([\'"]?)([^\'")]+)\1\s*\)#i',
            function (array $match): string {
                $path = str_replace('\\', '/', $match[2]);
                if (!self::allowedImage($path)) {
                    return 'none';
                }

                return "url('" . str_replace("'", '', $path) . "')";
            },
            $css
        );
    }

    private static function mustReplace(string $pattern, string $replacement, string $subject): string
    {
        $replaced = preg_replace($pattern, $replacement, $subject);
        if ($replaced === null) {
            throw new RuntimeException('pdf_failed');
        }

        return $replaced;
    }

    private static function mustReplaceCallback(string $pattern, callable $callback, string $subject): string
    {
        $replaced = preg_replace_callback($pattern, $callback, $subject);
        if ($replaced === null) {
            throw new RuntimeException('pdf_failed');
        }

        return $replaced;
    }

    private static function pullTag(string $html, string $tag): array
    {
        $open = '<' . $tag . '>';
        $close = '</' . $tag . '>';
        $start = strpos($html, $open);
        $end = $start === false ? false : strpos($html, $close, $start);

        if ($start === false || $end === false) {
            return [$html, ''];
        }

        $end += strlen($close);
        $innerStart = $start + strlen($open);

        return [substr($html, 0, $start) . substr($html, $end), substr($html, $innerStart, $end - strlen($close) - $innerStart)];
    }

    private static function pullDiv(string $html, string $class): array
    {
        $open = '<div class="' . $class . '"';
        $start = strpos($html, $open);
        $end = $start === false ? false : strpos($html, '</div>', $start);

        if ($start === false || $end === false) {
            return [$html, ''];
        }

        $end += 6;

        return [substr($html, 0, $start) . substr($html, $end), substr($html, $start, $end - $start)];
    }

    private static function allowedImage(string $path): bool
    {
        $path = str_replace('\\', '/', $path);
        if ($path === '' || str_contains($path, '..') || preg_match('#^https?:#i', $path) === 1) {
            return false;
        }

        $real = realpath($path);
        if ($real === false || !is_file($real)) {
            return false;
        }

        $size = filesize($real);
        if ($size === false || $size > self::MAX_IMAGE) {
            return false;
        }

        $real = str_replace('\\', '/', $real);
        foreach ([storage_path('app/public'), storage_path('app/private')] as $root) {
            $base = realpath($root);
            if ($base !== false && str_starts_with($real, str_replace('\\', '/', $base) . '/')) {
                return true;
            }
        }

        return false;
    }

    private static function owned(Correspondence $letter, mixed $path): bool
    {
        $prefix = 'correspondence/' . $letter->id . '/';

        return is_string($path)
            && str_starts_with($path, $prefix)
            && !str_contains($path, '..')
            && str_ends_with($path, '.pdf');
    }

    private static function tempDir(): string
    {
        return self::directory(storage_path('app/mpdf-tmp'));
    }

    private static function directory(string $dir): string
    {
        if (!is_dir($dir)) {
            if (@mkdir($dir, 0775, true)) {
                @chmod($dir, 0775);
            } elseif (!is_dir($dir)) {
                throw new RuntimeException('pdf_failed');
            }
        }

        return $dir;
    }

    private static function cleanup(?string $part): void
    {
        if ($part === null) {
            return;
        }

        $disk = self::disk();
        if ($disk->exists($part)) {
            $disk->delete($part);
        }
    }

    private static function complete(Correspondence $letter): Correspondence
    {
        $required = ['spec', 'meta', 'status', 'content_hash', 'letterhead_id', 'pdf_path', 'pdf_hash'];
        if (array_diff_key(array_flip($required), $letter->getAttributes()) !== []) {
            $full = Correspondence::query()->whereKey($letter->getKey())->first(self::renderColumns());
            if ($full === null) {
                throw new RuntimeException('pdf_failed');
            }
            $letter = $full;
        }

        if (!is_array($letter->spec) || $letter->spec === []) {
            throw new RuntimeException('pdf_failed');
        }

        return $letter;
    }

    private static function cached(Correspondence $letter, string $hash, string $relative): bool
    {
        return $letter->pdf_hash === $hash
            && self::owned($letter, $letter->pdf_path)
            && $letter->pdf_path === $relative
            && self::disk()->exists($relative);
    }

    private static function pruneParts(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $found = glob($directory . '/*.part.*');
        if (!is_array($found)) {
            return;
        }

        $now = time();
        $removed = 0;
        foreach ($found as $file) {
            if ($removed >= 20 || !is_file($file)) {
                continue;
            }
            $mtime = filemtime($file);
            if ($mtime !== false && $now - $mtime > 120) {
                unlink($file);
                $removed++;
            }
        }
    }
}
