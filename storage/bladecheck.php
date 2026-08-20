<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$path = $argv[1] ?? null;
if (!$path || !is_file($path)) {
    fwrite(STDERR, "usage: php bladecheck.php <blade-file>\n");
    exit(2);
}

$compiled = Illuminate\Support\Facades\Blade::compileString(file_get_contents($path));
$tmp = tempnam(sys_get_temp_dir(), 'bladecheck_');
file_put_contents($tmp, $compiled);

$exit = 0;
passthru('php -l ' . escapeshellarg($tmp), $exit);

@unlink($tmp);
exit((int)$exit);