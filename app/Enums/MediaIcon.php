<?php

namespace App\Enums;

enum MediaIcon: string
{
    case Idea = 'heroicon-o-light-bulb';
    case Analysis = 'heroicon-o-chart-bar';
    case Balance = 'heroicon-o-scale';
    case Speed = 'heroicon-o-bolt';
    case Team = 'heroicon-o-user-group';
    case Help = 'heroicon-o-question-mark-circle';
    case Goal = 'heroicon-o-flag';
    case Teach = 'heroicon-o-academic-cap';
    case Book = 'heroicon-o-book-open';
    case Checklist = 'heroicon-o-clipboard-document-check';
    case Safety = 'heroicon-o-shield-check';
    case Health = 'heroicon-o-heart';
    case Alert = 'heroicon-o-fire';
    case Office = 'heroicon-o-building-office';
    case Shipping = 'heroicon-o-truck';
    case Warehouse = 'heroicon-o-archive-box';
    case Tools = 'heroicon-o-wrench-screwdriver';
    case Tech = 'heroicon-o-cpu-chip';
    case Lock = 'heroicon-o-lock-closed';
    case Finance = 'heroicon-o-banknotes';
    case Bank = 'heroicon-o-building-library';
    case Growth = 'heroicon-o-arrow-trending-up';
    case Insight = 'heroicon-o-presentation-chart-line';
    case Talk = 'heroicon-o-chat-bubble-left-right';
    case Speech = 'heroicon-o-megaphone';
    case Mic = 'heroicon-o-microphone';
    case Headphones = 'heroicon-o-speaker-wave';
    case Video = 'heroicon-o-video-camera';
    case Music = 'heroicon-o-musical-note';
    case Play = 'heroicon-o-play-circle';
    case Calendar = 'heroicon-o-calendar';
    case Spark = 'heroicon-o-sparkles';

    public function getLabel(): string
    {
        return match ($this) {
            self::Idea => 'ایده',
            self::Analysis => 'تحلیل',
            self::Balance => 'تعادل',
            self::Speed => 'سرعت',
            self::Team => 'تیم',
            self::Help => 'پرسش و پاسخ',
            self::Goal => 'هدف',
            self::Teach => 'آموزش',
            self::Book => 'کتاب',
            self::Checklist => 'چک‌لیست',
            self::Safety => 'ایمنی',
            self::Health => 'سلامت',
            self::Alert => 'شرایط اضطراری',
            self::Office => 'سازمان',
            self::Shipping => 'حمل‌ونقل',
            self::Warehouse => 'انبار',
            self::Tools => 'ابزار',
            self::Tech => 'فناوری',
            self::Lock => 'امنیت',
            self::Finance => 'مالی',
            self::Bank => 'بانک',
            self::Growth => 'رشد',
            self::Insight => 'بینش',
            self::Talk => 'گفتگو',
            self::Speech => 'سخنرانی',
            self::Mic => 'میکروفن',
            self::Headphones => 'شنیداری',
            self::Video => 'ویدیو',
            self::Music => 'پادکست',
            self::Play => 'پخش',
            self::Calendar => 'تقویم',
            self::Spark => 'خلاقیت',
        };
    }

    public function getMaterialIcon(): string
    {
        return match ($this) {
            self::Idea => 'emoji_objects',
            self::Analysis => 'monitoring',
            self::Balance => 'balance',
            self::Speed => 'bolt',
            self::Team => 'groups',
            self::Help => 'contact_support',
            self::Goal => 'flag',
            self::Teach => 'school',
            self::Book => 'menu_book',
            self::Checklist => 'fact_check',
            self::Safety => 'verified_user',
            self::Health => 'favorite',
            self::Alert => 'crisis_alert',
            self::Office => 'domain',
            self::Shipping => 'local_shipping',
            self::Warehouse => 'warehouse',
            self::Tools => 'construction',
            self::Tech => 'memory',
            self::Lock => 'lock',
            self::Finance => 'account_balance_wallet',
            self::Bank => 'savings',
            self::Growth => 'trending_up',
            self::Insight => 'insights',
            self::Talk => 'forum',
            self::Speech => 'campaign',
            self::Mic => 'mic',
            self::Headphones => 'headphones',
            self::Video => 'smart_display',
            self::Music => 'podcasts',
            self::Play => 'play_circle',
            self::Calendar => 'calendar_month',
            self::Spark => 'lightbulb',
        };
    }

    public static function default(): self
    {
        return self::Headphones;
    }
}
