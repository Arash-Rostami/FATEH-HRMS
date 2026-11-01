<?php

namespace App\Console\Commands;

use App\Services\TenantService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ExportWinterraRag extends Command
{
    protected $signature = 'winterra:rag-export';

    protected $description = 'Renders every user legend and admin guide to plain-text markdown files in public/winterra for manual hand-off to the AI assistant RAG folder. Run it under the WinterrA tenant (APP_TENANT=persol).';

    protected $hidden = true;

    private array $files = [];

    private const ALIASES = ['messaging' => 'contact', 'faqs' => 'faq', 'feeds' => 'feed', 'posts' => 'announce', 'project' => 'projects'];

    public function handle(TenantService $tenants): int
    {
        if (config('app.tenant') !== 'persol') {
            $this->error('Run under the WinterrA tenant: APP_TENANT=persol php artisan winterra:rag-export');

            return self::FAILURE;
        }

        $out = public_path('winterra');
        $app = str_replace('ـ', '', config('app.name')) . ' (' . config('app.name_en') . ')';

        foreach ($this->legends() as $module => $view) {
            if ($tenants->offName('dashboard.' . str_replace('/', '.', $module))) {
                continue;
            }

            $this->write('user-' . Str::slug(str_replace('/', '-', $module)), "# {$app} — راهنمای کاربر: {$this->label($module)} ({$module})", [$this->text($view)]);
        }

        foreach (config('modules') as $module) {
            if ($tenants->off($module['id'])) {
                continue;
            }

            $module['title'] = trim($module['title'] ?? $module['id']);

            $parts = array_filter([
                $module['nav_category'] ?? null ? 'دسته‌بندی: ' . $module['nav_category'] : null,
                'خلاصه: ' . ($module['summary'] ?? ''),
                'توضیح کامل: ' . ($module['content'] ?? ''),
                $module['cms_hint'] ?? null ? 'نکتهٔ مدیریتی: ' . $module['cms_hint'] : null,
            ]);

            $this->write('module-' . Str::slug($module['id']), "# {$app} — معرفی ماژول: {$module['title']} ({$module['id']})", $parts);
        }

        foreach ($this->features() as $key => $feature) {
            if ($tenants->off(self::ALIASES[$key] ?? $key)) {
                continue;
            }

            $parts = [];

            foreach (['standard' => 'قابلیت‌های اصلی', 'unique' => 'ویژگی‌های متمایز', 'ux' => 'تجربهٔ کاربری'] as $field => $label) {
                $items = array_filter(array_column($feature[$field] ?? [], 'fa'));
                $items && $parts[] = "## {$label}\n" . implode("\n", array_map(fn (string $item) => "- {$item}", $items));
            }

            $this->write('features-' . Str::slug($key), "# {$app} — ویژگی‌های {$feature['title']['fa']} ({$key})", $parts);
        }

        foreach ($this->guides() as $resource => $views) {
            if ($tenants->offName('filament.admin.resources.' . $resource)) {
                continue;
            }

            $class = 'App\\Filament\\Resources\\' . Str::studly($resource) . 'Resource';
            $label = class_exists($class) ? $class::getPluralModelLabel() : $this->label($resource);

            $this->write('admin-' . Str::slug($resource), "# {$app} — راهنمای مدیر: {$label} ({$resource})", array_map($this->text(...), $views));
        }

        File::ensureDirectoryExists($out);
        File::cleanDirectory($out);

        foreach ($this->files as $name => $content) {
            File::put("{$out}/{$name}.md", $content);
        }

        $this->info('Tenant: ' . config('app.tenant') . ' — ' . count($this->files) . " files written to {$out}");

        return self::SUCCESS;
    }

    private function label(string $key): string
    {
        foreach ([Str::afterLast($key, '/'), Str::before($key, '/')] as $leaf) {
            $id = self::ALIASES[$leaf] ?? $leaf;

            foreach (config('modules') as $module) {
                if ($module['id'] === $id || in_array($id, $module['aliases'] ?? [], true)) {
                    return trim($module['title'] ?? $id);
                }
            }
        }

        return $key;
    }

    private function legends(): array
    {
        $base = resource_path('views/livewire/dashboard');
        $legends = [];

        foreach (File::allFiles($base) as $file) {
            if (!str_ends_with($file->getFilename(), 'legend.blade.php') && !str_ends_with($file->getFilename(), 'legends.blade.php')) {
                continue;
            }

            $folder = str_replace('\\', '/', $file->getRelativePath());
            $name = Str::before($file->getFilename(), '.blade.php');
            $module = $name === 'legend' ? $folder : "{$folder}/{$name}";
            $legends[$module] = 'livewire.dashboard.' . str_replace('/', '.', $folder) . '.' . $name;
        }

        ksort($legends);

        return $legends;
    }

    private function features(): array
    {
        $file = public_path('module-features.json');

        $features = is_file($file) ? json_decode(file_get_contents($file), true) : null;

        return is_array($features) ? array_filter($features, fn ($feature) => isset($feature['title']['fa'])) : [];
    }

    private function guides(): array
    {
        $guides = [];

        foreach (glob(resource_path('views/filament/resources/*/guide/*.blade.php')) as $file) {
            $resource = basename(dirname($file, 2));
            $guides[$resource][] = "filament.resources.{$resource}.guide." . basename($file, '.blade.php');
        }

        ksort($guides);

        return $guides;
    }

    private function text(string $view): string
    {
        $html = view($view)->render();
        $html = preg_replace(['#<(script|style)\b.*?</\1>#si', '#<span[^>]*material-symbols[^>]*>.*?</span>#si'], '', $html);
        $html = preg_replace('#</(p|div|li|h[1-6]|tr|section|button)>|<br\s*/?>#i', "\n", $html);
        $lines = array_map(fn (string $line) => trim(preg_replace('/[ \t\x{00A0}]+/u', ' ', $line)), explode("\n", html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5)));

        return implode("\n", array_filter($lines, fn (string $line) => $line !== ''));
    }

    private function write(string $name, string $title, array $parts): void
    {
        $body = trim(implode("\n\n", array_filter($parts)));

        if ($body !== '') {
            $this->files[$name] = "{$title}\n\n{$body}\n";
        }
    }
}
