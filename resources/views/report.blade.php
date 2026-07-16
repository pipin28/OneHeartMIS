<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ $appBrandLogoUrl }}">
    <title>Reports & Analytics | {{ $appBrandName }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/app.css') . '?v=' . filemtime(public_path('css/app.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/partials/nav.css') . '?v=' . filemtime(public_path('css/partials/nav.css')) }}">
</head>
<body class="has-shell">
    <div class="page">
        @include('partials.header')

        <main class="dashboard">
            <section class="wrap daily-report">
                <div class="report-head">
                    <div>
                        <div class="eyebrow">Daily Report</div>
                        <div class="hero-title hero-small">Cash detail report</div>
                        <p class="hero-sub">Paid collections for {{ $reportDateLabel }}.</p>
                    </div>
                    <form method="GET" action="{{ route('report') }}" class="report-date-filter">
                        <label>
                            <span>Date</span>
                            <input type="date" name="date" value="{{ $reportDate }}">
                        </label>
                        <button type="submit" class="button">Apply</button>
                        <button type="button" class="button is-ghost" onclick="window.print()">Print</button>
                    </form>
                </div>

                <div class="table-stats report-meta">
                    <span class="stat-pill soft">Scope: <strong>{{ $scopeLabel }}</strong></span>
                    <span class="stat-pill soft">Last updated: <strong>{{ $lastUpdated }}</strong></span>
                </div>

                <div class="report-status-grid">
                    <article class="status-card">
                        <div class="label">Gross Collection</div>
                        <div class="value">PHP {{ number_format((float) $summary['gross'], 2) }}</div>
                        <div class="trend neutral">{{ number_format((int) $summary['transactions']) }} transactions</div>
                    </article>
                    <article class="status-card">
                        <div class="label">Deductions</div>
                        <div class="value">PHP {{ number_format((float) $summary['deductions'], 2) }}</div>
                        <div class="trend neutral">Insurance and percentages</div>
                    </article>
                    <article class="status-card">
                        <div class="label">Net Collection</div>
                        <div class="value">PHP {{ number_format((float) $summary['net'], 2) }}</div>
                        <div class="trend positive">After deductions</div>
                    </article>
                </div>

                <article class="card report-card report-subscriber-card">
                    <div class="card-head">
                        <div>
                            <div class="card-eyebrow">Subscribers</div>
                            <h3>Members by contribution mode</h3>
                        </div>
                    </div>
                    <div class="subscriber-mode-grid">
                        @foreach ($subscriberModes as $mode)
                            <div class="subscriber-mode-item">
                                <span>{{ $mode['label'] }}</span>
                                <strong>{{ number_format((int) $mode['count']) }}</strong>
                            </div>
                        @endforeach
                    </div>
                </article>

                <article class="card report-card report-summary-card">
                    <div class="card-head">
                        <div>
                            <div class="card-eyebrow">Daily Summary</div>
                            <h3>Collections by type</h3>
                        </div>
                    </div>
                    <div class="table-scroll">
                        <table class="data-table modern compact">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th class="text-right">Count</th>
                                    <th class="text-right">Gross</th>
                                    <th class="text-right">Deductions</th>
                                    <th class="text-right">Net</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($typeTotals as $row)
                                    <tr>
                                        <td>{{ $row['type'] }}</td>
                                        <td class="text-right">{{ number_format((int) $row['count']) }}</td>
                                        <td class="text-right">PHP {{ number_format((float) $row['gross'], 2) }}</td>
                                        <td class="text-right">PHP {{ number_format((float) $row['deductions'], 2) }}</td>
                                        <td class="text-right">PHP {{ number_format((float) $row['net'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="muted text-center">No paid collections for this date.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="card report-card report-detail-card">
                    <div class="card-header table-toolbar">
                        <div>
                            <div class="card-title">Cash Detail Report</div>
                            <div class="card-subtitle">Daily paid transactions only</div>
                        </div>
                    </div>
                    <div class="table-scroll">
                        <table class="data-table modern compact">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Member</th>
                                    <th>Added By</th>
                                    <th>Reference No.</th>
                                    <th>Payment Ref.</th>
                                    <th>Type</th>
                                    <th>Plan</th>
                                    <th class="text-right">Gross</th>
                                    <th class="text-right">Deductions</th>
                                    <th class="text-right">Net</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($detailRows as $row)
                                    <tr>
                                        <td>{{ $row['time'] }}</td>
                                        <td>{{ $row['member'] }}</td>
                                        <td>{{ $row['added_by'] }}</td>
                                        <td>{{ $row['reference_number'] }}</td>
                                        <td>{{ $row['payment_reference'] }}</td>
                                        <td>{{ $row['payment_type'] }}</td>
                                        <td>{{ $row['plan_type'] }}</td>
                                        <td class="text-right">PHP {{ number_format((float) $row['gross'], 2) }}</td>
                                        <td class="text-right">PHP {{ number_format((float) $row['deductions'], 2) }}</td>
                                        <td class="text-right">PHP {{ number_format((float) $row['net'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="10" class="muted text-center">No paid collections for this date.</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="7">Total</th>
                                    <th class="text-right">PHP {{ number_format((float) $summary['gross'], 2) }}</th>
                                    <th class="text-right">PHP {{ number_format((float) $summary['deductions'], 2) }}</th>
                                    <th class="text-right">PHP {{ number_format((float) $summary['net'], 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </article>
            </section>
        </main>

        @include('partials.footer')
    </div>

    <style>
        .daily-report {
            display: block;
        }
        .report-head {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
        }
        .report-date-filter {
            display: flex;
            align-items: end;
            gap: 10px;
            flex-wrap: wrap;
        }
        .report-date-filter label {
            margin: 0;
            font-size: 12px;
            font-weight: 700;
            color: var(--muted);
        }
        .report-date-filter input[type="date"] {
            min-height: 38px;
            border-radius: 10px;
            border: 1px solid rgba(0, 0, 0, 0.12);
            background: #fff;
            padding: 8px 10px;
            font-family: inherit;
        }
        .report-meta,
        .report-card {
            margin-top: 16px;
        }
        .report-status-grid {
            margin-top: 18px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 12px;
        }
        .subscriber-mode-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 10px;
        }
        .subscriber-mode-item {
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 10px;
            padding: 14px;
            background: rgba(255, 255, 255, 0.72);
        }
        .subscriber-mode-item span {
            display: block;
            color: var(--muted);
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .subscriber-mode-item strong {
            display: block;
            margin-top: 6px;
            font-size: 28px;
            color: var(--text);
        }
        .data-table tfoot th {
            border-top: 1px solid rgba(0, 0, 0, 0.12);
            font-weight: 800;
        }
        @media (max-width: 900px) {
            .report-head {
                display: block;
            }
            .report-date-filter {
                margin-top: 14px;
            }
        }
        @media print {
            @page {
                size: landscape;
                margin: 10mm;
            }
            * {
                box-shadow: none !important;
                text-shadow: none !important;
            }
            .site-header,
            .site-footer,
            .report-date-filter {
                display: none !important;
            }
            html,
            body.has-shell {
                width: auto;
                padding: 0;
                background: #fff;
                color: #000;
                font-size: 11px;
            }
            .page {
                display: block;
                padding-top: 0;
                gap: 0;
                min-height: 0;
            }
            .dashboard,
            .wrap,
            .daily-report {
                display: block;
                width: 100%;
                max-width: none;
                margin: 0;
                padding: 0;
            }
            .report-head {
                display: block;
                margin-bottom: 10px;
            }
            .report-meta {
                display: block;
                margin: 6px 0 10px;
            }
            .stat-pill {
                display: inline-block;
                margin-right: 8px;
                padding: 0;
                border: 0;
                background: transparent;
                color: #000;
                font-size: 10px;
            }
            .eyebrow {
                font-size: 10px;
                color: #000;
                letter-spacing: 0;
            }
            .hero-title.hero-small {
                margin: 0;
                font-size: 18px;
                line-height: 1.2;
                color: #000;
            }
            .hero-sub {
                margin: 3px 0 0;
                color: #000;
                font-size: 11px;
            }
            .wrap,
            .card,
            .status-card {
                box-shadow: none;
            }
            .report-status-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 6px;
                margin: 8px 0 10px;
            }
            .status-card {
                display: block;
                padding: 6px;
                border: 1px solid #333;
                border-radius: 0;
                background: #fff;
                break-inside: avoid;
            }
            .status-card .label,
            .status-card .trend {
                color: #000;
                font-size: 9px;
            }
            .status-card .value {
                margin: 2px 0;
                color: #000;
                font-size: 13px;
                font-weight: 700;
            }
            .report-card,
            .report-detail-card,
            .report-subscriber-card,
            .report-summary-card {
                display: block;
                margin: 0 0 10px;
                padding: 0;
                border: 0;
                border-radius: 0;
                background: #fff;
                break-inside: avoid;
            }
            .card-head,
            .report-detail-card .card-header {
                display: block;
                margin: 0 0 5px;
                padding: 0;
                border: 0;
            }
            .card-eyebrow {
                color: #000;
                font-size: 9px;
            }
            .card-head h3,
            .card-title {
                margin: 0;
                color: #000;
                font-size: 12px;
                font-weight: 700;
            }
            .card-subtitle {
                color: #000;
                font-size: 9px;
            }
            .subscriber-mode-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 6px;
            }
            .subscriber-mode-item {
                padding: 5px;
                border: 1px solid #333;
                border-radius: 0;
                background: #fff;
            }
            .subscriber-mode-item span {
                color: #000;
                font-size: 9px;
            }
            .subscriber-mode-item strong {
                margin-top: 2px;
                color: #000;
                font-size: 13px;
                font-weight: 700;
            }
            .table-scroll {
                overflow: visible !important;
                width: 100%;
            }
            .data-table,
            .data-table.modern,
            .data-table.compact {
                width: 100% !important;
                min-width: 0 !important;
                border-collapse: collapse !important;
                table-layout: auto;
                background: #fff;
                color: #000;
                font-size: 9px;
            }
            .data-table th,
            .data-table td,
            .data-table.compact th,
            .data-table.compact td {
                padding: 4px 5px !important;
                border: 1px solid #333 !important;
                background: #fff !important;
                color: #000 !important;
                white-space: normal;
                vertical-align: top;
            }
            .data-table thead th {
                font-weight: 700;
                text-align: left;
            }
            .data-table .text-right,
            .text-right {
                text-align: right !important;
            }
            .data-table tfoot th {
                font-weight: 700;
                border-top: 1px solid #333 !important;
            }
        }
    </style>
</body>
</html>
