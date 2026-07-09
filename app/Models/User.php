<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\Email\Concerns\InteractsWithEmailAuthentication;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'avatar_path', 'timezone', 'week_start', 'locale'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, HasAvatar, HasEmailAuthentication, OAuthenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, InteractsWithAppAuthentication, InteractsWithAppAuthenticationRecovery, InteractsWithEmailAuthentication, Notifiable;

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_path ? Storage::disk('uploads')->url($this->avatar_path) : null;
    }

    /**
     * The tenant app panel is open to any authenticated member; the central
     * /admin panel is restricted to the super-admin allowlist.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin' ? $this->isSuperAdmin() : true;
    }

    /** Whether this user is on the super-admin allowlist (config, not the DB). */
    public function isSuperAdmin(): bool
    {
        return $this->email !== null
            && in_array(mb_strtolower($this->email), config('superadmin.emails', []), true);
    }

    /**
     * Whether this user may use the app during the private beta (approved,
     * super admin, or beta mode disabled). See Services\Auth\BetaAccess.
     */
    public function hasBetaAccess(): bool
    {
        return ! config('beta.enabled')
            || $this->approved_at !== null
            || $this->isSuperAdmin();
    }

    /**
     * CENTRAL model — users live in the central DB. Pin the connection so auth
     * lookups aren't redirected to a tenant DB while tenancy is initialized.
     */
    protected $connection = 'mysql';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'password' => 'hashed',
            'dashboard_widgets' => 'array',
        ];
    }

    /**
     * Workspaces this user belongs to (central pivot workspace_user).
     */
    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_user')
            ->withPivot(['role', 'membership_type', 'permissions'])
            ->withTimestamps();
    }

    /**
     * Location ids this user is restricted to in the given workspace, or null
     * for full access. Stored in the central workspace_user.permissions JSON.
     */
    public function allowedLocationIds(string $workspaceId): ?array
    {
        $permissions = DB::connection('mysql')
            ->table('workspace_user')
            ->where('user_id', $this->id)
            ->where('workspace_id', $workspaceId)
            ->value('permissions');

        $ids = $permissions ? (json_decode($permissions, true)['allowed_locations'] ?? null) : null;

        return (is_array($ids) && $ids !== []) ? array_map('intval', $ids) : null;
    }
}
