# Media user-panel pattern

## Scope and source of truth

The user-panel slice follows the frozen Knowledge contracts and their §13 reuse mandate. Files are public, protection grade 1; the user panel reads URLs only and does not write attachments. The department catalog is Tier V per contract section 14 (built inside `Media::cachedCatalog`); progress and notes are Tier L and never cached. Shared models, Actions, endpoints and the player remain owned by their respective slices.

## Library and access

`Main` is lazy, uses `FocusOnRecord`, and exposes one computed `library`. Its placeholder has the identical app-shell height and constant `x-data="media()"` expression. A disabled `reports:media` returns 404 before building the library. `mount()` calls `MarkMediaSeenAction::execute()` once, which clears the menu badge.

`MediaPresenter` reaches roots only through `Media::library($user)`. It eager-loads department and ordered, active, unexpired episodes. Episode reads do not apply an independent audience scope: the visible parent grants access. Future episodes remain listed, but every file and track URL is null. Progress and notes are queried only for the current viewer and the listed episode IDs.

Reports attached through `report_ids` are resolved once through `Report::forViewer($user)->notExpired()`. Missing, inaccessible or fileless reports are omitted. Document metadata is built once per distinct report, including when several series attach the same report. Neither raw model serialization nor storage paths enter the payload.

## Browser and player boundary

`media()` composes the player using property descriptors so player getters are not evaluated during object spread. Root getters remain getters as well. Player initialization consumes the root's progress URL and sync configuration before loading the payload. Keyboard binding, Persian time formatting (`faTime`) and native track construction reuse the player's methods; counters use the player's exported `fa` digit helper (`number()` wraps it).

Selection is view-only: browsing another item must not overwrite the currently playing episode. A moment link stores a pending seek without autoplay; the first explicit play supplies it through the player's resume hook. Search uses `highlightMatch` for escaped highlighting and the existing exported `normalize` from `memberPicker.js`; `highlightMatch.js` itself has no normalization helper.

Search keys and original ordering are indexed once per library payload. One parent effect filters and orders sidebar rows rather than recomputing the complete filtered library per row. The workspace mounts only the selected item's template, including only one video stage. Notes capture their episode ID before awaiting the request so a late response cannot attach a note to the wrong selection.

Unlock timers use the server clock offset. All locks due together cause one library refresh; no guessed file URL is constructed. Refresh failures have a manual retry. Mutation observers, timers, request controllers and track hosts are cleaned up on destroy.

## Reused UI

The shell, rail, row grammar and collapse include mirror Channel; route navigation and download behavior mirror `Tab/Reports`. Title, route and Alpine tabs, form buttons and fields, semantic table, bookmark, row-actions, saved-empty, quota meter, progress ring, empty states, skeletons, dialogs and maximize backdrop all use the shared components. The cinema view is the standard maximize (`maximize` mixin, maximize button, `max-backdrop`); the media code never writes the focus store. The video stage and the video/audio element follow the current episode (`isVideo`), never the series-level `kind`, which is presentation only. Rich descriptions use `rich-colors`.

No custom player skin, progress SVG, loading spinner, toast shell, normalizer or clipboard implementation is introduced. The existing quota-meter and ring markup are updated in place by small browser adapters because they accept server-side scalar props, not reactive Alpine expressions.

## Integration registrations — Lead only

- `resources/js/components/alpine/main.js`: import `media` from `./data/media.js` and register `Alpine.data('media', media)` eagerly, including the placeholder path.
- `resources/js/components/alpine/stores/pinned.js`: add `media` to `SCOPES`, `KEYS` (`saved_media`), `sets` (`null`) and `lists` (`[]`).
- `routes/web.php`: authenticated, tenant-gated `GET /media` → `Main`, name `media`; JSON `POST /media/progress` → `MediaProgressController`, name `media.progress`; JSON `POST /media/notes` → `MediaNoteController`, name `media.notes`; dedicated `media-progress` and `media-notes` limiters with the contract's JSON 401/419 behavior.
- `config/modules.php`: retitle the existing reports entry «دانش سازمانی» and append `media` as its final view; retain Reports as the existing home tab.
- `BadgeLegendCatalog`: register `media-controller` for `media-badge-legend`; register `UnseenMedia` (badge key `media-controller`), navigation and search entries as specified by the shared slice; there is no nudge, provider or profile-hook row.
- Import the shared slice's models/config/Actions and the admin slice's `mixins/player.js` and `components/dashboard/media/player.blade.php` before compiling this slice.
- Run the icon and asset build at integration: user-panel utility classes are not visible on the local Apache installation until built.

## Verification boundary

PHP and JavaScript syntax and Blade component compilation were checked without executing tests. The worktree deliberately has no copied vendor, node_modules or environment file. Tests were authored in the existing module/master slots and were not run. Runtime/browser checks, shared-player behavior, tenant registrations and the production build require the integrated checkout.

The shared model and Actions were re-read for query waste. The existing batched visibility queries and unchanged-progress guard were retained; no speculative shared rewrite or cross-owner edit was made. The confirmed small wins were applied in this slice: narrow episode selection, existing relation ordering, per-report material derivation and one-pass sidebar filtering.

## Targeted lookup evidence

- `Grep 'quota|meter|progress|ring|table|sort' resources/views/components/ui` found `quota-meter`, `decor/progress-ring` and `table`; all were adopted.
- `Grep 'normalize|toPersian|convertToPersian|formatTime|formatDuration|animate-eq' resources/js` found the exported Persian normalizer in `memberPicker.js`; the player's `faTime` is adopted for durations.
- `Grep 'media|keyboard|register|keydown|shortcut' resources/js/components/alpine/data/shortcut.js` found no transport-shortcut registry; `bindPlayerKeys` from the player is used instead of adding a document handler.
- `Grep 'size\\(|bytes|filesize|Number::' resources/views/livewire/dashboard/tab/reports` and `Grep 'bytes|[Ff]ile[Ss]ize|humanReadable|Number::' app/Helpers` found no report file-size formatter; the presenter uses one local size-to-kilobytes expression and `convertToPersian`.
- `Grep 'file_path|@js|wire:click|wire:model|localStorage|<script|rounded-full|#[0-9a-fA-F]{3,8}' resources/views/livewire/dashboard/media` found no matches.

## Master tests

`tests/Feature/Livewire/Dashboard/MediaTest.php` owns payload, parent audience, expiry, lock URL protection, viewer-owned state and deep-link assertions. Reports tests own the route pills, optional download progress and missing-file non-completion. ReportModelTest retains the Office-file thumbnail fallback. `data/__tests__/media.test.js` owns pure client filtering, pin ordering, selection, moment links, lock refresh, notes and teardown.

Player correctness is not certified by this slice: its transport, persistence, video host, sync queue and duration rules are owned by the admin/player slice and must pass its own master tests and the integration browser check.
