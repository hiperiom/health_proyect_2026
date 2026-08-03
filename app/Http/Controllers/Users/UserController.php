<?php

namespace App\Http\Controllers\Users;

use App\Enums\Gender;
use App\Enums\Nacionality;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\AssignRolesRequest;
use App\Http\Requests\Users\CheckEmailRequest;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UsersProfile;
use App\Notifications\UserCreatedTemporaryPassword;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
            ->with(['roles:id,slug,name,color_class,text_class,icon_svg', 'permissions:id,name,slug,module,description', 'usersProfile'])
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
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (User $user) {
                $entitledRoles = $user->entitledRoles();
                $primaryRole = $entitledRoles->first();
                $allRoles = $user->roles;
                $profile = $user->usersProfile->first();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status?->value,
                    'statusLabel' => $user->status?->label(),
                    'statusColorClass' => $user->status?->colorClass(),
                    'role' => $primaryRole?->slug,
                    'roleName' => $primaryRole?->name,
                    'role_ids' => $allRoles
                        ->pluck('id')
                        ->values()
                        ->all(),
                    'passwordUpdated' => $user->password_updated,
                    'roles' => $allRoles
                        ->map(fn (Role $r): array => [
                            'slug' => $r->slug,
                            'name' => $r->name,
                            'color_class' => $r->color_class,
                            'text_class' => $r->text_class,
                            'icon_svg' => $r->icon_svg,
                        ])
                        ->values()
                        ->all(),
                    'permission_ids' => $user->permissions
                        ->pluck('id')
                        ->values()
                        ->all(),
                    'usersProfileId' => $profile?->id,
                    'photoUrl' => $profile?->photo_url,
                    'firstName' => $profile?->first_name,
                    'lastName' => $profile?->last_name,
                    'nacionality' => $profile?->nacionality?->value,
                    'dni' => $profile?->dni,
                    'birthDate' => $profile?->birth_date?->toDateString(),
                    'gender' => $profile?->gender?->value,
                    'phoneMobile' => $profile?->phone_mobile,
                    'phoneLandline' => $profile?->phone_landline,
                    'createdAt' => $user->created_at?->toISOString(),
                    'updatedAt' => $user->updated_at?->toISOString(),
                ];
            });

        return Inertia::render('users/Index', [
            'items' => $users,
            'availableRoles' => $this->availableRoles(),
            'allPermissions' => $this->allPermissions(),
            'availableStatuses' => $this->availableStatuses(),
            'availableNacionalities' => $this->availableNacionalities(),
            'availableGenders' => $this->availableGenders(),
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

        DB::transaction(function () use ($data, $temporaryPassword) {
            $user = User::create([
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'email' => $data['email'],
                'status' => $data['status'],
                'password' => $temporaryPassword,
                'password_updated' => false,
            ]);

            $role = Role::query()->firstOrCreate(
                ['slug' => 'paciente'],
                ['name' => 'Paciente']
            );
            $user->roles()->sync([$role->id]);

            UsersProfile::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'nacionality' => $data['nacionality'],
                'dni' => $data['dni'],
                'birth_date' => $data['birth_date'],
                'gender' => $data['gender'],
                'phone_mobile' => $data['phone_mobile'],
                'phone_landline' => $data['phone_landline'] ?? null,
                'user_id' => $user->id,
                'created_by_user_id' => auth()->id(),
            ]);

            $user->notify(new UserCreatedTemporaryPassword($temporaryPassword));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);
        Inertia::flash('temporary_password', $temporaryPassword);

        return to_route('users.index');
    }

    public function edit(Request $request, User $user): Response
    {
        $user->load(['roles:id,slug,name', 'usersProfile']);
        $profile = $user->usersProfile->first();

        return Inertia::render('users/Index', [
            'item' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status?->value,
                'role_ids' => $user->roles->pluck('id')->values()->all(),
                'permission_ids' => $user->permissions
                    ->pluck('id')
                    ->values()
                    ->all(),
                'usersProfileId' => $profile?->id,
                'photoUrl' => $profile?->photo_url,
                'firstName' => $profile?->first_name,
                'lastName' => $profile?->last_name,
                'nacionality' => $profile?->nacionality?->value,
                'dni' => $profile?->dni,
                'birthDate' => $profile?->birth_date?->toDateString(),
                'gender' => $profile?->gender?->value,
                'phoneMobile' => $profile?->phone_mobile,
                'phoneLandline' => $profile?->phone_landline,
            ],
            'availableRoles' => $this->availableRoles(),
            'allPermissions' => $this->allPermissions(),
            'availableStatuses' => $this->availableStatuses(),
            'availableNacionalities' => $this->availableNacionalities(),
            'availableGenders' => $this->availableGenders(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $roleIds = $data['role_ids'] ?? null;
        unset($data['role_ids']);

        DB::transaction(function () use ($user, $data, $roleIds) {
            $email = $data['email'] ?? null;
            $firstName = $data['first_name'] ?? null;
            $lastName = $data['last_name'] ?? null;

            $userFields = [];
            if ($email !== null) {
                $userFields['email'] = $email;
            }
            if (array_key_exists('status', $data)) {
                $userFields['status'] = $data['status'];
            }
            if ($firstName !== null || $lastName !== null) {
                $profile = $user->usersProfile->first();
                $userFields['name'] = trim(
                    ($firstName ?? $profile?->first_name ?? '').' '.($lastName ?? $profile?->last_name ?? '')
                );
            }

            if ($userFields !== []) {
                $user->update($userFields);
            }

            $profileFields = collect($data)->only([
                'first_name',
                'last_name',
                'nacionality',
                'dni',
                'birth_date',
                'gender',
                'phone_mobile',
                'phone_landline',
            ])->filter(fn ($value) => $value !== null || array_key_exists('phone_landline', $data))->all();

            $profile = $user->usersProfile->first();

            if ($profile !== null && $profileFields !== []) {
                $profile->update($profileFields);
            }

            if ($roleIds !== null) {
                $user->roles()->sync($roleIds);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return to_route('users.index');
    }

    public function checkEmail(CheckEmailRequest $request): JsonResponse
    {
        $email = trim((string) $request->validated('email'));
        $ignoreUserId = $request->validated('ignore_id');

        $exists = User::query()
            ->when($ignoreUserId !== null, fn ($query) => $query->where('id', '!=', $ignoreUserId))
            ->where('email', $email)
            ->exists();

        return response()->json(['exists' => $exists]);
    }

    public function photoStore(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'file', 'image', 'mimes:png,jpg,jpeg', 'max:5120'],
        ]);

        $profile = $user->usersProfile->first();

        if ($profile === null) {
            return back();
        }

        $this->deletePhotoFile($profile);

        $extension = $request->file('photo')->getClientOriginalExtension();
        $filename = sprintf('%s-%s.%s', $profile->id, (string) Str::ulid(), strtolower($extension));
        $path = $request->file('photo')->storeAs(
            "users-profiles/photos/{$profile->id}",
            $filename,
            'public',
        );

        $profile->update(['photo_path' => $path]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile photo updated.')]);
        Inertia::flash('userProfilePhotoUrl', $profile->fresh()->photo_url);

        return back();
    }

    public function photoDestroy(User $user): RedirectResponse
    {
        $profile = $user->usersProfile->first();

        if ($profile === null) {
            return back();
        }

        $this->deletePhotoFile($profile);
        $profile->update(['photo_path' => null]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile photo removed.')]);
        Inertia::flash('userProfilePhotoUrl', null);

        return back();
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

    public function assignPermissions(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'permission_ids' => ['present', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        $permissionIds = array_values(array_unique($data['permission_ids'] ?? []));

        $user->permissions()->sync($permissionIds);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Permissions updated.')]);

        return to_route('users.index');
    }

    public function assignRoles(AssignRolesRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $roleIds = array_values(array_unique($data['role_ids'] ?? []));

        $user->roles()->sync($roleIds);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Roles updated.')]);

        return to_route('users.index');
    }

    protected function deletePhotoFile(UsersProfile $profile): void
    {
        if ($profile->photo_path && Storage::disk('public')->exists($profile->photo_path)) {
            Storage::disk('public')->delete($profile->photo_path);
        }
    }

    /**
     * @return array<int, array{id: int, name: string, slug: string, module: string, description: string|null}>
     */
    private function allPermissions(): array
    {
        return Permission::query()
            ->orderBy('module')
            ->orderBy('id')
            ->get(['id', 'name', 'slug', 'module', 'description'])
            ->map(fn (Permission $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'module' => $p->module,
                'description' => $p->description,
            ])
            ->all();
    }

    /** @return array<int, array{id: int, value: int, label: string, slug: string, color_class: string|null, text_class: string|null, icon_svg: string|null}> */
    private function availableRoles(): array
    {
        $slugs = array_map(fn (UserRole $r) => $r->value, UserRole::cases());

        $roles = Role::query()
            ->whereIn('slug', $slugs)
            ->get(['id', 'slug', 'name', 'color_class', 'text_class', 'icon_svg'])
            ->keyBy('slug');

        return collect(UserRole::cases())
            ->map(function (UserRole $role) use ($roles): array {
                $dbRole = $roles[$role->value] ?? null;

                return [
                    'id' => $dbRole?->id ?? 0,
                    'value' => $dbRole?->id ?? 0,
                    'label' => $role->label(),
                    'slug' => $role->value,
                    'color_class' => $dbRole?->color_class ?? null,
                    'text_class' => $dbRole?->text_class ?? null,
                    'icon_svg' => $dbRole?->icon_svg ?? null,
                ];
            })
            ->all();
    }

    /** @return array<int, array{value: string, label: string, colorClass: string}> */
    private function availableStatuses(): array
    {
        return collect(UserStatus::cases())
            ->map(fn (UserStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
                'colorClass' => $status->colorClass(),
            ])
            ->all();
    }

    /** @return array<int, array{value: string, label: string}> */
    private function availableNacionalities(): array
    {
        return collect(Nacionality::cases())
            ->map(fn (Nacionality $nacionality): array => [
                'value' => $nacionality->value,
                'label' => $nacionality->label(),
            ])
            ->all();
    }

    /** @return array<int, array{value: string, label: string}> */
    private function availableGenders(): array
    {
        return collect(Gender::cases())
            ->map(fn (Gender $gender): array => [
                'value' => $gender->value,
                'label' => $gender->label(),
            ])
            ->all();
    }
}
