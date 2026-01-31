<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                    </div>

                    @if ($members->isEmpty())
                        <div class="empty-state">
                            <div class="empty-title">No members yet</div>
                            <p class="empty-body">Add a member to see payment status here.</p>
                        </div>
                    @else
                        <div class="table-scroll">
                            <table class="data-table modern compact">
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
                                                    @if ($nextPayment && $nextStatus !== 'paid')
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

    <div class="modal-overlay" id="paymentHistoryModal" aria-hidden="true">
        <div class="modal-card modal-card-wide">
            <div class="modal-head">
                <div class="modal-head-left">
                    <div class="modal-title">Payment Ledger</div>
                    <div class="modal-subtitle" id="historySubtitle"></div>
                </div>
                <div class="modal-head-actions">
                    <button type="button" class="modal-close" aria-label="Close">&times;</button>
                </div>
            </div>
            <div class="table-scroll" style="max-height: 360px;">
                <table class="data-table modern compact" id="historyTable">
                    <thead>
                        <tr>
                            <th>Due Date</th>
                            <th>Amount</th>
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
            const historyModal = document.getElementById('paymentHistoryModal');
            const historyTableBody = document.querySelector('#historyTable tbody');
            const historySubtitle = document.getElementById('historySubtitle');

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

            const renderHistory = (rows = []) => {
                if (!historyTableBody) return;
                if (!rows.length) {
                    historyTableBody.innerHTML = '<tr><td colspan="5" class="muted text-center">No payments yet.</td></tr>';
                    return;
                }
                historyTableBody.innerHTML = rows.map(r => {
                    const status = (r.status || '').toLowerCase();
                    const label = status === 'paid' ? 'Paid' : (status === 'overdue' ? 'Overdue' : 'Pending');
                    const cls = status === 'paid' ? 'chip chip-accent' : (status === 'overdue' ? 'chip chip-muted' : 'chip chip-neutral');
                    return `
                        <tr>
                            <td>${r.due_date || '-'}</td>
                            <td class="text-right">${r.amount ? Number(r.amount).toLocaleString(undefined,{minimumFractionDigits:2}) : '-'}</td>
                            <td><span class="${cls}">${label}</span></td>
                            <td>${r.paid_at || '-'}</td>
                            <td>${r.reference || '-'}</td>
                        </tr>
                    `;
                }).join('');
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

            historyModal?.addEventListener('click', (e) => {
                if (e.target === historyModal || e.target.classList.contains('modal-close')) {
                    historyModal.classList.remove('is-visible');
                    historyModal.setAttribute('aria-hidden', 'true');
                }
            });

            maybeShowStatus();
        })();
    </script>
</body>
</html>
