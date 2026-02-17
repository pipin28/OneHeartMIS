<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('images/logo-oneheart.png') }}">
    <title>Payment | OneHeart Life Plan</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/partials/nav.css') . '?v=' . filemtime(public_path('css/partials/nav.css')) }}">
</head>
<body class="has-shell">
    <div class="page">
        @include('partials.header')

        <main class="dashboard">
            <section class="wrap">
                <div class="eyebrow">Payment</div>
                <div class="hero-title hero-small">Member payments</div>
                <p class="hero-sub">Track member payment status alongside key enrollment details.</p>

                <div class="card">
                    <div class="card-header table-toolbar">
                        <div>
                            <div class="card-title">Payment overview</div>
                            <div class="card-subtitle">Latest members first</div>
                        </div>
                        <div class="table-stats">
                            <span class="stat-pill soft">Scope: <strong>{{ $scopeLabel ?? 'Role-based' }}</strong></span>
                        </div>
                    </div>

                    @if ($members->isEmpty())
                        <div class="empty-state">
                            <div class="empty-title">No members yet</div>
                            <p class="empty-body">Add a member to see payment status here.</p>
                        </div>
                    @else
                        <div class="table-scroll">
                            <table class="data-table modern compact" id="paymentTable">
                                <thead>
                                    <tr>
                                        <th>Planholder</th>
                                        <th>Plan Type</th>
                                        <th>Mode / Terms</th>
                                        <th>Due Date</th>
                                        <th class="text-right">Amount</th>
                                        <th>Payment Status</th>
                                        <th class="text-right">Action</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($members as $member)
                                        @php
                                            $part1 = $part1s[$member->part1_id] ?? null;
                                            $status = strtolower($part1->payment_status ?? 'pending');
                                            $nextPayment = $pendingPayments[$member->part1_id][0] ?? null;
                                            $history = $paymentHistories[$member->part1_id] ?? collect();
                                            $nextStatus = strtolower($nextPayment->status ?? $status);
                                            $nextLabel = match ($nextStatus) {
                                                'paid' => 'Paid',
                                                'overdue' => 'Overdue',
                                                default => 'Pending',
                                            };
                                            $nextClass = match ($nextStatus) {
                                                'paid' => 'chip chip-accent',
                                                'overdue' => 'chip chip-muted',
                                                default => 'chip chip-neutral',
                                            };
                                            $isFullyPaid = $history->isNotEmpty()
                                                && $history->every(fn($row) => strtolower($row->status ?? '') === 'paid');
                                        @endphp
                                        <tr data-status="{{ $nextStatus }}">
                                            <td class="table-col-primary">{{ trim($member->first_name . ' ' . ($member->midle_name ?? '') . ' ' . $member->surname) }}</td>
                                            <td>{{ $part1->plan_type ?? '-' }}</td>
                                            <td>{{ $part1 ? trim($part1->mode_of_payment . ' / ' . $part1->terms_of_payment, ' /') : '-' }}</td>
                                            <td>{{ $nextPayment?->due_date ?? ($part1?->due_date ?? '-') }}</td>
                                            <td class="text-right">{{ $nextPayment?->amount ? number_format($nextPayment->amount, 2) : ($part1?->amount ? number_format($part1->amount,2) : '-') }}</td>
                                            <td><span class="{{ $nextClass }}">{{ $nextLabel }}</span></td>
                                            <td>
                                                <div class="action-stack stacked-actions">
                                                    @if ($isFullyPaid)
                                                        <span class="chip chip-accent">Fully Paid</span>
                                                    @elseif ($nextPayment && $nextStatus !== 'paid')
                                                        <form method="POST" action="{{ route('payments.pay', ['payment' => $nextPayment->id]) }}" class="inline-form pay-form">
                                                            @csrf
                                                            <input type="hidden" name="reference" value="">
                                                            <button type="submit" class="button payment-action primary">Mark as Paid</button>
                                                        </form>
                                                    @elseif($nextPayment && $nextStatus === 'paid')
                                                        <span class="chip chip-accent">Paid</span>
                                                    @else
                                                        <span class="chip chip-neutral">No schedule</span>
                                                    @endif
                                                    <button
                                                        type="button"
                                                        class="button is-ghost payment-history-trigger secondary"
                                                        data-history='@json($history)'
                                                        data-member='@json($member)'
                                                        data-part1='@json($part1)'
                                                    >View Ledger</button>
                                                </div>
                                            </td>
                                            <td>{{ $member->created_at ? \Carbon\Carbon::parse($member->created_at)->format('M d, Y') : '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>
        </main>

        @include('partials.footer')
    </div>

    <div class="status-modal {{ session('status') ? 'is-visible' : '' }}" id="paymentSuccessModal" data-message="{{ session('status') }}">
        <div class="status-card">
            <div class="status-title">Success</div>
            <p class="status-body">{{ session('status') ?? 'Marked as paid successfully.' }}</p>
            <div class="form-actions" style="justify-content: flex-end; gap: 8px; margin-top: 12px;">
            </div>
            <button type="button" class="status-close" aria-label="Close">OK</button>
        </div>
    </div>

    <div class="status-modal" id="paymentConfirmModal" aria-hidden="true">
        <div class="status-card">
            <div class="status-title">Confirm</div>
            <p class="status-body">Are you sure you want to mark as paid?</p>
            <div class="form-actions" style="justify-content: center; gap: 8px; margin-top: 12px;">
                <button type="button" class="button" id="paymentConfirmCancel" style="background: #d32f2f; color: #fff; border-color: #d32f2f;">Cancel</button>
                <button type="button" class="button" id="paymentConfirmYes" style="background: #2e7d32; color: #fff; border-color: #2e7d32;">Yes</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="paymentHistoryModal" aria-hidden="true">
        <div class="modal-card modal-card-wide">
            <div class="modal-head">
                <div class="modal-head-left">
                    <div class="modal-title">Payment Ledger</div>
                    <div class="modal-subtitle" id="historySubtitle"></div>
                </div>
                <div class="modal-head-actions">
                    <div class="button-group" role="group" aria-label="Filter ledger">
                        <button type="button" class="button is-ghost payment-filter is-active" data-filter="pending">Pending</button>
                        <button type="button" class="button is-ghost payment-filter" data-filter="paid">Paid</button>
                    </div>
                    <button type="button" class="modal-close" aria-label="Close">&times;</button>
                </div>
            </div>
            <div class="table-scroll" style="max-height: 360px;">
                <table class="data-table modern compact" id="historyTable">
                    <thead>
                        <tr>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Deductions</th>
                            <th>Net</th>
                            <th>Status</th>
                            <th>Paid At</th>
                            <th>Reference</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="button is-ghost modal-close">Close</button>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const successModal = document.getElementById('paymentSuccessModal');
            const successClose = successModal?.querySelector('.status-close');
            const confirmModal = document.getElementById('paymentConfirmModal');
            const confirmCancel = document.getElementById('paymentConfirmCancel');
            const confirmYes = document.getElementById('paymentConfirmYes');
            const historyModal = document.getElementById('paymentHistoryModal');
            const historyTableBody = document.querySelector('#historyTable tbody');
            const historySubtitle = document.getElementById('historySubtitle');
            const filterButtons = document.querySelectorAll('.payment-filter');
            let currentFilter = 'pending';
            let pendingForm = null;

            const applyFilter = (filter) => {
                if (!historyTableBody) return;
                const rows = historyTableBody.querySelectorAll('tr[data-status]');
                if (!rows.length) return;
                rows.forEach(row => {
                    const status = row.dataset.status || '';
                    row.style.display = status === filter ? '' : 'none';
                });
            };

            const maybeShowStatus = () => {
                if (successModal?.dataset.message) {
                    successModal.classList.add('is-visible');
                    successModal.setAttribute('aria-hidden', 'false');
                }
            };

            const closeSuccess = () => {
                if (!successModal) return;
                successModal.classList.remove('is-visible');
                successModal.setAttribute('aria-hidden', 'true');
            };

            successClose?.addEventListener('click', closeSuccess);
            successModal?.addEventListener('click', (e) => {
                if (e.target === successModal) closeSuccess();
            });

            const openConfirm = (form) => {
                pendingForm = form;
                confirmModal?.classList.add('is-visible');
                confirmModal?.setAttribute('aria-hidden', 'false');
            };

            const closeConfirm = () => {
                pendingForm = null;
                confirmModal?.classList.remove('is-visible');
                confirmModal?.setAttribute('aria-hidden', 'true');
            };

            confirmCancel?.addEventListener('click', closeConfirm);
            confirmModal?.addEventListener('click', (e) => {
                if (e.target === confirmModal) closeConfirm();
            });
            confirmYes?.addEventListener('click', () => {
                if (pendingForm) pendingForm.submit();
                closeConfirm();
            });

            const renderHistory = (rows = []) => {
                if (!historyTableBody) return;
                if (!rows.length) {
                    historyTableBody.innerHTML = '<tr><td colspan="7" class="muted text-center">No payments yet.</td></tr>';
                    return;
                }
                const toTime = (value) => {
                    if (!value) return Number.MAX_SAFE_INTEGER;
                    const time = Date.parse(value);
                    return Number.isNaN(time) ? Number.MAX_SAFE_INTEGER : time;
                };
                const sortedRows = [...rows].sort((a, b) => toTime(a.due_date) - toTime(b.due_date));
                historyTableBody.innerHTML = sortedRows.map(r => {
                    const status = (r.status || '').toLowerCase();
                    const label = status === 'paid' ? 'Paid' : (status === 'overdue' ? 'Overdue' : 'Pending');
                    const cls = status === 'paid' ? 'chip chip-accent' : (status === 'overdue' ? 'chip chip-muted' : 'chip chip-neutral');
                    return `
                        <tr data-status="${status === 'overdue' ? 'pending' : status}">
                            <td>${r.due_date || '-'}</td>
                            <td class="text-right">${r.amount ? Number(r.amount).toLocaleString(undefined,{minimumFractionDigits:2}) : '-'}</td>
                            <td class="text-right">${r.insurance_total ? Number(r.insurance_total).toLocaleString(undefined,{minimumFractionDigits:2}) : '-'}</td>
                            <td class="text-right">${r.net_amount ? Number(r.net_amount).toLocaleString(undefined,{minimumFractionDigits:2}) : '-'}</td>
                            <td><span class="${cls}">${label}</span></td>
                            <td>${r.paid_at || '-'}</td>
                            <td>${r.reference || '-'}</td>
                        </tr>
                    `;
                }).join('');
                applyFilter(currentFilter);
            };

            document.querySelectorAll('.payment-history-trigger').forEach(btn => {
                btn.addEventListener('click', () => {
                    const history = JSON.parse(btn.dataset.history || '[]');
                    const member = JSON.parse(btn.dataset.member || '{}');
                    historySubtitle.textContent = [member.first_name, member.midle_name, member.surname].filter(Boolean).join(' ') || 'Member';
                    renderHistory(history);
                    historyModal?.classList.add('is-visible');
                    historyModal?.setAttribute('aria-hidden', 'false');
                });
            });

            document.querySelectorAll('.pay-form').forEach(form => {
                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    openConfirm(form);
                });
            });

            historyModal?.addEventListener('click', (e) => {
                if (e.target === historyModal || e.target.classList.contains('modal-close')) {
                    historyModal.classList.remove('is-visible');
                    historyModal.setAttribute('aria-hidden', 'true');
                }
            });

            filterButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    filterButtons.forEach(b => b.classList.remove('is-active'));
                    btn.classList.add('is-active');
                    currentFilter = btn.dataset.filter || 'pending';
                    applyFilter(currentFilter);
                });
            });

            maybeShowStatus();
        })();
    </script>
</body>
</html>

