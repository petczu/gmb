<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ReportSchedules\Schemas;

use App\Models\Competitor;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Notifications\NotificationRecipients;
use App\Support\ReportBlocks;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

class ReportScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/report_schedules.schedule_section'))->schema([
                TextInput::make('name')->required()->maxLength(120)->default(__('resources/report_schedules.default_name')),

                Toggle::make('enabled')->default(true),

                Grid::make(2)->schema([
                    Select::make('frequency')
                        ->label(__('resources/report_schedules.frequency'))
                        ->options(['monthly' => __('resources/report_schedules.frequency_monthly_opt'), 'weekly' => __('resources/report_schedules.frequency_weekly_opt')])
                        ->default('monthly')
                        ->selectablePlaceholder(false)
                        ->live()
                        ->required(),

                    TextInput::make('send_day')
                        ->label(__('resources/report_schedules.day_of_month'))
                        ->numeric()->minValue(1)->maxValue(28)->default(1)
                        ->helperText(__('resources/report_schedules.day_of_month_helper'))
                        ->visible(fn (callable $get): bool => $get('frequency') === 'monthly')
                        ->required(),

                    Select::make('send_day')
                        ->label(__('resources/report_schedules.day_of_week'))
                        ->options([
                            1 => __('resources/report_schedules.monday'),
                            2 => __('resources/report_schedules.tuesday'),
                            3 => __('resources/report_schedules.wednesday'),
                            4 => __('resources/report_schedules.thursday'),
                            5 => __('resources/report_schedules.friday'),
                            6 => __('resources/report_schedules.saturday'),
                            7 => __('resources/report_schedules.sunday'),
                        ])
                        ->default(1)
                        ->selectablePlaceholder(false)
                        ->visible(fn (callable $get): bool => $get('frequency') === 'weekly')
                        ->required(),
                ]),
            ]),

            Section::make(__('resources/report_schedules.contents_section'))->schema([
                Grid::make(2)->schema([
                    Select::make('period')
                        ->label(__('resources/report_schedules.period'))
                        ->options(__('common.periods_no_custom'))
                        ->default('last_month')
                        ->selectablePlaceholder(false)
                        ->required(),

                    Select::make('location_ids')
                        ->label(__('resources/report_schedules.location'))
                        ->placeholder(__('common.all_locations'))
                        ->multiple()
                        ->options(fn (): array => Location::query()->orderBy('name')->pluck('name', 'id')->all()),

                    Select::make('language')
                        ->label(__('resources/report_schedules.language'))
                        ->options(['en' => 'English', 'de' => 'Deutsch'])
                        ->default('en')
                        ->selectablePlaceholder(false)
                        ->required(),
                ]),

                Toggle::make('compare')->label(__('resources/report_schedules.compare'))->default(true),

                // Same content blocks as the builder's "Report content" section.
                // Empty selection falls back to the workspace default.
                CheckboxList::make('blocks')
                    ->label(__('pages/reports.blocks'))
                    ->options(ReportBlocks::labels())
                    ->default(ReportBlocks::default())
                    // No competitors tracked → the block would render empty;
                    // disable it and say where to set them up instead.
                    ->disableOptionWhen(fn (string $value): bool => $value === 'competitors' && ! Competitor::query()->exists())
                    ->descriptions(fn (): array => Competitor::query()->exists() ? [] : ['competitors' => __('pages/reports.competitors_block_hint')])
                    ->columns(2)
                    ->bulkToggleable(),

                // Recipients by role/member (Included minus Excluded, same
                // model as the Notifications page). Empty selection falls back
                // to every workspace member.
                Select::make('recipients.include')
                    ->label(__('resources/report_schedules.recipients_include'))
                    ->placeholder(__('resources/report_schedules.recipients_all'))
                    ->multiple()
                    ->options(fn (): array => self::recipientOptions()),

                Select::make('recipients.exclude')
                    ->label(__('resources/report_schedules.recipients_exclude'))
                    ->placeholder(__('resources/report_schedules.recipients_none'))
                    ->multiple()
                    ->options(fn (): array => self::peopleOptions())
                    ->helperText(__('resources/report_schedules.recipients_helper')),
            ]),
        ]);
    }

    /**
     * Grouped recipient options for the "Included" select: role groups
     * ("Everyone", per-role) plus individual members.
     *
     * @return array<string, array<int|string, string>>
     */
    public static function recipientOptions(): array
    {
        $workspace = tenant();
        if ($workspace === null) {
            return [];
        }

        $groups = [NotificationRecipients::EVERYONE => __('pages/notifications.everyone')];
        foreach (self::roleNames($workspace) as $role) {
            $key = 'pages/notifications.group_'.$role;
            $groups[NotificationRecipients::ROLE_PREFIX.$role] = Lang::has($key)
                ? __($key)
                : __('pages/notifications.group_role', ['role' => Str::headline($role)]);
        }

        return [
            __('pages/notifications.groups') => $groups,
            __('pages/notifications.people') => self::peopleOptions(),
        ];
    }

    /**
     * Individual members only (for the "Excluded" select).
     *
     * @return array<int, string>
     */
    public static function peopleOptions(): array
    {
        $workspace = tenant();
        if ($workspace === null) {
            return [];
        }

        return $workspace->users()->get()->mapWithKeys(function (User $user): array {
            $label = $user->name.' · '.$user->email;
            if (($user->pivot->membership_type ?? null) === 'guest') {
                $label .= ' ('.__('pages/notifications.guest').')';
            }

            return [$user->id => $label];
        })->all();
    }

    /**
     * Every role defined for this workspace, standard roles first.
     *
     * @return list<string>
     */
    protected static function roleNames(Workspace $workspace): array
    {
        // App\Models\Role pins the central connection; the raw Spatie model
        // would query the tenant DB (no roles table there) and blow up.
        $defined = Role::query()->where('team_id', $workspace->id)->pluck('name')->all();

        return array_values(array_unique(array_merge(
            array_values(array_intersect(['owner', 'admin', 'member', 'guest'], $defined)),
            $defined,
        )));
    }
}
