<?php

$report = "";

$report .= "### C. Livewire user panel — edge cases / abuse (Additions)\n\n";

$report .= "**[SEVERITY: Medium] Stale Dependent Select Data (Unit/Section) on Department Change**\n";
$report .= "- **Category:** Data Integrity / UX\n";
$report .= "- **Location:** `app/Livewire/Dashboard/TaskBoard/Main.php`\n";
$report .= "- **Repro:** Select a Department, then select a Unit and Section. Then change the Department to another one via `wire:model.live=\"form.departmentId\"`.\n";
$report .= "- **Expected:** The `form.unit` and `form.section` should be reset when `departmentId` changes to prevent saving stale sub-selections that do not belong to the newly selected department.\n";
$report .= "- **Actual:** The Filament `TaskFormPresenter` implements `->afterStateUpdated(function (callable \$set) { \$set('unit', null); \$set('section', null); })`. However, the Livewire `Main` component does NOT have an `updatedFormDepartmentId()` method to clear the `$form->unit` and `$form->section` properties, so the old values remain.\n\n";


echo $report;
