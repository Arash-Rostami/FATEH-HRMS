<div wire:key="event-reminder" class="hidden">
    <div x-data
         x-init="window.__eventReminder = @js($reminder ?? false ? ['eventAtIso' => $reminder['event_at_iso'], 'title' => $reminder['title']] : null)">
    </div>
</div>
