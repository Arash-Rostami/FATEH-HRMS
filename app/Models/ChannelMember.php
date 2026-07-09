<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelMember extends Model
{
    use HasFactory;

    protected $primaryKey = ['user_id', 'channel_id'];

    public $incrementing = false;

    protected $fillable = [
        'channel_id',
        'user_id',
        'last_read_message_id',
        'joined_at',
        'entered_at',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lastReadMessage(): BelongsTo
    {
        return $this->belongsTo(ChannelMessage::class, 'last_read_message_id');
    }
}
