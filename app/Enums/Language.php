<?php

namespace App\Enums;

enum Language: string
{
    case AF = 'آفریکانس';
    case AM = 'امهری';
    case AR = 'عربی';
    case AZ = 'ترکی آذربایجانی';
    case BE = 'بلاروسی';
    case BG = 'بلغاری';
    case BN = 'بنگالی';
    case BR = 'برتون';
    case BS = 'بوسنیایی';
    case CA = 'کاتالان';
    case CS = 'چکی';
    case CY = 'ولزی';
    case DA = 'دانمارکی';
    case DE = 'آلمانی';
    case EL = 'یونانی';
    case EN = 'انگلیسی';
    case ES = 'اسپانیایی';
    case ET = 'استونیایی';
    case EU = 'باسکی';
    case FA = 'فارسی';
    case FI = 'فنلاندی';
    case FR = 'فرانسوی';
    case GA = 'ایرلندی';
    case GD = 'گیلیک اسکاتلندی';
    case GL = 'گالیسی';
    case GU = 'گجراتی';
    case HA = 'هوسا';
    case HE = 'عبری';
    case HR = 'کرواتی';
    case HU = 'مجاری';
    case HY = 'ارمنی';
    case ID = 'اندونزیایی';
    case IG = 'ایگبو';
    case IS = 'ایسلندی';
    case IT = 'ایتالیایی';
    case JA = 'ژاپنی';
    case JW = 'جاوه‌ای';
    case KA = 'گرجی';
    case KK = 'قزاقی';
    case KM = 'خمری';
    case KN = 'کانارا';
    case KO = 'کره‌ای';
    case KU = 'کردی';
    case KY = 'قرقیزی';
    case LA = 'لائوسی';
    case LT = 'لیتوانیایی';
    case LV = 'لتونیایی';
    case MG = 'مالاگاسی';
    case MK = 'مقدونیه‌ای';
    case ML = 'مالایالامی';
    case MN = 'مغولی';
    case MR = 'مراتی';
    case MS = 'مالایی';
    case MY = 'برمه‌ای';
    case NL = 'هلندی';
    case NO = 'نروژی';
    case NY = 'چیچوا';
    case OM = 'اورومو';
    case PA = 'پنجابی';
    case PL = 'لهستانی';
    case PS = 'پشتو';
    case PT = 'پرتغالی';
    case RO = 'رومانیایی';
    case RU = 'روسی';
    case SI = 'سینهالا';
    case SK = 'اسلواک';
    case SL = 'اسلوونیایی';
    case SN = 'شونا';
    case SO = 'سومالیایی';
    case SQ = 'آلبانیایی';
    case SR = 'صربی';
    case ST = 'سوتو';
    case SU = 'سوندایی';
    case SV = 'سوئدی';
    case SW = 'سواحیلی';
    case TA = 'تامیلی';
    case TE = 'تلوگو';
    case TH = 'تایلندی';
    case TI = 'تیگرینیا';
    case TK = 'ترکمنی';
    case TL = 'تاگالوگ';
    case TR = 'ترکی استانبولی';
    case UK = 'اوکراینی';
    case UR = 'اردو';
    case UZ = 'ازبکی';
    case VI = 'ویتنامی';
    case XH = 'خوسا';
    case YO = 'یوروبا';
    case ZU = 'زولو';

    public static function code(string $label): ?string
    {
        return self::tryFrom($label)?->name;
    }

    public static function codes(): array
    {
        return array_column(self::cases(), 'name');
    }

    public static function label(string $code): ?string
    {
        foreach (self::cases() as $case) {
            if ($case->name === strtoupper($code)) {
                return $case->value;
            }
        }

        return null;
    }

    public static function labels(): array
    {
        $labels = array_column(self::cases(), 'value');
        sort($labels);

        return $labels;
    }

    public static function toArray(): array
    {
        $cases = array_column(self::cases(), 'value', 'name');
        asort($cases);

        return $cases;
    }
}
