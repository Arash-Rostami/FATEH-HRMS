<?php

namespace App\Models;

use App\Enums\PresenceStatus;
use App\Models\Traits\HasProfileHierarchy;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements HasAvatar
{
    use HasFactory, Notifiable, HasProfileHierarchy;

    protected $fillable = [
        'name',
        'email',
        'password',
        'maximum',
        'type',
        'role',
        'status',
        'presence',
        'booking',
        'last_seen',
        'extra',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];


    public function permits(string $module, string $action): bool
    {
        return (bool) Permission::forUser($this->id)?->can($module, $action);

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class);

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    public function faqs(): HasMany
    {
        return $this->hasMany(FAQ::class);

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    public function feeds(): HasMany
    {
        return $this->hasMany(Feed::class);

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    public static function getCachedActiveOptions(): Collection
    {
        return once(fn() => Cache::remember('user_active_options',
            now()->addHour(),
            fn() => self::active()->orderBy('name')->pluck('name', 'id')
        ));

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    public static function getCachedAllOptions(): Collection
    {
        return once(fn() => Cache::remember('user_all_options',
            now()->addHours(6),
            fn() => self::orderBy('name')->pluck('name', 'id')
        ));

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    public static function getCachedNames(): Collection
    {
        return once(fn() => Cache::remember('user_names_map',
            now()->addHour(),
            fn() => self::orderBy('name')->pluck('name', 'id')
        ));

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    public function getExtraValue(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->extra ?? [], $key, $default);

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    public function getFilamentAvatarUrl(): ?string
    {
        $image = $this->profile?->image;

        if (!$image) return null;

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

        return Storage::disk('public')->url($image);

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    public function getTodaysDeskExtension(): ?string
    {
        return null;

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    public function isActive(): bool
    {
        return $this->status === 'active';

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    public function isAdmin(): bool
    {
        return $this->role === 'admin' || $this->role === 'developer';

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    public function isOnline(int $minutes = 5): bool
    {
        return ($this->last_seen && $this->last_seen->gte(now()->subMinutes($minutes))) || $this->isActive();

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    public function latestEnergyTest()
    {
        return $this->hasOne(EnergyTest::class)->latestOfMany('completed_at');

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    public function permissions(): HasOne
    {
        return $this->hasOne(Permission::class);

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    public function reservations(): HasMany { return $this->hasMany(Reservation::class);
    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    public function scopeActive($query)
    {
        return $query->where('status', 'active');

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    public function scopeOnline($query, int $minutes = 5)
    {
        return $query->where('last_seen', '>=', now()->subMinutes($minutes));

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    public function scopeSearch(Builder $query, string $term): void
    {
        $query->when($term, fn($q) => $q->where(fn($q) => $q
            ->where('name', 'like', "%{$term
    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}%")
            ->orWhereHas('profile', fn($p) => $p
                ->where('position', 'like', "%{$term
    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}%")
                ->orWhereHas('department', fn($d) => $d->whereAny(['name', 'code', 'description'], 'like', "%{$term
    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}%"))
            )
        ));

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    public function scopeWithRole($query, string $role)
    {
        return $query->where('role', $role);

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    public function setExtraValue(string $key, mixed $value): void
    {
        $extra = $this->extra ?? [];
        Arr::set($extra, $key, $value);
        $this->extra = $extra;

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    public function suggestions(): HasMany
    {
        return $this->hasMany(Suggestion::class);

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    public function touchLastSeen(): void
    {
        $this->update(['last_seen' => now()]);

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    protected static function booted(): void
    {
        $flushCache = fn() => collect([
            'user_active_options',
            'user_all_options',
            'user_names_map',
        ])->each(Cache::forget(...));

        static::saved($flushCache);
        static::deleted($flushCache);

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}


    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_seen' => 'datetime',
            'extra' => 'array',
            'maximum' => 'integer',
            'booking' => 'array',
            'presence' => PresenceStatus::class,
        ];

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    protected function smsNumber(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->profile?->cellphone
        );

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}

    public function assignedTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function requestedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Read::class, 'user_id');
    }

    public function onboardings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Onboarding::class, 'user_id');
    }

    public function authority(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Authority::class, 'user_id');
    }

    public function cancelledReservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by_id');
    }
}
