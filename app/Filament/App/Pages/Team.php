<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Mail\InviteMail;
use App\Models\Invitation;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use App\Models\Workspace;
use App\Services\ActivityLog\ActivityLogger;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class Team extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 80;

    protected static ?string $slug = 'team';

    protected string $view = 'filament.app.pages.team';

    /** Roles defined in the current workspace (name => Title). */
    protected function roleOptions(): array
    {
        return Role::query()
            ->where('team_id', $this->workspace()->id)
            ->orderBy('name')
            ->pluck('name', 'name')
            ->map(fn (string $name): string => Str::headline($name))
            ->all();
    }

    public static function getNavigationLabel(): string
    {
        return __('nav.team');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return tenancy()->initialized && auth()->user()?->can('manage_team');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage_team') ?? false;
    }

    protected function workspace(): Workspace
    {
        return once(fn () => Workspace::findOrFail(session('current_workspace_id')));
    }

    protected function applyTeamScope(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->workspace()->id);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => User::query()
                ->whereIn('id', $this->workspace()->users()->pluck('users.id')))
            ->columns([
                TextColumn::make('name')->label(__('pages/team.col_name'))->searchable()->sortable(),
                TextColumn::make('email')->label(__('pages/team.col_email'))->searchable(),
                TextColumn::make('role')
                    ->label(__('pages/team.col_role'))
                    ->badge()
                    ->state(fn (User $record): string => $this->roleOf($record))
                    ->color(fn (string $state): string => match ($state) {
                        'owner' => 'success',
                        'admin' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label(__('pages/team.edit'))
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->fillForm(fn (User $record): array => [
                        'name' => $record->name,
                        'email' => $record->email,
                        'allowed_locations' => $this->allowedLocationsOf($record),
                    ])
                    ->schema([
                        TextInput::make('name')->required()->maxLength(120),
                        TextInput::make('email')->email()->required()->maxLength(160),
                        Select::make('allowed_locations')
                            ->label(__('pages/team.location_access'))
                            ->multiple()
                            ->options(fn (): array => Location::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->placeholder(__('common.all_locations'))
                            ->helperText(__('pages/team.location_access_helper')),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->forceFill(['name' => $data['name'], 'email' => $data['email']])->save();

                        $this->workspace()->users()->updateExistingPivot($record->id, [
                            'permissions' => json_encode(['allowed_locations' => array_values($data['allowed_locations'] ?? [])]),
                        ]);

                        Notification::make()->title(__('pages/team.member_updated'))->success()->send();
                    }),

                Action::make('changeRole')
                    ->label(__('pages/team.change_role'))
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                    ->schema([
                        Select::make('role')->options($this->roleOptions())->required()
                            ->default(fn (User $record): string => $this->roleOf($record) ?: 'member'),
                    ])
                    ->action(function (User $record, array $data): void {
                        $this->applyTeamScope();
                        $record->syncRoles([$data['role']]);
                        // Keep the pivot role in sync so notification role-groups stay accurate.
                        $this->workspace()->users()->updateExistingPivot($record->id, ['role' => $data['role']]);
                        ActivityLogger::log('team.role_changed', ['member' => $record->name, 'role' => $data['role']]);
                        Notification::make()->title(__('pages/team.role_updated', ['role' => $data['role']]))->success()->send();
                    }),

                Action::make('remove')
                    ->label(__('pages/team.remove'))
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => $record->id !== auth()->id())
                    ->action(function (User $record): void {
                        $this->applyTeamScope();
                        $record->syncRoles([]);
                        $this->workspace()->users()->detach($record->id);
                        ActivityLogger::log('team.member_removed', ['member' => $record->name]);
                        Notification::make()->title(__('pages/team.member_removed'))->success()->send();
                    }),
            ])
            ->headerActions([
                Action::make('addMember')
                    ->label(__('pages/team.add_member'))
                    ->icon(Heroicon::OutlinedUserPlus)
                    ->schema([
                        TextInput::make('email')->email()->required()
                            ->helperText(__('pages/team.add_member_email_helper')),
                        // Guests are added through their own action (no login invite).
                        Select::make('role')->options(collect($this->roleOptions())->except('guest')->all())->default('member')->required(),
                        Select::make('allowed_locations')
                            ->label(__('pages/team.location_access'))
                            ->multiple()
                            ->options(fn (): array => Location::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->placeholder(__('common.all_locations'))
                            ->helperText(__('pages/team.location_access_helper')),
                        // The invite email goes out in this language and the
                        // account adopts it on accept (notifications, reports).
                        Select::make('locale')
                            ->label(__('pages/team.guest_language'))
                            ->options(['en' => 'English', 'de' => 'Deutsch'])
                            ->default(fn (): string => in_array(app()->getLocale(), ['en', 'de'], true) ? app()->getLocale() : 'en')
                            ->selectablePlaceholder(false)
                            ->helperText(__('pages/team.guest_language_helper')),
                    ])
                    ->action(function (array $data): void {
                        $workspace = $this->workspace();
                        $email = mb_strtolower(trim($data['email']));

                        $locale = in_array($data['locale'] ?? null, ['en', 'de'], true) ? $data['locale'] : 'en';
                        $locationIds = array_values(array_map('intval', $data['allowed_locations'] ?? []));

                        $invitation = Invitation::updateOrCreate(
                            ['workspace_id' => $workspace->id, 'email' => $email],
                            [
                                'token' => Invitation::makeToken(),
                                'role' => $data['role'],
                                'locale' => $locale,
                                'location_ids' => $locationIds ?: null,
                                'invited_by' => auth()->id(),
                                'expires_at' => now()->addDays(14),
                                'accepted_at' => null,
                            ],
                        );

                        Mail::to($email)->send(new InviteMail(
                            inviterName: (string) auth()->user()?->name,
                            workspaceName: $workspace->name,
                            acceptUrl: route('invite.show', $invitation->token),
                            role: $data['role'],
                            lang: $locale,
                        ));

                        ActivityLogger::log('team.member_invited', ['email' => $email, 'role' => $data['role']]);
                        Notification::make()->title(__('pages/team.invitation_sent'))->success()->send();
                    }),

                // A Guest is a notification-only contact: no login invite, no
                // password, no permissions — just selectable as an email recipient.
                Action::make('addGuest')
                    ->label(__('pages/team.add_guest'))
                    ->icon(Heroicon::OutlinedBellAlert)
                    ->color('gray')
                    ->schema([
                        TextInput::make('name')->label(__('pages/team.name'))->required()->maxLength(120),
                        TextInput::make('email')->email()->required()
                            ->helperText(__('pages/team.add_guest_helper')),
                        Select::make('allowed_locations')
                            ->label(__('pages/team.location_access'))
                            ->multiple()
                            ->options(fn (): array => Location::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->placeholder(__('common.all_locations'))
                            ->helperText(__('pages/team.guest_location_helper')),
                        Select::make('locale')
                            ->label(__('pages/team.guest_language'))
                            ->options(['en' => 'English', 'de' => 'Deutsch'])
                            ->default(fn (): string => app()->getLocale())
                            ->selectablePlaceholder(false)
                            ->helperText(__('pages/team.guest_language_helper')),
                    ])
                    ->action(function (array $data): void {
                        $workspace = $this->workspace();
                        $email = mb_strtolower(trim($data['email']));

                        $locale = in_array($data['locale'] ?? null, ['en', 'de'], true) ? $data['locale'] : app()->getLocale();
                        $locationIds = array_values(array_map('intval', $data['allowed_locations'] ?? []));

                        $user = User::firstOrCreate(
                            ['email' => $email],
                            [
                                'name' => $data['name'],
                                'password' => Hash::make(Str::random(40)),
                                'locale' => $locale,
                            ],
                        );

                        // Guests have no login to change the language themselves —
                        // apply the picked one to an existing guest account too.
                        if (! $user->wasRecentlyCreated && $user->getAttribute('locale') !== $locale) {
                            $user->forceFill(['locale' => $locale])->save();
                        }

                        if (! $user->wasRecentlyCreated && filled($data['name'])) {
                            $user->forceFill(['name' => $data['name']])->save();
                        }

                        $workspace->users()->syncWithoutDetaching([
                            $user->id => [
                                'role' => 'guest',
                                'membership_type' => 'guest',
                                'permissions' => json_encode(['allowed_locations' => $locationIds]),
                            ],
                        ]);

                        $this->applyTeamScope();
                        $user->unsetRelation('roles');
                        $user->syncRoles(['guest']);

                        ActivityLogger::log('team.guest_added', ['member' => $data['name'], 'email' => $email]);
                        Notification::make()->title(__('pages/team.guest_added'))->success()->send();
                    }),
            ]);
    }

    /** @return array<int, int> location ids the user is limited to ([] = all). */
    // ── Pending invitations ─────────────────────────────────────────────────

    /** Sent but not yet accepted invitations of this workspace. */
    public function pendingInvitations(): Collection
    {
        return Invitation::query()
            ->where('workspace_id', $this->workspace()->id)
            ->whereNull('accepted_at')
            ->latest('created_at')
            ->get();
    }

    /** Send the invite email again and extend its validity window. */
    public function resendInvitation(int $invitationId): void
    {
        $invitation = Invitation::query()
            ->where('workspace_id', $this->workspace()->id)
            ->whereNull('accepted_at')
            ->find($invitationId);

        if ($invitation === null) {
            return;
        }

        $invitation->forceFill(['expires_at' => now()->addDays(14)])->save();

        Mail::to($invitation->email)->send(new InviteMail(
            inviterName: (string) auth()->user()?->name,
            workspaceName: $this->workspace()->name,
            acceptUrl: route('invite.show', $invitation->token),
            role: (string) $invitation->role,
            lang: in_array($invitation->locale, ['en', 'de'], true) ? $invitation->locale : 'en',
        ));

        ActivityLogger::log('team.invite_resent', ['email' => $invitation->email]);
        Notification::make()->title(__('pages/team.invitation_sent'))->success()->send();
    }

    /** The invitation whose revoke-confirmation modal is open. */
    public ?int $revokingInvitationId = null;

    public function confirmRevokeInvitation(int $invitationId): void
    {
        $this->revokingInvitationId = $invitationId;
        $this->mountAction('revokeInvitation');
    }

    public function revokeInvitationAction(): Action
    {
        return Action::make('revokeInvitation')
            ->requiresConfirmation()
            ->modalHeading(__('pages/team.invite_revoke'))
            ->modalDescription(__('pages/team.invite_revoke_desc'))
            ->modalSubmitActionLabel(__('pages/team.invite_revoke'))
            ->color('danger')
            ->action(function (): void {
                $invitation = Invitation::query()
                    ->where('workspace_id', $this->workspace()->id)
                    ->whereNull('accepted_at')
                    ->find($this->revokingInvitationId);

                if ($invitation === null) {
                    return;
                }

                ActivityLogger::log('team.invite_revoked', ['email' => $invitation->email]);
                $invitation->delete();
                Notification::make()->title(__('pages/team.invite_revoked'))->success()->send();
            });
    }

    protected function allowedLocationsOf(User $user): array
    {
        $perms = $this->workspace()->users()->where('users.id', $user->id)->first()?->pivot->permissions;
        $arr = $perms ? (json_decode($perms, true)['allowed_locations'] ?? []) : [];

        return is_array($arr) ? array_map('intval', $arr) : [];
    }

    /** The user's role name in the current workspace, or '' if none. */
    protected function roleOf(User $user): string
    {
        $this->applyTeamScope();
        $user->unsetRelation('roles');

        return (string) ($user->getRoleNames()->first() ?? '');
    }
}
