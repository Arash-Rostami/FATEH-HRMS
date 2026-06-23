# QA Testing Report: Messenger Module ("مخاطبین (پیام‌رسان)")

## Environment
- Laravel 12 + Livewire 3 + Filament v5
- Browser: Chromium (via Playwright automation)
- Server: Local Artisan Serve / Vite Dev Server

---

## Findings

**Severity: blocker**
**Where:** Messenger chat interface (User panel -> `/contacts`)
**Steps to reproduce:**
1. Log in as a staff user (e.g., User A).
2. Navigate to the contacts/messenger page.
3. Open a chat with another user (e.g., User B).
4. Leave the message input completely blank (no text, no attachments).
5. Click the "Send" button.
**Expected vs. actual:**
- **Expected:** The application should block the send action and display a clear inline validation error (e.g., "نمی‌تواند خالی باشد").
- **Actual:** The system allows the action without showing any validation error in the UI. The request is processed silently without clear user feedback.

**Severity: blocker**
**Where:** Messenger chat interface (User panel -> `/contacts`)
**Steps to reproduce:**
1. Log in as a staff user.
2. Navigate to the contacts/messenger page.
3. Open a chat with another user.
4. Paste a message exceeding 2000 characters (e.g., 2001 chars) into the text area.
5. Click the "Send" button.
**Expected vs. actual:**
- **Expected:** The application should enforce the character limit and display an error indicating the message is too long.
- **Actual:** No validation error is shown in the UI.

**Severity: major**
**Where:** All Pages / Messenger UI
**Steps to reproduce:**
1. Start the local server (`php artisan serve` and `npm run dev`).
2. Navigate to any page on the local dev server (e.g., `/contacts`).
**Expected vs. actual:**
- **Expected:** All assets load successfully.
- **Actual:** Numerous `404 (Not Found)` and `net::ERR_CONNECTION_CLOSED` errors for Vite assets, specifically `material-symbols/rounded.css` and various video assets. This breaks core UI components like the send button and attachment icons.
**Console/network errors observed:**
- `Failed to load resource: the server responded with a status of 404 (Not Found)`
- `Failed to load resource: net::ERR_CONNECTION_CLOSED`

**Severity: minor**
**Where:** Messenger chat interface (User panel -> `/contacts`)
**Steps to reproduce:**
1. Log in as a staff user.
2. Open a chat.
3. Type a valid message.
4. Rapidly click the "Send" button or press "Enter" multiple times.
**Expected vs. actual:**
- **Expected:** The application should debounce the input or temporarily disable the send button to prevent duplicate messages.
- **Actual:** No UI blocking mechanism is present to prevent rapid-fire sending, allowing multiple rapid requests.

---

*Note: Due to severe Vite asset loading failures locally breaking core visual components, manual verification of edge cases requiring UI interaction (File Attachments limits/types, Admin Panel UI sync, right-to-left layout perfection, and real-time Websocket presence updates) could not be fully completed without visual obstruction.*
