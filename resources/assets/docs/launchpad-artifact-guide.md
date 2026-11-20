# Launchpad artifact guide (English first, Persian second)

Give this file to your AI so the artifact it builds is accepted on upload and matches the Fateh app visually. Every number and rule comes from the app code; add nothing of your own. Part 1 is English, Part 2 (below it) is the same guide in Persian. The shared CSS tokens and starter files live in Part 2 sections 4 and 5 and apply to both languages.

## Part 1 - English

### Suggested prompt for the AI

```
Read the attached guide fully and build a web app exactly as it says.
The app is a few plain files in one folder (index.html, styles.css, app.js and font files).
Every word the user sees must be Persian; no English word may appear on screen.
Use no internet address at all; everything must come from these files.
Read the data from data.json and build the four states: loading, normal, empty, failed.
Build the look only from the colours and sizes in the guide; it must work on phones and desktops, in the light theme only (no dark mode).
At the end, go through the final checklist one by one and report the result.
```

### 1. Hard acceptance rules

| Rule | Value |
| :--- | :--- |
| Structure | Flat files only; no folders, no zip; all files selected together |
| Entry file | Exactly `index.html` (lowercase), must exist |
| Max file count | 50 |
| Max size per file | 10 MB (10240 KB) |
| Max total size | 50 MB (51200 KB) |
| Max file name length | 120 characters |
| Duplicate names | Names differing only in letter case (`a.html` and `A.HTML`) count as duplicates |
| Forbidden names | Empty, starting with a dot, containing `/`, `\`, `..` or control characters |
| Allowed extensions | `html` `htm` `css` `js` `mjs` `json` `txt` `svg` `png` `jpg` `jpeg` `gif` `webp` `avif` `ico` `woff` `woff2` `ttf` `otf` |
| Denied fragments | Any name containing `.php` `.phtml` `.phar` `.htaccess` `.user.ini` `.cgi` `.sh` `.asp` `.jsp` (so `x.php.txt` is rejected too) |
| PHP content | A file is rejected if its first 512 bytes contain `<?php` or `<?=` (even with a `txt` extension) |
| Reserved file | `data.json` is written by the app; do not ship one except a test sample you remove before upload |

Links:

- All links relative, no leading slash: `styles.css` is right, `/styles.css` is wrong.
- Load NOTHING from the internet: no CDN, no Google Fonts, no remote images, no online library. Ship library files next to the app.
- No `http://` or `https://` address anywhere in the code.

The app opens in a sandbox (`allow-scripts` only) and the server adds a strict policy to every file:

| Do not do this | Because |
| :--- | :--- |
| `document.cookie` | No cookies; it throws |
| `localStorage` / `sessionStorage` outside `try/catch` | It throws a security error here; if needed, use only inside `try/catch` and assume it may fail |
| `alert` `confirm` `prompt` | Blocked; show messages inside the page |
| `eval`, `new Function` | Not allowed |
| `window.open`, new windows, navigating to another site | New windows are blocked. Do not navigate the frame to another site: the sandbox does not prevent navigation of the frame itself |
| Form submit | Blocked; use plain buttons and JavaScript |
| `fetch` of anything except your own files | Only relative URLs of the same app work |
| `<audio>` `<video>` `<iframe>` `<object>` `<embed>` | Not allowed |
| Downloads, printing, service workers | Unsupported |

Allowed: scripts and styles inline or from sibling files, images from own files or `data:`/`blob:`, fonts from own files or `data:`, `fetch` of sibling files.

Testing note: do not open `index.html` in a bare tab; it only opens inside the Launchpad frame. Test locally with a simple server (for example `python -m http.server`) and a sample `data.json` beside it.

### 2. Data contract: data.json

The page reads `data.json`. The nightly planner starts at 05:01 Tehran time and schedules up to 43 programs at one-minute intervals from 05:02 to 05:44; overflow waits until the next night. Only live programs (active and inside their access dates) are included. Creating or replacing a program with a data source, or activating one, also queues a fetch. No polling is needed; a manual "Refresh" button that reads the file again is enough. This button does not request fresh data from the source; managers use the Launchpad list action for that.

```js
fetch('data.json', {cache: 'no-store'})
```

Shapes: `{"updated_at": "...", "ok": true, "data": ...}` and `{"updated_at": "...", "ok": false, "data": null}`. `updated_at` is an ISO date string.

| Situation | Meaning | Page behaviour |
| :--- | :--- | :--- |
| 404 reply | The file is unavailable; the first run may still be pending | Empty state; do not promise that the next run will fix it |
| `ok: true` | Success; `data` is the service reply | Normal view; empty state if there is nothing to show |
| `ok: false` | Fetch failed, `data` is `null` | Failed state with the last update time |

- Show `updated_at` as a Jalali date only.
- The four internal statuses (`ok` `unreachable` `denied` `invalid`) are visible to people who can manage the program; the page must not depend on them and reads only `ok`.
- Google Sheets source: `data.valueRanges[i].values` is an array of rows; for an empty range `values` may be missing, so always use `?? []`. Values are the text as shown in the sheet.
- Other API source: `data` is exactly the JSON the API returned (max 2 MB).
- Ask the owner for the shape of `data`; never assume it, any field may be missing.

Required UI states: loading (pulsing skeleton), normal (title, last update, "refresh" button, content), empty (icon, "nothing to show", short hint), failed (error colours, last update time, "try again" button).

### 3. How the owner connects the data source (Launchpad form)

The section is optional; without it no `data.json` is created. The URL must start with `https://`, port 443 only, no IP addresses or internal hosts.

The first choice is «نوع منبع داده»; the fields below depend on it.

| Type | Fields | Required |
| :--- | :--- | :--- |
| بدون منبع داده (default) | None | Nothing is validated or stored; when replacing, the old connection is kept |
| Google Sheets | Access (public or private), spreadsheet id, cell ranges, Google key file text (JSON) | Access, id and ranges required; the key only when access is private; URL, method and body are hidden |
| API بدون نیاز به ورود | URL, method (GET or POST) | URL and method required; request body (JSON) only for POST and optional |
| API با کلید یا توکن | URL, method, key type (Bearer, X-API-Key, or custom Header name), Header name (custom only), key value | Value required; name required only for custom; value is hidden after saving |
| API با نام کاربری و رمز عبور | URL, method, login URL, login body (JSON), token location in the reply | Login URL and body required; location optional (empty = auto-detect among `access_token` `token` `data.access_token` `data.token` `jwt` `id_token`) |

Google Sheets notes: one range per line, at most 10 lines of 100 characters, e.g. `'Sheet name'!A1:H17`. Public access needs no key and works only while the sheet is shared as «هر کسی که لینک را دارد» with role «بیننده» (Viewer). Private access needs the service-account key; share the sheet with the `client_email` found in the key, as Viewer.

The app retries up to 3 times (network errors, 429, 5xx only), each call waits at most 10 seconds, and a failed run still writes `data.json` with `ok:false`. The owner can refresh manually, once per 10 minutes.

### 4. Design system

Details are in Part 2 section 4 (tokens, type scale, spacing, radius, components); the CSS there is the single source. Summary:

- Colours only via the `--md-sys-color-*` variables (no hex outside the `:root` blocks); light theme only: no dark mode and no `prefers-color-scheme` block, and `<meta name="color-scheme" content="light">` in the head; always pair `primary` with `on-primary`; page background `surface`; card border `outline-variant`.
- Font Vazirmatn from local woff2 files, copied from `resources/assets/fonts/vazirmatn/`: `Vazirmatn-Regular.woff2` (400) and `Vazirmatn-Bold.woff2` (700) next to `index.html`. No 500 weight exists. Inline SVG icons only.
- Sizes in `rem`; text 14px, captions 12px, micro 11px, section title 16px bold, big numbers 24-36px bold; 4px spacing grid; radius card/table 16px, button 12px, badge 8px; max page width `88rem`.
- Components: page shell, card, filled button (44px), tonal button (40px), compact table (sticky header, inner horizontal scroll), badge, empty state, skeleton, section title, stat tile.
- Phone first: works at 400px with no horizontal page scroll; desktop switch at 768px; touch targets at least 32px; inputs 16px.
- `dir="rtl"`, `lang="fa"`; numbers via `Intl.NumberFormat('fa-IR')`; dates only via `Intl.DateTimeFormat('fa-IR-u-ca-persian', {timeZone: 'Asia/Tehran'})`, never Gregorian on screen.
- Accessibility: visible focus ring (2px primary, 2px offset); disable animations under `prefers-reduced-motion`; Persian `aria-label` on icon-only buttons.

### 5. Starter kit

Use the `index.html`, `styles.css` and `app.js` in Part 2 section 5 as they are, then adapt how `data` is rendered. They render all four states from `data.json`.

### 6. Copy rules

- Every visible word is Persian; no English words or Latin abbreviations on screen.
- Short plain sentences; every error says what happened and what to do.
- Buttons are simple verbs: refresh, try again, close (in Persian).
- Persian digits through `Intl.NumberFormat` and Jalali dates only.
- Alt texts and `aria-label` are Persian too.

### 7. Final checklist

- [ ] Flat files only, `index.html` present, at most 50 files, each under 10 MB, total under 50 MB.
- [ ] All extensions allowed; no denied fragment; no name starting with a dot.
- [ ] No `http://`/`https://` address, no link with a leading slash.
- [ ] No cookie, `alert`, `confirm`, `prompt`, `eval`, `window.open` or form submit.
- [ ] Every storage use is inside `try/catch` (or absent).
- [ ] All four states work: `data.json` present, missing (404), `ok:false`, empty range.
- [ ] No horizontal page scroll at 400px; desktop layout from 768px.
- [ ] Light theme only (no dark mode, no `prefers-color-scheme`); no hex outside the `:root` block.
- [ ] No English word, Latin abbreviation or Gregorian date on screen.
- [ ] Fonts load locally and both font files sit beside `index.html`.
- [ ] Focus ring visible; animations off under reduced motion.
- [ ] No console errors.

### 8. Form fill sheet (the AI ends its answer with this)

The form shows only the fields of the chosen «نوع منبع داده», in the order of this sheet. Write the sheet in that order with the exact labels, and if the form on screen differs from this guide (last synced 26 Aban 1405), follow the form and tell the author.

SAFETY RULE: the author must NEVER paste a real key, password, token or service-account JSON into this AI chat. The AI never asks for, invents or repeats a secret; it writes a placeholder such as `<PASTE_KEY_HERE>`. The author types the secret directly into the Launchpad form (it is stored encrypted and never shown again).

Field labels below are the Persian wording of the form. Program type: this guide covers only the file program (the default). The other type, a shortcut to a sibling system («ساخت میان‌بر به سامانهٔ دیگر»), needs no files and is out of scope for artifacts.

**Main fields**

| Field label | Required | What to put | Example | Rule |
| :--- | :--- | :--- | :--- | :--- |
| نام برنامه | Required | Short program name | `گزارش پروژه‌ها` | 3 to 120 characters; unique per department; same title in the same department = replace |
| توضیح کوتاه (اختیاری) | Optional | One sentence | `آمار هفتگی پروژه‌ها` | Max 500 characters |
| دپارتمان | Required | Department to own it | pick from list | At most 3 programs per department |
| انتخاب فایل‌ها | Required | All files at once | `index.html`, `styles.css`, `app.js`, 2 fonts | Flat; `index.html` present; max 50 files, 10 MB each, 50 MB total; allowed types per section 1 |
| مخاطبان | Optional | People who may see it | pick from list | Max 200; empty = only the owner and managers; editable later through Details, then Edit |
| از چه تاریخی باز شود؟ (اختیاری) | Optional | Jalali start date | `۱۴۰۵/۰۹/۰۱` | Editable later through Details, then Edit |
| تا چه تاریخی باز بماند؟ (اختیاری) | Optional | Jalali end of access | `۱۴۰۵/۱۲/۲۹` | Today or later; not before the start date |
| آخرین روز نگهداری برنامه (اختیاری) | Optional | Jalali last day kept | `۱۴۰۶/۰۱/۳۱` | Today or later; not before the start date |

**Data source** (accordion «دریافت خودکار اطلاعات (اختیاری)»; leave it empty if the app has no live data)

| Field label | Required | What to put | Example | Rule |
| :--- | :--- | :--- | :--- | :--- |
| نشانی دریافت اطلاعات (API) | Required if used | Service address | `https://api.example.com/v1/report` | `https://` only, port 443, no IP, no user or password in it, max 500, one line, no spaces; shown only for the three API types |
| روش دریافت اطلاعات | Required | GET (default, filled automatically) or POST | `GET` | Only GET or POST |
| اطلاعات ارسالی (JSON) | Optional | Only if the service needs a POST body | `{"from":"2026-01-01"}` | Only for POST; valid JSON; max 2000 characters |
| نوع منبع داده | Required, first field | One of the five types below (default: بدون منبع داده) | | The three fields above appear only for the three API types |

Type «بدون منبع داده»: nothing else is shown, validated or stored.

Type «API بدون نیاز به ورود»: no extra fields.

Type «API با کلید یا توکن»:

| Field label | Required | What to put | Example | Rule |
| :--- | :--- | :--- | :--- | :--- |
| نوع کلید دسترسی | Required | `Bearer` (default), `X-API-Key` or custom Header | | One of the three |
| نام کلید در درخواست (Header) | Required only for custom | Header name from the service guide | `X-Custom-Key` | Letters, digits, hyphen; max 64; not Host, Content-Length, Transfer-Encoding, Connection |
| مقدار کلید یا توکن | Required, secret | Placeholder `<PASTE_KEY_HERE>` | | One line, max 2000; for Bearer give only the code, the word Bearer is added automatically |

Type «API با نام کاربری و رمز عبور»:

| Field label | Required | What to put | Example | Rule |
| :--- | :--- | :--- | :--- | :--- |
| نشانی ورود | Required | Service login address | `https://api.example.com/auth/login` | Same address rules as above |
| اطلاعات ورود | Required, secret | Placeholder JSON `{"username":"<USERNAME>","password":"<PASSWORD>"}` | | JSON object, max 2000 |
| محل کد دسترسی در پاسخ ورود | Optional | Leave empty; fill only if the service guide names a place | `data.token` | Empty = auto-detect in this order: `access_token` `token` `data.access_token` `data.token` `jwt` `id_token`; letters, digits, `.` `-` `_`, max 80 |

Type «Google Sheets» (address, method and body are hidden). The ranges layout must match what the artifact reads (`data.valueRanges[i].values`, one entry per range line, in order). Empty cells at the end of a row are dropped, an empty row inside the range is `[]`, and the data is at most 2 MB per response and per run.

| Field label | Required | What to put | Example | Rule |
| :--- | :--- | :--- | :--- | :--- |
| نحوه دسترسی | Required | `public` (default): no key; the sheet must be shared as «هر کسی که لینک را دارد» with role «بیننده» (Viewer). `private`: service-account key | | One of the two |
| شناسهٔ فایل گوگل‌شیت | Required | Only the part between `/d/` and `/edit` | `1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms` | 20 to 80 letters, digits, `-` `_` |
| سلول‌های موردنیاز | Required | One range per line, always with the tab name | `'داشبورد پروژه'!A1:H17` | 1 to 10 lines, 100 characters each; tab name required, quoted with `'` if it has spaces or Persian; cells like `A1:H17` or `A1`; the tab name must be the sheet's real tab name (copy it from the tab itself; spaces, the half-space, ي/ك and letter case are matched loosely, a different name is not); when Google is reachable, saving is refused and the real tab names are listed; otherwise the fetch ends with the status «تنظیمات یا پاسخ سرویس قابل استفاده نیست» (invalid) instead of showing data |
| متن فایل کلید گوگل (JSON) | Required only when access is private, secret | Placeholder `<PASTE_KEY_HERE>` | | Whole service-account JSON, max 10000; not stored for public access |

Keep the form defaults unless the service documentation requires otherwise: GET, type «API بدون نیاز به ورود», and Bearer for fixed-key authentication. The app adds the word Bearer automatically and can detect the token location; specify that location only when needed.

**REPLY TEMPLATE** (the AI must end its answer with this block, then the NEXT STEPS below, in the author's language)

```
Form sheet
- نام برنامه: <value>
- توضیح کوتاه: <value or empty>
- دپارتمان: <department>
- فایل‌ها: index.html, <other files>
- مخاطبان: <value or empty>
- از چه تاریخی باز شود؟: <Jalali date or empty>
- تا چه تاریخی باز بماند؟: <Jalali date or empty>
- آخرین روز نگهداری برنامه: <Jalali date or empty>
- دریافت خودکار اطلاعات: <not needed | one option below>
  - نوع منبع داده: <option label>
  - نشانی دریافت اطلاعات (API): <https address>
  - روش دریافت اطلاعات: <GET | POST>
  - اطلاعات ارسالی (JSON): <value or empty>
  - (auth fields of the chosen option; secrets as <PASTE_KEY_HERE>)
```

**NEXT STEPS** (the AI prints these, numbered)

1. In your file picker select ALL app files together from one folder (no zip, no folder): `index.html` plus the others; at most 50 files, 10 MB each, 50 MB total, only the allowed types.
2. Open «افزودن برنامه» and fill the sheet above field by field.
3. Open «دریافت خودکار اطلاعات (اختیاری)» only if the app needs live data. For Google Sheets, first share the sheet with the key's `client_email` as Viewer.
4. Type every secret (key, token, login information, Google key) yourself directly in the form; never send them to the AI.
5. Press «ذخیره برنامه» and check that the program opens from the list. If you configured a data source, saving queues a fetch request. Check its status in the list a little later: «به‌روز» (ok), «ارتباط با سرویس برقرار نشد» (unreachable), «سرویس اجازهٔ دسترسی نداد» (denied), «تنظیمات یا پاسخ سرویس قابل استفاده نیست» (invalid); «در انتظار اولین دریافت» means no fetch result has been recorded yet.
6. If the status is not «به‌روز»: unreachable = check the address and try again later (the app makes up to 3 attempts for retryable errors, then waits for the nightly schedule described in section 2; manual source refresh once per 10 minutes); denied = key, token or login information is wrong or expired, or the Google Sheet is not shared as Viewer; invalid = address, JSON or ranges are wrong, or the reply has no content, is not JSON or is over 2 MB. The artifact's refresh button only rereads the existing file.
7. To replace the files or change the description or data source, create the program again with the SAME title in the same department and confirm replacement (requires management permission). Previous files are deleted; audience and dates stay unchanged, even if new values are selected. To keep the old source, leave «نوع منبع داده» on «بدون منبع داده». To change it, fill in the new connection completely. Replacement does not provide a way to remove a source while keeping the program as a file-based program.

---

## Part 2 - فارسی

# راهنمای ساخت برنامه برای پیشخوان برنامه‌ها

این راهنما را همراه با توضیح برنامه‌ای که می‌خواهید به هوش مصنوعی بدهید. راهنما می‌گوید برنامه باید چه فایل‌هایی داشته باشد، اطلاعات را از کجا بخواند و چه ظاهری داشته باشد تا در پیشخوان اجرا شود. محدودیت‌ها را از روی کد سامانه نوشته‌ایم؛ هوش مصنوعی نباید آن‌ها را تغییر دهد یا محدودیت تازه‌ای اضافه کند.

## متن پیشنهادی برای هوش مصنوعی

```
راهنمای پیوست را کامل بخوان و برنامهٔ موردنظر من را طبق آن بساز.
خروجی را به صورت فایل‌های جداگانه بده: index.html، styles.css، app.js و فایل‌های فونت. همه در یک پوشه باشند و زیرپوشه نداشته باشند.
همهٔ عنوان‌ها، دکمه‌ها و پیام‌هایی که می‌نویسی فارسی و ساده باشند.
برنامه نباید چیزی از اینترنت بگیرد؛ فایل‌های لازم باید کنار خود برنامه باشند.
اگر برنامه به اطلاعات بیرونی نیاز دارد، آن‌ها را فقط از data.json بخوان. هنگام دریافت، نمایش اطلاعات، خالی بودن اطلاعات و خطا، پیام و ظاهر مناسب نشان بده.
رنگ‌ها و اندازه‌ها را از راهنما بگیر. برنامه باید روی گوشی و رایانه درست دیده شود و فقط ظاهر روشن داشته باشد.
در پایان، همهٔ موارد فهرست بررسی را بررسی کن و نتیجه را بنویس. سپس راهنمای پر کردن فرم و گام‌های بعدی را بده.
```

## ۱. شرایط پذیرش فایل‌ها

پیش از تحویل فایل‌ها، همهٔ موارد زیر را رعایت کنید.

| قاعده | مقدار |
| :--- | :--- |
| محل فایل‌ها | همهٔ فایل‌ها کنار هم در یک پوشه باشند؛ زیرپوشه نسازید. هنگام بارگذاری، خود فایل‌ها را با هم انتخاب کنید، نه پوشه یا فایل zip را |
| صفحهٔ اصلی | وجود فایل `index.html` با همین نام و حروف کوچک الزامی است |
| حداکثر تعداد فایل | ۵۰ |
| حداکثر اندازهٔ هر فایل | ۱۰ مگابایت (۱۰۲۴۰ کیلوبایت) |
| حداکثر اندازهٔ همهٔ فایل‌ها با هم | ۵۰ مگابایت (۵۱۲۰۰ کیلوبایت) |
| حداکثر طول نام فایل | ۱۲۰ کاراکتر |
| نام تکراری | نامی که فقط در بزرگی و کوچکی حروف فرق دارد (مثل `a.html` و `A.HTML`) تکراری است |
| نام ممنوع | نام خالی، نامی که با نقطه شروع شود، یا نامی که `/` یا `\` یا `..` یا کاراکتر کنترلی، مثل خط جدید، داشته باشد |
| پسوندهای مجاز | `html` `htm` `css` `js` `mjs` `json` `txt` `svg` `png` `jpg` `jpeg` `gif` `webp` `avif` `ico` `woff` `woff2` `ttf` `otf` |
| ممنوع | هر نامی که یکی از این بخش‌ها را داشته باشد: `.php` `.phtml` `.phar` `.htaccess` `.user.ini` `.cgi` `.sh` `.asp` `.jsp` (مثلاً `x.php.txt` هم رد می‌شود) |
| محتوای PHP | اگر ۵۱۲ بایت اول هر فایل شامل `<?php` یا `<?=` باشد، فایل رد می‌شود (حتی با پسوند `txt`) |
| فایل اطلاعات | سامانه خودش `data.json` را می‌سازد. فقط برای آزمایش روی رایانه می‌توانید نمونهٔ آن را بسازید؛ پیش از بارگذاری، این نمونه را حذف کنید |

پیوندها و آدرس‌ها:

- آدرس فایل‌ها را فقط با نام همان فایل بنویسید: `styles.css` درست است؛ `/styles.css` و `./../x` درست نیستند. این روش «آدرس نسبی» نام دارد.
- چیزی از اینترنت نگیرید: فونت گوگل، تصویر سایت دیگر و فایل‌های آمادهٔ آنلاین یا CDN مجاز نیستند. اگر کد آماده‌ای لازم دارید، فایل آن را کنار فایل‌های برنامه بگذارید.
- هیچ نشانی `http://` یا `https://` در کد نباشد.

برنامه در یک قاب جدا از بقیهٔ سامانه اجرا می‌شود. این محیط محدودشده (`sandbox` فقط با `allow-scripts`) اجازهٔ انجام بعضی کارها را نمی‌دهد. هنگام نوشتن کد، این موارد را رعایت کنید:

| قابلیت | محدودیت و روش درست |
| :--- | :--- |
| `document.cookie` | کوکی ندارد و خطا می‌دهد |
| `localStorage` / `sessionStorage` بیرون از `try/catch` | در این محیط خطای امنیتی می‌دهد؛ اگر لازم است، فقط داخل `try/catch` و با فرض اینکه ممکن است کار نکند |
| `alert` `confirm` `prompt` | بسته است؛ پیام را داخل خود صفحه نشان بدهید |
| `eval` و `new Function` | اجازه ندارد |
| `window.open`، پنجرهٔ تازه و رفتن به سایت دیگر | باز کردن پنجرهٔ تازه بسته است. برنامه را به سایت دیگری هدایت نکنید؛ این محیط جلوی تغییر نشانی خود قاب را نمی‌گیرد |
| ارسال فرم (`form` با `submit`) | بسته است؛ با دکمهٔ معمولی و جاوااسکریپت کار کنید |
| `fetch` به جز فایل‌های خود برنامه | فقط نشانی نسبی همین برنامه مجاز است |
| `<audio>` `<video>` `<iframe>` `<object>` `<embed>` | مجاز نیست |
| دانلود فایل، چاپ و `service worker` (کد پس‌زمینه برای کارهایی مثل اجرای آفلاین) | پشتیبانی نمی‌شود |

مجاز است: اسکریپت و استایل داخل خود `index.html` و فایل‌های هم‌سطح، تصویر از فایل‌های خود برنامه یا `data:` و `blob:`، فونت از فایل‌های خود برنامه یا `data:`، و `fetch` فایل‌های هم‌سطح.

برای آزمایش، دو حالت را از هم جدا کنید: در سامانه، برنامه را از داخل پیشخوان باز کنید؛ نشانی فایل بارگذاری‌شده در یک برگهٔ جدا باز نمی‌شود. روی رایانهٔ خودتان، از یک سرور محلی ساده، مثل `python -m http.server`، استفاده کنید و یک `data.json` نمونه کنار فایل‌ها بگذارید. باز کردن مستقیم فایل با دوبار کلیک برای آزمایش دریافت اطلاعات کافی نیست.

## ۲. اطلاعات برنامه: فایل data.json

اگر برنامه به اطلاعات یک سرویس بیرونی نیاز دارد، سامانه آن‌ها را می‌گیرد و در `data.json` می‌گذارد. خود برنامه فقط همین فایل را می‌خواند و مستقیم به سرویس وصل نمی‌شود.

برنامه‌ریزی دریافت شبانه ساعت ۰۵:۰۱ به وقت تهران شروع می‌شود. دریافت‌ها با فاصلهٔ یک دقیقه، از ۰۵:۰۲ تا ۰۵:۴۴، برای حداکثر ۴۳ برنامه انجام می‌شوند؛ برنامه‌های بیشتر تا شب بعد منتظر می‌مانند. فقط برنامه‌های در دسترس (فعال و در بازهٔ تاریخ دسترسی) در دریافت شبانه شرکت می‌کنند. با ساخت، جایگزینی یا فعال‌سازی برنامه‌ای که منبع اطلاعات دارد هم درخواست دریافت در صف قرار می‌گیرد.

نیازی نیست صفحه مدام فایل را بخواند؛ دکمهٔ «به‌روزرسانی» برای دوباره خواندن آن کافی است. این دکمه فقط فایل موجود را می‌خواند و اطلاعات تازه از سرویس نمی‌گیرد. برای دریافت تازه از سرویس، فردی که اجازهٔ مدیریت برنامه را دارد از دکمهٔ دریافت اطلاعات در فهرست پیشخوان استفاده می‌کند.

```js
fetch('data.json', {cache: 'no-store'})
```

دو شکل ممکن فایل:

```json
{"updated_at": "2026-11-17T05:02:11+03:30", "ok": true, "data": {}}
```

```json
{"updated_at": "2026-11-17T05:02:11+03:30", "ok": false, "data": null}
```

| وضعیت | معنی | کار صفحه |
| :--- | :--- | :--- |
| پاسخ ۴۰۴ | فایل در دسترس نیست؛ ممکن است اولین دریافت هنوز انجام نشده باشد | حالت «خالی»؛ وعده ندهید که حتماً با دریافت بعدی مشکل حل می‌شود |
| `ok: true` | دریافت موفق بود؛ `data` همان پاسخ سرویس است | نمایش عادی؛ اگر داده نبود حالت «خالی» |
| `ok: false` | دریافت ناموفق بود و `data` برابر `null` است | حالت «ناموفق» با زمان آخرین به‌روزرسانی |

- `updated_at` زمان آخرین دریافت، با قالب استاندارد ISO است. هنگام نمایش، آن را به تاریخ شمسی تبدیل کنید.
- چهار وضعیت دریافت (`ok` `unreachable` `denied` `invalid`) در پیشخوان به کسانی نشان داده می‌شوند که اجازهٔ مدیریت برنامه را دارند. برنامه برای تشخیص موفقیت دریافت، فقط مقدار `ok` داخل فایل را بررسی کند.
- اگر منبع گوگل‌شیت باشد: `data.valueRanges[i].values` آرایه‌ای از ردیف‌هاست و برای محدودهٔ خالی ممکن است `values` اصلاً وجود نداشته باشد؛ همیشه `?? []` بگذارید. مقدارها همان متنی‌اند که در شیت دیده می‌شود.
- اگر منبع API دیگر باشد: `data` دقیقاً همان JSON است که سرویس برگردانده (حداکثر ۲ مگابایت).
- از سازندهٔ برنامه یا راهنمای سرویس بپرسید چه اطلاعاتی داخل `data` می‌آید. نام و شکل اطلاعات را حدس نزنید؛ کد را طوری بنویسید که نبودن یک فیلد باعث خراب شدن صفحه نشود.

حالت‌های لازم در رابط:

| حالت | چه چیزی دیده شود |
| :--- | :--- |
| در حال بارگذاری | نوارهای خاکستری با تغییر آرام روشنایی، به‌جای محتوایی که هنوز آماده نیست (skeleton) |
| عادی | عنوان، زمان آخرین به‌روزرسانی، دکمهٔ «به‌روزرسانی» و محتوا |
| خالی | آیکن، «اطلاعاتی برای نمایش نیست» و توضیح کوتاه |
| ناموفق | پیام خطا با رنگ خطا، زمان آخرین به‌روزرسانی و دکمهٔ «تلاش دوباره» |

## ۳. تنظیم دریافت اطلاعات در فرم پیشخوان

اگر برنامه به اطلاعات بیرونی نیاز دارد، سازنده هنگام افزودن برنامه بخش «دریافت خودکار اطلاعات» را پر می‌کند. برای برنامه‌ای که اطلاعات بیرونی نمی‌خواهد، این بخش را خالی بگذارید؛ در این حالت سامانه فایل `data.json` نمی‌سازد.

نشانی سرویس را از راهنمای همان سرویس بگیرید. نشانی باید عمومی باشد، با `https://` شروع شود و از درگاه استاندارد آن، یعنی ۴۴۳، استفاده کند. نشانی عددی IP و نشانی شبکهٔ داخلی پذیرفته نمی‌شود.

معنی چند واژه در فرم: API راه ارتباط برنامه‌ها برای گرفتن اطلاعات است. JSON قالب نوشتن اطلاعات است؛ نام فیلدها و علامت‌های آن را باید دقیقاً مطابق راهنمای سرویس وارد کنید. توکن یا کلید دسترسی، کدی است که اجازهٔ دریافت اطلاعات می‌دهد. Header بخشی از درخواست است که این کد در آن فرستاده می‌شود؛ نام و نوع آن را حدس نزنید و از راهنمای سرویس بگیرید.

اولین فیلد «نوع منبع داده» است و بقیهٔ فیلدها بر اساس آن نمایش داده می‌شوند.

| نوع | فیلدها | الزام |
| :--- | :--- | :--- |
| بدون منبع داده (پیش‌فرض) | ندارد | چیزی بررسی یا ذخیره نمی‌شود؛ هنگام جایگزینی، اتصال قبلی حفظ می‌شود |
| Google Sheets | نحوه دسترسی (عمومی یا خصوصی)، شناسهٔ فایل، سلول‌های موردنیاز، متن فایل کلید گوگل (JSON) | نحوه دسترسی، شناسه و سلول‌ها لازم؛ کلید فقط وقتی دسترسی خصوصی باشد؛ نشانی و روش و اطلاعات ارسالی نمایش داده نمی‌شود |
| API بدون نیاز به ورود | نشانی دریافت اطلاعات، روش (GET یا POST) | نشانی و روش لازم؛ اطلاعات ارسالی (JSON) فقط برای POST و اختیاری |
| API با کلید یا توکن | نشانی، روش، نوع کلید (Bearer یا X-API-Key یا نام دلخواه)، نام کلید (فقط نوع دلخواه)، مقدار کلید | مقدار لازم؛ نام فقط برای نوع دلخواه لازم؛ مقدار پس از ذخیره دیده نمی‌شود |
| API با نام کاربری و رمز عبور | نشانی، روش، نشانی ورود، اطلاعات ورود (JSON)، محل کد دسترسی در پاسخ | نشانی ورود و اطلاعات ورود لازم؛ محل کد اختیاری (خالی = یافتن خودکار از `access_token` `token` `data.access_token` `data.token` `jwt` `id_token`) |

نکته‌های Google Sheets: در «سلول‌های موردنیاز» هر محدوده یک خط است، حداکثر ۱۰ خط و هر خط ۱۰۰ کاراکتر؛ مثل `'داشبورد پروژه'!A1:H17`. دسترسی عمومی کلید نمی‌خواهد و فقط وقتی کار می‌کند که فایل با «هر کسی که لینک را دارد» و نقش «بیننده» به اشتراک گذاشته شده باشد. دسترسی خصوصی کلید حساب سرویس می‌خواهد؛ فایل شیت باید با ایمیل `client_email` داخل کلید و با دسترسی «بیننده» به اشتراک گذاشته شود.

در هر نوبت دریافت، اگر مشکل از شبکه، محدودیت درخواست سرویس (۴۲۹) یا خطای سرور سرویس (۵xx) باشد، سامانه حداکثر سه بار تلاش می‌کند. هر درخواست حداکثر ۱۰ ثانیه زمان دارد. اگر دریافت ناموفق بماند، سامانه باز هم `data.json` را می‌نویسد، با `ok:false` و بدون اطلاعات قبلی. کسانی که اجازهٔ مدیریت برنامه را دارند می‌توانند از پیشخوان هم دریافت تازه بخواهند؛ حداکثر یک بار در هر ۱۰ دقیقه.

## ۴. ظاهر برنامه: رنگ، اندازه و چیدمان

رنگ‌ها را با نام‌های تعریف‌شده در بلوک زیر به کار ببرید؛ کد مستقیم رنگ، مثل `#ffffff`، فقط در همین بلوک باشد. فقط ظاهر روشن بسازید؛ حالت تیره و بلوک `prefers-color-scheme` اضافه نکنید. در بخش `head` هم بنویسید `&lt;meta name="color-scheme" content="light"&gt;` (با علامت‌های کوچک‌تر و بزرگ‌تر معمولی). رنگ‌های زیر از ظاهر پیش‌فرض سامانه گرفته شده‌اند.

```css
:root {
  --md-sys-color-primary: #4e5f66;
  --md-sys-color-on-primary: #ffffff;
  --md-sys-color-primary-container: #d1e7ef;
  --md-sys-color-on-primary-container: #0a1e24;
  --md-sys-color-secondary-container: #d7e3f7;
  --md-sys-color-on-secondary-container: #101c2b;
  --md-sys-color-error: #ba1a1a;
  --md-sys-color-on-error: #ffffff;
  --md-sys-color-error-container: #ffdad6;
  --md-sys-color-on-error-container: #410002;
  --md-sys-color-success: #10b981;
  --md-sys-color-success-container: #d1fae5;
  --md-sys-color-on-success-container: #064e3b;
  --md-sys-color-warning: #f59e0b;
  --md-sys-color-warning-container: #fef3c7;
  --md-sys-color-on-warning-container: #422006;
  --md-sys-color-surface: #f9f9fc;
  --md-sys-color-on-surface: #1a1c1e;
  --md-sys-color-surface-variant: #e1e2ec;
  --md-sys-color-on-surface-variant: #44474f;
  --md-sys-color-surface-container-highest: #d4d5dc;
  --md-sys-color-surface-container-low: color-mix(in srgb, var(--md-sys-color-surface), var(--md-sys-color-surface-variant) 25%);
  --md-sys-color-surface-container: color-mix(in srgb, var(--md-sys-color-surface), var(--md-sys-color-surface-variant) 50%);
  --md-sys-color-surface-container-high: color-mix(in srgb, var(--md-sys-color-surface), var(--md-sys-color-surface-variant) 75%);
  --md-sys-color-outline: #74777f;
  --md-sys-color-outline-variant: #c4c6cf;
  --md-sys-elevation-1: 0 1px 2px rgba(0, 0, 0, 0.06), 0 1px 3px rgba(0, 0, 0, 0.1);
  --md-sys-motion-fast: 200ms cubic-bezier(0.2, 0, 0, 1);
}
```

رنگ متن و پس‌زمینه باید با هم خوانا باشند: روی پس‌زمینهٔ `primary`، متن را با `on-primary` بنویسید. پس‌زمینهٔ صفحه `surface` و خط دور کارت `outline-variant` باشد. برای پیام موفقیت، هشدار و خطا، رنگ پس‌زمینهٔ `container` و رنگ متن `on-container` همان وضعیت را با هم به کار ببرید. رنگ تازه اضافه نکنید.

### فونت و آیکن

- فونت Vazirmatn از فایل محلی و با `@font-face` نسبی بارگذاری شود؛ از `fonts.googleapis.com` استفاده نکنید.
- در سامانه فقط دو وزن موجود است: ۴۰۰ (Regular) و ۷۰۰ (Bold). وزن ۵۰۰ وجود ندارد؛ برای تأکید از ۷۰۰ استفاده کنید.
- این دو فایل فونت را از پوشهٔ `resources/assets/fonts/vazirmatn/` پروژه بردارید و کنار `index.html` بگذارید:
  - `Vazirmatn-Regular.woff2`
  - `Vazirmatn-Bold.woff2`
- آیکن‌ها فقط SVG داخل خود صفحه (inline) باشند؛ نه ایموجی و نه کتابخانهٔ آیکن.

### اندازهٔ نوشته‌ها و فاصله‌ها

| نقش | اندازه |
| :--- | :--- |
| عنوان بخش | `1rem` (۱۶px) و وزن ۷۰۰ |
| عنوان کارت | `0.875rem`–`1rem` و وزن ۷۰۰ |
| متن، سلول جدول | `0.875rem` (۱۴px) |
| توضیح و سرستون | `0.75rem` (۱۲px) |
| ریزمتن (نشان، زمان) | `0.6875rem` (۱۱px) |
| برچسب دکمه | `0.925rem` و وزن ۷۰۰ |
| عدد درشت (stat) | `1.5rem`–`2.25rem` و وزن ۷۰۰ |

اندازه‌ها را با `rem` بنویسید تا با تغییر اندازهٔ نوشته در مرورگر، صفحه هم متناسب تغییر کند. فاصله‌ها مضرب ۴px باشند: ۴، ۸، ۱۲، ۱۶، ۲۰، ۲۴. برای گردی گوشه‌ها این اندازه‌ها را به کار ببرید: کارت و جدول ۱۶px، دکمه ۱۲px، نشان وضعیت و نوار بارگذاری ۸px، کارت بارگذاری ۱۶px. عرض صفحه از `88rem` بیشتر نشود. سرستون ثابت جدول `z-index: 10` داشته باشد تا هنگام پیمایش روی ردیف‌ها دیده شود.

### مشخصات اجزا

در جدول زیر، «پدینگ» فاصلهٔ محتوا از لبهٔ داخلی است. «حالت شناور» یعنی وقتی نشانگر ماوس روی آن قسمت قرار دارد. «سرستون چسبان» هنگام بالا و پایین رفتن جدول، بالای آن باقی می‌ماند. نشان وضعیت هم برچسب کوچکی مثل «موفق» یا «ناموفق» است.

| جزء | مشخصات |
| :--- | :--- |
| قاب صفحه | `dir="rtl"`، `lang="fa"`، پس‌زمینهٔ `surface`، لبه‌ها `1rem` و از ۷۶۸px به بالا `1.25rem`، فاصلهٔ بخش‌ها ۱۶px و از ۷۶۸px ۲۰px |
| کارت | پس‌زمینهٔ `surface`، مرز ۱px `outline-variant`، شعاع ۱۶px، سایهٔ `--md-sys-elevation-1`، پدینگ ۱۶px (از ۷۶۸px: ۲۰px) |
| دکمهٔ اصلی (filled) | ارتفاع ۴۴px، پدینگ افقی ۲۴px، شعاع ۱۲px، پس‌زمینهٔ `primary`، متن `on-primary`، با فشردن کمی کوچک شود |
| دکمهٔ ملایم (tonal) | ارتفاع ۴۰px، پدینگ افقی ۱۶px، شعاع ۱۲px، پس‌زمینهٔ `primary-container`، متن `on-surface-variant`، در حالت شناور `surface-container-highest` |
| جدول فشرده | داخل کارت با `overflow-x: auto`؛ سرستون `surface-container-high` و چسبان، متن ۱۲px؛ سلول‌ها پدینگ `0.5rem 0.75rem`، متن ۱۴px؛ خط جداکنندهٔ هر ردیف `outline-variant`؛ ردیف شناور کمی رنگ `primary` |
| نشان (badge) | شعاع ۸px، پدینگ `0.125rem 0.5rem`، متن ۱۱px و وزن ۷۰۰؛ رنگ فقط از جفت‌های container |
| حالت خالی | ستون وسط‌چین، ارتفاع حداقل ۱۶rem، شفافیت ۶۰٪ با رنگ `on-surface-variant`؛ آیکن ۴۰px در جعبهٔ شعاع ۱۶px با رنگ `surface-container-high`؛ عنوان ۱۶px؛ توضیح ۱۲px |
| اسکلت بارگذاری | میله‌هایی با `surface-variant`، شعاع ۸px، ارتفاع ۱۶px و تپش دو ثانیه‌ای |
| عنوان بخش | جعبهٔ آیکن ۳۲px با `primary-container`، عنوان ۱۶px با وزن ۷۰۰ و خط نازک `outline-variant` تا انتها |
| کاشی آمار (stat) | کارت با عدد درشت وزن ۷۰۰ و زیر آن عنوان ۱۲px با `on-surface-variant`؛ عددها `tabular-nums` |

### گوشی و رایانه

- ابتدا صفحه را برای گوشی طراحی کنید. در عرض ۴۰۰px نباید کل صفحه به چپ و راست کشیده شود؛ فقط جدول می‌تواند داخل کارت خودش پیمایش افقی داشته باشد.
- از عرض ۷۶۸px به بالا، چیدمان رایانه را اعمال کنید (`@media (min-width: 768px)`). برای تغییر چیدمان، مرز عرض دیگری اضافه نکنید.
- ناحیهٔ قابل لمس هر دکمه یا ابزار دست‌کم ۳۲px باشد؛ ارتفاع دکمهٔ اصلی ۴۴px باشد.
- متن فیلدها دست‌کم `1rem` تا مرورگر iOS بزرگ‌نمایی نکند. ارتفاع کامل با `dvh`، نه `vh`.
- راست‌به‌چپ: اولین فرزند سمت راست است؛ متن جدول `text-align: right`؛ عدد و کد و واحد را در `dir="ltr"` بگذارید؛ آیکن‌ها وارونه نشوند و فلش «بازگشت» به راست اشاره کند.
- هنگام جابه‌جایی با صفحه‌کلید، دور ابزار انتخاب‌شده خط واضح نشان دهید: `outline: 2px solid var(--md-sys-color-primary); outline-offset: 2px`. اگر کاربر کاهش حرکت را انتخاب کرده است (`prefers-reduced-motion: reduce`)، همهٔ پویانمایی‌ها خاموش شوند. دکمه‌ای که فقط آیکن دارد، یک `aria-label` فارسی هم داشته باشد تا صفحه‌خوان کاربرد آن را بگوید.

### عدد و تاریخ

```js
const num = new Intl.NumberFormat('fa-IR');
const jalali = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {timeZone: 'Asia/Tehran', dateStyle: 'medium', timeStyle: 'short'});
```

تاریخ میلادی هرگز روی صفحه دیده نشود؛ هر تاریخ فقط با `jalali.format(new Date(...))` و هر عدد با `num.format(...)`.

## ۵. شروع سریع

سه فایل زیر را کنار دو فایل فونت بگذارید. این نمونه اطلاعات `data.json` را می‌خواند و حالت بارگذاری، نمایش، خالی و خطا را نشان می‌دهد. بخش نمایش اطلاعات را با ساختار واقعی `data` برنامهٔ خودتان هماهنگ کنید؛ نمونه به‌تنهایی برای همهٔ سرویس‌ها آماده نیست.

`index.html`

```html
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>گزارش</title>
<link rel="stylesheet" href="styles.css">
<script src="app.js" defer></script>
</head>
<body>
<main class="shell">
  <header class="head">
    <h1 class="title">گزارش</h1>
    <span class="meta" id="stamp"></span>
    <button class="btn" id="reload" type="button">به‌روزرسانی</button>
  </header>
  <section class="card" id="view" aria-live="polite"></section>
</main>
</body>
</html>
```

`styles.css` (بلوک `:root` بخش ۴ را بالای همین فایل بگذارید)

```css
@font-face{font-family:Vazirmatn;font-weight:400;font-display:swap;src:url(Vazirmatn-Regular.woff2) format('woff2')}
@font-face{font-family:Vazirmatn;font-weight:700;font-display:swap;src:url(Vazirmatn-Bold.woff2) format('woff2')}
*{box-sizing:border-box}
html{font-size:100%}
body{margin:0;font-family:Vazirmatn,system-ui,sans-serif;font-size:.875rem;line-height:1.5;background:var(--md-sys-color-surface);color:var(--md-sys-color-on-surface)}
.shell{max-width:88rem;margin:0 auto;padding:1rem;display:grid;gap:1rem}
.head{display:flex;flex-wrap:wrap;align-items:center;gap:.75rem}
.title{margin:0;font-size:1rem;font-weight:700;flex:1}
.meta{font-size:.6875rem;color:var(--md-sys-color-on-surface-variant)}
.card{background:var(--md-sys-color-surface);border:1px solid var(--md-sys-color-outline-variant);border-radius:1rem;box-shadow:var(--md-sys-elevation-1);padding:1rem;overflow:hidden}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;height:2.5rem;padding:0 1rem;border:0;border-radius:.75rem;background:var(--md-sys-color-primary-container);color:var(--md-sys-color-on-surface-variant);font:inherit;font-size:.925rem;font-weight:700;cursor:pointer;transition:background var(--md-sys-motion-fast),transform var(--md-sys-motion-fast)}
.btn:hover{background:var(--md-sys-color-surface-container-highest)}
.btn:active{transform:scale(.95)}
.btn-fill{height:2.75rem;padding:0 1.5rem;background:var(--md-sys-color-primary);color:var(--md-sys-color-on-primary)}
.btn-fill:hover{background:var(--md-sys-color-primary)}
:focus-visible{outline:2px solid var(--md-sys-color-primary);outline-offset:2px;border-radius:.5rem}
.scroll{overflow-x:auto}
table{width:100%;border-collapse:separate;border-spacing:0;text-align:right}
th{position:sticky;top:0;z-index:10;background:var(--md-sys-color-surface-container-high);color:var(--md-sys-color-on-surface-variant);font-size:.75rem;padding:.5rem .75rem;white-space:nowrap}
td{padding:.5rem .75rem;border-top:1px solid var(--md-sys-color-outline-variant);white-space:nowrap}
tbody tr:hover{background:color-mix(in srgb,var(--md-sys-color-primary) 4%,transparent)}
.state{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.5rem;min-height:16rem;text-align:center}
.state h2{margin:0;font-size:1rem;font-weight:700}
.state p{margin:0;font-size:.75rem;color:var(--md-sys-color-on-surface-variant)}
.state.empty{opacity:.6}
.icon{display:grid;place-items:center;width:4rem;height:4rem;border-radius:1rem;background:var(--md-sys-color-surface-container-high)}
.icon svg{width:2.5rem;height:2.5rem;fill:currentColor}
.state.fail .icon{background:var(--md-sys-color-error-container);color:var(--md-sys-color-on-error-container)}
.badge{display:inline-flex;padding:.125rem .5rem;border-radius:.5rem;font-size:.6875rem;font-weight:700;background:var(--md-sys-color-success-container);color:var(--md-sys-color-on-success-container)}
.stat{font-size:1.5rem;font-weight:700;font-variant-numeric:tabular-nums}
.bar{height:1rem;margin-bottom:.75rem;border-radius:.5rem;background:var(--md-sys-color-surface-variant);animation:pulse 2s cubic-bezier(.4,0,.6,1) infinite}
.bar.short{width:40%}
@keyframes pulse{50%{opacity:.5}}
@media (min-width:768px){.shell{padding:1.25rem;gap:1.25rem}.card{padding:1.25rem}}
@media (prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important}}
```

`app.js`

```js
const num = new Intl.NumberFormat('fa-IR');
const jalali = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {timeZone: 'Asia/Tehran', dateStyle: 'medium', timeStyle: 'short'});
const view = document.getElementById('view');
const stamp = document.getElementById('stamp');
const svg = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v4H4zm0 6h16v10H4z"/></svg>';

const el = (tag, cls, text) => {
  const node = document.createElement(tag);
  if (cls) node.className = cls;
  if (text !== undefined) node.textContent = text;
  return node;
};

const state = (kind, title, hint, retry) => {
  const box = el('div', 'state ' + kind);
  const icon = el('div', 'icon');
  icon.innerHTML = svg;
  box.append(icon, el('h2', '', title), el('p', '', hint));
  if (retry) {
    const button = el('button', 'btn btn-fill', 'تلاش دوباره');
    button.type = 'button';
    button.addEventListener('click', load);
    box.append(button);
  }
  view.replaceChildren(box);
};

const skeleton = () => {
  const box = el('div');
  box.append(el('div', 'bar short'), el('div', 'bar'), el('div', 'bar'), el('div', 'bar'));
  view.replaceChildren(box);
};

const rowsOf = (data) => {
  if (data && Array.isArray(data.valueRanges)) return data.valueRanges[0]?.values ?? [];
  if (Array.isArray(data) && data.length && typeof data[0] === 'object') {
    const keys = Object.keys(data[0]);
    return [keys, ...data.map((item) => keys.map((key) => item[key] ?? ''))];
  }
  return [];
};

const table = (rows) => {
  const wrap = el('div', 'scroll');
  const grid = el('table');
  const head = el('tr');
  rows[0].forEach((cell) => head.append(el('th', '', String(cell))));
  const body = el('tbody');
  rows.slice(1).forEach((row) => {
    const line = el('tr');
    rows[0].forEach((_, index) => line.append(el('td', '', String(row[index] ?? ''))));
    body.append(line);
  });
  grid.append(el('thead'), body);
  grid.firstChild.append(head);
  wrap.append(grid);
  const top = el('div');
  top.append(el('div', 'stat', num.format(rows.length - 1)), el('p', 'meta', 'تعداد ردیف‌ها'));
  view.replaceChildren(top, wrap);
};

async function load() {
  skeleton();
  stamp.textContent = '';
  try {
    const res = await fetch('data.json', {cache: 'no-store'});
    if (res.status === 404) return state('empty', 'اطلاعاتی برای نمایش نیست', 'فایل اطلاعات در دسترس نیست. اگر مشکل ادامه داشت، به سازندهٔ برنامه اطلاع دهید.');
    if (!res.ok) throw new Error('http');
    const payload = await res.json();
    const when = payload.updated_at ? jalali.format(new Date(payload.updated_at)) : '';
    stamp.textContent = when ? 'آخرین به‌روزرسانی: ' + when : '';
    if (!payload.ok) return state('fail', 'دریافت اطلاعات انجام نشد', 'دریافت از سرویس ناموفق بود. به سازندهٔ برنامه اطلاع دهید؛ «تلاش دوباره» فقط فایل موجود را دوباره می‌خواند.', true);
    const rows = rowsOf(payload.data);
    if (rows.length < 2) return state('empty', 'اطلاعاتی برای نمایش نیست', 'در این گزارش هنوز ردیفی ثبت نشده است.');
    table(rows);
  } catch (error) {
    state('fail', 'اطلاعات نمایش داده نشد', 'دوباره تلاش کنید. اگر مشکل ادامه داشت، برنامه را از پیشخوان باز کنید یا به سازندهٔ آن اطلاع دهید.', true);
  }
}

document.getElementById('reload').addEventListener('click', load);
load();
```

برای آزمایش محلی، کنار فایل‌ها یک `data.json` نمونه بگذارید و پیش از بارگذاری در پیشخوان آن را حذف کنید (سامانه خودش می‌سازد):

```json
{"updated_at":"2026-11-17T05:02:11+03:30","ok":true,"data":{"valueRanges":[{"values":[["نام","مقدار"],["الف","۱۲"],["ب","۳۰"]]}]}}
```

## ۶. متن‌هایی که کاربر می‌بیند

- هر کلمهٔ قابل‌دیدن فارسی باشد؛ کلمهٔ انگلیسی یا مخفف لاتین (مثل OK، API، N/A) در صفحه ننویسید. نام فنی Header فقط در این راهنما لاتین است و در رابط کاربر نمی‌آید.
- به‌جای واژهٔ «نویسه» از «کاراکتر» استفاده کنید.
- جمله‌ها کوتاه و ساده باشند. پیام خطا هم مشکل را بگوید و هم قدم بعدی را؛ توصیه باید با کاری که دکمه واقعاً انجام می‌دهد هماهنگ باشد.
- دکمه‌ها فعل ساده باشند: «به‌روزرسانی»، «تلاش دوباره»، «بستن».
- اعداد با رقم فارسی (`Intl.NumberFormat`) و تاریخ شمسی؛ هرگز تاریخ یا ماه میلادی.
- متن‌های جایگزین تصویر و `aria-label` هم فارسی باشند.

## ۷. فهرست بررسی نهایی

پیش از تحویل، هر مورد را بررسی و نتیجه را بنویسید.

- [ ] فقط فایل‌های هم‌سطح، `index.html` موجود، حداکثر ۵۰ فایل، هر فایل زیر ۱۰ مگابایت و مجموع زیر ۵۰ مگابایت.
- [ ] همهٔ پسوندها مجازند؛ هیچ نامی بخش ممنوع (`.php`، `.sh` و مانند آن) ندارد و هیچ نامی با نقطه شروع نمی‌شود.
- [ ] هیچ نشانی `http://` یا `https://` و هیچ پیوند با اسلش اول در کد نیست.
- [ ] هیچ استفاده‌ای از cookie، `alert`، `confirm`، `prompt`، `eval`، `window.open` و ارسال فرم نیست.
- [ ] هر استفاده از `localStorage` یا `sessionStorage` داخل `try/catch` است (یا اصلاً نیست).
- [ ] با وجود `data.json`، نبودن آن (۴۰۴)، `ok:false` و محدودهٔ خالی، هر چهار حالت درست نشان داده می‌شود.
- [ ] در عرض ۴۰۰px اسکرول افقی صفحه نیست و در ۷۶۸px و بیشتر چیدمان رایانه درست است.
- [ ] فقط حالت روشن (بدون حالت تیره و بدون `prefers-color-scheme`) و رنگ مستقیم (hex) بیرون از بلوک `:root` نیست.
- [ ] هیچ کلمهٔ انگلیسی، مخفف لاتین یا تاریخ میلادی روی صفحه دیده نمی‌شود.
- [ ] فونت از فایل‌های خود برنامه خوانده می‌شود و هر دو فایل فونت کنار `index.html` هستند.
- [ ] حلقهٔ فوکوس دیده می‌شود و با `prefers-reduced-motion` انیمیشن‌ها خاموش می‌شوند.
- [ ] کنسول مرورگر هیچ خطایی ندارد.

## ۸. راهنمای پر کردن فرم (در پایان پاسخ هوش مصنوعی)

فرم فقط فیلدهای مربوط به «نوع منبع داده» انتخاب‌شده را نمایش می‌دهد. ترتیب و عنوان فیلدها باید دقیقاً مانند برگه باشد. اگر فرم با این راهنما تفاوت داشت (آخرین همگام‌سازی: ۲۶ آبان ۱۴۰۵)، از فرم پیروی کنید و موضوع را به برنامه‌نویس اطلاع دهید.

کلید، رمز عبور، توکن و فایل کلید گوگل محرمانه‌اند. آن‌ها را در گفتگو با هوش مصنوعی نفرستید. هوش مصنوعی نباید مقدار واقعی آن‌ها را بخواهد، بسازد یا تکرار کند؛ فقط جای آن‌ها متنی مثل `<PASTE_KEY_HERE>` بنویسد. مقدار واقعی را خودتان مستقیم در فرم پیشخوان وارد کنید. سامانه آن را رمزگذاری‌شده ذخیره می‌کند و پس از ذخیره دوباره نشان نمی‌دهد.

جدول‌های زیر می‌گویند هر قسمت فرم را چطور پر کنید. این راهنما برای ساخت برنامه با فایل است؛ همان گزینه‌ای که ابتدا در فرم انتخاب شده است. گزینهٔ «ساخت میان‌بر به سامانهٔ دیگر» برای باز کردن یک سامانهٔ موجود است، فایل نمی‌خواهد و در این راهنما توضیح داده نمی‌شود.

**فیلدهای اصلی**

| عنوان فیلد | الزام | چه بنویسید | نمونه | قاعده |
| :--- | :--- | :--- | :--- | :--- |
| نام برنامه | الزامی | نام کوتاه و روشن | `گزارش پروژه‌ها` | ۳ تا ۱۲۰ کاراکتر؛ در هر دپارتمان تکراری نباشد. استفاده از نام قبلی برای جایگزینی، اجازهٔ مدیریت آن برنامه را می‌خواهد |
| توضیح کوتاه (اختیاری) | اختیاری | یک جمله | `آمار هفتگی پروژه‌ها` | حداکثر ۵۰۰ کاراکتر |
| دپارتمان | الزامی | دپارتمان صاحب برنامه | از فهرست | حداکثر ۳ برنامه در هر دپارتمان |
| انتخاب فایل‌ها | الزامی | همهٔ فایل‌ها با هم | `index.html`، `styles.css`، `app.js` و دو فونت | هم‌سطح؛ `index.html` حتماً باشد؛ حداکثر ۵۰ فایل، هر کدام ۱۰ مگابایت، مجموع ۵۰ مگابایت؛ نوع‌های مجاز طبق بخش ۱ |
| مخاطبان | اختیاری | افرادی که می‌توانند ببینند | از فهرست | حداکثر ۲۰۰ نفر؛ خالی = فقط مالک و مدیران؛ بعداً از «جزئیات» و «ویرایش» قابل تغییر است |
| از چه تاریخی باز شود؟ (اختیاری) | اختیاری | تاریخ شروع شمسی | `۱۴۰۵/۰۹/۰۱` | بعداً از «جزئیات» و «ویرایش» قابل تغییر است |
| تا چه تاریخی باز بماند؟ (اختیاری) | اختیاری | پایان دسترسی شمسی | `۱۴۰۵/۱۲/۲۹` | امروز یا بعد از آن؛ نه قبل از تاریخ شروع |
| آخرین روز نگهداری برنامه (اختیاری) | اختیاری | آخرین روز نگهداری شمسی | `۱۴۰۶/۰۱/۳۱` | امروز یا بعد از آن؛ نه قبل از تاریخ شروع |

**دریافت اطلاعات از سرویس دیگر** (بخش بازشوندهٔ «دریافت خودکار اطلاعات (اختیاری)»؛ فقط اگر برنامه به اطلاعات بیرونی نیاز دارد)

| عنوان فیلد | الزام | چه بنویسید | نمونه | قاعده |
| :--- | :--- | :--- | :--- | :--- |
| نشانی دریافت اطلاعات (API) | در صورت استفاده الزامی | نشانی سرویس | `https://api.example.com/v1/report` | فقط `https://` و درگاه ۴۴۳، بدون IP و بدون نام کاربری یا رمز در نشانی، حداکثر ۵۰۰ کاراکتر، یک خط و بدون فاصله؛ فقط برای سه نوع API نمایش داده می‌شود |
| روش دریافت اطلاعات | الزامی | GET (پیش‌فرض، خودکار پر می‌شود) یا POST | `GET` | فقط GET یا POST |
| اطلاعات ارسالی (JSON) | اختیاری | متنی که طبق راهنمای سرویس باید همراه درخواست POST فرستاده شود | `{"from":"2026-01-01"}` | فقط برای POST؛ قالب JSON درست؛ حداکثر ۲۰۰۰ کاراکتر |
| نوع منبع داده | الزامی، فیلد اول | یکی از پنج نوع زیر (پیش‌فرض: بدون منبع داده) | | سه فیلد بالا فقط برای سه نوع API نمایش داده می‌شوند |

نوع «بدون منبع داده»: چیز دیگری نمایش داده، بررسی یا ذخیره نمی‌شود.

نوع «API بدون نیاز به ورود»: فیلد اضافه ندارد.

نوع «API با کلید یا توکن»:

| عنوان فیلد | الزام | چه بنویسید | نمونه | قاعده |
| :--- | :--- | :--- | :--- | :--- |
| نوع کلید دسترسی | الزامی | `Bearer` (پیش‌فرض)، `X-API-Key` یا نام دیگر (Header) | | یکی از سه گزینه |
| نام کلید در درخواست (Header) | فقط برای نام دیگر الزامی | نام Header از راهنمای سرویس | `X-Custom-Key` | حروف انگلیسی، عدد و خط تیره؛ حداکثر ۶۴؛ نه Host، Content-Length، Transfer-Encoding، Connection |
| مقدار کلید یا توکن | الزامی، محرمانه | هوش مصنوعی فقط `<PASTE_KEY_HERE>` بنویسد؛ مقدار واقعی را خودتان در فرم وارد کنید | | یک خط، حداکثر ۲۰۰۰ کاراکتر؛ برای Bearer فقط خود کد، کلمهٔ Bearer خودکار اضافه می‌شود |

نوع «API با نام کاربری و رمز عبور»:

| عنوان فیلد | الزام | چه بنویسید | نمونه | قاعده |
| :--- | :--- | :--- | :--- | :--- |
| نشانی ورود | الزامی | نشانی ورود سرویس | `https://api.example.com/auth/login` | همان قواعد نشانی بالا |
| اطلاعات ورود | الزامی، محرمانه | نمونه با جای خالی: `{"username":"<USERNAME>","password":"<PASSWORD>"}`؛ نام فیلدها را از راهنمای سرویس بگیرید و مقدار واقعی را فقط در فرم وارد کنید | | شیء JSON، یعنی مجموعهٔ نام و مقدار داخل `{}`؛ حداکثر ۲۰۰۰ کاراکتر |
| محل کد دسترسی در پاسخ ورود | اختیاری | خالی بگذارید؛ فقط اگر راهنمای سرویس محل مشخصی گفته | `data.token` | خالی = یافتن خودکار به این ترتیب: `access_token` `token` `data.access_token` `data.token` `jwt` `id_token`؛ حروف انگلیسی، عدد و `.` `-` `_`، حداکثر ۸۰ |

نوع «Google Sheets» (فیلدهای نشانی، روش دریافت و اطلاعات ارسالی نشان داده نمی‌شوند). چیدمان محدوده‌ها باید با چیزی که برنامه می‌خواند (`data.valueRanges[i].values`، یک مورد برای هر خط محدوده، به همان ترتیب) یکی باشد. سلول‌های خالی انتهای هر ردیف حذف می‌شوند، ردیف خالی میانی `[]` است و حجم داده حداکثر ۲ مگابایت برای هر پاسخ و برای کل یک دریافت است.

| عنوان فیلد | الزام | چه بنویسید | نمونه | قاعده |
| :--- | :--- | :--- | :--- | :--- |
| نحوه دسترسی | الزامی | `public` (پیش‌فرض): بدون کلید؛ شیت باید با «هر کسی که لینک را دارد» و نقش «بیننده» (Viewer) به اشتراک گذاشته شود. `private`: کلید حساب سرویس | | یکی از دو گزینه |
| شناسهٔ فایل گوگل‌شیت | الزامی | فقط بخش بین `/d/` و `/edit` | `1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms` | ۲۰ تا ۸۰ حرف انگلیسی، عدد، `-` `_` |
| سلول‌های موردنیاز | الزامی | هر محدوده یک خط، همیشه همراه نام برگه | `'داشبورد پروژه'!A1:H17` | ۱ تا ۱۰ خط، هر خط ۱۰۰ کاراکتر؛ نام برگه لازم است و اگر فاصله یا حرف فارسی دارد در `'` می‌آید؛ سلول‌ها مثل `A1:H17` یا `A1`؛ نام برگه باید همان نام واقعی برگهٔ گوگل‌شیت باشد (از خود برگه کپی کنید؛ فاصله، نیم‌فاصله، «ي/ك» و بزرگی و کوچکی حروف تفاوتی ندارد، ولی نام دیگر پیدا نمی‌شود)؛ اگر گوگل در دسترس باشد، ذخیره انجام نمی‌شود و نام برگه‌های موجود نمایش داده می‌شود؛ در غیر این صورت وضعیت دریافت «تنظیمات یا پاسخ سرویس قابل استفاده نیست» می‌شود و داده‌ای نمایش داده نمی‌شود |
| متن فایل کلید گوگل (JSON) | فقط برای دسترسی خصوصی الزامی، محرمانه | هوش مصنوعی فقط `<PASTE_KEY_HERE>` بنویسد؛ خودتان متن کامل فایل کلید را در فرم کپی کنید | | کل JSON حساب سرویس گوگل، یعنی حسابی که برنامه با آن به شیت دسترسی می‌گیرد؛ حداکثر ۱۰۰۰۰ کاراکتر؛ برای دسترسی عمومی ذخیره نمی‌شود |

پیش‌فرض‌های فرم را بی‌دلیل تغییر ندهید: روش دریافت GET، نوع «API بدون نیاز به ورود»، و در اتصال با کلید، نوع Bearer است. اگر راهنمای سرویس چیز دیگری خواسته، همان را انتخاب کنید. کلمهٔ Bearer را سامانه خودش اضافه می‌کند. محل کد دسترسی هم معمولاً خودکار پیدا می‌شود؛ فقط اگر لازم بود آن را مشخص کنید.

**قالب پاسخ** (هوش مصنوعی در پایان پاسخ، این برگه و سپس «گام‌های بعدی» را به زبان کاربر بنویسد)

```
برگهٔ فرم
- نام برنامه: <مقدار>
- توضیح کوتاه: <مقدار یا خالی>
- دپارتمان: <نام دپارتمان>
- فایل‌ها: index.html، <فایل‌های دیگر>
- مخاطبان: <مقدار یا خالی>
- از چه تاریخی باز شود؟: <تاریخ شمسی یا خالی>
- تا چه تاریخی باز بماند؟: <تاریخ شمسی یا خالی>
- آخرین روز نگهداری برنامه: <تاریخ شمسی یا خالی>
- دریافت خودکار اطلاعات: <لازم نیست | یکی از گزینه‌های زیر>
  - نوع منبع داده: <عنوان گزینه>
  - نشانی دریافت اطلاعات (API): <نشانی https>
  - روش دریافت اطلاعات: <GET | POST>
  - اطلاعات ارسالی (JSON): <مقدار یا خالی>
  - (فیلدهای گزینهٔ انتخاب‌شده؛ مقدارهای محرمانه فقط به شکل <PASTE_KEY_HERE>)
```

**گام‌های بعدی** (هوش مصنوعی این مراحل را به صورت فهرست شماره‌دار بنویسد)

1. در پنجرهٔ انتخاب فایل، همهٔ فایل‌های برنامه را با هم از یک پوشه انتخاب کنید (بدون zip و پوشه): `index.html` و بقیه؛ حداکثر ۵۰ فایل، هر کدام ۱۰ مگابایت، مجموع ۵۰ مگابایت و فقط نوع‌های مجاز.
2. «افزودن برنامه» را باز کنید و برگهٔ بالا را فیلد به فیلد پر کنید.
3. «دریافت خودکار اطلاعات (اختیاری)» را فقط اگر برنامه به اطلاعات بیرونی نیاز دارد باز کنید. برای گوگل‌شیت، ابتدا در تنظیمات اشتراک‌گذاری شیت، ایمیل نوشته‌شده مقابل `client_email` در فایل کلید را اضافه کنید و دسترسی آن را «بیننده» بگذارید.
4. کلید، توکن، اطلاعات ورود و کلید گوگل را خودتان مستقیم در فرم وارد کنید؛ آن‌ها را برای هوش مصنوعی نفرستید.
5. «ذخیره برنامه» را بزنید و برنامه را از فهرست باز کنید تا نتیجه را ببینید. اگر اتصال دریافت اطلاعات را تنظیم کرده‌اید، پس از ذخیره درخواست دریافت در صف قرار می‌گیرد. کمی بعد وضعیت را در فهرست بررسی کنید: «به‌روز»، «ارتباط با سرویس برقرار نشد»، «سرویس اجازهٔ دسترسی نداد» یا «تنظیمات یا پاسخ سرویس قابل استفاده نیست». «در انتظار اولین دریافت» یعنی هنوز نتیجه‌ای ثبت نشده است.
6. اگر دریافت ناموفق بود، از توضیح وضعیت در جدول زیر کمک بگیرید. برای دریافت تازه از سرویس، از دکمهٔ دریافت اطلاعات در فهرست پیشخوان استفاده کنید؛ این کار هر ۱۰ دقیقه یک بار ممکن است. دکمهٔ داخل خود برنامه فقط فایل موجود را دوباره می‌خواند.
7. برای جایگزینی فایل‌ها، برنامه را با همان نام در همان دپارتمان بسازید و جایگزینی را تأیید کنید؛ باید اجازهٔ مدیریت آن برنامه را داشته باشید. فایل‌های قبلی حذف می‌شوند، اما مخاطبان و تاریخ‌های قبلی باقی می‌مانند؛ انتخاب تازهٔ مخاطب یا تاریخ در این فرم آن‌ها را تغییر نمی‌دهد. برای حفظ اتصال قبلی، «نوع منبع داده» را روی «بدون منبع داده» بگذارید. برای تغییر توضیح، مخاطبان، تاریخ‌ها یا اتصال، بدون جایگزینی فایل‌ها، در «جزئیات برنامه» دکمهٔ «ویرایش» را بزنید؛ در ویرایش، «بدون منبع داده» اتصال قبلی را حذف می‌کند.

| وضعیت دریافت | چه چیزی را بررسی کنید؟ |
| :--- | :--- |
| ارتباط با سرویس برقرار نشد | نشانی سرویس و در دسترس بودن آن را بررسی کنید. سامانه برای خطاهای قابل تکرار تا سه بار تلاش می‌کند؛ زمان دریافت شبانه در بخش ۲ آمده است |
| سرویس اجازهٔ دسترسی نداد | کلید، توکن یا اطلاعات ورود ممکن است اشتباه یا منقضی شده باشد. برای گوگل‌شیت، اشتراک‌گذاری با ایمیل حساب سرویس و دسترسی «بیننده» را هم بررسی کنید |
| تنظیمات یا پاسخ سرویس قابل استفاده نیست | نشانی، قالب JSON و محدودهٔ سلول‌ها را با راهنمای سرویس تطبیق دهید. پاسخ بدون محتوا، غیر JSON یا بزرگ‌تر از ۲ مگابایت هم پذیرفته نمی‌شود |

اطلاعاتی که سرویس می‌فرستد در `data.json` قرار می‌گیرد و برای همهٔ کسانی که به برنامه دسترسی دارند قابل مشاهده است. سرویس باید فقط اطلاعات مناسب همین مخاطبان را برگرداند.
