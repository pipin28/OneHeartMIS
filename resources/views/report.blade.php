<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('images/logo-oneheart.png') }}">
    <title>Report | OneHeart Life Plan</title>
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
                <div class="eyebrow">Report</div>
                <div class="hero-title hero-small">Reports &amp; analytics</div>
                <p class="hero-sub">Collection, outstanding, aging, staff performance, and export-ready rows.</p>

                <form method="GET" action="{{ route('report') }}" class="form-actions report-filters">
                    <div class="button-group members-mode-group" role="group" aria-label="Quick date presets">
                        <a class="button is-ghost member-mode-filter {{ ($preset ?? 'month') === 'today' ? 'is-active' : '' }}" href="{{ route('report', ['preset' => 'today']) }}">Today</a>
                        <a class="button is-ghost member-mode-filter {{ ($preset ?? 'month') === 'week' ? 'is-active' : '' }}" href="{{ route('report', ['preset' => 'week']) }}">This Week</a>
                        <a class="button is-ghost member-mode-filter {{ ($preset ?? 'month') === 'month' ? 'is-active' : '' }}" href="{{ route('report', ['preset' => 'month']) }}">This Month</a>
                    </div>
                    <label>
                        <span>From</span>
                        <input type="date" name="start_date" value="{{ $startDate ?? '' }}">
                    </label>
                    <label>
                        <span>To</span>
                        <input type="date" name="end_date" value="{{ $endDate ?? '' }}">
                    </label>
                    <button type="submit" class="button">Apply</button>
                    <a href="{{ $csvUrl }}" class="button is-warning">Export CSV</a>
                    <button type="button" class="button is-ghost" onclick="window.print()">Print (PDF)</button>
                </form>

                <div class="table-stats report-meta">
                    <span class="stat-pill soft">Range: <strong>{{ $startDate }} to {{ $endDate }}</strong></span>
                    <span class="stat-pill soft">Scope: <strong>{{ $scopeLabel ?? 'Role-based' }}</strong></span>
                    <span class="stat-pill soft">Last updated: <strong>{{ $lastUpdated }}</strong></span>
                </div>

                <div class="report-status-grid">
                    <article class="status-card">
                        <div class="label">Net Collection</div>
                        <div class="value">{{ number_format((float) ($summary['collection_net'] ?? 0), 2) }}</div>
                        <div class="trend neutral">Gross {{ number_format((float) ($summary['collection_gross'] ?? 0), 2) }}</div>
                    </article>
                    <article class="status-card">
                        <div class="label">Deductions</div>
                        <div class="value">{{ number_format((float) ($summary['collection_deductions'] ?? 0), 2) }}</div>
                        <div class="trend neutral">Insurance + percentages</div>
                    </article>
                    <article class="status-card">
                        <div class="label">Outstanding</div>
                        <div class="value">{{ number_format((float) ($summary['outstanding'] ?? 0), 2) }}</div>
                        <div class="trend warning">Pending + overdue as of today</div>
                    </article>
                    <article class="status-card">
                        <div class="label">Collection Efficiency</div>
                        <div class="value">{{ number_format((float) ($summary['collection_efficiency'] ?? 0), 2) }}%</div>
                        <div class="trend neutral">{{ (int) ($summary['paid_count'] ?? 0) }} paid of {{ (int) ($summary['due_count'] ?? 0) }} due</div>
                    </article>
                    <article class="status-card">
                        <div class="label">Paid Amount (Due Range)</div>
                        <div class="value">{{ number_format((float) ($summary['paid_amount_due_range'] ?? 0), 2) }}</div>
                        <div class="trend positive">Payments marked paid</div>
                    </article>
                    <article class="status-card">
                        <div class="label">Unpaid Amount (Due Range)</div>
                        <div class="value">{{ number_format((float) ($summary['unpaid_amount_due_range'] ?? 0), 2) }}</div>
                        <div class="trend warning">Needs collection follow-up</div>
                    </article>
                    <article class="status-card">
                        <div class="label">Members In Scope</div>
                        <div class="value">{{ number_format((int) ($summary['members_count'] ?? 0)) }}</div>
                        <div class="trend neutral">Filtered by role scope</div>
                    </article>
                </div>

                <div class="dashboard-grid report-grid">
                    <article class="card chart-card wide">
                        <div class="card-head">
                            <div>
                                <div class="card-eyebrow">Collection Trend</div>
                                <h3>{{ $trend['bucket'] ?? 'Daily' }} net collection</h3>
                            </div>
                            <span class="chip chip-neutral">Max {{ number_format((float) ($trend['max'] ?? 0), 2) }}</span>
                        </div>
                        @if (empty($trend['series']) || collect($trend['series'])->every(fn($x) => (float) ($x['value'] ?? 0) <= 0))
                            <div class="empty-state">
                                <div class="empty-title">No paid data in selected range</div>
                            </div>
                        @else
                            <div class="trend-bars">
                                @foreach (($trend['series'] ?? []) as $point)
                                    @php
                                        $max = max((float) ($trend['max'] ?? 0), 1);
                                        $value = (float) ($point['value'] ?? 0);
                                        $height = max(6, (int) round(($value / $max) * 120));
                                    @endphp
                                    <div class="trend-bar-wrap" title="{{ $point['label'] }}: {{ number_format($value, 2) }}">
                                        <div class="trend-bar" style="height: {{ $height }}px"></div>
                                        <div class="trend-bar-label">{{ $point['label'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </article>

                    <article class="card">
                        <div class="card-head">
                            <div>
                                <div class="card-eyebrow">Aging</div>
                                <h3>Overdue buckets</h3>
                            </div>
                        </div>
                        <table class="data-table compact">
                            <tbody>
                                <tr><th>1-30 days</th><td class="text-right">{{ number_format((float) ($aging['1_30'] ?? 0), 2) }}</td></tr>
                                <tr><th>31-60 days</th><td class="text-right">{{ number_format((float) ($aging['31_60'] ?? 0), 2) }}</td></tr>
                                <tr><th>61+ days</th><td class="text-right">{{ number_format((float) ($aging['61_plus'] ?? 0), 2) }}</td></tr>
                            </tbody>
                        </table>
                    </article>

                    <article class="card">
                        <div class="card-head">
                            <div>
                                <div class="card-eyebrow">Deductions</div>
                                <h3>Insurance and role %</h3>
                            </div>
                        </div>
                        <table class="data-table compact">
                            <tbody>
                                <tr><th>Insurance total</th><td class="text-right">{{ number_format((float) ($deductionSummary['insurance_total'] ?? 0), 2) }}</td></tr>
                                <tr><th>Collector %</th><td class="text-right">{{ number_format((float) ($deductionSummary['roles']['collector'] ?? 0), 2) }}</td></tr>
                                <tr><th>Agent %</th><td class="text-right">{{ number_format((float) ($deductionSummary['roles']['agent'] ?? 0), 2) }}</td></tr>
                                <tr><th>Manager %</th><td class="text-right">{{ number_format((float) ($deductionSummary['roles']['manager'] ?? 0), 2) }}</td></tr>
                                <tr><th>Others %</th><td class="text-right">{{ number_format((float) ($deductionSummary['roles']['others'] ?? 0), 2) }}</td></tr>
                            </tbody>
                        </table>
                    </article>
                </div>

                <div class="card">
                    <div class="card-header table-toolbar">
                        <div>
                            <div class="card-title">Plan type performance</div>
                            <div class="card-subtitle">Members, collected, and outstanding by plan</div>
                        </div>
                    </div>
                    <div class="table-scroll">
                        <table class="data-table modern compact">
                            <thead>
                                <tr>
                                    <th>Plan Type</th>
                                    <th class="text-right">Members</th>
                                    <th class="text-right">Collected</th>
                                    <th class="text-right">Outstanding</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($planBreakdown as $row)
                                    <tr>
                                        <td>{{ $row['plan'] }}</td>
                                        <td class="text-right">{{ number_format((int) $row['members']) }}</td>
                                        <td class="text-right">{{ number_format((float) $row['collected'], 2) }}</td>
                                        <td class="text-right">{{ number_format((float) $row['outstanding'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="muted">No data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="dashboard-grid report-grid">
                    <article class="card">
                        <div class="card-head">
                            <div>
                                <div class="card-eyebrow">Mode Mix</div>
                                <h3>Payment mode distribution</h3>
                            </div>
                        </div>
                        <div class="table-scroll">
                            <table class="data-table modern compact">
                                <thead>
                                    <tr>
                                        <th>Mode</th>
                                        <th class="text-right">Members</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($modeBreakdown as $row)
                                        <tr>
                                            <td>{{ $row['mode'] }}</td>
                                            <td class="text-right">{{ number_format((int) $row['count']) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="2" class="muted">No data.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </article>

                    <article class="card">
                        <div class="card-head">
                            <div>
                                <div class="card-eyebrow">Top Unpaid</div>
                                <h3>Highest unpaid members</h3>
                            </div>
                        </div>
                        <div class="table-scroll">
                            <table class="data-table modern compact">
                                <thead>
                                    <tr>
                                        <th>Member</th>
                                        <th>Plan</th>
                                        <th class="text-right">Unpaid</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($topUnpaidMembers as $row)
                                        <tr>
                                            <td>{{ $row['member'] }}</td>
                                            <td>{{ $row['plan'] }}</td>
                                            <td class="text-right">{{ number_format((float) $row['unpaid'], 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="muted">No unpaid balances.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </article>
                </div>

                <div class="card">
                    <div class="card-header table-toolbar">
                        <div>
                            <div class="card-title">Upcoming due (next 30 days)</div>
                            <div class="card-subtitle">Prioritize collections due in 7 days first</div>
                        </div>
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
                                @forelse ($upcomingDue as $row)
                                    <tr>
                                        <td>{{ $row['member'] }}</td>
                                        <td>{{ $row['plan'] }}</td>
                                        <td>{{ $row['due_date'] }}</td>
                                        <td><span class="chip {{ $row['window'] === 'Next 7 days' ? 'chip-warning' : 'chip-neutral' }}">{{ $row['window'] }}</span></td>
                                        <td class="text-right">{{ number_format((float) $row['amount'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="muted">No upcoming dues.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="dashboard-grid report-grid">
                    @foreach (['collector' => 'Collector', 'agent' => 'Agent', 'manager' => 'Manager'] as $key => $label)
                        <article class="card">
                            <div class="card-head">
                                <div>
                                    <div class="card-eyebrow">Staff Performance</div>
                                    <h3>{{ $label }} leaderboard</h3>
                                </div>
                            </div>
                            <div class="table-scroll">
                                <table class="data-table modern compact">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th class="text-right">Members</th>
                                            <th class="text-right">Collected</th>
                                            <th class="text-right">Overdue Rate</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse (($staffPerformance[$key] ?? collect()) as $row)
                                            <tr>
                                                <td>{{ $row['name'] }}</td>
                                                <td class="text-right">{{ number_format((int) $row['members']) }}</td>
                                                <td class="text-right">{{ number_format((float) $row['collected'], 2) }}</td>
                                                <td class="text-right">{{ number_format((float) $row['overdue_rate'], 2) }}%</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="muted">No staff data.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        </main>

        @include('partials.footer')
    </div>

    <style>
        .report-filters {
            margin-top: 20px;
            justify-content: flex-start;
            gap: 10px;
            align-items: end;
            flex-wrap: wrap;
        }
        .report-filters label {
            margin: 0;
            font-size: 12px;
            font-weight: 700;
            color: var(--muted);
        }
        .report-filters input[type="date"] {
            min-height: 38px;
            border-radius: 10px;
            border: 1px solid rgba(0, 0, 0, 0.12);
            background: #fff;
            padding: 8px 10px;
            font-family: inherit;
        }
        .report-meta {
            margin-top: 10px;
        }
        .report-status-grid {
            margin-top: 18px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }
        .report-grid {
            margin-top: 16px;
        }
        .trend-bars {
            display: flex;
            align-items: flex-end;
            gap: 6px;
            min-height: 180px;
            padding: 8px 0 0;
            overflow-x: auto;
        }
        .trend-bar-wrap {
            min-width: 28px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }
        .trend-bar {
            width: 100%;
            border-radius: 8px 8px 0 0;
            background: linear-gradient(180deg, #ffb347, #ff9f1c);
        }
        .trend-bar-label {
            font-size: 11px;
            color: var(--muted);
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            max-height: 70px;
            overflow: hidden;
        }
        @media print {
            .site-header,
            .site-footer,
            .report-filters,
            .members-mode-group {
                display: none !important;
            }
            body.has-shell {
                padding: 0;
                background: #fff;
            }
            .page {
                padding-top: 0;
                gap: 0;
                min-height: 0;
            }
            .wrap {
                border: none;
                box-shadow: none;
                border-radius: 0;
                padding: 16px;
            }
            .card,
            .status-card {
                break-inside: avoid;
            }
        }
    </style>
</body>
</html>

