<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('images/logo-oneheart.png') }}">
    <title>Add Members | OneHeart Life Plan</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/partials/nav.css') . '?v=' . filemtime(public_path('css/partials/nav.css')) }}">
</head>
@php
    $isDraft = $isDraft ?? false;
@endphp
<body class="has-shell" data-draft="{{ $isDraft ? '1' : '0' }}">
    <div class="page">
        @include('partials.header')

        <main class="dashboard">
            <section class="wrap">
                @php
                    $hasPart1 = !empty($part1?->id);
                    $hasPart2 = !empty($part2?->id);
                    $hasAddress = !empty($address?->id);
                    $part1Id = $part1->id ?? null;
                    $part2Id = $part2->id ?? null;
                    $addressId = $address->id ?? null;
                    $assignmentId = $assignment->id ?? null;
                @endphp
                <div class="progress-steps">
                    @if ($isDraft)
                        <a class="step-pill" href="{{ route('add-members.draft.staff') }}">
                            <input type="radio" name="progress_step" aria-hidden="true">
                            <span>Staff Info</span>
                        </a>
                        <a class="step-pill is-current" href="#">
                            <input type="radio" name="progress_step" checked aria-hidden="true">
                            <span>Member Enrollment</span>
                        </a>
                        <a class="step-pill" href="{{ route('add-members.draft.part2') }}">
                            <input type="radio" name="progress_step" aria-hidden="true">
                            <span>Member Details</span>
                        </a>
                        <a class="step-pill" href="{{ route('add-members.draft.address') }}">
                            <input type="radio" name="progress_step" aria-hidden="true">
                            <span>Address</span>
                        </a>
                        <a class="step-pill" href="{{ route('add-members.draft.beneficiaries') }}">
                            <input type="radio" name="progress_step" aria-hidden="true">
                            <span>Beneficiaries</span>
                        </a>
                    @else
                        <a class="step-pill" href="{{ route('add-members.staff', ['assignment' => $assignmentId]) }}">
                            <input type="radio" name="progress_step" aria-hidden="true">
                            <span>Staff Info</span>
                        </a>
                        <a class="step-pill is-current" href="#">
                            <input type="radio" name="progress_step" checked aria-hidden="true">
                            <span>Member Enrollment</span>
                        </a>
                        <a class="step-pill is-disabled" href="#">
                            <input type="radio" name="progress_step" aria-hidden="true">
                            <span>Member Details</span>
                        </a>
                        <a class="step-pill is-disabled" href="#">
                            <input type="radio" name="progress_step" aria-hidden="true">
                            <span>Address</span>
                        </a>
                        <a class="step-pill is-disabled" href="#">
                            <input type="radio" name="progress_step" aria-hidden="true">
                            <span>Beneficiaries</span>
                        </a>
                    @endif
                </div>
                <div class="eyebrow">Add Members</div>
                <div class="hero-title hero-small">Member enrollment</div>
                <p class="hero-sub">Use this screen to onboard new members. Drop in your forms, uploads, and validation messages here.</p>

                @if ($assignment)
                    <div class="status" style="margin-bottom: 16px;">
                        <strong>Collector:</strong> {{ $assignment->collector_name }} |
                        <strong>Agent:</strong> {{ $assignment->agent_name }} |
                        <strong>Manager:</strong> {{ $assignment->manager_name }}
                        <a href="{{ route('add-members.staff', ['assignment' => $assignment->id]) }}" class="button is-ghost" style="margin-left: 12px;">Edit staff info</a>
                    </div>
                @endif

                @if (session('status'))
                    <div class="status">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ $isDraft ? '#' : route('add-members.store') }}" class="form-grid" id="enrollmentForm">
                    @csrf
                    <input type="hidden" name="member_assignment_id" value="{{ old('member_assignment_id', $assignmentId ?? '') }}">
                    <div>
                        <label for="user_id">User ID (auto)</label>
                        <input type="number" id="user_id" name="user_id" value="{{ old('user_id', $part1->user_id ?? $nextUserId ?? '') }}" placeholder="123" readonly>
                    </div>
                    <div>
                        <label for="lpaf_no">LPAF No.</label>
                        <input type="number" id="lpaf_no" name="lpaf_no" value="{{ old('lpaf_no', $part1->lpaf_no ?? '') }}" placeholder="10001" required>
                    </div>
                    <div>
                        <label for="application_date">Application Date</label>
                        <input type="date" id="application_date" name="application_date" value="{{ old('application_date', $part1->application_date ?? \Carbon\Carbon::now()->toDateString()) }}" required>
                    </div>
                    <div>
<!--  -->                        <label for="sales_counselor_code">Sales Counselor Code</label>
                        <input type="text" id="sales_counselor_code" name="sales_counselor_code" value="{{ old('sales_counselor_code', $part1->sales_counselor_code ?? '') }}" placeholder="SC-009" required>
                    </div>
                    <div>
                        <label for="plan_type">Plan / Service</label>
                        <select id="plan_type" name="plan_type" required>
                            <option value="" disabled {{ old('plan_type', $part1->plan_type ?? '') === null ? 'selected' : '' }}>Select a plan</option>
                            @foreach ($planSettings as $value => $meta)
                                <option value="{{ $value }}" {{ old('plan_type', $part1->plan_type ?? '') === $value ? 'selected' : '' }}>{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="gross_contact_price">Gross Contract Price</label>
                        <input type="text" id="gross_contact_price" name="gross_contact_price" value="{{ old('gross_contact_price', $part1->gross_contact_price ?? '') }}" placeholder="30,000" inputmode="decimal" autocomplete="off" readonly required>
                    </div>
                    <div>
                        <label for="mode_of_payment">Mode of Payment</label>
                        <select id="mode_of_payment" name="mode_of_payment" required>
                            <option value="" disabled {{ old('mode_of_payment', $part1->mode_of_payment ?? '') === null ? 'selected' : '' }}>Select an option</option>
                            @foreach (['Monthly', 'Quarterly', 'Semi-Annual', 'Annual', 'One-time'] as $option)
                                <option value="{{ $option }}" {{ old('mode_of_payment', $part1->mode_of_payment ?? '') === $option ? 'selected' : '' }}>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="terms_of_payment">Terms of Payment</label>
                        <input type="text" id="terms_of_payment" name="terms_of_payment" value="{{ old('terms_of_payment', $part1->terms_of_payment ?? '') }}" readonly required>
                    </div>
                    <div>
                        <label for="due_date">Due Date</label>
                        <input type="date" id="due_date" name="due_date" value="{{ old('due_date', $part1->due_date ?? \Carbon\Carbon::now()->addYears(5)->toDateString()) }}" required>
                    </div>
                    <div>
                        <label for="amount">Amount</label>
                        <input type="text" id="amount" name="amount" value="{{ old('amount', $part1->amount ?? '') }}" placeholder="15,000" inputmode="decimal" autocomplete="off" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit">{{ $isDraft ? 'Next' : 'Save & next' }}</button>
                    </div>
                </form>

                @isset($members)
                    <div class="data-table">
                        <div class="hero-title hero-small">Existing members</div>
                        @if ($members->count())
                            <div class="table-scroll">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>User ID</th>
                                            <th>LPAF No.</th>
                                            <th>Application Date</th>
                                            <th>Plan Type</th>
                                            <th>Mode</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($members as $member)
                                            <tr>
                                                <td>{{ $member->user_id }}</td>
                                                <td>{{ $member->lpaf_no }}</td>
                                                <td>{{ $member->application_date }}</td>
                                                <td>{{ $member->plan_type }}</td>
                                                <td>{{ $member->mode_of_payment }}</td>
                                                <td>{{ number_format($member->amount, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="hero-sub">No members yet. Save a new member to see it listed here.</p>
                        @endif
                    </div>
                @endisset
            </section>
        </main>

        @include('partials.footer')
    </div>

    <div class="status-modal {{ session('status') ? 'is-visible' : '' }}" id="statusModal" data-message="{{ session('status') }}">
        <div class="status-card">
            <div class="status-title">Success</div>
            <p class="status-body">{{ session('status') }}</p>
            <button type="button" class="status-close" aria-label="Close">Close</button>
        </div>
    </div>

    <div class="status-modal {{ $errors->any() ? 'is-visible' : '' }}" id="errorModal" data-message="{{ $errors->first() }}">
        <div class="status-card">
            <div class="status-title">Error</div>
            <p class="status-body">{{ $errors->first() }}</p>
            <button type="button" class="status-close" aria-label="Close">Close</button>
        </div>
    </div>

    <div id="planData" data-plans='@json($planSettings ?? [])'></div>

    <script>
        const plans = JSON.parse(document.getElementById('planData')?.dataset.plans || '{}');

        document.querySelectorAll('.progress-steps input[type="radio"]').forEach(radio => {
            radio.addEventListener('click', (e) => {
                e.preventDefault();
                const link = radio.closest('a');
                if (!link || link.classList.contains('is-disabled')) return;
                document.querySelectorAll('.progress-steps input[type="radio"]').forEach(r => r.checked = false);
                radio.checked = true;
                window.location.href = link.href;
            });
        });
        (() => {
            const modal = document.getElementById('statusModal');
            const closeBtn = modal?.querySelector('.status-close');
            const closeModal = () => modal?.classList.remove('is-visible');
            if (modal && modal.dataset.message) {
                modal.classList.add('is-visible');
                closeBtn?.addEventListener('click', closeModal);
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) closeModal();
                });
            } else {
                modal?.classList.remove('is-visible');
            }
        })();
        (() => {
            const modal = document.getElementById('errorModal');
            const closeBtn = modal?.querySelector('.status-close');
            const closeModal = () => modal?.classList.remove('is-visible');
            if (modal && modal.dataset.message) {
                modal.classList.add('is-visible');
                closeBtn?.addEventListener('click', closeModal);
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) closeModal();
                });
            } else {
                modal?.classList.remove('is-visible');
            }
        })();
        (() => {
            const isDraft = document.body.dataset.draft === "1";
            if (!isDraft) return;
            const DRAFT_KEY = "oneheart_member_draft_v1";
            const form = document.getElementById('enrollmentForm');
            const readDraft = () => {
                try {
                    return JSON.parse(localStorage.getItem(DRAFT_KEY)) || {};
                } catch {
                    return {};
                }
            };
            const writeDraft = (data) => localStorage.setItem(DRAFT_KEY, JSON.stringify(data));
            const getFormValues = (node) => {
                const data = {};
                node?.querySelectorAll('input, select, textarea').forEach(el => {
                    if (!el.name || el.name.endsWith('[]')) return;
                    if (el.type === 'radio') {
                        if (el.checked) data[el.name] = el.value;
                        return;
                    }
                    if (el.type === 'checkbox') {
                        data[el.name] = el.checked ? (el.value || true) : '';
                        return;
                    }
                    data[el.name] = el.value;
                });
                return data;
            };
            const fillForm = () => {
                const enrollment = readDraft().enrollment || {};
                Object.entries(enrollment).forEach(([key, val]) => {
                    const el = form?.querySelector(`[name="${key}"]`);
                    if (el && val !== undefined && val !== null) el.value = val;
                });
            };
            const saveDraft = () => {
                const draft = readDraft();
                draft.enrollment = { ...(draft.enrollment || {}), ...getFormValues(form) };
                writeDraft(draft);
            };

            form?.addEventListener('submit', (e) => {
                e.preventDefault();
                saveDraft();
                window.location.href = "{{ route('add-members.draft.part2') }}";
            });

            document.querySelectorAll('.progress-steps a').forEach(link => {
                link.addEventListener('click', (e) => {
                    if (!link.href || link.href.endsWith('#')) return;
                    e.preventDefault();
                    saveDraft();
                    window.location.href = link.href;
                });
            });

            fillForm();
        })();
        (() => {
            const planField = document.getElementById('plan_type');
            const grossField = document.getElementById('gross_contact_price');
            const modeField = document.getElementById('mode_of_payment');
            const termsField = document.getElementById('terms_of_payment');
            const amountField = document.getElementById('amount');
            const dueField = document.getElementById('due_date');
            const form = document.querySelector('form.form-grid');

           
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

            const termsByMode = {
                Monthly: '60 months',
                Quarterly: '20 quarters',
                'Semi-Annual': '10 simi annual',
                Annual: '5 years',
                'One-time': 'Infinite',
            };

            const populateTerms = (mode) => {
                if (!termsField) return;
                termsField.value = termsByMode[mode] || '';
            };

            const parsePayments = (terms, mode) => {
                const parsed = parseInt((terms || '').split(' ')[0], 10);
                if (Number.isFinite(parsed) && parsed > 0) return parsed;
                return (mode || '').toLowerCase() === 'one-time' ? 1 : 0;
            };

            const calcAmount = (plan, mode, terms, grossOverride) => {
                const meta = plans[plan] || {};
                return Number(grossOverride || meta.contract_amount || 0);
            };

            const hydrate = (plan) => {
                const meta = plans[plan];
                if (!meta) return;

                const isLegacy = plan === 'Legacy Care';

                if (grossField && (!grossField.value || grossField.dataset.autofill !== 'false')) {
                    grossField.value = formatNumber(meta.contract_amount);
                }

                if (modeField) {
                    modeField.disabled = false;
                    modeField.dataset.locked = isLegacy ? 'true' : 'false';
                    modeField.classList.toggle('is-locked', isLegacy);
                    if (isLegacy) {
                        modeField.value = 'One-time';
                    } else if (!modeField.value) {
                        modeField.value = meta.default_mode;
                    }
                }

                if (termsField) {
                    termsField.readOnly = true;
                    if (isLegacy) {
                        populateTerms('One-time');
                    } else {
                        populateTerms(modeField?.value || meta.default_mode);
                    }
                }

                if (dueField) {
                    dueField.disabled = false;
                    dueField.required = true;
                    if (!dueField.value) {
                        const fiveYears = new Date();
                        fiveYears.setFullYear(fiveYears.getFullYear() + 5);
                        dueField.value = fiveYears.toISOString().slice(0, 10);
                    }
                }
                if (amountField && (!amountField.value || amountField.dataset.autofill !== 'false')) {
                    const amt = calcAmount(
                        plan,
                        modeField?.value || meta.default_mode,
                        termsField?.value || meta.default_terms,
                        parseNumber(grossField?.value || meta.contract_amount)
                    );
                    amountField.value = formatNumber(amt);
                }
            };

            planField?.addEventListener('change', (e) => {
                hydrate(e.target.value);
            });

            modeField?.addEventListener('change', () => {
                if (modeField.dataset.locked === 'true') {
                    modeField.value = 'One-time';
                    populateTerms('One-time');
                    return;
                }
                if (!planField?.value) return;
                if (!amountField) return;
                populateTerms(modeField.value);
                const amt = calcAmount(planField.value, modeField.value, termsField?.value || '', parseNumber(grossField?.value || 0));
                amountField.value = formatNumber(amt);
            });

            grossField?.addEventListener('input', () => {
                if (!planField?.value || !amountField) return;
                const amt = calcAmount(planField.value, modeField?.value || '', termsField?.value || '', parseNumber(grossField?.value || 0));
                amountField.value = formatNumber(amt);
            });

            termsField?.addEventListener('change', () => {
                if (!planField?.value || !amountField) return;
                const amt = calcAmount(planField.value, modeField?.value || '', termsField?.value || '', parseNumber(grossField?.value || 0));
                amountField.value = formatNumber(amt);
            });

            if (planField?.value) {
                hydrate(planField.value);
            } else if (modeField?.value) {
                populateTerms(modeField.value);
            }

            if (grossField?.value) grossField.value = formatNumber(parseNumber(grossField.value));
            if (amountField?.value) amountField.value = formatNumber(parseNumber(amountField.value));

            form?.addEventListener('submit', () => {
                if (grossField) grossField.value = stripCommas(grossField.value);
                if (amountField) amountField.value = stripCommas(amountField.value);
            });
        })();
    </script>
</body>
</html>

