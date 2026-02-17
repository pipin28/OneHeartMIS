<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('images/logo-oneheart.png') }}">
    <title>Settings | OneHeart Life Plan</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/partials/nav.css') . '?v=' . filemtime(public_path('css/partials/nav.css')) }}">
    <style>
        .settings-modal .modal-card {
            max-width: 1100px;
            width: min(1100px, 94vw);
        }
        .settings-modal.is-visible .modal-card {
            animation: settingsModalPop 0.22s ease-out;
        }
        @keyframes settingsModalPop {
            from {
                transform: translateY(10px) scale(0.98);
                opacity: 0;
            }
            to {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }
    </style>
</head>
<body class="has-shell">
    <div class="page">
        @include('partials.header')

        <div class="card" style="margin-bottom: 14px;">
                    <div class="card-header table-toolbar">
                        <div>
                            <div class="card-title">Settings Shortcuts</div>
                            <div class="card-subtitle">Quick access to percentage and insurance configuration</div>
                        </div>
                    </div>
                    <div class="form-actions" style="justify-content: flex-start; flex-wrap: wrap; gap: 10px;">
                        <button type="button" class="button is-ghost" id="openMonthlySettings">Monthly Percentage Settings</button>
                        <button type="button" class="button is-ghost" id="openQuarterlySettings">Quarterly Percentage Settings</button>
                        <button type="button" class="button is-ghost" id="openSemiAnnualSettings">Semi-Annual Percentage Settings</button>
                        <button type="button" class="button is-ghost" id="openAnnualSettings">Annual Percentage Settings</button>
                        <button type="button" class="button is-ghost" id="openInsuranceSettings">Insurance Partnership</button>
                    </div>
                </div>

        <main class="dashboard">
            <section class="wrap">
                <div class="eyebrow">Settings</div>
                <div class="hero-title hero-small">Plan &amp; service settings</div>

                
               
                <div class="form-actions form-actions-split plan-actions">
                    <div></div>
                    <div class="form-actions">
                        <button type="button" class="button is-danger" id="openDeletePlanModal">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 6h18"></path>
                                <path d="M8 6V4h8v2"></path>
                                <path d="M19 6l-1 14H6L5 6"></path>
                                <path d="M10 11v6"></path>
                                <path d="M14 11v6"></path>
                            </svg>
                            Delete plan
                        </button>
                        <button type="button" class="button is-warning" id="openUpdatePlanModal">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 20h9"></path>
                                <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4 11.5-11.5z"></path>
                            </svg>
                            Update plan
                        </button>
                        <button type="button" class="button is-primary" id="openPlanModal">
                            <span aria-hidden="true">＋</span>
                            <span>Add plan</span>
                        </button>
                    </div>
                </div>

                @if (session('status'))
                    <div class="status">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="status status-error">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('settings.update') }}" class="form-grid">
                    @csrf
                    <div class="data-table" style="grid-column: 1 / -1;">
                        <div class="table-scroll">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Plan / Service</th>
                                        <th>Contract Amount</th>
                                        <th>Legacy Monthly Amount</th>
                                        <th>Premium Amount</th>
                                        <th>Default Mode</th>
                                        <th>Default Terms</th>
                                        <th>Default Months</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($plans as $name => $meta)
                                        @php
                                            $isLegacyPlan = strtolower($name) === 'legacy care';
                                        @endphp
                                        <tr>
                                            <td class="table-col-primary">{{ $name }}</td>
                                            <td>
                                                <input type="text" name="plans[{{ $name }}][contract_amount]" value="{{ old('plans.' . $name . '.contract_amount', $meta['contract_amount']) }}" placeholder="30,000" inputmode="decimal" autocomplete="off" required>
                                            </td>
                                            <td>
                                                <input
                                                    type="text"
                                                    name="plans[{{ $name }}][legacy_monthly_amount]"
                                                    value="{{ old('plans.' . $name . '.legacy_monthly_amount', $meta['legacy_monthly_amount'] ?? '') }}"
                                                    placeholder="{{ $isLegacyPlan ? '500' : '-' }}"
                                                    inputmode="decimal"
                                                    autocomplete="off"
                                                    {{ $isLegacyPlan ? '' : 'readonly' }}
                                                >
                                            </td>
                                            <td>
                                                <input type="text" name="plans[{{ $name }}][premium_amount]" value="{{ old('plans.' . $name . '.premium_amount', $meta['premium_amount']) }}" placeholder="500" inputmode="decimal" autocomplete="off" readonly>
                                            </td>
                                            <td>
                                                <select name="plans[{{ $name }}][default_mode]" required>
                                                    @foreach (['Monthly', 'Quarterly', 'Semi-Annual', 'Annual', 'One-time'] as $mode)
                                                        <option value="{{ $mode }}" {{ old('plans.' . $name . '.default_mode', $meta['default_mode']) === $mode ? 'selected' : '' }}>{{ $mode }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="plans[{{ $name }}][default_terms]" value="{{ old('plans.' . $name . '.default_terms', $meta['default_terms']) }}" required>
                                            </td>
                                            <td>
                                                <input type="number" name="plans[{{ $name }}][default_months]" value="{{ old('plans.' . $name . '.default_months', $meta['default_months']) }}" min="1" required>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit">Save settings</button>
                    </div>
                </form>

                @php
                    $roles = ['collector' => 'Collector', 'agent' => 'Agent', 'manager' => 'Manager', 'others' => 'Others'];
                    $modeSlugs = [
                        'Monthly' => 'monthly',
                        'Quarterly' => 'quarterly',
                        'Semi-Annual' => 'semi-annual',
                        'Annual' => 'annual',
                    ];
                    $percentageSections = [
                        'Monthly' => [
                            'first_month' => '1st month',
                            'months_2_12' => '2-12 months',
                            'months_13_60' => '13-60 months',
                        ],
                        'Quarterly' => [
                            'first_quarter' => '1st quarter (3 mos)',
                            'quarters_2_4' => '2-4 quarters (4-12 mos)',
                            'quarters_5_20' => '5-20 quarters (13-60 mos)',
                        ],
                        'Semi-Annual' => [
                            'first_semi' => '1st semi (6 mos)',
                            'semis_2_2' => '2nd semi (12 mos)',
                            'semis_3_10' => '3-10 semis (18-60 mos)',
                        ],
                        'Annual' => [
                            'first_year' => '1st year',
                            'years_2_5' => '2-5 years',
                        ],
                    ];
                @endphp

                @foreach ($percentageSections as $mode => $tiers)
                    @php $slug = $modeSlugs[$mode] ?? strtolower($mode) @endphp
                    <div class="modal-overlay settings-modal" id="{{ $slug }}SettingsModal" aria-hidden="true">
                        <div class="modal-card modal-card-wide">
                            <div class="modal-head">
                                <div>
                                    <div class="modal-title">{{ $mode }} Percentage Settings</div>
                                    <div class="modal-subtitle">Set percentages for {{ strtolower($mode) }} payments.</div>
                                </div>
                                <button type="button" class="modal-close" aria-label="Close">&times;</button>
                            </div>
                            <form method="POST" action="{{ route('settings.percentages.update') }}" class="form-grid">
                                @csrf
                                <input type="hidden" name="mode" value="{{ $mode }}">
                                <div class="data-table" style="grid-column: 1 / -1;">
                                    <div class="table-scroll">
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>Role</th>
                                                    @foreach ($tiers as $tierLabel)
                                                        <th>{{ $tierLabel }}</th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($roles as $key => $label)
                                                    <tr>
                                                        <td class="table-col-primary">{{ $label }}</td>
                                                        @foreach ($tiers as $tierKey => $tierLabel)
                                                            <td>
                                                                <input
                                                                    type="number"
                                                                    name="percentages[{{ $mode }}][{{ $key }}][{{ $tierKey }}]"
                                                                    value="{{ $percentages[$mode][$key][$tierKey] ?? '' }}"
                                                                    min="0"
                                                                    max="100"
                                                                    step="0.01"
                                                                    placeholder="%"
                                                                />
                                                            </td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="form-actions" style="justify-content: flex-end;">
                                    <button type="button" class="button is-ghost modal-close">Close</button>
                                    <button type="submit" class="button is-primary">Save {{ $mode }} settings</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endforeach

                @php
                    $insuranceRows = $insurancePartners ?? [];
                    if (count($insuranceRows) < 2) {
                        $insuranceRows = array_pad($insuranceRows, 2, ['id' => null, 'name' => '', 'amount' => '']);
                    }
                @endphp

                <div class="modal-overlay settings-modal" id="insuranceSettingsModal" aria-hidden="true">
                    <div class="modal-card modal-card-wide">
                        <div class="modal-head">
                            <div>
                                <div class="modal-title">Insurance Partnership</div>
                                <div class="modal-subtitle">Set insurance partners and fixed deduction per payment.</div>
                            </div>
                            <button type="button" class="modal-close" aria-label="Close">&times;</button>
                        </div>
                        <form method="POST" action="{{ route('settings.insurance.update') }}" class="form-grid">
                            @csrf
                            <div class="data-table" style="grid-column: 1 / -1;">
                                <div class="table-scroll">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Partner</th>
                                                <th>Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($insuranceRows as $index => $row)
                                                <tr>
                                                    <td>
                                                        <input type="hidden" name="partners[{{ $index }}][id]" value="{{ $row['id'] ?? '' }}">
                                                        <input type="text" name="partners[{{ $index }}][name]" value="{{ $row['name'] ?? '' }}" placeholder="Insurance Company">
                                                    </td>
                                                    <td style="max-width: 160px;">
                                                        <input type="number" name="partners[{{ $index }}][amount]" value="{{ $row['amount'] ?? '' }}" min="0" step="0.01" placeholder="0.00">
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="form-actions" style="justify-content: flex-end;">
                                <button type="button" class="button is-ghost modal-close">Close</button>
                                <button type="submit" class="button is-primary">Save insurance partners</button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        </main>

        @include('partials.footer')
    </div>

    <div class="modal-overlay" id="planModal" aria-hidden="true">
        <div class="modal-card modal-card-narrow">
            <div class="modal-head">
                <div>
                    <div class="modal-title">Create plan</div>
                    <div class="modal-subtitle">Add a new plan/service.</div>
                </div>
                <button type="button" class="modal-close" id="closePlanModal" aria-label="Close">×</button>
            </div>
            <form method="POST" action="{{ route('settings.plan.store') }}" class="form-grid">
                @csrf
                <div>
                    <label for="plan_name">Plan / Service Name</label>
                    <input type="text" id="plan_name" name="name" placeholder="New Plan" required>
                </div>
                <div>
                    <label for="plan_contract_amount">Contract Amount</label>
                    <input type="text" id="plan_contract_amount" name="contract_amount" placeholder="30,000" inputmode="decimal" autocomplete="off" required>
                </div>
                <div>
                    <label for="plan_legacy_monthly_amount">Legacy Monthly Amount</label>
                    <input type="text" id="plan_legacy_monthly_amount" name="legacy_monthly_amount" placeholder="500" inputmode="decimal" autocomplete="off">
                </div>
                <div>
                    <label for="plan_default_mode">Default Mode</label>
                    <select id="plan_default_mode" name="default_mode" required>
                        @foreach (['Monthly', 'Quarterly', 'Semi-Annual', 'Annual', 'One-time'] as $mode)
                            <option value="{{ $mode }}">{{ $mode }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="plan_default_terms">Default Terms</label>
                    <input type="text" id="plan_default_terms" name="default_terms" placeholder="60 months (5 years)" required>
                </div>
                <div>
                    <label for="plan_default_months">Default Months</label>
                    <input type="number" id="plan_default_months" name="default_months" min="1" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="button is-primary">Create plan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="updatePlanSelectModal" aria-hidden="true">
        <div class="modal-card modal-card-narrow">
            <div class="modal-head">
                <div>
                    <div class="modal-title">Update plan</div>
                    <div class="modal-subtitle">Choose a plan to update.</div>
                </div>
                <button type="button" class="modal-close" id="closeUpdatePlanModal" aria-label="Close">×</button>
            </div>
            <form class="form-grid" id="updatePlanSelectForm">
                <div>
                    <label for="update_plan_select">Plan / Service</label>
                    <select id="update_plan_select" required>
                        <option value="" disabled selected>Select a plan</option>
                        @foreach ($plans as $name => $meta)
                            <option value="{{ $name }}"
                                data-contract="{{ $meta['contract_amount'] }}"
                                data-legacy-monthly="{{ $meta['legacy_monthly_amount'] ?? '' }}"
                                data-mode="{{ $meta['default_mode'] }}"
                                data-terms="{{ $meta['default_terms'] }}"
                                data-months="{{ $meta['default_months'] }}">
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="button">Next</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="updatePlanModal" aria-hidden="true">
        <div class="modal-card modal-card-narrow">
            <div class="modal-head">
                <div>
                    <div class="modal-title">Edit plan</div>
                    <div class="modal-subtitle">Update the selected plan details.</div>
                </div>
                <button type="button" class="modal-close" id="closeEditPlanModal" aria-label="Close">×</button>
            </div>
            <form method="POST" action="{{ route('settings.plan.update') }}" class="form-grid" id="updatePlanForm">
                @csrf
                <input type="hidden" name="original_name" id="update_original_name">
                <div>
                    <label for="update_plan_name">Plan / Service Name</label>
                    <input type="text" id="update_plan_name" name="name" placeholder="Plan name" required>
                </div>
                <div>
                    <label for="update_plan_contract">Contract Amount</label>
                    <input type="text" id="update_plan_contract" name="contract_amount" placeholder="30,000" inputmode="decimal" autocomplete="off" required>
                </div>
                <div>
                    <label for="update_plan_legacy_monthly_amount">Legacy Monthly Amount</label>
                    <input type="text" id="update_plan_legacy_monthly_amount" name="legacy_monthly_amount" placeholder="500" inputmode="decimal" autocomplete="off">
                </div>
                <div>
                    <label for="update_plan_default_mode">Default Mode</label>
                    <select id="update_plan_default_mode" name="default_mode" required>
                        @foreach (['Monthly', 'Quarterly', 'Semi-Annual', 'Annual', 'One-time'] as $mode)
                            <option value="{{ $mode }}">{{ $mode }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="update_plan_default_terms">Default Terms</label>
                    <input type="text" id="update_plan_default_terms" name="default_terms" placeholder="60 months (5 years)" required>
                </div>
                <div>
                    <label for="update_plan_default_months">Default Months</label>
                    <input type="number" id="update_plan_default_months" name="default_months" min="1" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="button is-warning">Update plan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="deletePlanModal" aria-hidden="true">
        <div class="modal-card modal-card-narrow">
            <div class="modal-head">
                <div>
                    <div class="modal-title">Delete plan</div>
                    <div class="modal-subtitle">Select a plan to remove.</div>
                </div>
                <button type="button" class="modal-close" id="closeDeletePlanModal" aria-label="Close">×</button>
            </div>
            <form method="POST" action="{{ route('settings.plan.delete') }}" class="form-grid">
                @csrf
                <div>
                    <label for="delete_plan_name">Plan / Service</label>
                    <select id="delete_plan_name" name="name" required>
                        <option value="" disabled selected>Select a plan</option>
                        @foreach ($plans as $name => $meta)
                            <option value="{{ $name }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="button is-danger">Delete plan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    (() => {
        const bindings = [
            { trigger: 'openMonthlySettings', modal: 'monthlySettingsModal' },
            { trigger: 'openQuarterlySettings', modal: 'quarterlySettingsModal' },
            { trigger: 'openSemiAnnualSettings', modal: 'semi-annualSettingsModal' },
            { trigger: 'openAnnualSettings', modal: 'annualSettingsModal' },
            { trigger: 'openInsuranceSettings', modal: 'insuranceSettingsModal' },
        ];

        bindings.forEach(({ trigger, modal }) => {
            const openBtn = document.getElementById(trigger);
            const overlay = document.getElementById(modal);
            if (!openBtn || !overlay) return;
            const closeEls = overlay.querySelectorAll('.modal-close');
            const open = () => {
                overlay.classList.add('is-visible');
                overlay.setAttribute('aria-hidden', 'false');
            };
            const close = () => {
                overlay.classList.remove('is-visible');
                overlay.setAttribute('aria-hidden', 'true');
            };
            openBtn.addEventListener('click', open);
            closeEls.forEach(btn => btn.addEventListener('click', close));
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) close();
            });
        });
    })();

    (() => {
        const openBtn = document.getElementById('openPlanModal');
        const modal = document.getElementById('planModal');
        const closeBtn = document.getElementById('closePlanModal');
        if (!openBtn || !modal || !closeBtn) return;
        const open = () => modal.classList.add('is-visible');
        const close = () => modal.classList.remove('is-visible');
        openBtn.addEventListener('click', open);
        closeBtn.addEventListener('click', close);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) close();
        });
    })();

    (() => {
        const openBtn = document.getElementById('openUpdatePlanModal');
        const modal = document.getElementById('updatePlanSelectModal');
        const editModal = document.getElementById('updatePlanModal');
        const closeBtn = document.getElementById('closeUpdatePlanModal');
        const closeEditBtn = document.getElementById('closeEditPlanModal');
        const selectForm = document.getElementById('updatePlanSelectForm');
        const selectField = document.getElementById('update_plan_select');
        const nameField = document.getElementById('update_plan_name');
        const originalField = document.getElementById('update_original_name');
        const contractField = document.getElementById('update_plan_contract');
        const legacyMonthlyField = document.getElementById('update_plan_legacy_monthly_amount');
        const modeField = document.getElementById('update_plan_default_mode');
        const termsField = document.getElementById('update_plan_default_terms');
        const monthsField = document.getElementById('update_plan_default_months');

        if (!openBtn || !modal || !editModal || !closeBtn || !closeEditBtn || !selectForm || !selectField) return;

        const stripCommasLocal = (value) => (value || '').toString().replace(/,/g, '');
        const parseNumberLocal = (value) => {
            const cleaned = stripCommasLocal(value).replace(/[^0-9.]/g, '');
            const num = Number(cleaned);
            return Number.isFinite(num) ? num : 0;
        };
        const formatNumberLocal = (value) => {
            if (value === '' || value === null || value === undefined) return '';
            const num = Number(value);
            if (!Number.isFinite(num)) return '';
            return num.toLocaleString('en-US', { maximumFractionDigits: 2 });
        };

        const open = () => modal.classList.add('is-visible');
        const close = () => modal.classList.remove('is-visible');
        const openEdit = () => editModal.classList.add('is-visible');
        const closeEdit = () => editModal.classList.remove('is-visible');

        openBtn.addEventListener('click', open);
        closeBtn.addEventListener('click', close);
        closeEditBtn.addEventListener('click', closeEdit);

        modal.addEventListener('click', (e) => {
            if (e.target === modal) close();
        });
        editModal.addEventListener('click', (e) => {
            if (e.target === editModal) closeEdit();
        });

        selectForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const option = selectField.options[selectField.selectedIndex];
            if (!option || !option.value) return;
            originalField.value = option.value;
            nameField.value = option.value;
            contractField.value = formatNumberLocal(parseNumberLocal(option.dataset.contract || 0));
            const legacyMonthlyRaw = option.dataset.legacyMonthly || '';
            legacyMonthlyField.value = legacyMonthlyRaw === ''
                ? ''
                : formatNumberLocal(parseNumberLocal(legacyMonthlyRaw));
            modeField.value = option.dataset.mode || 'Monthly';
            termsField.value = option.dataset.terms || '';
            monthsField.value = option.dataset.months || 1;
            close();
            openEdit();
        });
    })();

    (() => {
        const openBtn = document.getElementById('openDeletePlanModal');
        const modal = document.getElementById('deletePlanModal');
        const closeBtn = document.getElementById('closeDeletePlanModal');
        if (!openBtn || !modal || !closeBtn) return;
        const open = () => modal.classList.add('is-visible');
        const close = () => modal.classList.remove('is-visible');
        openBtn.addEventListener('click', open);
        closeBtn.addEventListener('click', close);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) close();
        });
    })();

    (() => {
        const rows = document.querySelectorAll('tbody tr');
        const form = document.querySelector('form.form-grid');
        const modalForm = document.querySelector('#planModal form');
        const stripCommas = (value) => (value || '').toString().replace(/,/g, '');
        const parseNumber = (value) => {
            const cleaned = stripCommas(value).replace(/[^0-9.]/g, '');
            const num = Number(cleaned);
            return Number.isFinite(num) ? num : 0;
        };
        const formatNumber = (value) => {
            if (value === '' || value === null || value === undefined) return '';
            const num = Number(value);
            if (!Number.isFinite(num)) return '';
            return num.toLocaleString('en-US', { maximumFractionDigits: 2 });
        };
        const computePremium = (contractInput, monthsInput, modeSelect, premiumInput) => {
            const contract = parseNumber(contractInput.value || 0);
            const months = Math.max(1, Number(monthsInput.value || 1));
            const mode = (modeSelect?.value || 'Monthly').toLowerCase();
            const factor = (() => {
                switch (mode) {
                    case 'quarterly':
                        return 3;
                    case 'semi-annual':
                    case 'semi annual':
                        return 6;
                    case 'annual':
                    case 'yearly':
                        return 12;
                    case 'one-time':
                    case 'one time':
                    case 'one_time':
                        return months;
                    default:
                        return 1;
                }
            })();
            const computed = (mode === 'one-time' || mode === 'one time' || mode === 'one_time')
                ? Math.round(contract)
                : Math.round((contract / months) * factor);
            premiumInput.value = formatNumber(computed);
        };

        rows.forEach(row => {
            const contractInput = row.querySelector('input[name*="[contract_amount]"]');
            const legacyMonthlyInput = row.querySelector('input[name*="[legacy_monthly_amount]"]');
            const monthsInput = row.querySelector('input[name*="[default_months]"]');
            const modeSelect = row.querySelector('select[name*="[default_mode]"]');
            const premiumInput = row.querySelector('input[name*="[premium_amount]"]');
            if (!contractInput || !monthsInput || !premiumInput || !modeSelect) return;

            const recalc = () => computePremium(contractInput, monthsInput, modeSelect, premiumInput);
            if (contractInput.value) contractInput.value = formatNumber(parseNumber(contractInput.value));
            if (legacyMonthlyInput?.value) legacyMonthlyInput.value = formatNumber(parseNumber(legacyMonthlyInput.value));
            contractInput.addEventListener('input', recalc);
            legacyMonthlyInput?.addEventListener('input', () => {
                const num = parseNumber(legacyMonthlyInput.value);
                legacyMonthlyInput.value = formatNumber(num);
            });
            monthsInput.addEventListener('input', recalc);
            modeSelect.addEventListener('change', recalc);
            recalc();
        });

        if (form) {
            form.addEventListener('submit', () => {
                form.querySelectorAll('input[name*="[contract_amount]"]').forEach(input => {
                    input.value = stripCommas(input.value);
                });
                form.querySelectorAll('input[name*="[premium_amount]"]').forEach(input => {
                    input.value = stripCommas(input.value);
                });
                form.querySelectorAll('input[name*="[legacy_monthly_amount]"]').forEach(input => {
                    input.value = stripCommas(input.value);
                });
            });
        }

        const modalContract = document.getElementById('plan_contract_amount');
        const modalLegacyMonthly = document.getElementById('plan_legacy_monthly_amount');
        const updateContract = document.getElementById('update_plan_contract');
        const updateLegacyMonthly = document.getElementById('update_plan_legacy_monthly_amount');
        if (modalContract?.value) {
            modalContract.value = formatNumber(parseNumber(modalContract.value));
        }
        if (modalLegacyMonthly?.value) {
            modalLegacyMonthly.value = formatNumber(parseNumber(modalLegacyMonthly.value));
        }
        modalContract?.addEventListener('input', () => {
            const num = parseNumber(modalContract.value);
            modalContract.value = formatNumber(num);
        });
        modalLegacyMonthly?.addEventListener('input', () => {
            const num = parseNumber(modalLegacyMonthly.value);
            modalLegacyMonthly.value = formatNumber(num);
        });

        updateContract?.addEventListener('input', () => {
            const num = parseNumber(updateContract.value);
            updateContract.value = formatNumber(num);
        });
        updateLegacyMonthly?.addEventListener('input', () => {
            const num = parseNumber(updateLegacyMonthly.value);
            updateLegacyMonthly.value = formatNumber(num);
        });

        modalForm?.addEventListener('submit', () => {
            if (modalContract) modalContract.value = stripCommas(modalContract.value);
            if (modalLegacyMonthly) modalLegacyMonthly.value = stripCommas(modalLegacyMonthly.value);
        });

        const updateForm = document.getElementById('updatePlanForm');
        updateForm?.addEventListener('submit', () => {
            if (updateContract) updateContract.value = stripCommas(updateContract.value);
            if (updateLegacyMonthly) updateLegacyMonthly.value = stripCommas(updateLegacyMonthly.value);
        });
    })();
    </script>
</body>
</html>

