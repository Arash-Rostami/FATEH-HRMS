<?php
$keys = ['shenasnameh', 'national_id', 'insurance_record'];
$timestamp = 1708684000;
$ext = 'jpg';

foreach ($keys as $key) {
    $fileName = "doc_standard_{$key}_{$timestamp}.{$ext}";
    echo "Testing: $fileName\n";
    $normalized = str_replace('__', '_', $fileName);
    if (preg_match('/doc_(standard|custom)_(.+)__?(\d{10,})\.\w+/', $normalized, $matches)) {
        echo "MATCH: Type: {$matches[1]}, Key: {$matches[2]}, TS: {$matches[3]}\n";
    } else {
        echo "FAIL\n";
    }
}
