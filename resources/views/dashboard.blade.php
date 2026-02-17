<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('images/logo-oneheart.png') }}">
    <title>Dashboard | OneHeart Life Plan</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/partials/nav.css') . '?v=' . filemtime(public_path('css/partials/nav.css')) }}">
</head>
<body class="has-shell" data-show-welcome="{{ session('status') ? '1' : '0' }}" data-user-name="{{ auth()->user()->name ?? '' }}">
    <div class="page">
        @include('partials.header')

        <main class="dashboard">
            <section class="wrap dashboard-hero">
                <div class="dashboard-brand">
                    <img src="{{ asset('images/logo-oneheart.png') }}" alt="OneHeart Life Plan logo" class="dashboard-brand-logo">
                    <div class="dashboard-brand-text">OneHeart Life Plan</div>
                </div>
                <div class="hero-meta">
                    <span class="eyebrow">System overview</span>
                    <span class="pill">Live data</span>
                </div>
                <div class="hero-title">OneHeart Control Center</div>
                <p class="hero-sub">Role-scoped summary of collections, receivables, and upcoming due accounts.</p>
                <div class="table-stats">
                    <span class="stat-pill soft">Scope: <strong>{{ $scopeLabel ?? 'Role-based' }}</strong></span>
                    <span class="stat-pill soft">Last updated: <strong>{{ $lastUpdated ?? now()->format('M d, Y h:i A') }}</strong></span>
                </div>
                <div class="status-grid">
                    <div class="status-card">
                        <div class="label">Members in scope</div>
                        <div class="value">{{ number_format((int) ($summary['members_count'] ?? 0)) }}</div>
                        <div class="trend neutral">Based on your role access</div>
                    </div>
                    <div class="status-card">
                        <div class="label">Collected this month</div>
                        <div class="value">{{ number_format((float) ($summary['collected_month_net'] ?? 0), 2) }}</div>
                        <div class="trend positive">{{ (int) ($summary['collected_month_count'] ?? 0) }} paid transactions</div>
                    </div>
                    <div class="status-card">
                        <div class="label">Due today</div>
                        <div class="value">{{ number_format((float) ($summary['due_today_amount'] ?? 0), 2) }}</div>
                        <div class="trend warning">{{ (int) ($summary['due_today_count'] ?? 0) }} accounts due</div>
                    </div>
                    <div class="status-card">
                        <div class="label">Overdue</div>
                        <div class="value">{{ number_format((float) ($summary['overdue_amount'] ?? 0), 2) }}</div>
                        <div class="trend warning">{{ (int) ($summary['overdue_count'] ?? 0) }} overdue transactions</div>
                    </div>
                    <div class="status-card">
                        <div class="label">Collection efficiency</div>
                        <div class="value">{{ number_format((float) ($summary['collection_efficiency'] ?? 0), 2) }}%</div>
                        <div class="trend neutral">Paid due items this month</div>
                    </div>
                </div>
            </section>

            <section class="dashboard-grid">
                <article class="card chart-card wide">
                    <div class="card-head">
                        <div>
                            <div class="card-eyebrow">Collections</div>
                            <h3>Last 12 months net collection</h3>
                        </div>
                        <span class="chip chip-positive">Net amount</span>
                    </div>
                    <canvas id="collectionTrendChart" height="140"></canvas>
                </article>

                <article class="card chart-card">
                    <div class="card-head">
                        <div>
                            <div class="card-eyebrow">Payments</div>
                            <h3>Status distribution</h3>
                        </div>
                        <span class="chip chip-neutral">Paid/Pending/Overdue</span>
                    </div>
                    <canvas id="statusMixChart" height="120"></canvas>
                </article>

                <article class="card chart-card">
                    <div class="card-head">
                        <div>
                            <div class="card-eyebrow">Plans</div>
                            <h3>Plan type mix</h3>
                        </div>
                        <span class="chip chip-warning">Member count</span>
                    </div>
                    <canvas id="planMixChart" height="120"></canvas>
                </article>
            </section>

            <section class="dashboard-grid">
                <article class="card">
                    <div class="card-head">
                        <div>
                            <div class="card-eyebrow">Activity</div>
                            <h3>Recent paid transactions (this month)</h3>
                        </div>
                        <a href="{{ route('payment') }}" class="link-accent">Open payment</a>
                    </div>
                    <div class="table-scroll">
                        <table class="data-table modern compact">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Plan</th>
                                    <th>Paid At</th>
                                    <th class="text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentPayments as $row)
                                    <tr>
                                        <td>{{ $row['member'] }}</td>
                                        <td>{{ $row['plan'] }}</td>
                                        <td>{{ $row['paid_at'] ? \Carbon\Carbon::parse($row['paid_at'])->format('M d, Y h:i A') : '-' }}</td>
                                        <td class="text-right">{{ number_format((float) $row['amount'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="muted">No paid transactions this month.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="card">
                    <div class="card-head">
                        <div>
                            <div class="card-eyebrow">Focus List</div>
                            <h3>Upcoming dues (next 30 days)</h3>
                        </div>
                        <a href="{{ route('report') }}" class="link-accent">Open report</a>
                    </div>
                    <div class="table-scroll">
                        <table class="data-table modern compact">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Plan</th>
                                    <th>Due Date</th>
                                    <th>Window</th>
                                    <th class="text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($upcomingDues as $row)
                                    @php
                                        $chip = ($row['days_left'] ?? 0) <= 7 ? 'chip chip-warning' : 'chip chip-neutral';
                                        $label = ($row['days_left'] ?? 0) <= 7 ? 'Next 7 days' : 'Next 30 days';
                                    @endphp
                                    <tr>
                                        <td>{{ $row['member'] }}</td>
                                        <td>{{ $row['plan'] }}</td>
                                        <td>{{ $row['due_date'] }}</td>
                                        <td><span class="{{ $chip }}">{{ $label }}</span></td>
                                        <td class="text-right">{{ number_format((float) $row['amount'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="muted">No upcoming dues in 30 days.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </article>
            </section>
        </main>

        @include('partials.footer')
    </div>

    <div class="loading-overlay" id="pageLoader">
        <div class="spinner"></div>
        <div class="loading-text">Signing you in...</div>
    </div>

    <div class="welcome-modal" id="welcomeModal">
        <div class="welcome-card">
            <div class="welcome-badge">Welcome</div>
            <h2 class="welcome-title">Great to see you, <span class="welcome-name"></span>!</h2>
            <p class="welcome-copy">Your dashboard is ready. Let us check today's numbers.</p>
            <button type="button" class="welcome-close" aria-label="Close welcome">
                <span class="welcome-btn-text">Enter dashboard</span>
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            padding: 8px 10px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.78);
            border: 1px solid rgba(0, 0, 0, 0.06);
        }
        .dashboard-brand-logo {
            width: 36px;
            height: 36px;
            object-fit: contain;
            display: block;
        }
        .dashboard-brand-text {
            font-weight: 800;
            color: #1f2733;
            letter-spacing: 0.2px;
            font-size: 14px;
        }
        @media (max-width: 640px) {
            .dashboard-brand-logo {
                width: 32px;
                height: 32px;
            }
            .dashboard-brand-text {
                font-size: 13px;
            }
        }
    </style>
    <script type="application/json" id="collectionTrendPayload">@json($collectionTrend ?? ['labels' => [], 'values' => []])</script>
    <script type="application/json" id="statusMixPayload">@json($statusMix ?? ['labels' => [], 'values' => []])</script>
    <script type="application/json" id="planMixPayload">@json($planMix ?? ['labels' => [], 'values' => []])</script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const readJson = (id, fallback) => {
                const el = document.getElementById(id);
                if (!el) return fallback;
                try { return JSON.parse(el.textContent || '{}'); } catch (_) { return fallback; }
            };

            const collectionTrend = readJson('collectionTrendPayload', { labels: [], values: [] });
            const statusMix = readJson('statusMixPayload', { labels: [], values: [] });
            const planMix = readJson('planMixPayload', { labels: [], values: [] });

            const withCtx = (id) => {
                const el = document.getElementById(id);
                return el ? el.getContext('2d') : null;
            };

            const trendCtx = withCtx('collectionTrendChart');
            if (trendCtx) {
                new Chart(trendCtx, {
                    type: 'bar',
                    data: {
                        labels: collectionTrend.labels || [],
                        datasets: [{
                            label: 'Net Collection',
                            data: collectionTrend.values || [],
                            backgroundColor: 'rgba(255, 159, 28, 0.35)',
                            borderColor: '#ff9f1c',
                            borderWidth: 2,
                            borderRadius: 8,
                            barPercentage: 0.75
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(0,0,0,0.06)' },
                                ticks: { callback: (v) => Number(v).toLocaleString() }
                            },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }

            const statusCtx = withCtx('statusMixChart');
            if (statusCtx) {
                new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: statusMix.labels || [],
                        datasets: [{
                            data: statusMix.values || [],
                            backgroundColor: ['#2f9e44', '#6796ff', '#ff9f1c'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        cutout: '62%',
                        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }
                    }
                });
            }

            const planCtx = withCtx('planMixChart');
            if (planCtx) {
                new Chart(planCtx, {
                    type: 'bar',
                    data: {
                        labels: planMix.labels || [],
                        datasets: [{
                            label: 'Members',
                            data: planMix.values || [],
                            backgroundColor: 'rgba(103, 150, 255, 0.35)',
                            borderColor: '#6796ff',
                            borderWidth: 2,
                            borderRadius: 8,
                            barPercentage: 0.65
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(0,0,0,0.06)' } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }

            const body = document.body;
            const showWelcome = body.dataset.showWelcome === '1';
            const userName = body.dataset.userName || 'OneHeart member';
            const loader = document.getElementById('pageLoader');
            const modal = document.getElementById('welcomeModal');
            const nameTarget = modal?.querySelector('.welcome-name');
            const closeBtn = modal?.querySelector('.welcome-close');

            if (nameTarget) nameTarget.textContent = userName;

            const closeModal = () => modal?.classList.remove('is-visible');

            closeBtn?.addEventListener('click', closeModal);
            modal?.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });

            if (showWelcome) {
                loader?.classList.add('is-active');
                setTimeout(() => {
                    loader?.classList.remove('is-active');
                    modal?.classList.add('is-visible');
                }, 900);
            }
        });
    </script>
</body>
</html>

