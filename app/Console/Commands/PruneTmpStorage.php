<?php

namespace App\Console\Commands;

use FilesystemIterator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Throwable;

class PruneTmpStorage extends Command
{
    protected $signature = 'storage:prune-tmp {--dry : Report what would be deleted without deleting anything}';

    protected $description = 'Delete temp units (top-level files or folders) under the allowlisted local-disk roots in filesystems.tmp_prune.roots plus the Livewire temp-upload folder, when nothing inside was modified within filesystems.tmp_prune.hours. Symlinks are never followed or deleted and a unit containing one is kept; roots that are missing are ignored, roots that are empty, dotted, symlinked, non-local, outside their disk or not tmp-named are refused.';

    private int $cutoff;

    private int $pruned = 0;

    private int $bytes = 0;

    private int $skipped = 0;

    private int $failed = 0;

    private int $invalid = 0;

    public function handle(): int
    {
        $this->pruned = 0;
        $this->bytes = 0;
        $this->skipped = 0;
        $this->failed = 0;
        $this->invalid = 0;
        $hours = max(1, (int) config('filesystems.tmp_prune.hours'));
        $max = max(1, (int) config('filesystems.tmp_prune.max'));
        $dry = (bool) $this->option('dry');
        $this->cutoff = now()->getTimestamp() - $hours * 3600;
        $roots = config('filesystems.tmp_prune.roots', []);
        $roots[] = [config('livewire.temporary_file_upload.disk') ?: config('filesystems.default'), FileUploadConfiguration::directory()];

        foreach ($roots as [$disk, $path]) {
            if ($this->pruned >= $max) {
                break;
            }
            $root = $this->safeRoot((string) $disk, (string) $path);
            if ($root !== null) {
                try {
                    $this->sweep($root, $max, $dry);
                } catch (Throwable) {
                    $this->failed++;
                    Log::warning('storage:prune-tmp failed', ['root' => $path, 'error' => 'root unreadable']);
                }
            }
        }

        $mb = round($this->bytes / 1048576, 1);
        $verb = $dry ? 'Would prune' : 'Pruned';
        $this->info("{$verb} {$this->pruned} temp items ({$mb} MB) idle for over {$hours} hours; {$this->skipped} kept (symlink or special); {$this->failed} failed; {$this->invalid} invalid roots.");

        return $this->failed + $this->invalid > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function safeRoot(string $disk, string $path): ?string
    {
        $rel = trim(str_replace('\\', '/', $path), '/');
        $segments = $rel === '' ? [] : explode('/', $rel);
        if ($segments === [] || in_array('.', $segments, true) || in_array('..', $segments, true) || !config("filesystems.disks.{$disk}")) {
            return $this->refuse($disk, $path);
        }
        if (!str_contains(strtolower((string) end($segments)), 'tmp')) {
            return $this->refuse($disk, $path);
        }
        try {
            $base = Storage::disk($disk)->getAdapter() instanceof LocalFilesystemAdapter ? realpath(Storage::disk($disk)->path('')) : false;
        } catch (Throwable) {
            $base = false;
        }
        if ($base === false) {
            return $this->refuse($disk, $path);
        }
        $probe = $base;
        foreach ($segments as $segment) {
            $probe .= DIRECTORY_SEPARATOR . $segment;
            if (is_link($probe)) {
                return $this->refuse($disk, $path);
            }
        }
        if (!is_dir($probe)) {
            return null;
        }
        $real = realpath($probe);
        if ($real === false || !str_starts_with($real, $base . DIRECTORY_SEPARATOR)) {
            return $this->refuse($disk, $path);
        }

        return $real;
    }

    private function refuse(string $disk, string $path): ?string
    {
        $this->invalid++;
        $this->error("Refusing unsafe temp root '{$path}' on disk '{$disk}'.");

        return null;
    }

    private function sweep(string $root, int $max, bool $dry): void
    {
        foreach (new FilesystemIterator($root, FilesystemIterator::SKIP_DOTS) as $unit) {
            if ($this->pruned >= $max) {
                return;
            }
            try {
                $stat = $unit->isLink() ? null : $this->inspect($unit);
                if ($stat === null) {
                    $this->skipped++;
                    continue;
                }
                [$newest, $size] = $stat;
                if ($newest >= $this->cutoff) {
                    continue;
                }
                if (!$dry) {
                    $this->remove($unit);
                }
                $this->pruned++;
                $this->bytes += $size;
            } catch (Throwable $e) {
                $this->failed++;
                Log::warning('storage:prune-tmp failed', ['item' => basename($root) . '/' . $unit->getFilename(), 'error' => $e->getMessage()]);
            }
        }
    }

    private function inspect(SplFileInfo $unit): ?array
    {
        if ($unit->isFile()) {
            return [$unit->getMTime(), $unit->getSize()];
        }
        if (!$unit->isDir()) {
            return null;
        }
        $newest = $unit->getMTime();
        $size = 0;
        $entries = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($unit->getPathname(), FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($entries as $entry) {
            if ($entry->isLink()) {
                return null;
            }
            $newest = max($newest, $entry->getMTime());
            $size += $entry->isFile() ? $entry->getSize() : 0;
        }

        return [$newest, $size];
    }

    private function remove(SplFileInfo $unit): void
    {
        clearstatcache(true, $unit->getPathname());
        if ($unit->isLink()) {
            throw new RuntimeException('entry changed during prune');
        }
        if ($unit->isDir()) {
            $entries = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($unit->getPathname(), FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($entries as $entry) {
                $this->unlink($entry);
            }
            unset($entries);
        }
        $this->unlink($unit);
    }

    private function unlink(SplFileInfo $entry): void
    {
        $path = $entry->getPathname();
        if ($entry->isLink() || ($entry->isFile() && $entry->getMTime() >= $this->cutoff)) {
            throw new RuntimeException('entry changed during prune');
        }
        $ok = $entry->isDir() ? @rmdir($path) : @unlink($path);
        if (!$ok && file_exists($path)) {
            throw new RuntimeException('delete failed');
        }
    }
}
