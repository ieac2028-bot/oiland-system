<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Branch;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => Ticket::generateReference(),
            'customer_id' => User::factory()->customer(),
            'branch_id' => Branch::factory(),
            'assigned_to' => null,
            'subject' => ucfirst(fake()->sentence(5)),
            'message' => fake()->paragraph(),
            'category' => fake()->randomElement(array_keys(Ticket::CATEGORIES)),
            'priority' => TicketPriority::Normal->value,
            'status' => TicketStatus::Pending->value,
            'resolved_at' => null,
            'resolved_by' => null,
        ];
    }

    public function inProgress(?User $assignee = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketStatus::InProgress->value,
            'assigned_to' => $assignee?->id ?? User::factory()->branchStaff(),
        ]);
    }

    public function resolved(?User $resolver = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketStatus::Resolved->value,
            'assigned_to' => $resolver?->id ?? User::factory()->branchStaff(),
            'resolved_by' => $resolver?->id ?? User::factory()->branchStaff(),
            'resolved_at' => now(),
        ]);
    }

    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => TicketPriority::Urgent->value,
        ]);
    }
}
