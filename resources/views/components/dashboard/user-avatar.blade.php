@props([
    'user',
    'size' => 'md',
    'dot' => true,
    'badge' => true,
])

@php
    $online = $user->isOnline();
@endphp

<x-ui.avatar
    :existingImage="$user->getProfileImageUrl() ?? $user->getInitialsAvatarUrl()"
    :alt="$user->name"
    :inactive="! $user->isActive()"
    :size="$size"
    :online="$dot ? $online : null"
    :status="$badge ? $user->presence : null"
    :focus="$user->focusLabel()"
    :onlineLabel="$online ? 'آنلاین' : 'آفلاین · ' . $user->lastSeenLabel()"
    {{ $attributes }}
/>
