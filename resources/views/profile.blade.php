<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ $appBrandLogoUrl }}">
    <title>Profile | {{ $appBrandName }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/app.css') . '?v=' . filemtime(public_path('css/app.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/partials/nav.css') . '?v=' . filemtime(public_path('css/partials/nav.css')) }}">
</head>
<body class="has-shell">
    <div class="page">
        @include('partials.header')

        <main class="dashboard">
            <section class="wrap">
                <div class="eyebrow">Profile</div>
                <div class="hero-title hero-small">Account information</div>
                <p class="hero-sub">Read-only details for your logged-in account.</p>

                <div class="card">
                    <div class="card-header table-toolbar">
                        <div>
                            <div class="card-title">User details</div>
                            <div class="card-subtitle">Current session account</div>
                        </div>
                        @if ($summary['assigned_members'] !== null)
                            <div class="form-actions">
                                <button type="button" class="button is-ghost" id="openPercentageHistoryModal">Percentage Monthly History</button>
                            </div>
                        @endif
                    </div>
                    <div class="table-scroll">
                        <table class="data-table modern compact">
                            <tbody>
                                <tr><th>User ID</th><td>{{ $user->id }}</td></tr>
                                <tr><th>Name</th><td>{{ $user->name }}</td></tr>
                                <tr><th>Username</th><td>{{ $user->username }}</td></tr>
                                <tr><th>Role</th><td>{{ ucfirst($user->role) }}</td></tr>
                                <tr><th>Email Verified At</th><td>{{ $user->email_verified_at ? \Carbon\Carbon::parse($user->email_verified_at)->format('M d, Y h:i A') : '-' }}</td></tr>
                                <tr><th>Created At</th><td>{{ $user->created_at ? \Carbon\Carbon::parse($user->created_at)->format('M d, Y h:i A') : '-' }}</td></tr>
                                <tr><th>Updated At</th><td>{{ $user->updated_at ? \Carbon\Carbon::parse($user->updated_at)->format('M d, Y h:i A') : '-' }}</td></tr>
                                @if ($summary['assigned_members'] !== null)
                                    <tr><th>Assigned Members</th><td>{{ $summary['assigned_members'] }}</td></tr>
                                    <tr><th>Percentage Amount (This Month)</th><td>{{ number_format((float) $summary['percentage_this_month'], 2) }}</td></tr>
                                    <tr><th>Percentage Amount (Last Month)</th><td>{{ number_format((float) $summary['percentage_last_month'], 2) }}</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>

        @include('partials.footer')
    </div>

    @if ($summary['assigned_members'] !== null)
        <div class="modal-overlay" id="percentageHistoryModal" aria-hidden="true">
            <div class="modal-card modal-card-wide">
                <div class="modal-head">
                    <div>
                        <div class="modal-title">Percentage Monthly History</div>
                        <div class="modal-subtitle">Month and total percentage amount.</div>
                    </div>
                    <button type="button" class="modal-close" aria-label="Close">&times;</button>
                </div>
                <div class="form-grid" style="padding: 0 0 12px 0;">
                    <div>
                        <label for="percentageHistoryMonth">Select Month</label>
                        <input type="month" id="percentageHistoryMonth">
                    </div>
                </div>
                <div class="table-scroll" style="max-height: 360px;">
                    <table class="data-table modern compact">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th class="text-right">Percentage Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($summary['percentage_monthly_history'] as $row)
                                <tr data-month="{{ $row['month_key'] }}">
                                    <td>{{ $row['month'] }}</td>
                                    <td class="text-right">{{ number_format((float) $row['amount'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="muted text-center">No percentage history yet.</td>
                                </tr>
                            @endforelse
                            <tr id="percentageHistoryNoMatch" style="display:none;">
                                <td colspan="2" class="muted text-center">No percentage history for selected month.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="button is-ghost modal-close">Close</button>
                </div>
            </div>
        </div>
    @endif

    <script>
        (() => {
            const trigger = document.getElementById('openPercentageHistoryModal');
            const modal = document.getElementById('percentageHistoryModal');
            if (!trigger || !modal) return;
            const closeEls = modal.querySelectorAll('.modal-close');
            const monthInput = document.getElementById('percentageHistoryMonth');
            const body = modal.querySelector('tbody');
            const noMatchRow = document.getElementById('percentageHistoryNoMatch');

            const applyMonthFilter = () => {
                if (!body) return;
                const selected = monthInput?.value || '';
                const rows = body.querySelectorAll('tr[data-month]');
                if (!rows.length) return;
                let shown = 0;
                rows.forEach((row) => {
                    const key = row.dataset.month || '';
                    const visible = !selected || key === selected;
                    row.style.display = visible ? '' : 'none';
                    if (visible) shown++;
                });
                if (noMatchRow) {
                    noMatchRow.style.display = shown === 0 ? '' : 'none';
                }
            };

            const open = () => {
                if (monthInput && !monthInput.value) {
                    monthInput.value = new Date().toISOString().slice(0, 7);
                }
                applyMonthFilter();
                modal.classList.add('is-visible');
                modal.setAttribute('aria-hidden', 'false');
            };
            const close = () => {
                modal.classList.remove('is-visible');
                modal.setAttribute('aria-hidden', 'true');
            };

            trigger.addEventListener('click', open);
            monthInput?.addEventListener('change', applyMonthFilter);
            closeEls.forEach(btn => btn.addEventListener('click', close));
            modal.addEventListener('click', (e) => {
                if (e.target === modal) close();
            });
        })();
    </script>
</body>
</html>

