<?php

namespace Tests\Feature;

use App\Enums\FuelType;
use App\Models\Branch;
use App\Models\CatalogItem;
use App\Models\Review;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerJourneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_from_customer_pages(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('my.tickets.index'))->assertRedirect(route('login'));
        $this->get(route('my.reviews.index'))->assertRedirect(route('login'));
        $this->get(route('my.branches.index'))->assertRedirect(route('login'));
    }

    public function test_customer_dashboard_renders(): void
    {
        $customer = User::factory()->customer()->create();
        $branch = Branch::factory()->create();
        $customer->followedBranches()->attach($branch);

        $this->actingAs($customer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($branch->name);
    }

    public function test_customer_can_open_an_inquiry_and_it_is_visible_to_them(): void
    {
        $customer = User::factory()->customer()->create();
        $branch = Branch::factory()->create();

        $this->actingAs($customer)
            ->post(route('my.tickets.store'), [
                'branch_id' => $branch->id,
                'subject' => 'Card terminal declined twice',
                'category' => 'billing',
                'priority' => 'high',
                'message' => 'The card terminal declined my card on two separate fills tonight.',
            ])
            ->assertRedirect();

        $ticket = Ticket::query()->where('customer_id', $customer->id)->firstOrFail();

        $this->assertSame($branch->id, $ticket->branch_id);
        $this->assertSame('pending', $ticket->status->value);
        $this->assertStringStartsWith('TKT-', $ticket->reference);

        $this->actingAs($customer)
            ->get(route('my.tickets.show', $ticket))
            ->assertOk()
            ->assertSee($ticket->reference)
            ->assertSee('Card terminal declined twice');
    }

    public function test_customer_cannot_open_an_inquiry_with_an_invalid_category(): void
    {
        $customer = User::factory()->customer()->create();
        $branch = Branch::factory()->create();

        $this->actingAs($customer)
            ->post(route('my.tickets.store'), [
                'branch_id' => $branch->id,
                'subject' => 'Testing validation',
                'category' => 'not_a_category',
                'priority' => 'high',
                'message' => 'This should not be accepted because the category is invalid.',
            ])
            ->assertSessionHasErrors('category');

        $this->assertSame(0, Ticket::query()->count());
    }

    public function test_customer_replies_to_a_ticket_are_public_but_internal_notes_stay_hidden(): void
    {
        $customer = User::factory()->customer()->create();
        $branch = Branch::factory()->create();
        $agent = User::factory()->branchStaff($branch)->create();
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'branch_id' => $branch->id,
        ]);

        TicketReply::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'message' => 'We pulled the pump totals and found the discrepancy.',
            'is_internal_note' => false,
        ]);

        TicketReply::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'message' => 'INTERNAL ONLY: pump four was miscalibrated by 4 litres.',
            'is_internal_note' => true,
        ]);

        $this->actingAs($customer)
            ->get(route('my.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('We pulled the pump totals')
            ->assertDontSee('INTERNAL ONLY');
    }

    public function test_customer_can_reply_to_their_own_open_ticket(): void
    {
        $customer = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'in_progress',
        ]);

        $this->actingAs($customer)
            ->from(route('my.tickets.show', $ticket))
            ->post(route('my.tickets.replies.store', $ticket), [
                'message' => 'Adding the invoice number for reference: INV-2291.',
            ])
            ->assertRedirect(route('my.tickets.show', $ticket))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('ticket_replies', [
            'ticket_id' => $ticket->id,
            'user_id' => $customer->id,
            'message' => 'Adding the invoice number for reference: INV-2291.',
        ]);
    }

    public function test_customer_cannot_read_another_customers_ticket(): void
    {
        $owner = User::factory()->customer()->create();
        $intruder = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create(['customer_id' => $owner->id]);

        $this->actingAs($intruder)
            ->get(route('my.tickets.show', $ticket))
            ->assertForbidden();
    }

    public function test_customer_can_leave_a_review_and_await_moderation(): void
    {
        $customer = User::factory()->customer()->create();
        $branch = Branch::factory()->create();
        $item = CatalogItem::factory()->product(FuelType::Unleaded)->create();
        $branch->catalogItems()->attach($item, ['is_available' => true]);

        $this->actingAs($customer)
            ->post(route('reviews.store', $branch), [
                'rating' => 5,
                'title' => 'Excellent service',
                'body' => 'Attendants were quick and the prices matched the board exactly.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('reviews', [
            'branch_id' => $branch->id,
            'user_id' => $customer->id,
            'status' => 'pending',
        ]);
    }

    public function test_customer_submitting_a_second_review_updates_the_first(): void
    {
        $customer = User::factory()->customer()->create();
        $branch = Branch::factory()->create();

        Review::factory()->create([
            'branch_id' => $branch->id,
            'user_id' => $customer->id,
            'status' => 'published',
            'rating' => 3,
        ]);

        $this->actingAs($customer)
            ->post(route('reviews.store', $branch), [
                'rating' => 4,
                'title' => 'Updated thoughts',
                'body' => 'Second visit was noticeably better than the first.',
            ])
            ->assertRedirect();

        $this->assertSame(1, Review::query()->count());
        $this->assertSame(4, Review::query()->firstOrFail()->rating);
    }

    public function test_customer_can_withdraw_their_own_review(): void
    {
        $customer = User::factory()->customer()->create();
        $review = Review::factory()->create(['user_id' => $customer->id]);

        $this->actingAs($customer)
            ->delete(route('reviews.destroy', $review))
            ->assertRedirect();

        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    public function test_customer_cannot_withdraw_another_customers_review(): void
    {
        $review = Review::factory()->create();

        $this->actingAs(User::factory()->customer()->create())
            ->delete(route('reviews.destroy', $review))
            ->assertForbidden();

        $this->assertDatabaseHas('reviews', ['id' => $review->id]);
    }

    public function test_customer_can_follow_and_unfollow_a_branch(): void
    {
        $customer = User::factory()->customer()->create();
        $branch = Branch::factory()->create();

        $this->actingAs($customer)
            ->post(route('branches.follow', $branch))
            ->assertRedirect();

        $this->assertDatabaseHas('branch_followers', [
            'user_id' => $customer->id,
            'branch_id' => $branch->id,
        ]);

        $this->actingAs($customer)
            ->delete(route('branches.unfollow', $branch))
            ->assertRedirect();

        $this->assertDatabaseMissing('branch_followers', [
            'user_id' => $customer->id,
            'branch_id' => $branch->id,
        ]);
    }

    public function test_followed_branches_page_only_offers_unfollow_for_the_customers_own_follows(): void
    {
        $customer = User::factory()->customer()->create();
        $mine = Branch::factory()->create(['name' => 'My Usual Station']);
        $someoneElses = Branch::factory()->create(['name' => 'Someone Elses Station']);

        $customer->followedBranches()->attach($mine);
        User::factory()->customer()->create()->followedBranches()->attach($someoneElses);

        $response = $this->actingAs($customer)->get(route('my.branches.index'));

        $response->assertOk()->assertSee('My Usual Station');

        // A branch followed by a different customer may only be offered as a
        // suggestion, never as something the viewer can unfollow.
        $this->assertSame(
            1,
            substr_count($response->getContent(), 'Unfollow'),
            'Only the viewer\'s own followed branch should render an unfollow button.',
        );

        $this->assertStringContainsString(
            'Someone Elses Station',
            $response->getContent(),
            'Unfollowed branches should still be offered as suggestions.',
        );
    }

    public function test_only_published_unflagged_reviews_are_visible_on_the_public_branch_page(): void
    {
        $branch = Branch::factory()->create();

        Review::factory()->create([
            'branch_id' => $branch->id,
            'status' => 'published',
            'is_flagged' => false,
            'body' => 'Published and visible to everyone.',
        ]);

        Review::factory()->create([
            'branch_id' => $branch->id,
            'status' => 'pending',
            'body' => 'Still awaiting moderation.',
        ]);

        Review::factory()->create([
            'branch_id' => $branch->id,
            'status' => 'published',
            'is_flagged' => true,
            'body' => 'Flagged and under review.',
        ]);

        $this->get(route('branches.show', $branch))
            ->assertOk()
            ->assertSee('Published and visible to everyone.')
            ->assertDontSee('Still awaiting moderation.')
            ->assertDontSee('Flagged and under review.');
    }
}
