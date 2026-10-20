<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use App\Services\ActivityLogger;
use App\Support\ActivityAction;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            ...$validated,
            // Public registration always creates a customer account; staff
            // accounts are provisioned by a Super Admin from the user screen.
            'role' => UserRole::Customer->value,
            'branch_id' => null,
        ]);

        $this->activityLogger->log(
            ActivityAction::USER_CREATED,
            "{$user->name} registered a customer account.",
            $user,
            ['role' => $user->role->value],
            $user,
        );

        event(new Registered($user));
        $user->notify(new WelcomeNotification($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->intended(route('dashboard'))
            ->with('status', 'Welcome aboard! Your customer account is ready.');
    }
}
