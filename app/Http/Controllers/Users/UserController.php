<?php

namespace App\Http\Controllers\Users;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Notifications\UserCreatedTemporaryPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $role = trim((string) $request->query('role', ''));
        $perPage = (int) $request->query('per_page', 10);

        if (! in_array($perPage, [10, 50, 100], true)) {
            $perPage = 10;
        }

        $users = User::query()
            ->with('roles:id,slug,name')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $like = '%'.$search.'%';
                    $q->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            })
            ->when(in_array($role, UserRole::slugs(), true), function ($query) use ($role) {
                $query->whereHas('roles', function ($q) use ($role) {
                    $q->where('slug', $role);
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (User $user) {
                $primaryRole = $user->roles->first();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $primaryRole?->slug,
                    'roleName' => $primaryRole?->name,
                    'passwordUpdated' => $user->password_updated,
                    'createdAt' => $user->created_at?->toISOString(),
                    'updatedAt' => $user->updated_at?->toISOString(),
                ];
            });

        return Inertia::render('users/Index', [
            'items' => $users,
            'availableRoles' => $this->availableRoles(),
            'filters' => [
                'search' => $search,
                'role' => $role,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $temporaryPassword = Str::password(16);
        $data['password'] = $temporaryPassword;
        $data['password_updated'] = false;
        $roleSlug = $data['role'];
        unset($data['role']);

        $user = DB::transaction(function () use ($data, $roleSlug) {
            $user = User::create($data);

            $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
            $user->roles()->attach($role->id);

            return $user;
        });

        $user->notify(new UserCreatedTemporaryPassword($temporaryPassword));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);
        Inertia::flash('temporary_password', $temporaryPassword);

        return to_route('users.index');
    }

    public function edit(Request $request, User $user): Response
    {
        $user->load('roles:id,slug,name');

        return Inertia::render('users/Index', [
            'item' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first()?->slug,
            ],
            'availableRoles' => $this->availableRoles(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $roleSlug = $data['role'] ?? null;
        unset($data['role']);

        DB::transaction(function () use ($user, $data, $roleSlug) {
            if (! empty($data['password'])) {
                $data['password'] = bcrypt($data['password']);
            } else {
                unset($data['password']);
            }

            $user->update($data);

            if ($roleSlug !== null) {
                $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
                $user->roles()->sync([$role->id]);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return to_route('users.index');
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $temporaryPassword = Str::password(16);

        $user->update([
            'password' => $temporaryPassword,
            'password_updated' => false,
        ]);

        $user->notify(new UserCreatedTemporaryPassword($temporaryPassword));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Temporary password sent to the user.')]);

        return to_route('users.index');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User deleted.')]);

        return to_route('users.index');
    }

    /** @return array<int, array{value: string, label: string}> */
    private function availableRoles(): array
    {
        return collect(UserRole::cases())
            ->map(fn (UserRole $role): array => [
                'value' => $role->value,
                'label' => $role->label(),
            ])
            ->all();
    }
}
