<?php

namespace App\Livewire\Dashboard\Correspondence\Actions;

use App\Livewire\Dashboard\AiReport\Forms\ComposerForm;
use App\Livewire\Dashboard\Correspondence\Forms\LetterForm;
use App\Models\Correspondence;
use App\Support\FileNamer;
use finfo;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class LetterUploads
{
    private const MAX_PIXELS = 8000000;

    private const MIME_BY_EXT = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'txt' => 'text/plain',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public static function store(Correspondence $letter, array $changes, array &$stored): void
    {
        $limits = LetterForm::limits();
        $existing = count($letter->spec['attachments'] ?? []);
        $total = 0;
        foreach (array_slice(is_array($changes['files'] ?? null) ? $changes['files'] : [], 0, max(0, $limits['files'] - $existing)) as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }
            $meta = self::inspect($file, $limits['mimes'], $limits['per_file_kb']);
            if ($meta === null) {
                continue;
            }
            $total += $meta['size'];
            if ($total > $limits['total_kb'] * 1024) {
                break;
            }
            $path = self::put($letter, $file, $meta, false);
            $stored[] = $path;
            $spec = $letter->spec ?? [];
            $spec['attachments'][] = ['path' => $path, 'name' => $meta['name'], 'mime' => $meta['mime'], 'size' => $meta['size']];
            $letter->spec = $spec;
        }

        $image = $changes['image'] ?? null;
        if ($image instanceof UploadedFile && ($meta = self::inspect($image, $limits['image_mimes'], $limits['image_kb'])) !== null && str_starts_with($meta['mime'], 'image/')) {
            $path = self::put($letter, $image, $meta, true);
            $stored[] = $path;
            $payload = $changes['payload'] ?? [];
            $spec = $letter->spec ?? [];
            $spec['blocks'][] = LetterBody::imageBlock($path, $payload['image_size'] ?? null, $payload['image_align'] ?? null, $payload['image_caption'] ?? '');
            $letter->spec = $spec;
        }
    }

    private static function inspect(UploadedFile $file, string $extensions, int $maxKb): ?array
    {
        $tmp = $file->getRealPath() ?: $file->getPathname();
        $name = mb_substr((string) $file->getClientOriginalName(), 0, 200);
        $size = (int) $file->getSize();
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $allowed = array_filter(array_map('trim', explode(',', $extensions)));
        $expected = self::MIME_BY_EXT[$ext] ?? '';
        $zipOffice = $mime === 'application/zip' && in_array($ext, ['docx', 'xlsx'], true);
        if ($size < 1 || $size > $maxKb * 1024 || !in_array($ext, $allowed, true) || ComposerForm::isEncryptedOffice($mime, $name)) {
            return null;
        }
        if ($expected !== '' && $mime !== $expected && !$zipOffice && !($ext === 'txt' && str_starts_with($mime, 'text/'))) {
            return null;
        }

        return ['name' => $name, 'mime' => $mime, 'size' => $size, 'tmp' => $tmp];
    }

    private static function put(Correspondence $letter, UploadedFile $file, array $meta, bool $image): string
    {
        $path = LetterRules::directory((int) $letter->id) . '/' . FileNamer::stored('correspondence', $file);
        $bytes = $image && function_exists('imagecreatefromstring') ? self::reencode($meta['tmp'], $meta['mime']) : file_get_contents($meta['tmp']);
        if (!is_string($bytes) || $bytes === '') {
            LetterRules::invalid($image ? 'form.image' : 'form.files');
        }
        Storage::disk('local')->put($path, $bytes);

        return $path;
    }

    private static function reencode(string $tmp, string $mime): ?string
    {
        $raw = file_get_contents($tmp);
        $info = is_string($raw) ? @getimagesizefromstring($raw) : false;
        if ($info === false || $info[0] * $info[1] > self::MAX_PIXELS) {
            return null;
        }

        $src = @imagecreatefromstring($raw);
        if ($src === false) {
            return null;
        }

        $alpha = in_array($mime, ['image/png', 'image/webp'], true);
        $alpha && self::keepAlpha($src);

        $width = imagesx($src);
        $max = LetterForm::limits()['image_max_width'];
        if ($width > $max && $max > 0) {
            $height = imagesy($src);
            $dst = imagecreatetruecolor($max, max(1, (int) round($height * ($max / $width))));
            $alpha && self::keepAlpha($dst);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $max, imagesy($dst), $width, $height);
            imagedestroy($src);
            $src = $dst;
        }

        ob_start();
        $ok = match ($mime) {
            'image/png' => imagepng($src),
            'image/webp' => function_exists('imagewebp') && imagewebp($src, null, 85),
            default => imagejpeg($src, null, 85),
        };
        $out = ob_get_clean();
        imagedestroy($src);

        return $ok && is_string($out) && $out !== '' ? $out : null;
    }

    private static function keepAlpha(GdImage $image): void
    {
        imagealphablending($image, false);
        imagesavealpha($image, true);
    }
}
