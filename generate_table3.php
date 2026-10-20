<?php
$strings = require 'lang/fa/resources/task/strings.php';
$tabs = $strings['tabs'];
$filters = $strings['filters'];
$validation = $strings['validation'];

echo "### E. Cross-panel string/label consistency (Tabs & Filters)\n\n";

echo "| Lang Key | Lang File String | Filament Admin Panel | Livewire Panel |\n";
echo "| -------- | ---------------- | -------------------- | -------------- |\n";

foreach ($tabs as $key => $val) {
    echo "| `tabs.$key` | $val | `__(\"...tabs.$key\")` | Hardcoded |\n";
}

echo "\n";
foreach ($validation as $key => $val) {
    echo "| `validation.$key.*` | ... | `__(\"...validation.$key.*\")` | Hardcoded in `TaskForm` |\n";
}
