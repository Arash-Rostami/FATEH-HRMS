<?php
$file = "resources/css/core/filament.css";
$content = file_get_contents($file);

// Replace slideInRight in the media query block.
$content = str_replace(
    '.fi-modal.fi-no-database .fi-modal-window {
        @apply !bg-[var(--md-sys-color-surface)]
        !shadow-[4px_0_40px_color-mix(in_srgb,var(--md-sys-color-primary),_transparent_18%)];
        border-radius: 0 1rem 1rem 0 !important;
        margin-inline-start: auto !important;
        margin-inline-end: 0 !important;
    }',
    '.fi-modal.fi-no-database .fi-modal-window {
        @apply !bg-[var(--md-sys-color-surface)]
        !shadow-[4px_0_40px_color-mix(in_srgb,var(--md-sys-color-primary),_transparent_18%)];
        border-radius: 0 1rem 1rem 0 !important;
        margin-inline-start: auto !important;
        margin-inline-end: 0 !important;
        animation: none !important;
    }',
    $content
);

file_put_contents($file, $content);
