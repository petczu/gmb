<x-filament-panels::page>
    @php($d = $this->viewData())
    @php($aiOver = $d['subscribed'] && $d['aiCap'] > 0 && $d['aiUsed'] >= $d['aiCap'])
    @php($reportOver = $d['subscribed'] && $d['reportCap'] > 0 && $d['reportsUsed'] >= $d['reportCap'])

    @if (! $d['enabled'])
        <x-filament::section>
            <x-slot name="heading">{{ __('pages/billing.not_configured') }}</x-slot>
            <p style="color: rgb(107 114 128);">
                {!! __('pages/billing.not_configured_body') !!}
            </p>
        </x-filament::section>
    @endif

    <div x-data="{ tab: 'plan' }">
        {{-- Tabs (native Filament styling) --}}
        <div style="margin-bottom:1.25rem;">
            <x-filament::tabs>
                <x-filament::tabs.item
                    alpine-active="tab === 'plan'"
                    x-on:click="tab = 'plan'">
                    {{ __('pages/billing.tab_plan') }}
                </x-filament::tabs.item>

                <x-filament::tabs.item
                    alpine-active="tab === 'invoices'"
                    x-on:click="tab = 'invoices'; $wire.loadInvoices()">
                    {{ __('pages/billing.tab_invoices') }}
                </x-filament::tabs.item>
            </x-filament::tabs>
        </div>

        {{-- ============================ PLAN TAB ============================ --}}
        <div x-show="tab === 'plan'" style="display:flex; flex-direction:column; gap:0.5rem;">
            {{-- Status row --}}
            <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:1.5rem; margin-bottom:1.25rem;">
                <x-filament::section>
                    <x-slot name="heading">{{ __('pages/billing.current_plan') }}</x-slot>
                    <div style="font-size:1.25rem; font-weight:700;">{{ $d['currentPlan'] ? ucfirst($d['currentPlan']) : '—' }}</div>
                    <div style="color:{{ $d['onTrial'] ? 'rgb(22 101 52)' : 'rgb(107 114 128)' }}; font-size:0.85rem; font-weight:{{ $d['onTrial'] ? 600 : 400 }};">
                        @if ($d['onTrial']) {{ __('pages/billing.trial_days_left', ['days' => $d['trialDaysLeft']]) }}
                        @elseif ($d['onGracePeriod']) {{ __('pages/billing.cancels_at_period_end') }}
                        @elseif ($d['subscribed']) {{ __('pages/billing.active') }}
                        @else {{ __('pages/billing.no_active_plan') }} @endif
                    </div>
                    @if ($d['canceled'] && $d['cancelAt'])
                        <div style="margin-top:0.4rem; font-size:0.8rem; color:rgb(202 138 4);">
                            {{ __('pages/billing.cancels_on', ['date' => $d['cancelAt']->translatedFormat('j. F Y')]) }}
                        </div>
                    @elseif ($d['nextPayment'] && $d['nextPayment']['date'])
                        @php($sym = ['EUR' => '€', 'USD' => '$', 'GBP' => '£'][$d['nextPayment']['currency']] ?? ($d['nextPayment']['currency'].' '))
                        <div style="margin-top:0.4rem; font-size:0.8rem; color:rgb(107 114 128);">
                            {{ $d['onTrial'] ? __('pages/billing.first_payment') : __('pages/billing.next_payment') }}
                            <strong style="color:rgb(55 65 81);">{{ $sym }}{{ number_format($d['nextPayment']['amount']) }}</strong>
                            {{ __('pages/billing.payment_on', ['date' => $d['nextPayment']['date']->translatedFormat('j. F Y')]) }}
                        </div>
                    @endif
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">{{ __('pages/billing.locations') }}</x-slot>
                    <div style="font-size:1.5rem; font-weight:700;">{{ $d['locationCount'] }}</div>
                    <div style="color:rgb(107 114 128); font-size:0.85rem;">{{ __('pages/billing.billed_per_location') }}</div>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">{{ __('pages/billing.ai_auto_replies') }}</x-slot>
                    <div style="font-size:1.5rem; font-weight:700; color:{{ $aiOver && $d['creditBalance'] <= 0 ? 'rgb(220 38 38)' : 'inherit' }};">
                        {{ number_format($d['aiUsed']) }}@if ($d['subscribed']) / {{ number_format($d['aiCap']) }}@endif
                    </div>
                    <div style="font-size:0.8rem; color:{{ $aiOver && $d['creditBalance'] <= 0 ? 'rgb(220 38 38)' : 'rgb(107 114 128)' }};">
                        @if ($aiOver && $d['creditBalance'] > 0)
                            {{ __('pages/billing.allowance_used_credits') }}
                        @elseif ($aiOver)
                            {{ __('pages/billing.limit_paused') }}
                        @else
                            {{ __('pages/billing.this_month_resets') }}
                        @endif
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">{{ __('pages/billing.ai_reports') }}</x-slot>
                    <div style="font-size:1.5rem; font-weight:700; color:{{ $reportOver ? 'rgb(202 138 4)' : 'inherit' }};">
                        {{ number_format($d['reportsUsed']) }}@if ($d['subscribed']) / {{ number_format($d['reportCap']) }}@endif
                    </div>
                    <div style="font-size:0.8rem; color:{{ $reportOver ? 'rgb(202 138 4)' : 'rgb(107 114 128)' }};">
                        {{ $reportOver ? __('pages/billing.limit_basic_only') : __('pages/billing.this_month_resets') }}
                    </div>
                </x-filament::section>
            </div>

            @if ($aiOver && $d['creditBalance'] > 0)
                <div style="background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; border-radius:0.6rem; padding:0.7rem 1rem; font-size:0.88rem; margin-bottom:0.5rem;">
                    {!! __('pages/billing.banner_on_credits', ['count' => number_format($d['creditBalance'])]) !!}
                </div>
            @elseif ($aiOver)
                <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; border-radius:0.6rem; padding:0.7rem 1rem; font-size:0.88rem; margin-bottom:0.5rem;">
                    {!! __('pages/billing.banner_all_used') !!}
                </div>
            @endif

            {{-- Buy credits (custom amount) --}}
            @if ($d['creditsAvailable'])
                <x-filament::section style="margin-bottom:1.25rem;">
                    <x-slot name="heading">{{ __('pages/billing.credits_section') }}</x-slot>
                    <x-slot name="description">
                        {{ __('pages/billing.credits_section_desc', ['report' => $d['reportCredits']]) }}
                    </x-slot>

                    <div style="display:flex; gap:2rem; align-items:flex-start; flex-wrap:wrap;">
                    <div x-data="{
                            qty: {{ max($d['creditMin'], min($d['creditMax'], $this->creditQty)) }},
                            price: {{ $d['creditPrice'] }},
                            min: {{ $d['creditMin'] }},
                            sliderMax: {{ $d['creditSliderMax'] }},
                            maxInput: {{ $d['creditMax'] }},
                            threshold: {{ $d['creditVolumeThreshold'] }},
                            disc: {{ $d['creditVolumeDiscount'] }},
                            get discounted() { return this.threshold > 0 && this.qty >= this.threshold; },
                            get unit() { return this.discounted ? this.price * (1 - this.disc / 100) : this.price; },
                            get total() { return (this.qty * this.unit).toFixed(2); },
                            clampQty() { let q = Math.round(this.qty || this.min); this.qty = Math.max(this.min, Math.min(this.maxInput, q)); }
                        }"
                        style="flex:1; min-width:20rem; max-width:34rem;">
                        <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.6rem;">
                            <div style="font-weight:700; font-size:1.05rem;"><span x-text="qty"></span> {{ __('pages/billing.credits_word') }}</div>
                            <input type="number" x-model.number="qty" @change="clampQty()" @blur="clampQty()"
                                :min="min" :max="maxInput" step="10"
                                style="width:7rem; padding:0.35rem 0.6rem; border:1px solid rgb(209 213 219); border-radius:0.5rem; font-size:0.9rem;">
                            <span x-show="discounted" x-cloak style="background:#dcfce7; color:#166534; font-size:0.75rem; font-weight:700; padding:0.15rem 0.5rem; border-radius:999px;">−<span x-text="disc"></span>%</span>
                        </div>

                        <input type="range" :min="min" :max="sliderMax" step="10" x-model.number="qty" style="width:100%;">
                        <div style="display:flex; justify-content:space-between; color:rgb(107 114 128); font-size:0.8rem;">
                            <span x-text="min"></span><span x-text="sliderMax"></span>
                        </div>

                        <div style="display:flex; flex-wrap:wrap; gap:0.4rem; margin:0.75rem 0;">
                            @foreach ($d['creditPresets'] as $p)
                                <button type="button" @click="qty = {{ $p }}"
                                    style="padding:0.35rem 0.8rem; border:1px solid rgb(209 213 219); border-radius:0.5rem; background:#fff; cursor:pointer; font-size:0.85rem;">
                                    {{ $p }}
                                </button>
                            @endforeach
                        </div>

                        @if ($d['creditVolumeThreshold'] > 0)
                            <div style="color:rgb(22 101 52); font-size:0.8rem; margin-bottom:0.25rem;">
                                {{ __('pages/billing.volume_hint', ['percent' => $d['creditVolumeDiscount'], 'qty' => number_format($d['creditVolumeThreshold'])]) }}
                            </div>
                        @endif

                        <div style="border-top:1px solid rgb(229 231 235); margin-top:0.5rem; padding-top:0.75rem; display:flex; flex-direction:column; gap:0.35rem; font-size:0.92rem;">
                            <div style="display:flex; justify-content:space-between;"><span style="color:rgb(75 85 99);">{{ __('pages/billing.credits_label') }}</span><span x-text="qty"></span></div>
                            <div style="display:flex; justify-content:space-between;">
                                <span style="color:rgb(75 85 99);">{{ __('pages/billing.price_per_credit') }}</span>
                                <span>
                                    <span x-show="discounted" x-cloak style="color:rgb(156 163 175); text-decoration:line-through; margin-right:0.35rem;">€<span x-text="price.toFixed(2)"></span></span>€<span x-text="unit.toFixed(3)"></span>
                                </span>
                            </div>
                            <div style="display:flex; justify-content:space-between; font-weight:700; font-size:1.05rem; padding-top:0.25rem;"><span>{{ __('pages/billing.total_label') }}</span><span>€<span x-text="total"></span></span></div>
                        </div>

                        <div style="margin-top:0.9rem;">
                            <x-filament::button x-on:click="clampQty(); $wire.buyCredits(qty)" color="primary" class="fi-w-full">
                                {{ __('pages/billing.pay_now') }} €<span x-text="total"></span>
                            </x-filament::button>
                        </div>
                        <div style="color:rgb(107 114 128); font-size:0.78rem; margin-top:0.5rem;">{{ __('pages/billing.credits_never_expire') }}</div>
                    </div>

                    {{-- Current credit balance --}}
                    <div style="flex:0 0 16rem; min-width:14rem; border:1px solid rgb(229 231 235); border-radius:0.9rem; padding:1.1rem 1.2rem; background:#fff;">
                        <div style="font-weight:600; font-size:0.95rem; margin-bottom:0.6rem;">{{ __('pages/billing.extra_credits') }}</div>
                        <div style="font-size:1.6rem; font-weight:700;">{{ number_format($d['creditBalance']) }}</div>
                        <div style="color:rgb(107 114 128); font-size:0.85rem;">{{ __('pages/billing.never_expire_short') }}</div>
                        <div style="margin-top:0.6rem; padding-top:0.6rem; border-top:1px solid rgb(243 244 246); color:rgb(75 85 99); font-size:0.85rem;">
                            {{ __('pages/billing.credits_spent_month', ['count' => number_format($d['creditsSpentThisMonth'])]) }}
                        </div>
                        <a href="{{ \App\Filament\App\Pages\Credits::getUrl() }}"
                           style="display:inline-block; margin-top:0.7rem; font-size:0.85rem; color:#2d19ec; font-weight:600; text-decoration:none;">
                            {{ __('pages/billing.credits_history_link') }} &rarr;
                        </a>
                    </div>
                    </div>{{-- /credits-and-balance flex row --}}

                    {{-- Auto top-up --}}
                    <div style="margin-top:1.25rem; border-top:1px solid rgb(229 231 235); padding-top:1rem;">
                        <div style="font-weight:600; font-size:0.95rem;">{{ __('pages/billing.auto_recharge_section') }}</div>
                        <div style="color:rgb(107 114 128); font-size:0.82rem; margin-bottom:0.75rem;">{{ __('pages/billing.auto_recharge_desc') }}</div>

                        @if (! $d['hasCard'])
                            <div style="background:#fffbeb; border:1px solid #fde68a; color:#92400e; border-radius:0.6rem; padding:0.7rem 0.9rem; font-size:0.85rem; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:0.75rem;">
                                <span>{{ __('pages/billing.auto_recharge_needs_card') }}</span>
                                <x-filament::button wire:click="addPaymentMethod" color="warning" size="sm">{{ __('pages/billing.add_payment_method') }}</x-filament::button>
                            </div>
                        @else
                            <div style="display:flex; flex-wrap:wrap; align-items:flex-end; gap:1.5rem;">
                                <label style="display:flex; align-items:center; gap:0.5rem; font-size:0.88rem; cursor:pointer;">
                                    <input type="checkbox" wire:model="autoRechargeEnabled" style="width:1rem; height:1rem;">
                                    {{ __('pages/billing.auto_recharge_enable') }}
                                </label>

                                <div style="display:flex; flex-direction:column; gap:0.2rem;">
                                    <span style="font-size:0.8rem; color:rgb(75 85 99);">{{ __('pages/billing.auto_recharge_threshold') }}</span>
                                    <input type="number" min="0" wire:model="autoRechargeThreshold"
                                        style="width:7rem; padding:0.4rem 0.6rem; border:1px solid rgb(209 213 219); border-radius:0.5rem; font-size:0.88rem;">
                                </div>

                                <div style="display:flex; flex-direction:column; gap:0.2rem;">
                                    <span style="font-size:0.8rem; color:rgb(75 85 99);">{{ __('pages/billing.auto_recharge_amount') }}</span>
                                    <input type="number" min="{{ $d['creditMin'] }}" max="{{ $d['creditMax'] }}" step="10" wire:model="autoRechargeAmount"
                                        style="width:8rem; padding:0.4rem 0.6rem; border:1px solid rgb(209 213 219); border-radius:0.5rem; font-size:0.88rem;">
                                </div>

                                <x-filament::button wire:click="saveAutoRecharge" color="primary" size="sm">{{ __('pages/billing.auto_recharge_save') }}</x-filament::button>
                            </div>
                            <div style="color:rgb(107 114 128); font-size:0.78rem; margin-top:0.5rem;">{{ __('pages/billing.auto_recharge_amount_help') }}</div>
                        @endif
                    </div>
                </x-filament::section>
            @endif

            {{-- Interval toggle --}}
            @if ($d['hasYearly'])
                <div style="display:flex; gap:0.4rem; align-items:center; margin:0.5rem 0;">
                    <x-filament::button size="sm" :color="$d['interval'] === 'month' ? 'primary' : 'gray'" wire:click="$set('interval','month')">{{ __('pages/billing.monthly') }}</x-filament::button>
                    <x-filament::button size="sm" :color="$d['interval'] === 'year' ? 'primary' : 'gray'" wire:click="$set('interval','year')">{{ __('pages/billing.yearly') }} <span style="opacity:.8;">−20%</span></x-filament::button>
                </div>
            @endif

            {{-- Plan cards --}}
            <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:1.5rem;">
                @foreach ($d['plans'] as $key => $plan)
                    @php($isCurrent = $d['currentPlan'] === $key)
                    @php($yearly = $d['interval'] === 'year')
                    @php($priceAvailable = $yearly ? $plan->yearlyPriceId !== null : $plan->priceId !== null)
                    <div style="border:2px solid {{ $isCurrent ? '#2d19ec' : 'rgb(229 231 235)' }}; border-radius:0.9rem; padding:1.1rem 1.2rem; background:#fff; display:flex; flex-direction:column; gap:0.5rem;">
                        <div style="display:flex; align-items:baseline; justify-content:space-between;">
                            <span style="font-weight:700; font-size:1.05rem;">{{ $plan->name }}</span>
                            <span style="font-weight:700;">
                                @if ($yearly)
                                    €{{ $plan->yearlyPriceUsd() }}<span style="color:rgb(107 114 128); font-weight:400; font-size:0.8rem;">{{ __('pages/billing.per_loc_yr') }}</span>
                                @else
                                    €{{ $plan->priceUsd }}<span style="color:rgb(107 114 128); font-weight:400; font-size:0.8rem;">{{ __('pages/billing.per_loc_mo') }}</span>
                                @endif
                            </span>
                        </div>
                        <div style="color:rgb(55 65 81); font-size:0.85rem;">
                            {{ __('pages/billing.plan_ai_summary', ['replies' => number_format($plan->aiReplyCap), 'reports' => $plan->reportCap]) }}
                        </div>
                        <ul style="margin:0; padding-left:1.1rem; color:rgb(75 85 99); font-size:0.82rem;">
                            <li>{{ __('pages/billing.feat_inbox') }}</li>
                            <li>{{ __('pages/billing.feat_automations') }}</li>
                            <li>{{ $plan->allows(\App\Billing\Plans::SCHEDULED_REPORTS) ? __('pages/billing.feat_scheduled') : __('pages/billing.feat_monthly_pdf') }}</li>
                            @if ($plan->allows(\App\Billing\Plans::WHITE_LABEL))<li>{{ __('pages/billing.feat_white_label') }}</li>@endif
                            @if ($plan->allows(\App\Billing\Plans::CUSTOM_ROLES))<li>{{ __('pages/billing.feat_custom_roles') }}</li>@endif
                            @if ($plan->allows(\App\Billing\Plans::MCP))<li>{{ __('pages/billing.feat_mcp') }}</li>@endif
                        </ul>

                        <div style="margin-top:auto; padding-top:0.5rem;">
                            @if (! $d['enabled'] || ! $priceAvailable)
                                <x-filament::button color="gray" disabled>{{ $priceAvailable ? __('pages/billing.configure_stripe') : __('pages/billing.not_available') }}</x-filament::button>
                            @elseif ($d['subscribed'])
                                @if ($isCurrent)
                                    @if ($d['onGracePeriod'])
                                        <x-filament::button wire:click="resumeSubscription" color="primary">{{ __('pages/billing.resume') }}</x-filament::button>
                                    @else
                                        <x-filament::button color="gray" disabled>{{ __('pages/billing.current_plan_btn') }}</x-filament::button>
                                    @endif
                                @else
                                    <x-filament::button wire:click="switchPlan('{{ $key }}')" color="primary" outlined>{{ __('pages/billing.switch_to', ['plan' => $plan->name]) }}</x-filament::button>
                                @endif
                            @else
                                {{-- Stripe Checkout free-trial (no card required during the trial). --}}
                                <x-filament::button wire:click="startCheckout('{{ $key }}')" color="primary">
                                    {{ $d['hasUsedTrial'] ? __('pages/billing.subscribe') : __('pages/billing.start_trial') }}
                                </x-filament::button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <p style="color:rgb(107 114 128); font-size:0.8rem; margin-top:0.75rem;">
                {{ __('pages/billing.footer_note') }}
            </p>
        </div>

        {{-- ========================== INVOICES TAB ========================== --}}
        <div x-show="tab === 'invoices'" x-cloak>
            <x-filament::section>
                <x-slot name="heading">{{ __('pages/billing.invoices') }}</x-slot>
                <x-slot name="description">{{ __('pages/billing.invoices_desc') }}</x-slot>

                @if (! $d['hasInvoices'])
                    <p style="color:rgb(107 114 128);">{{ __('pages/billing.invoices_unavailable') }}</p>
                @elseif ($this->invoices === null)
                    <p style="color:rgb(107 114 128);">{{ __('pages/billing.invoices_loading') }}</p>
                @elseif (count($this->invoices) === 0)
                    <p style="color:rgb(107 114 128);">{{ __('pages/billing.invoices_empty') }}</p>
                @else
                    <table style="width:100%; border-collapse:collapse; font-size:0.9rem;">
                        <thead>
                            <tr style="text-align:left; color:rgb(107 114 128); border-bottom:1px solid rgb(229 231 235);">
                                <th style="padding:0.5rem 0; font-weight:600;">{{ __('pages/billing.invoice_date') }}</th>
                                <th style="padding:0.5rem 0; font-weight:600;">{{ __('pages/billing.invoice_total') }}</th>
                                <th style="padding:0.5rem 0;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->invoices as $inv)
                                <tr style="border-bottom:1px solid rgb(243 244 246);">
                                    <td style="padding:0.65rem 0;">{{ $inv['date'] }}</td>
                                    <td style="padding:0.65rem 0;">{{ $inv['total'] }}</td>
                                    <td style="padding:0.65rem 0; text-align:right;">
                                        <a href="{{ $inv['url'] }}" style="color:#2d19ec; font-weight:600; display:inline-flex; align-items:center; gap:5px;">
                                            <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                            {{ __('pages/billing.invoice_download') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
