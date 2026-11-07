<?php

/**
 * Every limit of the AI Report module in one place — nothing is hardcoded at a call site.
 * Provider key/url come from env and model/timeout/max_output are literals, all in config/services.php ('ai_report').
 * Per-file, inline, inline-total and total upload sizes (KB) come from the 'ai' group of config/file_upload_limits.php
 * through App\Support\AiReportUploadLimits; the upload.* values here are only the fallback.
 * Read through the config repository: config('ai_report.upload.max_files').
 */

return [
    'daily_cap' => 6,
    'stale_minutes' => 5,
    'stale_draft_days' => 7,
    'share_link_days' => 7,
    'max_versions' => 10,
    'max_recipients' => 20,
    'list_max' => 200,
    'no_refund' => ['too_large', 'blocked'],
    'poll_seconds' => 4,
    'slow_seconds' => 90,
    'eta_seconds' => [15, 30],

    'types' => [
        'free' => ['label' => 'آزاد (بدون قالب)', 'blocks' => [], 'rules' => ''],
        'monthly' => ['label' => 'گزارش ماهانه', 'blocks' => ['title', 'facts', 'kpi_row', 'summary', 'chart', 'table', 'callout'], 'rules' => 'Monthly performance report for the period the user names (default: the previous Jalali month). Compare the period with the one before it, state the main drivers of change, and close with the decisions needed for next month. Add a progress block instead of the table only when the request gives targets. The whole page, including the assumptions section, has at most {blocks} blocks.'],
        'budget' => ['label' => 'گزارش بودجه و هزینه', 'blocks' => ['title', 'facts', 'kpi_row', 'table', 'chart', 'summary', 'callout'], 'rules' => 'Budget versus actual report: every money table has budget, actual, variance and variance-percent columns (leave percent_column null for the variance-percent column), the budget, actual and variance totals equal the sum of the rows while the variance-percent total is "—", and the callout names the largest overruns. Use at most one table besides the chart; the whole page, including the assumptions section, has at most {blocks} blocks.'],
        'attachment' => ['label' => 'خلاصهٔ فایل‌های پیوست', 'blocks' => ['title', 'facts', 'summary', 'section', 'table', 'callout'], 'rules' => 'Summarize the attached material only: facts list the source files and their scope, use at most two sections that follow the structure of the material, tables reproduce only figures present in the files, and the callout lists open questions; never add outside knowledge. The whole page, including the assumptions section, has at most {blocks} blocks.'],
        'incident' => ['label' => 'گزارش حادثه / رخداد', 'blocks' => ['title', 'facts', 'summary', 'section', 'action_items', 'callout'], 'rules' => 'Incident report: facts give when, where and who was informed; the first section is a time-ordered account, the next covers impact and root cause; action_items list corrective actions with owner, due date and status; the callout states residual risk. Stay factual, no blame. The whole page, including the assumptions section, has at most {blocks} blocks.'],
        'meeting' => ['label' => 'صورت‌جلسه', 'blocks' => ['title', 'facts', 'summary', 'section', 'action_items', 'callout'], 'rules' => 'Meeting minutes: facts give date, place, attendees and chair; use at most two sections for the agenda items (fold minor items together); action_items list every decision and follow-up with owner, due date and status; the callout lists items deferred to the next meeting. The whole page, including the assumptions section, has at most {blocks} blocks.'],
    ],

    'lengths' => [
        'one_page' => ['label' => 'یک‌صفحه‌ای', 'rules' => 'One page: a single element in pages with at most {one_page_blocks} blocks including the assumptions section; when the report type lists more blocks, drop optional ones first (kpi_row, then chart, then table) and never the title, the assumptions section or the final callout; at most 4 bullets per list and at most 8 rows per table.'],
        'standard' => ['label' => 'استاندارد', 'rules' => 'Standard length: 1 to 2 pages.'],
        'detailed' => ['label' => 'مفصل', 'rules' => 'Detailed: 2 to 4 pages; every section has at least two paragraphs, every figure is explained, and extra data goes into tables rather than longer paragraphs.'],
    ],

    'audiences' => [
        'management' => ['label' => 'مدیران', 'rules' => 'Audience is senior management: lead with outcomes and decisions, keep technical detail out of the summary, prefer rounded figures with units.'],
        'technical' => ['label' => 'کارشناسان', 'rules' => 'Audience is technical staff: include method, assumptions, exact figures and data gaps; precise terminology is welcome.'],
        'mixed' => ['label' => 'عمومی (مدیران و کارشناسان)', 'rules' => 'Mixed audience: a plain-language summary first, then technical detail in later sections; define every term once.'],
    ],

    'prompt' => [
        'min' => 10,
        'max' => 4000,
    ],

    'upload' => [
        'tier' => 'document',
        'max_files' => 5,
        'total_kb' => 25600,
        'inline_kb' => 4096,
        'inline_total_kb' => 12288,
        'mimes' => 'pdf,jpg,jpeg,png,webp,docx,xlsx,csv,md,txt',
        'accept' => '.pdf,application/pdf,.jpg,.jpeg,image/jpeg,.png,image/png,.webp,image/webp,.docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document,.xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,.csv,text/csv,.txt,text/plain,.md,text/markdown',
    ],

    'extract' => [
        'text_per_file' => 40000,
        'text_total' => 80000,
        'text_read_factor' => 4,
        'xlsx_rows' => 500,
        'xlsx_raw_rows' => 5000,
        'xlsx_sheets' => 5,
        'xlsx_cols' => 30,
        'docx_xml_kb' => 8192,
        'xlsx_xml_kb' => 20480,
        'docx_zip_kb' => 51200,
        'zip_entries' => 5000,
    ],

    'provider' => [
        'connect_timeout' => 10,
        'max_calls' => 2,
    ],

    'job' => [
        'padding' => 30,
        'retry_margin' => 10,
    ],

    'lock' => [
        'ttl' => 10,
        'wait' => 5,
    ],

    'spec' => [
        'kpi_items' => 4,
        'kpi_min' => 2,
        'facts_items' => 8,
        'chart_labels' => 12,
        'chart_series' => 3,
        'pie_slices' => 6,
        'chart_max_abs' => 1e15,
        'chart_label_chars' => 40,
        'pages' => 6,
        'blocks' => 8,
        'one_page_blocks' => 6,
        'items' => 12,
        'columns' => 6,
        'rows' => 40,
        'options' => 5,
        'short' => 200,
        'cell' => 120,
        'paragraph' => 1200,
        'bytes' => 65536,
    ],

    'assumptions' => [
        'heading' => ['fa' => 'مفروضات و محدودیت‌ها', 'en' => 'Assumptions and limitations'],
        'fallback' => ['fa' => 'این گزارش فقط بر پایهٔ درخواست و فایل‌های پیوست تهیه شده و داده‌ای از منبع دیگر به آن افزوده نشده است.', 'en' => 'This report is based only on the request and the attached files; no other data source was added.'],
    ],

    'status_values' => [
        'danger' => ['late', 'overdue', 'delayed', 'blocked', 'high', 'at risk', 'risk', 'critical', 'عقب‌افتاده', 'معوق', 'تأخیر', 'با تأخیر', 'تاخیر', 'با تاخیر', 'متوقف', 'متوقف شده', 'بالا', 'زیاد', 'در خطر', 'پرخطر', 'بحرانی'],
        'warning' => ['in progress', 'ongoing', 'pending', 'medium', 'در حال انجام', 'در جریان', 'در انتظار', 'متوسط', "نیمه\u{200C}کاره"],
        'success' => ['done', 'completed', 'closed', 'on track', 'low', 'انجام‌شده', 'انجام شد', 'تکمیل', 'تکمیل‌شده', 'بسته', 'طبق برنامه', 'کم', 'پایین'],
    ],

    'check' => [
        'total_tolerance' => 0.005,
        'percent_tolerance' => 1.0,
        'numeric_ratio' => 0.8,
    ],
];
