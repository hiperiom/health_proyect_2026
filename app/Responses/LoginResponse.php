<?php

namespace App\Responses;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user && $user->roles()->count() > 1) {
            return redirect()->route('role.selection');
        }

        return redirect()->intended(route('dashboard'));
    }
}
