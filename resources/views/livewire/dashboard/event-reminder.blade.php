<div wire:key="event-reminder" class="hidden">
    <div x-data wire:key="event-reminder-{{ $reminder['id'] ?? 'none' }}"
         x-init="window.__eventReminder = @js($reminder ?? false ? ['id' => $reminder['id'], 'eventAtIso' => $reminder['event_at_iso'], 'title' => $reminder['title']] : null); $dispatch('event-reminder-armed')">
    </div>
</div>
