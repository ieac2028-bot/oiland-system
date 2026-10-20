<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_renders(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Sign in');
    }

    public function test_a_verified_user_can_sign_in_and_out(): void
    {
        $user = User::factory()->customer()->create([
            'email' => 'driver@example.test',
            'password' => Hash::make('secret-password'),
        ]);

        $this->post(route('login'), [
            'email' => 'driver@example.test',
            'password' => 'secret-password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_sign_in_fails_with_a_wrong_password(): void
    {
        User::factory()->customer()->create([
            'email' => 'driver@example.test',
            'password' => Hash::make('secret-password'),
        ]);

        $this->post(route('login'), [
            'email' => 'driver@example.test',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_sign_in_requires_an_email_and_password(): void
    {
        $this->post(route('login'), [])->assertSessionHasErrors(['email', 'password']);
    }

    public function test_a_visitor_can_register_and_is_signed_in_as_a_customer(): void
    {
        Notification::fake();

        $this->post(route('register'), [
            'name' => 'New Driver',
            'email' => 'new.driver@example.test',
            'phone' => '+97150 555 9000',
            'password' => 'a-strong-password',
            'password_confirmation' => 'a-strong-password',
        ])->assertRedirect();

        $this->assertAuthenticated();
        $this->assertSame('customer', auth()->user()->role->value);
        $this->assertDatabaseHas('users', ['email' => 'new.driver@example.test']);
    }

    public function test_registration_rejects_a_duplicate_email(): void
    {
        User::factory()->customer()->create(['email' => 'taken@example.test']);

        $this->post(route('register'), [
            'name' => 'Impostor',
            'email' => 'taken@example.test',
            'password' => 'a-strong-password',
            'password_confirmation' => 'a-strong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_registration_rejects_a_password_below_the_minimum_length(): void
    {
        $this->post(route('register'), [
            'name' => 'Weak Password',
            'email' => 'weak@example.test',
            'password' => 'abc',
            'password_confirmation' => 'abc',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    public function test_an_unverified_user_is_sent_to_the_verification_notice(): void
    {
        $user = User::factory()->customer()->unverified()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_a_verified_user_passes_the_verification_gate(): void
    {
        $user = User::factory()->customer()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    public function test_logging_out_does_not_require_a_verified_email(): void
    {
        $user = User::factory()->customer()->unverified()->create();

        $this->actingAs($user)->post(route('logout'))->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_a_password_reset_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->customer()->create(['email' => 'forgetful@example.test']);

        $this->post(route('password.email'), ['email' => 'forgetful@example.test'])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_a_password_can_be_reset_with_a_valid_token(): void
    {
        $user = User::factory()->customer()->create(['email' => 'reset.me@example.test']);
        $token = Password::broker()->createToken($user);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => 'reset.me@example.test',
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('brand-new-password', $user->fresh()->password));
    }

    public function test_a_password_reset_with_an_invalid_token_is_refused(): void
    {
        $user = User::factory()->customer()->create(['email' => 'reset.me@example.test']);

        $this->post(route('password.store'), [
            'token' => 'not-a-real-token',
            'email' => 'reset.me@example.test',
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ]);

        $this->assertFalse(Hash::check('brand-new-password', $user->fresh()->password));
    }

    public function test_staff_reaching_the_customer_dashboard_never_see_a_customers_tickets(): void
    {
        $branch = Branch::factory()->create();
        $staff = User::factory()->branchStaff($branch)->create();
        $customer = User::factory()->customer()->create();

        Ticket::factory()->create([
            'customer_id' => $customer->id,
            'branch_id' => $branch->id,
            'subject' => 'A customer only ticket',
        ]);

        $this->actingAs($staff)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('A customer only ticket');
    }
}
