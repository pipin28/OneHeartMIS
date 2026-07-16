<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ $appBrandLogoUrl }}">
    <title>Claimed Members | {{ $appBrandName }}</title>
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
                <div class="eyebrow">Members</div>
                <div class="hero-title hero-small">Claimed members</div>
                <p class="hero-sub">Members marked as claimed from the member directory.</p>

                <div class="card">
                    <div class="card-header table-toolbar">
                        <div>
                            <div class="card-title">Claimed Records</div>
                            <div class="card-subtitle">Hidden from Show Members</div>
                        </div>
                        <div class="table-stats">
                            <span class="stat-pill soft">Scope: <strong>{{ $scopeLabel ?? 'Role-based' }}</strong></span>
                            <span class="stat-pill">Total: <strong>{{ $members->count() }}</strong></span>
                        </div>
                    </div>

                    @if ($members->isEmpty())
                        <div class="empty-state">
                            <div class="empty-title">No claimed members</div>
                            <p class="empty-body">Claimed members will appear here.</p>
                        </div>
                    @else
                        <div class="form-actions claimed-members-filters">
                            <input type="search" id="claimedMemberSearch" class="members-search" placeholder="Search name, contact, address, category, or date">
                        </div>
                        <div class="table-scroll">
                            <table class="data-table modern compact" id="claimedMembersTable">
                                <thead>
                                    <tr>
                                        <th>Planholder</th>
                                        <th>Contact</th>
                                        <th>Address</th>
                                        <th>Age Category</th>
                                        <th class="text-right">Contributed</th>
                                        <th class="text-right">Cash / Refund</th>
                                        <th class="text-right">Burial</th>
                                        <th class="text-right">Total Benefit</th>
                                        <th>Claimed At</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($members as $member)
                                        @php
                                            $part1 = $part1s[$member->part1_id] ?? null;
                                            $address = $addresses[$member->id] ?? null;
                                            $fullName = trim($member->first_name . ' ' . ($member->midle_name ?? '') . ' ' . $member->surname);
                                            $claimedAt = !empty($part1?->claimed_at)
                                                ? \Carbon\Carbon::parse($part1->claimed_at)->format('M d, Y')
                                                : '-';
                                            $benefit = $claimBenefits[$member->part1_id] ?? [];
                                            $claimMonths = (int) ($benefit['claim_contribution_months'] ?? 0);
                                            $cashAssistance = (float) ($benefit['claim_cash_assistance'] ?? 0);
                                            $burialAssistance = (float) ($benefit['claim_burial_assistance'] ?? 0);
                                            $totalBenefit = (float) ($benefit['claim_total_amount'] ?? ($cashAssistance + $burialAssistance));
                                        @endphp
                                        <tr
                                            class="claimed-member-row"
                                            data-search="{{ strtolower(trim($fullName . ' ' . ($address->contact_no ?? '') . ' ' . ($address->complete_address ?? '') . ' ' . ($part1->plan_type ?? '') . ' ' . $claimedAt . ' claimed')) }}"
                                        >
                                            <td class="table-col-primary">{{ $fullName }}</td>
                                            <td>{{ $address->contact_no ?? '-' }}</td>
                                            <td>{{ $address->complete_address ?? '-' }}</td>
                                            <td>{{ $part1->plan_type ?? '-' }}</td>
                                            <td class="text-right">{{ $claimMonths }} month(s)</td>
                                            <td class="text-right">{{ number_format($cashAssistance, 2) }}</td>
                                            <td class="text-right">{{ number_format($burialAssistance, 2) }}</td>
                                            <td class="text-right"><strong>{{ number_format($totalBenefit, 2) }}</strong></td>
                                            <td>{{ $claimedAt }}</td>
                                            <td><span class="chip chip-accent">Claimed</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="members-pagination" id="claimedMembersPagination">
                            <div class="members-pagination-info" id="claimedMembersPageInfo">Page 1 of 1</div>
                            <div class="members-page-numbers" id="claimedMembersPageNumbers"></div>
                            <button type="button" class="button is-ghost" id="claimedMembersNextPage">Next</button>
                        </div>
                    @endif
                </div>
            </section>
        </main>

        @include('partials.footer')
    </div>
    <style>
        .claimed-members-filters {
            justify-content: flex-start;
            margin: 0 0 14px;
        }
    </style>
    <script>
        (() => {
            const rows = Array.from(document.querySelectorAll(".claimed-member-row"));
            const searchInput = document.getElementById("claimedMemberSearch");
            const pageInfo = document.getElementById("claimedMembersPageInfo");
            const pageNumbers = document.getElementById("claimedMembersPageNumbers");
            const nextButton = document.getElementById("claimedMembersNextPage");
            const perPage = 8;
            let currentPage = 1;

            const getFilteredRows = () => {
                const term = (searchInput?.value || "").toLowerCase().trim();
                if (!term) return rows;
                return rows.filter(row => (row.dataset.search || "").includes(term));
            };

            const renderPageNumbers = (totalPages) => {
                if (!pageNumbers) return;
                pageNumbers.innerHTML = "";
                for (let page = 1; page <= totalPages; page++) {
                    const btn = document.createElement("button");
                    btn.type = "button";
                    btn.className = `button is-ghost members-page-btn ${page === currentPage ? "is-active" : ""}`;
                    btn.textContent = String(page);
                    btn.addEventListener("click", () => {
                        currentPage = page;
                        renderPage();
                    });
                    pageNumbers.appendChild(btn);
                }
            };

            const renderPage = () => {
                if (!rows.length) return;

                const filteredRows = getFilteredRows();
                const totalPages = Math.max(1, Math.ceil(filteredRows.length / perPage));
                if (currentPage > totalPages) currentPage = totalPages;

                const start = (currentPage - 1) * perPage;
                const visibleRows = new Set(filteredRows.slice(start, start + perPage));

                rows.forEach((row) => {
                    row.style.display = visibleRows.has(row) ? "" : "none";
                });

                if (pageInfo) {
                    pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
                }
                if (nextButton) {
                    nextButton.disabled = currentPage >= totalPages;
                }
                renderPageNumbers(totalPages);
            };

            nextButton?.addEventListener("click", () => {
                currentPage += 1;
                renderPage();
            });
            searchInput?.addEventListener("input", () => {
                currentPage = 1;
                renderPage();
            });

            renderPage();
        })();
    </script>
</body>
</html>
