# Launchpad automatic data update guide (English first, Persian second)

For the owner of an API or a Google Sheet who wants it to feed a Launchpad program, with no login. Every number and rule comes from the app code. Part 1 is English, Part 2 (below it) is the same guide in Persian. Login, key and private-sheet options are not covered here; see the main guide `launchpad-artifact-guide.md`.

## Part 1 - English

### 1. The idea and three guarantees

The app fetches your data by itself, saves it as `data.json` beside the program page, and the page only reads that file.

1. The app, not the visitor's browser, fetches the data. It runs once per night: the planner starts at 05:01 Tehran time and runs programs one minute apart from 05:02, up to 43 per night; the rest wait for the next night. A fetch request is also registered when a live program is created, replaced, edited or activated; if the program's nightly turn is already queued, that run serves it and the result arrives a little later. Inactive or out-of-window programs never fetch. People who manage the program can press «دریافت اطلاعات تازه»; only one request per 10 minutes is accepted.
2. The program never logs in and never calls any API. It only reads `data.json`. The numbers are as fresh as the last run, not live.
3. It is not a proxy. Only the owner types the address in the form; it must be `https://` on port 443, and internal, private or IP-number addresses are refused. The nightly run covers a program only while it is live (active and inside its access and retention dates).

### 2. What your API or sheet must provide (no login)

| Case | Requirement |
| :--- | :--- |
| API | A public `https://` address (no user or password in it, no IP number) that answers GET (or POST) with JSON. A redirect is not followed |
| Google Sheet | The sheet is shared as «هر کسی که لینک را دارد» with role «بیننده» (Viewer) |
| Size | Reply at most 2 MB (per run for a sheet) |
| Time | Each request waits at most 10 seconds (5 to connect); a whole run at most 100 seconds |
| Retries | Up to 3 attempts, only for network errors, 429 and 5xx |

If the fetch fails, the file is still written as `{"updated_at": "...", "ok": false, "data": null}` and the old data is not kept. Statuses (visible only to people who manage the program):

| Status | Meaning |
| :--- | :--- |
| «به‌روز» | Fetch succeeded; `ok: true` |
| «ارتباط با سرویس برقرار نشد» | No answer, network error, 429 or 5xx |
| «سرویس اجازهٔ دسترسی نداد» | The service answered 401 or 403, or the sheet is not public |
| «تنظیمات یا پاسخ سرویس قابل استفاده نیست» | Wrong address, tab or ranges, other 4xx, a reply that is not JSON, or over 2 MB |
| «در انتظار اولین دریافت» | No result recorded yet |

### 3. Fill sheet (plain case)

Open «افزودن برنامه», then «دریافت خودکار اطلاعات (اختیاری)». Choose «نوع منبع داده» first; leave every login, key and header field empty (they exist only for the other types).

**Case A: public API**

| Field label | What to put |
| :--- | :--- |
| نوع منبع داده | `API بدون نیاز به ورود` |
| نشانی دریافت اطلاعات (API) | `https://api.example.com/v1/report` |
| روش دریافت اطلاعات | `دریافت مستقیم (GET)` (default; POST only if the service says so) |
| اطلاعات ارسالی (JSON) | Empty (POST only, optional) |

**Case B: public Google Sheet**

| Field label | What to put |
| :--- | :--- |
| نوع منبع داده | `Google Sheets` |
| نحوه دسترسی | `عمومی (هرکسی که لینک را داشته باشد)` (default) |
| شناسهٔ فایل گوگل‌شیت | Only the part between `/d/` and `/edit`, e.g. `1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms` |
| سلول‌های موردنیاز | One range per line, 1 to 10 lines, 100 characters each, always with the tab name, e.g. `'داشبورد پروژه'!A1:H17` |
| متن فایل کلید گوگل (JSON) | Hidden; leave it |

Tab names: quote a name with spaces or Persian letters with `'`. Spaces, half-spaces (ZWNJ), Arabic ي/ك versus Persian ی/ک and letter case are tolerated when the tab is looked up, but a different word is not. The sheet must have that tab, and a hidden tab is not found.

Login, header-key and private-sheet options exist; they are described in the main guide `launchpad-artifact-guide.md`.

### 4. Troubleshooting

| Status or reason | Plain fix |
| :--- | :--- |
| «ارتباط با سرویس برقرار نشد» | Check that the address opens in a browser; wait for the next night or press «دریافت اطلاعات تازه» later |
| «سرویس اجازهٔ دسترسی نداد» | The API wants a login, or the sheet is not shared as «هر کسی که لینک را دارد» / «بیننده» |
| «تنظیمات یا پاسخ سرویس قابل استفاده نیست» (API) | Use https, no IP number; the reply must be JSON and under 2 MB |
| «برگه‌ای با این نام وجود ندارد» | Copy the tab name from the sheet; the tab must exist and not be hidden |
| «محدوده وارد شده صحیح نیست» / «تعداد سلول‌های انتخابی بیش از حد مجاز است» | Write ranges like `'tab'!A1:H17`; make the ranges smaller (the total is 2 MB) |
| «در انتظار اولین دریافت» | Wait a few minutes after saving, then open the list again |

### 5. What the program must do

- Read the file with a relative fetch: `fetch('data.json', {cache: 'no-store'})`.
- If the reply is 404 or `ok` is `false`, show the failed or empty state with `updated_at`; use `data` only when `ok` is `true`.
- The full data contract (shapes, states, Google Sheets `valueRanges`) is in section 2 of the main guide `launchpad-artifact-guide.md`; it is not repeated here.

---

## Part 2 - فارسی

# راهنمای به‌روزرسانی خودکار اطلاعات برنامه

این راهنما برای کسی است که صاحب یک API یا یک گوگل‌شیت است و می‌خواهد اطلاعات آن به یک برنامهٔ پیشخوان برسد، بدون ورود و رمز. ورود با کلید یا رمز و گوگل‌شیت خصوصی در راهنمای اصلی `launchpad-artifact-guide.md` آمده است.

## ۱. ایده و سه تضمین

سامانه خودش اطلاعات را می‌گیرد و کنار صفحهٔ برنامه در `data.json` می‌گذارد. صفحه فقط همین فایل را می‌خواند.

۱. اطلاعات را سامانه می‌گیرد، نه مرورگر بازدیدکننده. هر شب یک بار: برنامه‌ریزی ساعت ۰۵:۰۱ به وقت تهران شروع می‌شود و برنامه‌ها با فاصلهٔ یک دقیقه، از ۰۵:۰۲، اجرا می‌شوند؛ حداکثر ۴۳ برنامه در هر شب و بقیه تا شب بعد منتظر می‌مانند. با ساخت، جایگزینی، ویرایش یا فعال شدن یک برنامهٔ فعال هم درخواست دریافت ثبت می‌شود؛ اگر نوبت شبانهٔ برنامه از قبل در صف باشد، همان اجرا می‌شود و نتیجه کمی دیرتر می‌رسد. برنامهٔ غیرفعال یا خارج از بازهٔ دسترسی هرگز اطلاعات نمی‌گیرد. کسی که اجازهٔ مدیریت برنامه را دارد می‌تواند «دریافت اطلاعات تازه» را بزند؛ در هر ۱۰ دقیقه فقط یک درخواست پذیرفته می‌شود.
۲. برنامه هرگز وارد سرویس نمی‌شود و هیچ API را صدا نمی‌زند. فقط `data.json` را می‌خواند. اطلاعات به اندازهٔ آخرین دریافت تازه است، نه زنده.
۳. سامانه واسطهٔ آزاد نیست. فقط مالک نشانی را در فرم می‌نویسد؛ نشانی باید `https://` و درگاه ۴۴۳ باشد و نشانی داخلی، خصوصی یا عددی (IP) پذیرفته نمی‌شود. دریافت شبانه فقط وقتی انجام می‌شود که برنامه زنده باشد: فعال باشد و در بازهٔ دسترسی و نگهداری خود باشد.

## ۲. سرویس یا شیت شما چه چیزی باید بدهد (بدون ورود)

| حالت | شرط |
| :--- | :--- |
| API | نشانی عمومی `https://` (بدون نام کاربری و رمز در آن، بدون IP عددی) که با GET (یا POST) جواب JSON بدهد. هدایت به نشانی دیگر دنبال نمی‌شود |
| گوگل‌شیت | فایل با «هر کسی که لینک را دارد» و نقش «بیننده» به اشتراک گذاشته شده باشد |
| حجم | پاسخ حداکثر ۲ مگابایت (برای شیت: جمع در یک نوبت) |
| زمان | هر درخواست حداکثر ۱۰ ثانیه (۵ ثانیه برای اتصال)؛ کل یک نوبت حداکثر ۱۰۰ ثانیه |
| تلاش دوباره | حداکثر ۳ بار، فقط برای خطای شبکه، ۴۲۹ و خطای ۵xx |

اگر دریافت ناموفق باشد، باز هم فایل با `{"updated_at": "...", "ok": false, "data": null}` نوشته می‌شود و اطلاعات قبلی نگه داشته نمی‌شود. وضعیت‌ها فقط به کسانی که اجازهٔ مدیریت برنامه را دارند نشان داده می‌شود:

| وضعیت | معنی |
| :--- | :--- |
| «به‌روز» | دریافت موفق بود؛ `ok: true` |
| «ارتباط با سرویس برقرار نشد» | جواب نیامد، خطای شبکه، ۴۲۹ یا ۵xx |
| «سرویس اجازهٔ دسترسی نداد» | سرویس ۴۰۱ یا ۴۰۳ داد، یا شیت عمومی نیست |
| «تنظیمات یا پاسخ سرویس قابل استفاده نیست» | نشانی، برگه یا سلول‌ها غلط است، خطای ۴xx دیگر، پاسخ JSON نیست، یا بیش از ۲ مگابایت است |
| «در انتظار اولین دریافت» | هنوز نتیجه‌ای ثبت نشده |

## ۳. برگهٔ پر کردن (حالت ساده)

«افزودن برنامه» را باز کنید و سپس «دریافت خودکار اطلاعات (اختیاری)». اول «نوع منبع داده» را انتخاب کنید. فیلدهای ورود، کلید و Header را خالی بگذارید؛ آن‌ها فقط برای نوع‌های دیگرند.

**حالت الف: API عمومی**

| عنوان فیلد | چه بنویسید |
| :--- | :--- |
| نوع منبع داده | `API بدون نیاز به ورود` |
| نشانی دریافت اطلاعات (API) | `https://api.example.com/v1/report` |
| روش دریافت اطلاعات | `دریافت مستقیم (GET)` (پیش‌فرض؛ POST فقط اگر سرویس بخواهد) |
| اطلاعات ارسالی (JSON) | خالی (فقط برای POST و اختیاری) |

**حالت ب: گوگل‌شیت عمومی**

| عنوان فیلد | چه بنویسید |
| :--- | :--- |
| نوع منبع داده | `Google Sheets` |
| نحوه دسترسی | `عمومی (هرکسی که لینک را داشته باشد)` (پیش‌فرض) |
| شناسهٔ فایل گوگل‌شیت | فقط کد بین `/d/` و `/edit`، مثل `1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms` |
| سلول‌های موردنیاز | هر محدوده یک خط، از ۱ تا ۱۰ خط، هر خط ۱۰۰ کاراکتر، همیشه با نام برگه، مثل `'داشبورد پروژه'!A1:H17` |
| متن فایل کلید گوگل (JSON) | نمایش داده نمی‌شود؛ کاری نکنید |

نام برگه: اگر فاصله یا حرف فارسی دارد، آن را بین دو علامت `'` بگذارید. هنگام پیدا کردن برگه، فاصله، نیم‌فاصله، «ي/ك» عربی به‌جای «ی/ک» فارسی و بزرگی و کوچکی حروف مهم نیست؛ اما کلمهٔ دیگر پذیرفته نمی‌شود. برگه باید در فایل باشد و برگهٔ پنهان پیدا نمی‌شود.

گزینه‌های ورود، کلید Header و شیت خصوصی هم هست؛ آن‌ها در راهنمای اصلی `launchpad-artifact-guide.md` آمده است.

## ۴. رفع مشکل

| وضعیت یا علت | کار ساده |
| :--- | :--- |
| «ارتباط با سرویس برقرار نشد» | ببینید نشانی در مرورگر باز می‌شود؛ شب بعد یا بعداً با «دریافت اطلاعات تازه» دوباره امتحان کنید |
| «سرویس اجازهٔ دسترسی نداد» | سرویس ورود می‌خواهد، یا شیت با «هر کسی که لینک را دارد» و «بیننده» به اشتراک گذاشته نشده |
| «تنظیمات یا پاسخ سرویس قابل استفاده نیست» (API) | https باشد و IP عددی نباشد؛ پاسخ باید JSON و کمتر از ۲ مگابایت باشد |
| «برگه‌ای با این نام وجود ندارد» | نام برگه را از خود شیت کپی کنید؛ برگه باید باشد و پنهان نباشد |
| «محدوده وارد شده صحیح نیست» / «تعداد سلول‌های انتخابی بیش از حد مجاز است» | محدوده را مثل `'برگه'!A1:H17` بنویسید؛ محدوده را کوچک‌تر کنید (جمع ۲ مگابایت) |
| «در انتظار اولین دریافت» | بعد از ذخیره چند دقیقه صبر کنید و فهرست را دوباره باز کنید |

## ۵. برنامه چه کاری باید بکند

- فایل را با `fetch` نسبی بخواند: `fetch('data.json', {cache: 'no-store'})`.
- اگر پاسخ ۴۰۴ بود یا `ok` برابر `false` بود، حالت «ناموفق» یا «خالی» را با `updated_at` نشان دهد؛ فقط وقتی `ok` برابر `true` است از `data` استفاده کند.
- شکل کامل اطلاعات (دو شکل فایل، حالت‌ها، `valueRanges` گوگل‌شیت) در بخش ۲ راهنمای اصلی `launchpad-artifact-guide.md` است و اینجا تکرار نشده.
