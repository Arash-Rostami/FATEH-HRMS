<?php

namespace App\Services;

use App\Enums\Language;
use App\Enums\Nationality;
use App\Enums\ProfileDetailGroup;


class ProfileDetailCatalog
{
    public static function attributes(string $prefix = 'values'): array
    {
        $attrs = [];
        foreach (self::definitions() as $key => $def) {
            $attrs["{$prefix}.{$key}"] = $def['label'];
        }
        return $attrs;
    }

    public static function definition(string $key): ?array
    {
        return self::definitions()[$key] ?? null;
    }

    public static function definitions(): array
    {
        return [
            // ───────────────── Identity extras ─────────────────
            'birthplace' => ['section' => 'identity', 'label' => 'محل تولد', 'type' => 'select', 'options' => self::getSortedOptions('cities'), 'searchable' => true],
            'nationality' => ['section' => 'identity', 'label' => 'ملیت', 'type' => 'select', 'options' => self::getSortedOptions('nationalities'), 'searchable' => true],
            'religion' => ['section' => 'identity', 'label' => 'دین', 'type' => 'select', 'options' => self::list(['اسلام', 'مسیحیت', 'یهودیت', 'زرتشتی', 'سایر'])],
            'foreign_residency' => ['section' => 'identity', 'label' => 'پاسپورت/اقامت سایر کشورها', 'type' => 'select', 'options' => self::hasNot()],
            'clearance_certificate' => ['section' => 'identity', 'label' => 'گواهی عدم سوءپیشینه', 'type' => 'select', 'options' => self::hasNot()],

            // ───────────────── Employment extras ─────────────────
            'company_name' => ['section' => 'employment', 'label' => 'نام شرکت', 'type' => 'text'],
            'service_city' => ['section' => 'employment', 'label' => 'شهر محل خدمت', 'type' => 'select', 'options' => self::getSortedOptions('cities'), 'searchable' => true],
            'contract_type' => ['section' => 'employment', 'label' => 'نوع قرارداد', 'type' => 'select', 'options' => self::list(['دائم', 'موقت', 'پروژه‌ای', 'ساعتی', 'کارآموزی'])],
            'job_title' => ['section' => 'employment', 'label' => 'شغل', 'type' => 'text'],
            'work_shift' => ['section' => 'employment', 'label' => 'شیفت کاری', 'type' => 'select', 'options' => self::list(['اداری', 'شیفت صبح', 'شیفت عصر', 'شیفت شب', 'گردشی'])],

            // ───────────────── Experience & insurance history ─────────────────
            'company_work_history' => ['section' => 'experience', 'label' => 'سابقه کار در شرکت', 'type' => 'text'],
            'company_insurance_history' => ['section' => 'experience', 'label' => 'سابقه بیمه در شرکت', 'type' => 'text'],
            'group_companies_work_history' => ['section' => 'experience', 'label' => 'سابقه کار در سایر شرکت‌های گروه', 'type' => 'text'],
            'prior_company' => ['section' => 'experience', 'label' => 'شرکت (سابقه قبلی)', 'type' => 'text'],
            'prior_cooperation_start' => ['section' => 'experience', 'label' => 'تاریخ شروع همکاری (قبلی)', 'type' => 'date', 'help' => 'نمونه: ۱۴۰۰/۰۵/۲۰'],
            'prior_cooperation_end' => ['section' => 'experience', 'label' => 'تاریخ پایان همکاری (قبلی)', 'type' => 'date', 'help' => 'نمونه: ۱۴۰۲/۰۵/۲۰'],
            'related_prior_experience' => ['section' => 'experience', 'label' => 'سابقه کار مرتبط قبل از شرکت‌های گروه', 'type' => 'text'],
            'unrelated_prior_experience' => ['section' => 'experience', 'label' => 'سابقه کار غیرمرتبط قبل از شرکت‌های گروه', 'type' => 'text'],
            'total_insurance_history' => ['section' => 'experience', 'label' => 'سابقه بیمه کل', 'type' => 'text'],

            // ───────────────── Education ─────────────────
            'education_status' => ['section' => 'education', 'label' => 'وضعیت تحصیلی', 'type' => 'select', 'options' => self::list(['فارغ‌التحصیل', 'در حال تحصیل', 'انصراف'])],
            'university_name' => ['section' => 'education', 'label' => 'دانشگاه محل تحصیل', 'type' => 'text'],
            'education_city' => ['section' => 'education', 'label' => 'شهر محل تحصیل', 'type' => 'select', 'options' => self::getSortedOptions('cities'), 'searchable' => true],

            // ───────────────── Skills & achievements ─────────────────
            'english_level' => ['section' => 'skills', 'label' => 'میزان آشنایی با زبان انگلیسی', 'type' => 'select', 'options' => self::list(['ابتدایی', 'متوسط', 'پیشرفته', 'مادری'])],
            'fluent_language' => ['section' => 'skills', 'label' => 'زبان خارجی با تسلط کامل', 'type' => 'select', 'options' => self::getSortedOptions('languages'), 'searchable' => true],
            'training_courses' => ['section' => 'skills', 'label' => 'دوره‌های آموزشی گذرانده‌شده', 'type' => 'textarea'],
            'specialized_software' => ['section' => 'skills', 'label' => 'نرم‌افزار تخصصی با تسلط', 'type' => 'textarea'],
            'teaching_experience' => ['section' => 'skills', 'label' => 'سابقه تدریس', 'type' => 'text'],
            'teaching_field' => ['section' => 'skills', 'label' => 'زمینه تدریس', 'type' => 'text'],
            'achievements' => ['section' => 'skills', 'label' => 'دستاوردهای ورزشی/علمی/فرهنگی/هنری (مقام/مدال)', 'type' => 'textarea'],

            // ───────────────── Family & dependents ─────────────────
            'marriage_date' => ['section' => 'family', 'label' => 'تاریخ ازدواج', 'type' => 'date', 'help' => 'نمونه: ۱۳۹۵/۰۳/۱۵'],
            'spouse_job' => ['section' => 'family', 'label' => 'شغل همسر', 'type' => 'text'],
            'daughters_single' => ['section' => 'family', 'label' => 'تعداد فرزندان دختر مجرد', 'type' => 'select', 'options' => self::list(range(0, 10))],
            'daughters_under18' => ['section' => 'family', 'label' => ' تعداد دختر کمتر از ۱۸ سال', 'type' => 'select', 'options' => self::list(range(0, 10))],
            'daughters_over18_student' => ['section' => 'family', 'label' => ' تعداد دختر بالاتر از ۱۸ سال دانشجو', 'type' => 'select', 'options' => self::list(range(0, 10))],
            'daughters_over18_nonstudent' => ['section' => 'family', 'label' => 'تعداد دختر بالاتر از ۱۸ سال غیردانشجو', 'type' => 'select', 'options' => self::list(range(0, 10))],
            'sons_under18' => ['section' => 'family', 'label' => ' تعداد پسر کمتر از ۱۸ سال', 'type' => 'select', 'options' => self::list(range(0, 10))],
            'sons_over18_student' => ['section' => 'family', 'label' => 'تعداد پسر بالاتر از ۱۸ سال دانشجو', 'type' => 'select', 'options' => self::list(range(0, 10))],
            'sons_over18_nonstudent' => ['section' => 'family', 'label' => 'تعداد پسر بالاتر از ۱۸ سال غیردانشجو ', 'type' => 'select', 'options' => self::list(range(0, 10))],
            'children_disabled' => ['section' => 'family', 'label' => 'تعداد فرزندان دارای معلولیت', 'type' => 'select', 'options' => self::list(range(0, 10))],
            'dependent_parents' => ['section' => 'family', 'label' => 'پدر و مادر تحت تکفل', 'type' => 'text'],
            'other_dependents' => ['section' => 'family', 'label' => 'سایر افراد تحت تکفل', 'type' => 'text'],
            'total_dependents' => ['section' => 'family', 'label' => 'تعداد کل افراد تحت تکفل', 'type' => 'select', 'options' => self::list(range(0, 20))],

            // ───────────────── Health & physical condition ─────────────────
            'blood_type' => ['section' => 'health', 'label' => 'گروه خون', 'type' => 'select', 'options' => self::list(['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'])],
            'height' => ['section' => 'health', 'label' => 'قد (سانتی‌متر)', 'type' => 'number'],
            'weight' => ['section' => 'health', 'label' => 'وزن (کیلوگرم)', 'type' => 'number'],
            'physical_condition' => ['section' => 'health', 'label' => 'وضعیت جسمانی', 'type' => 'select', 'options' => self::list(['سالم', 'دارای محدودیت'])],
            'disability_type' => ['section' => 'health', 'label' => 'نوع معلولیت', 'type' => 'select', 'options' => self::list(['حرکتی', 'شنیداری', 'گفتاری', 'بصری', 'ذهنی', 'چند معلولیتی', 'سایر'])],
            'chronic_disease' => ['section' => 'health', 'label' => 'سابقه بیماری خاص', 'type' => 'text'],
            'family_chronic_disease' => ['section' => 'health', 'label' => 'سابقه بیماری خاص همسر یا فرزند', 'type' => 'text'],
            'disease_type' => ['section' => 'health', 'label' => 'نوع بیماری', 'type' => 'text'],
            'pre_employment_exam' => ['section' => 'health', 'label' => 'آزمایشات بدو استخدام', 'type' => 'text'],
            'periodic_exam' => ['section' => 'health', 'label' => 'آزمایشات ادواری', 'type' => 'text'],
            'last_periodic_exam_date' => ['section' => 'health', 'label' => 'آخرین تاریخ آزمایشات ادواری', 'type' => 'date', 'help' => 'نمونه: ۱۴۰۳/۰۲/۱۰'],

            // ───────────────── Military service ─────────────────
            'military_status' => ['section' => 'military', 'label' => 'وضعیت نظام وظیفه', 'type' => 'select', 'options' => self::list(['مشمول', 'در حال خدمت', 'پایان خدمت', 'معاف', 'معافیت تحصیلی'])],
            'exemption_type' => ['section' => 'military', 'label' => 'نوع معافیت', 'type' => 'select', 'options' => self::list(['کفالت', 'پزشکی', 'موارد خاص', 'سایر'])],
            'medical_exemption_type' => ['section' => 'military', 'label' => 'نوع معافیت پزشکی', 'type' => 'text'],

            // ───────────────── Financial & welfare ─────────────────
            'insurance_type' => ['section' => 'financial', 'label' => 'نوع بیمه', 'type' => 'text'],
            'insurability_status' => ['section' => 'financial', 'label' => 'وضعیت بیمه‌پذیری', 'type' => 'select', 'options' => self::list(['مشمول', 'غیرمشمول'])],
            'supplementary_insurance' => ['section' => 'financial', 'label' => 'بیمه تکمیلی', 'type' => 'select', 'options' => self::hasNot()],
            'loan_fund_member' => ['section' => 'financial', 'label' => 'عضویت صندوق وام', 'type' => 'select', 'options' => self::list(['عضو', 'غیرعضو'])],
            'housing_status' => ['section' => 'financial', 'label' => 'وضعیت مسکن', 'type' => 'select', 'options' => self::list(['شخصی', 'استیجاری', 'سازمانی', 'سایر'])],
            'personal_vehicle' => ['section' => 'financial', 'label' => 'وسیله نقلیه شخصی', 'type' => 'select', 'options' => self::hasNot()],
            'commute_method' => ['section' => 'financial', 'label' => 'وسیله ایاب و ذهاب به محل کار', 'type' => 'select', 'options' => self::list(['سرویس شرکت', 'خودرو شخصی', 'حمل و نقل عمومی', 'سایر'])],

            // ───────────────── Other ─────────────────
            'city_code' => ['section' => 'other', 'label' => 'کد شهر', 'type' => 'text'],
        ];
    }

    public static function grouped(): array
    {
        $out = [];
        foreach (ProfileDetailGroup::ordered() as $group) {
            $out[$group->value] = [];
        }
        foreach (self::definitions() as $key => $def) {
            $out[$def['section']][$key] = $def;
        }
        return array_filter($out);
    }

    public static function isSystem(string $key): bool
    {
        return array_key_exists($key, self::definitions());
    }

    public static function keys(): array
    {
        return array_keys(self::definitions());
    }

    public static function rules(string $prefix = 'values'): array
    {
        $rules = [];
        foreach (self::definitions() as $key => $def) {
            $rules["{$prefix}.{$key}"] = self::ruleFor($def);
        }
        return $rules;
    }

    public static function selectGroups(): array
    {
        $out = [];
        foreach (self::grouped() as $section => $fields) {
            $label = ProfileDetailGroup::from($section)->getLabel();
            $out[$label] = collect($fields)->map(fn($d) => $d['label'])->all();
        }
        return $out;
    }

    private static function getSortedOptions(string $source): array
    {
        static $cache = [];

        if (!isset($cache[$source])) {
            $items = match ($source) {
                'cities' => CityService::all(),
                'languages' => array_column(Language::cases(), 'value'),
                'nationalities' => array_column(Nationality::cases(), 'value'),
            };

            sort($items);

            $cache[$source] = array_combine($items, $items);
        }

        return $cache[$source];
    }

    private static function hasNot(): array
    {
        return ['دارد' => 'دارد', 'ندارد' => 'ندارد'];
    }

    private static function list(array $items): array
    {
        return array_combine($items, $items);
    }

    private static function ruleFor(array $def): string
    {
        return match ($def['type']) {
            'number' => 'nullable|integer|min:0|max:1000000',
            'textarea' => 'nullable|string|max:2000',
            'date' => 'nullable|string|max:20',
            'select' => 'nullable|string|in:' . implode(',', array_keys($def['options'] ?? [])),
            default => 'nullable|string|max:255',
        };
    }
}
