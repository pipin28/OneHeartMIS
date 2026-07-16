<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ $appBrandLogoUrl }}">
    <title>Add Members | {{ $appBrandName }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/app.css') . '?v=' . filemtime(public_path('css/app.css')) }}">
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
                        <a class="step-pill is-current" href="#">
                            <input type="radio" name="progress_step" checked aria-hidden="true">
                            <span>Member Enrollment</span>
                        </a>
                    @else
                        <a class="step-pill" href="{{ route('add-members.staff', ['assignment' => $assignmentId]) }}">
                            <input type="radio" name="progress_step" aria-hidden="true">
                            <span>Staff Info</span>
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
                        <a class="step-pill is-current" href="#">
                            <input type="radio" name="progress_step" checked aria-hidden="true">
                            <span>Member Enrollment</span>
                        </a>
                    @endif
                </div>
                <div class="eyebrow">Add Members</div>
                <div class="hero-title hero-small">Member enrollment</div>


                @if ($assignment)
                    <div class="status" style="margin-bottom: 16px;">
                        <strong>Unit:</strong> {{ $assignment->unit_name ?? '-' }} |
                        <strong>Agent:</strong> {{ $assignment->agent_name }} |
                        <strong>Unit Manager:</strong> {{ $assignment->manager_name }} |
                        <strong>Sales Associate:</strong> {{ $assignment->sales_associate ?? '-' }} |
                        <strong>Contact:</strong> {{ $assignment->staff_contact ?? '-' }}
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
                        <label for="added_by">Added By</label>
                        <input type="text" id="added_by" value="{{ $addedByUser->name ?? auth()->user()->name ?? '-' }}" readonly>
                    </div>
                    <div>
                        <label for="application_date">Application Date</label>
                        <input type="date" id="application_date" name="application_date" value="{{ old('application_date', $part1->application_date ?? \Carbon\Carbon::now()->toDateString()) }}" required>
                    </div>
                    <div>
                        <label for="approved_date">Approved Date</label>
                        <input type="date" id="approved_date" name="approved_date" value="{{ old('approved_date', $part1->approved_date ?? \Carbon\Carbon::now()->toDateString()) }}" required>
                    </div>
                    <div>
                        <label for="mode_of_payment">Contribution</label>
                        <select id="mode_of_payment" name="mode_of_payment" required>
                            <option value="" disabled {{ old('mode_of_payment', $part1->mode_of_payment ?? '') === null ? 'selected' : '' }}>Select an option</option>
                            @foreach (['Monthly', 'Quarterly', 'Semi-Annual', 'Annual'] as $option)
                                <option value="{{ $option }}" {{ old('mode_of_payment', $part1->mode_of_payment ?? '') === $option ? 'selected' : '' }}>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="registration_fee">Registration Fee</label>
                        <input type="text" id="registration_fee" value="{{ number_format((float) ($registrationFee ?? 300), 2) }}" readonly>
                    </div>
                    <div>
                        <label for="amount">Amount</label>
                        <input type="text" id="amount" value="{{ old('amount', isset($part1->amount) && (float) $part1->amount > 0 ? number_format((float) $part1->amount, 2) : '') }}" placeholder="Based on member age" readonly>
                    </div>
                    <div class="form-actions">
                        <button type="submit">{{ $isDraft ? 'Save member' : 'Save & next' }}</button>
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
                                            <th>Application Date</th>
                                            <th>Approved Date</th>
                                            <th>Age Category</th>
                                            <th>Contribution</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($members as $member)
                                            <tr>
                                                <td>{{ $member->user_id }}</td>
                                                <td>{{ $member->application_date }}</td>
                                                <td>{{ $member->approved_date }}</td>
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

            const currentCsrfToken = () => form?.querySelector('input[name="_token"]')?.value
                || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                || '';

            const setCsrfToken = (token) => {
                if (!token) return;
                const input = form?.querySelector('input[name="_token"]');
                const meta = document.querySelector('meta[name="csrf-token"]');
                if (input) input.value = token;
                if (meta) meta.setAttribute('content', token);
            };

            const refreshCsrfToken = async () => {
                const response = await fetch("{{ route('csrf-token') }}", {
                    method: "GET",
                    credentials: "same-origin",
                    headers: {
                        "Accept": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                });

                if (response.status === 401 || response.redirected) {
                    window.location.href = "{{ route('login') }}";
                    throw new Error("Session expired. Please log in again.");
                }

                if (!response.ok) {
                    throw new Error("Could not refresh the security token.");
                }

                const data = await response.json();
                setCsrfToken(data.token);

                return data.token || '';
            };

            const submitDraft = (payload, token) => fetch("{{ route('add-members.draft.submit') }}", {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": token,
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: JSON.stringify({ _token: token, ...payload }),
            });

            form?.addEventListener('submit', async (e) => {
                e.preventDefault();
                saveDraft();
                const draft = readDraft();
                const payload = {
                    staff: draft.staff || {},
                    enrollment: draft.enrollment || {},
                    member: draft.member || {},
                    address: draft.address || {},
                    beneficiaries: draft.beneficiaries || [],
                };

                try {
                    let token = currentCsrfToken();
                    let response = await submitDraft(payload, token);

                    if (response.status === 419) {
                        token = await refreshCsrfToken();
                        response = await submitDraft(payload, token);
                    }

                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw new Error(data?.message || "Save failed.");
                    }
                    localStorage.removeItem(DRAFT_KEY);
                    window.location.href = data.redirect || "{{ route('show-members') }}";
                } catch (err) {
                    alert(err.message || "Save failed.");
                }
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
            const amountField = document.getElementById('amount');
            const modeField = document.getElementById('mode_of_payment');
            if (!amountField) return;

            const formatNumber = (value) => {
                if (value === '' || value === null || value === undefined) return '';
                const num = Number(value);
                if (!Number.isFinite(num)) return '';
                return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            };
            const normalizeMode = (mode) => {
                const value = String(mode || '').trim().toLowerCase();
                if (value === 'semi annual') return 'semi-annual';
                if (value === 'yearly') return 'annual';
                return value;
            };
            const multiplierForMode = (mode) => {
                switch (normalizeMode(mode)) {
                    case 'quarterly':
                        return 3;
                    case 'semi-annual':
                        return 6;
                    case 'annual':
                        return 12;
                    default:
                        return 1;
                }
            };
            const amountByCategory = Object.entries(plans).reduce((carry, [category, meta]) => {
                carry[category] = Number(meta.contract_amount || 0);
                return carry;
            }, {});
            const categoryForAge = (age) => {
                const value = Number(age || 0);
                if (value >= 81) return 'Age 81 above';
                if (value >= 71) return 'Age 71 to 80';
                if (value >= 66) return 'Age 66 to 70';
                if (value >= 60) return 'Age 60 to 65';
                return '';
            };
            let baseAmount = Number(String(amountField.value || '').replace(/,/g, '')) || 0;
            const setAmount = (amount) => {
                baseAmount = Number(amount || 0);
                amountField.value = baseAmount > 0
                    ? formatNumber(baseAmount * multiplierForMode(modeField?.value))
                    : '';
            };
            const hydrateFromDraftAge = () => {
                try {
                    const draft = JSON.parse(localStorage.getItem("oneheart_member_draft_v1")) || {};
                    const category = categoryForAge(draft.member?.age);
                    if (category && amountByCategory[category] !== undefined) {
                        setAmount(amountByCategory[category]);
                    }
                } catch {
                    return;
                }
            };

            if (amountField.value) {
                setAmount(baseAmount);
            } else {
                hydrateFromDraftAge();
            }
            modeField?.addEventListener('change', () => setAmount(baseAmount));
        })();
    </script>
</body>
</html>
