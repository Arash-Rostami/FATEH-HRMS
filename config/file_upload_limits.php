<?php

/**
 * Shared KB size tiers for every file-upload validation rule in the app —
 * both admin Filament `FileUpload::make()->maxSize()` calls and user-panel
 * Livewire Form/Action `'max:N'` rules reference these same keys instead of
 * hardcoding a number, so fields that write to the same underlying storage
 * from both panels (Task, Ths, ReleaseRequest, Profile) can't silently drift
 * apart. Read straight through the config repository: config('file_upload_limits.image').
 *
 * Add a new tier here if none of the four fit — never a bespoke number on the call site.
 */

return [
    'icon' => 1024,
    'image' => 5120,
    'document' => 10240,
    'video' => 97280,
];
