<?php

namespace App\Models;

use App\Models\Traits\HasTicketCountHelpers;
use App\Models\Traits\HasTicketOptions;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;
    use HasTicketCountHelpers;
    use HasTicketOptions;

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

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
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

    protected function priority(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                $scalar = $value instanceof \BackedEnum ? $value->value : (string)$value;
                return in_array(strtolower($scalar), ['low', 'medium', 'high'])
                    ? strtolower($scalar)
                    : 'low';
            }
        );
    }

    protected function requestType(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                $scalar = $value instanceof \BackedEnum ? $value->value : (string)$value;
                return in_array(strtolower($scalar), ['support', 'access', 'development'])
                    ? strtolower($scalar)
                    : 'support';
            }
        );
    }

    protected function satisfactionScore(): Attribute
    {
        return Attribute::make(
            set: fn($value) => ($value >= 0 && $value <= 5) ? (float)$value : null
        );
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                $scalar = $value instanceof \BackedEnum ? $value->value : (string)$value;
                return in_array(strtolower($scalar), ['open', 'closed', 'in-progress'])
                    ? strtolower($scalar)
                    : 'open';
            }
        );
    }
}
