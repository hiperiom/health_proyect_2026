<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ForcePasswordUpdateController extends Controller
{
    /**
     * Show the force password update form.
     */
    public function show(Request $request): Response|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->password_updated) {
            return $this->redirectToDashboard($user);
        }

        return Inertia::render('auth/ForcePasswordUpdate');
    }

    /**
     * Handle the force password update.
     */
    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($request->password),
            'password_updated' => true,
        ]);

        return $this->redirectToDashboard($user);
    }

    /**
     * Redirect to the user's dashboard based on their current team.
     */
    private function redirectToDashboard(User $user): RedirectResponse
    {
        $team = $user->currentTeam ?? $user->personalTeam();

        if ($team) {
            URL::defaults(['current_team' => $team->slug]);

            return redirect("/{$team->slug}/dashboard");
        }

        return redirect('/');
    }
}
