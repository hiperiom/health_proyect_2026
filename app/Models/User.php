<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Role> $roles
 * @property-read Collection<int, Permission> $permissions
 */
#[Fillable(['name', 'email', 'password', 'password_updated'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'password_updated' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * The roles assigned to the user.
     *
     * @return BelongsToMany<Role>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'users_roles')
            ->withTimestamps();
    }

    /**
     * The permissions assigned directly to the user.
     *
     * @return BelongsToMany<Permission>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'users_permissions')
            ->withTimestamps();
    }

    /**
     * Patients created by this user (i.e. patient records associated with
     * the user account through `created_by_user_id`).
     *
     * @return HasMany<Patients>
     */
    public function createdPatients(): HasMany
    {
        return $this->hasMany(Patients::class, 'created_by_user_id');
    }

    /**
     * Get the user's primary role (the only role, if any).
     */
    public function primaryRole(): ?Role
    {
        return $this->roles()->first();
    }

    /**
     * Get the list of permission slugs available to the user.
     *
     * - Superusuario gets every permission in the system.
     * - All other users get the slugs of their directly assigned permissions.
     *
     * @return array<int, string>
     */
    public function permissionSlugs(): array
    {
        if ($this->primaryRole()?->slug === 'superusuario') {
            return Permission::query()->pluck('slug')->all();
        }

        return $this->permissions()->pluck('slug')->all();
    }

    /**
     * Determine whether the user has a matching record in any of the
     * domain tables that justify the given role.
     *
     * For the `paciente` role, the user is considered to "own" a patient
     * record if they appear as the creator of any `Patients` row OR if a
     * patient row exists whose `email` matches the user's email.
     *
     * For staff roles (`doctor`, `enfermeria`, `asistencial`) we currently
     * do not have dedicated domain tables, so we fall back to the same
     * `createdPatients` association as a proxy: a staff user that has
     * created patient records is allowed to keep their role. This can be
     * tightened later by introducing proper staff tables.
     *
     * For administrative roles (`superusuario`, `administrador`) the user
     * is always considered to have a valid match because those roles
     * describe an organisational position rather than a domain record.
     */
    public function hasDomainRecordForRole(string $roleSlug): bool
    {
        return match ($roleSlug) {
            'paciente' => $this->createdPatients()->exists()
                || Patients::query()->where('email', $this->email)->exists(),
            'doctor', 'enfermeria', 'asistencial' => $this->createdPatients()->exists(),
            'superusuario', 'administrador' => true,
            default => false,
        };
    }

    /**
     * Return only the roles that the user is currently entitled to keep
     * because they have a matching domain record. Roles for which the
     * user has no domain record are dropped, so callers can render an
     * "Indefinido" placeholder for users without any entitled role.
     *
     * @return Collection<int, Role>
     */
    public function entitledRoles(): Collection
    {
        return $this->roles
            ->filter(fn (Role $role): bool => $this->hasDomainRecordForRole($role->slug))
            ->values();
    }
}
