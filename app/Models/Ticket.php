<?php

namespace App\Models;

use App\Models\Traits\HasTicketCountHelpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;
    use HasTicketCountHelpers;

    public static $requestTypeOptions = [
        'support' => 'Support',
        'access' => 'Access',
    ];

    public static $requestAreaOptions = [
        'support' => [
            '' => 'انتخاب نمایید',
            'windows_office' => 'Windows & Software',
            'vpn' => 'Internet, Wifi & VPN',
            'voip_telephone' => 'Telephone, VoIP & SIM Card',
            'printer_scanner' => 'Printer, Scanner & Copier Devices',
            'remote_working' => 'Remote Working & Forticlient',
            'host_domain' => 'Host, Domain & Website',
            'backups_restore' => 'Backup, Restore & Archive',
            'purchase_requests' => 'Item & Purchase Requests or Maintenance',
            'generate_bi_reports' => 'Generate or Modify Organizational Reports',
            'other' => 'Other'
        ],
        'access' => [
            '' => 'انتخاب نمایید',
            'bi' => 'BI',
            'rahkaran' => 'Rahkaran',
            'file_server' => 'File Server',
            'mizito' => 'Mizito',
            'chargoon' => 'Chargoon',
            'hrms' => 'HRMS',
            'bms' => 'BMS',
            'sarv_crm' => 'CRM',
            'email' => 'Email',
            'other' => 'Other',
        ],
    ];

    protected $fillable = [
        'requester_id',
        'request_type',
        'request_area',
        'request_subject',
        'description',
        'priority',
        'attachment',
        'additional_notes',
        'assigned_to',
        'completion_deadline',
        'completion_date',
        'action_result',
        'status',
        'effectiveness',
        'satisfaction_score',
        'requester_files',
        'assignee_files',
        'extra',
    ];

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function getRequestAreaOptions($requestType, $requestArea)
    {
        return self::$requestAreaOptions[$requestType][$requestArea] ?? 'Not Found';
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function setPriorityAttribute($value)
    {
        $this->attributes['priority'] = in_array(strtolower($value), ['low', 'medium', 'high'])
            ? strtolower($value) : 'low';
    }

    public function setRequestTypeAttribute($value)
    {
        $this->attributes['request_type'] = in_array(strtolower($value), ['support', 'access'])
            ? strtolower($value) : 'support';
    }

    public function setSatisfactionScoreAttribute($value)
    {
        $this->attributes['satisfaction_score'] = ($value >= 0 && $value <= 5) ? (float)$value : null;
    }

    public function setStatusAttribute($value)
    {
        $this->attributes['status'] = in_array(strtolower($value), ['open', 'closed', 'in-progress'])
            ? strtolower($value) : 'open';
    }

    protected function casts(): array
    {
        return [
            'extra' => 'array',
            'completion_deadline' => 'datetime',
            'completion_date' => 'datetime',
            'satisfaction_score' => 'float',
            'requester_files' => 'array',
            'assignee_files' => 'array',
        ];
    }
}
