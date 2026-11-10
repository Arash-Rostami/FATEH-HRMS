# Letterhead fonts manifest (PHASE 1 output, contract for PHASE 2)

All families are SIL Open Font License 1.1 (embedding and commercial use allowed). The license file sits beside the fonts (`OFL.txt`). Existing files (Yekan, Iranyekan, Material Symbols) are untouched.

| slug | Persian name | English name | script | weights | ttf (PDF) | woff2 (browser) | version | source |
|---|---|---|---|---|---|---|---|---|
| `vazirmatn` | وزیرمتن | Vazirmatn | fa (+Latin) | 400, 700 | yes | yes | v33.003 | https://github.com/rastikerdar/vazirmatn/releases/tag/v33.003 |
| `noto-naskh-arabic` | نوتو نسخ عربی | Noto Naskh Arabic | fa (formal naskh) | 400, 700 | yes | NO (ttf only) | v2.021 (unhinted static) | https://github.com/notofonts/arabic/releases/tag/NotoNaskhArabic-v2.021 |
| `tinos` | تینوس | Tinos (Times New Roman metric-compatible serif) | latin | 400, 700, 400i, 700i | yes | NO (ttf only) | 1.340 | https://github.com/google/fonts/tree/main/ofl/tinos (license: https://github.com/googlefonts/tinos) |
| `inter` | اینتر | Inter | latin | 400, 700, 400i, 700i | yes | yes | 4.1 (ttf 4.001; woff2 is the web build 4.66) | https://github.com/rsms/inter/releases/tag/v4.1 |

Why: Vazirmatn = modern clean Persian sans, the de-facto standard, full shaping; Noto Naskh Arabic = traditional formal naskh for official letters, has Persian letters, ۰-۹ digits and ZWNJ; Tinos = formal Times-like serif for Latin; Inter = clean neutral sans for Latin.

## Files (resources/assets/fonts/)

- `vazirmatn/`: Vazirmatn-Regular.ttf, Vazirmatn-Bold.ttf, Vazirmatn-Regular.woff2, Vazirmatn-Bold.woff2, OFL.txt
- `noto-naskh-arabic/`: NotoNaskhArabic-Regular.ttf, NotoNaskhArabic-Bold.ttf, OFL.txt
- `tinos/`: Tinos-Regular.ttf, Tinos-Bold.ttf, Tinos-Italic.ttf, Tinos-BoldItalic.ttf, OFL.txt
- `inter/`: Inter-Regular/Bold/Italic/BoldItalic `.ttf` and `.woff2`, OFL.txt (copy of upstream LICENSE.txt)

Build note (assetPattern.md): `fonts/` is copied verbatim to `public/build/assets/fonts/` and the PWA precache glob includes `*.woff2`, so the 6 woff2 files here (about 0.6 MB) will be precached by the service worker; `.ttf` is not in that glob.

## mPDF caveat (important)

The installed mPDF throws `GPOS Lookup Type 5, Format 3 not supported` when a modern Arabic font (every Noto Naskh release 2.018-2.021, Lateef and Scheherazade New were tested) is registered with `useOTL` enabled. Vazirmatn parses fine with `useOTL => 0xFF`. Noto Naskh therefore MUST be registered with `useOTL => 0` (mPDF's built-in Arabic shaper; the font contains the Arabic presentation forms: 140 of 141 in FE70-FEFC plus 631 in FB50-FDFF). Consequence: no `useKashida`, no OTL mark positioning. Do not set `useOTL` on `noto-naskh-arabic`.

## mPDF fontdata entries (fontDir already includes resource_path('assets/fonts'); paths are relative to it)

Same structure as `app/Services/Correspondence/CorrespondencePdf.php` (`$fontData + [...]`):

```php
'vazirmatn' => [
    'R' => 'vazirmatn/Vazirmatn-Regular.ttf',
    'B' => 'vazirmatn/Vazirmatn-Bold.ttf',
    'useOTL' => 0xFF,
    'useKashida' => 75,
],
'notonaskh' => [
    'R' => 'noto-naskh-arabic/NotoNaskhArabic-Regular.ttf',
    'B' => 'noto-naskh-arabic/NotoNaskhArabic-Bold.ttf',
    'useOTL' => 0,
],
'tinos' => [
    'R' => 'tinos/Tinos-Regular.ttf',
    'B' => 'tinos/Tinos-Bold.ttf',
    'I' => 'tinos/Tinos-Italic.ttf',
    'BI' => 'tinos/Tinos-BoldItalic.ttf',
],
'inter' => [
    'R' => 'inter/Inter-Regular.ttf',
    'B' => 'inter/Inter-Bold.ttf',
    'I' => 'inter/Inter-Italic.ttf',
    'BI' => 'inter/Inter-BoldItalic.ttf',
],
```

mPDF keys must be lowercase, no hyphens/spaces (hence `notonaskh`). The CSS `font-family` used in the HTML for PDF must equal the key.

## Browser @font-face (suggested for PHASE 2)

Vazirmatn and Inter: `src: url(...woff2) format('woff2'), url(...ttf) format('truetype')` per weight/style. Noto Naskh Arabic and Tinos: `url(...ttf) format('truetype')` only (no woff2 converter was available; no tools were installed in phase 1).

## Verification (PHASE 1)

A throwaway Mpdf instance (tempDir in the scratchpad) registered all four families with the entries above and wrote a PDF with «۱۲۳۴۵۶۷۸۹۰», «می‌شود» and Latin text in every weight/style: no errors, all 12 faces embedded (`MPDFAA+Vazirmatn-*`, `NotoNaskhArabic-*`, `Tinos-*`, `Inter-*`). The PDF could not be viewed visually (no renderer installed); glyph coverage for Persian letters (پ چ ژ گ), ۰-۹ and ZWNJ in Noto Naskh was confirmed from its cmap.

## PHASE 2 addition: Parastoo (closest free Nazanin-style naskh)

B Nazanin itself is proprietary (Borna Rayaneh / Bornaware) and is NOT bundled. If the user holds a licence, they copy their own `BNazanin.ttf` / `BNazaninBold.ttf` into `b-nazanin/`. Times New Roman is Monotype/Microsoft proprietary; Tinos (above) is the metric-compatible replacement.

| slug | name | script | weights | ttf | woff2 | version | license | source |
|---|---|---|---|---|---|---|---|---|
| `parastoo` | Parastoo (پرستو) | fa (+Latin) | 400, 700 | yes | yes | 2.0.1 (repo master, last commit 2021-05-28) | SIL OFL 1.1, (c) Saber Rastikerdar | https://github.com/rastikerdar/parastoo-font (dist/) |

Files: `parastoo/`: Parastoo-Regular.ttf (94 KB), Parastoo-Bold.ttf (98 KB), Parastoo-Regular.woff2 (46 KB), Parastoo-Bold.woff2 (47 KB), OFL.txt (upstream LICENSE).

mPDF entry (OTL parses fine, tested with 0xFF and 0):

```php
'parastoo' => [
    'R' => 'parastoo/Parastoo-Regular.ttf',
    'B' => 'parastoo/Parastoo-Bold.ttf',
    'useOTL' => 0xFF,
    'useKashida' => 75,
],
```

## Registry and optional B Nazanin (PHASE 2)

The families above are wired through `config/ai/correspondence.php` `fonts` (see `correspondencePattern.md`, "Letterhead fonts"). `nazanin` is a legacy key that points at Yekan (label «یکان (پیش‌فرض قبلی)»), kept valid for stored rows. `bnazanin` is optional: copy your own licensed `BNazanin.ttf` (and optionally `BNazaninBold.ttf`) into `b-nazanin/`; only do so if your licence covers server and PDF use. The folder is gitignored (`.gitignore`), so deploy it by hand. While the file is absent `bnazanin` is not selectable and a stored value renders as `parastoo`.
