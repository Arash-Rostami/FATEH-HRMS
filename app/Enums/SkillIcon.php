<?php

namespace App\Enums;

enum SkillIcon: string
{
    case Code = 'code';
    case Terminal = 'terminal';
    case DataObject = 'data_object';
    case Database = 'database';
    case Cloud = 'cloud';
    case Security = 'security';
    case BugReport = 'bug_report';
    case IntegrationInstructions = 'integration_instructions';
    case Hub = 'hub';
    case Memory = 'memory';
    case DeveloperBoard = 'developer_board';
    case Api = 'api';
    case DesignServices = 'design_services';
    case Brush = 'brush';
    case Palette = 'palette';
    case PhotoCamera = 'photo_camera';
    case Videocam = 'videocam';
    case Movie = 'movie';
    case MusicNote = 'music_note';
    case Edit = 'edit';
    case Language = 'language';
    case Translate = 'translate';
    case RecordVoiceOver = 'record_voice_over';
    case Campaign = 'campaign';
    case Forum = 'forum';
    case Groups = 'groups';
    case Handshake = 'handshake';
    case Diversity3 = 'diversity_3';
    case Calculate = 'calculate';
    case Science = 'science';
    case Biotech = 'biotech';
    case Psychology = 'psychology';
    case School = 'school';
    case MenuBook = 'menu_book';
    case AutoStories = 'auto_stories';
    case Lightbulb = 'lightbulb';
    case BusinessCenter = 'business_center';
    case TrendingUp = 'trending_up';
    case Monitoring = 'monitoring';
    case Insights = 'insights';
    case Assessment = 'assessment';
    case Gavel = 'gavel';
    case AccountBalance = 'account_balance';
    case Paid = 'paid';
    case Engineering = 'engineering';
    case Construction = 'construction';
    case PrecisionManufacturing = 'precision_manufacturing';
    case Build = 'build';
    case Settings = 'settings';
    case Plumbing = 'plumbing';
    case ElectricalServices = 'electrical_services';
    case SportsSoccer = 'sports_soccer';
    case FitnessCenter = 'fitness_center';
    case DirectionsRun = 'directions_run';
    case SelfImprovement = 'self_improvement';
    case SupportAgent = 'support_agent';
    case HeadsetMic = 'headset_mic';
    case MedicalServices = 'medical_services';
    case Restaurant = 'restaurant';
    case LocalShipping = 'local_shipping';
    case Flight = 'flight';
    case DirectionsCar = 'directions_car';
    case WorkspacePremium = 'workspace_premium';
    case Star = 'star';
    case MilitaryTech = 'military_tech';
    case RocketLaunch = 'rocket_launch';
    case Bolt = 'bolt';

    public static function default(): self
    {
        return self::WorkspacePremium;
    }
}
