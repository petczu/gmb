<?php

namespace App\Filament\App\Resources\Automations\Schemas;

use App\Models\AiAgent;
use App\Models\Location;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class AutomationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('resources/automations.flow_section'))
                    ->description(__('resources/automations.flow_section_desc'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(120)
                            ->columnSpanFull(),

                        // Moved out of the cramped name row to its own full-width line.
                        Toggle::make('enabled')
                            ->default(true)
                            ->columnSpanFull(),

                        // Only one trigger exists, so it's hidden but still submitted.
                        Select::make('trigger')
                            ->options(['new_review' => __('resources/automations.trigger_new_review')])
                            ->default('new_review')
                            ->dehydrated()
                            ->hidden(),

                        CheckboxList::make('rating_filter')
                            ->label(__('resources/automations.rating_is'))
                            ->options([5 => '5★', 4 => '4★', 3 => '3★', 2 => '2★', 1 => '1★'])
                            ->columns(5)
                            ->helperText(__('resources/automations.rating_helper'))
                            ->columnSpanFull(),

                        Toggle::make('all_locations')
                            ->label(__('resources/automations.all_locations'))
                            ->default(true)
                            ->live()
                            ->columnSpanFull(),

                        Select::make('location_ids')
                            ->label(__('resources/automations.locations'))
                            ->multiple()
                            ->options(fn (): array => Location::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->visible(fn (Get $get): bool => ! $get('all_locations'))
                            ->columnSpanFull(),

                        Toggle::make('reply_to_previous')
                            ->label(__('resources/automations.reply_to_previous'))
                            ->helperText(__('resources/automations.reply_to_previous_helper')),

                        Toggle::make('approve_before_posting')
                            ->label(__('resources/automations.approve_before_posting'))
                            ->helperText(__('resources/automations.approve_before_posting_helper'))
                            ->columnSpanFull(),
                    ]),

                Section::make(__('resources/automations.timing_section'))
                    ->description(__('resources/automations.timing_section_desc'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('reply_delay_min_minutes')
                            ->label(__('resources/automations.reply_delay_min'))
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->suffix(__('resources/automations.minutes_suffix'))
                            ->helperText(__('resources/automations.reply_delay_helper')),

                        TextInput::make('reply_delay_max_minutes')
                            ->label(__('resources/automations.reply_delay_max'))
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->suffix(__('resources/automations.minutes_suffix'))
                            ->gte('reply_delay_min_minutes')
                            ->validationMessages([
                                'gte' => __('resources/automations.reply_delay_max_error'),
                            ]),

                        Toggle::make('respect_working_hours')
                            ->label(__('resources/automations.respect_working_hours'))
                            ->helperText(__('resources/automations.respect_working_hours_helper'))
                            ->live()
                            ->columnSpanFull(),

                        CheckboxList::make('working_hours.days')
                            ->label(__('resources/automations.working_days'))
                            ->options([
                                1 => __('resources/automations.day_mon'),
                                2 => __('resources/automations.day_tue'),
                                3 => __('resources/automations.day_wed'),
                                4 => __('resources/automations.day_thu'),
                                5 => __('resources/automations.day_fri'),
                                6 => __('resources/automations.day_sat'),
                                7 => __('resources/automations.day_sun'),
                            ])
                            ->columns(4)
                            ->default([1, 2, 3, 4, 5])
                            ->visible(fn (Get $get): bool => (bool) $get('respect_working_hours'))
                            ->columnSpanFull(),

                        TimePicker::make('working_hours.start')
                            ->label(__('resources/automations.working_start'))
                            ->seconds(false)
                            ->default('09:00')
                            ->visible(fn (Get $get): bool => (bool) $get('respect_working_hours')),

                        TimePicker::make('working_hours.end')
                            ->label(__('resources/automations.working_end'))
                            ->seconds(false)
                            ->default('18:00')
                            ->visible(fn (Get $get): bool => (bool) $get('respect_working_hours')),
                    ]),

                Section::make(__('resources/automations.content_section'))
                    ->description(__('resources/automations.content_section_desc'))
                    ->schema([
                        Radio::make('content_type')
                            ->label('')
                            ->options([
                                'ai_agent' => __('resources/automations.content_ai_agent'),
                                'default_message' => __('resources/automations.content_default_message'),
                            ])
                            ->default('ai_agent')
                            ->live(),

                        Select::make('ai_agent_id')
                            ->label(__('resources/automations.ai_agent'))
                            ->options(fn (): array => AiAgent::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->native(false)
                            ->required(fn (Get $get): bool => $get('content_type') === 'ai_agent')
                            ->visible(fn (Get $get): bool => $get('content_type') === 'ai_agent'),

                        Textarea::make('default_message')
                            ->label(__('resources/automations.default_message'))
                            ->rows(4)
                            ->required(fn (Get $get): bool => $get('content_type') === 'default_message')
                            ->visible(fn (Get $get): bool => $get('content_type') === 'default_message'),
                    ]),
            ]);
    }
}
