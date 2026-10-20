<?php

$strings = require 'lang/fa/resources/task/strings.php';
$fields = $strings['fields'];
$hints = $strings['hints'];
$validation = $strings['validation'];

echo "### E. Cross-panel string/label consistency (Full Field-by-Field Table)\n\n";

echo "| Lang Key | Lang File String | Filament Admin Panel | Livewire Panel |\n";
echo "| -------- | ---------------- | -------------------- | -------------- |\n";

$filament_fields = [
    'id' => '`__("...fields.id")` via TaskTablePresenter',
    'title' => '`__("...fields.title")`',
    'description' => '`__("...fields.description")`',
    'status' => '`__("...fields.status")`',
    'creator' => '`__("...fields.creator")`',
    'assignee' => '`__("...fields.assignee")`',
    'assignee_hint' => '`__("...fields.assignee_hint")`',
    'self_assigned' => '`__("...fields.self_assigned")`',
    'delegated' => '`__("...fields.delegated")`',
    'deadline' => '`__("...fields.deadline")`',
    'deadline_date' => '`__("...fields.deadline_date")`',
    'deadline_time' => '`__("...fields.deadline_time")`',
    'created_at' => '`__("...fields.created_at")`',
    'updated_at' => '`__("...fields.updated_at")`',
    'deleted_at' => '`__("...fields.deleted_at")`',
    'department' => '`__("...fields.department")`',
    'unit' => '`__("...fields.unit")`',
    'section' => '`__("...fields.section")`',
    'project' => '`__("...fields.project")`',
    'scheme' => '`__("...fields.scheme")`',
    'action_source_domain' => '`__("...fields.action_source_domain")`',
    'action_source' => '`__("...fields.action_source")`',
    'collaborators' => '`__("...fields.collaborators")`',
    'responsible_user' => '`__("...fields.responsible_user")`',
    'state' => '`__("...fields.state")`',
    'attachments' => '`__("...fields.attachments")`',
    'file' => '`__("...fields.file")`',
    'view_file' => '`__("...fields.view_file")`'
];

$livewire_fields = [
    'id' => 'Hardcoded (`#{{ $task["id"] }}`)',
    'title' => 'Hardcoded (`عنوان وظیفه`)',
    'description' => 'Hardcoded (`توضیحات`)',
    'status' => 'Hardcoded (`تغییر وضعیت`)',
    'creator' => 'Not directly labeled',
    'assignee' => 'Hardcoded (`مسئول انجام` / `محول کردن به:`)',
    'assignee_hint' => 'N/A',
    'self_assigned' => 'Hardcoded (`خودم (شخصی)` / `بدون مسئول (خودم)`)',
    'delegated' => 'N/A',
    'deadline' => 'Hardcoded (`مهلت انجام`)',
    'deadline_date' => 'N/A (using generic label)',
    'deadline_time' => 'N/A',
    'created_at' => 'Not explicitly labeled',
    'updated_at' => 'N/A',
    'deleted_at' => 'N/A',
    'department' => 'Hardcoded (`واحد سازمانی/دپارتمان`)',
    'unit' => 'Hardcoded (`واحد (زیرمجموعه)`)',
    'section' => 'Hardcoded (`بخش (زیرمجموعه)`)',
    'project' => 'Hardcoded (`پروژه`)',
    'scheme' => 'Hardcoded (`طرح`)',
    'action_source_domain' => 'Hardcoded (`حوزه منشاء اقدام`)',
    'action_source' => 'Hardcoded (`منشاء اقدام`)',
    'collaborators' => 'Hardcoded (`همکاران`)',
    'responsible_user' => 'Hardcoded (`جوابگو`)',
    'state' => 'Hardcoded (`تعیین تکلیف`)',
    'attachments' => 'Hardcoded (`پیوست‌ها`)',
    'file' => 'N/A',
    'view_file' => 'N/A'
];

foreach ($fields as $key => $val) {
    $fil = $filament_fields[$key] ?? 'Unknown';
    $liv = $livewire_fields[$key] ?? 'Unknown';
    echo "| `fields.$key` | $val | $fil | $liv |\n";
}
