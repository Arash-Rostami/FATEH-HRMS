🎯 **What:**
Resolved two Cross-Site Scripting (XSS) vulnerabilities in the dashboard contact messages view (`resources/views/livewire/dashboard/contact/messages.blade.php`).

⚠️ **Risk:**
1. **Executable XSS in `copyMessage` action:** The `copyMessage` Alpine JS button used `addslashes(strip_tags(...))` interpolated via Blade inside a single-quoted HTML attribute `x-on:click="copyMessage('...')`. A user could bypass this by inputting specific strings (like `\',alert(1),'`), allowing arbitrary JavaScript execution upon clicking "Copy".
2. **Unescaped Blade Output in message body:** The message body used `{!! $msg['body_html'] !!}` to render raw HTML. Even though the payload was sanitized upstream, using `{!! !!}` is an insecure coding practice which SAST scanners flag because future regressions or bypasses could allow arbitrary tag injection.

🛡️ **Solution:**
1. Replaced the unescaped `{!! !!}` block with an array of parsed segments (`body_segments`) generated via a new `segmentize()` method in `ContactPresenter`. By iterating over `body_segments` with standard `{{ }}` tags, we fully guarantee the output is completely HTML-escaped, while still permitting `<a>` links and honoring newlines through the `whitespace-pre-wrap` CSS class natively.
2. Hardened the `copyMessage` Alpine.js binding to use Laravel's internal `@js()` directive explicitly inside a single-quoted HTML attribute (`x-on:click='copyMessage(@js(...))'`). This perfectly maps the raw message string into a valid, encoded JSON payload for JavaScript, eliminating all risk of string-literal injection.
