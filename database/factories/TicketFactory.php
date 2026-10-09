<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Profile;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    private static array $closedAssignees = [];

    public function configure(): static
    {
        return $this->afterMaking(function (Ticket $ticket) {
            if (blank($ticket->assigned_to) && $ticket->status === 'in-progress') {
                $ticket->assigned_to = User::factory()->create(['status' => 'active', 'role' => 'user'])->id;
            }

            if (blank($ticket->assigned_to)) {
                return;
            }

            $profile = Profile::where('user_id', $ticket->assigned_to)->first();
            $dept = $ticket->targetDepartmentId ?: $profile?->department_id ?: $ticket->effectiveTargetDepartment();

            if (!$dept) {
                $dept = Department::factory()->create()->code;
            }

            if (!$ticket->targetDepartmentId) {
                $ticket->extra = [...($ticket->extra ?? []), 'target_department' => $dept];
            }

            if (!$profile) {
                Profile::factory()->create(['user_id' => $ticket->assigned_to, 'department_id' => $dept]);
            } elseif ($profile->department_id !== $dept) {
                $profile->update(['department_id' => $dept]);
            }

            if (Ticket::isClosingStatus($ticket->status)) {
                self::$closedAssignees[spl_object_id($ticket)] = $ticket->assigned_to;
                $ticket->assigned_to = null;
            }
        })->afterCreating(function (Ticket $ticket) {
            if ($assignee = self::$closedAssignees[spl_object_id($ticket)] ?? null) {
                unset(self::$closedAssignees[spl_object_id($ticket)]);
                $ticket->forceFill(['assigned_to' => $assignee])->saveQuietly();
            }
        });
    }

    public function definition(): array
    {
        return [
            'requester_id' => User::inRandomOrder()->value('id') ?? User::factory(),
            'request_type' => fake()->randomElement(['support', 'access', 'development']),
            'request_area' => null,
            'request_subject' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'attachment' => null,
            'additional_notes' => null,
            'assigned_to' => null,
            'completion_deadline' => null,
            'completion_date' => null,
            'action_result' => null,
            'status' => 'open',
            'effectiveness' => null,
            'satisfaction_score' => null,
            'requester_files' => [],
            'assignee_files' => [],
            'extra' => [],
        ];
    }
}
