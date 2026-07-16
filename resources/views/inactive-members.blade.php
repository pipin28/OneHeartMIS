<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ $appBrandLogoUrl }}">
    <title>Inactive Members | {{ $appBrandName }}</title>
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
                <div class="hero-title hero-small">Inactive members</div>
                <p class="hero-sub">Members moved out of the active list after 30 days without payment.</p>

                <div class="card">
                    <div class="card-header table-toolbar">
                        <div>
                            <div class="card-title">Inactive Records</div>
                            <div class="card-subtitle">Hidden from Show Members</div>
                        </div>
                        <div class="table-stats">
                            <span class="stat-pill soft">Scope: <strong>{{ $scopeLabel ?? 'Role-based' }}</strong></span>
                            <span class="stat-pill">Total: <strong>{{ $members->count() }}</strong></span>
                        </div>
                    </div>

                    @if ($members->isEmpty())
                        <div class="empty-state">
                            <div class="empty-title">No inactive members</div>
                            <p class="empty-body">Members will appear here once they reach 30 days without payment.</p>
                        </div>
                    @else
                        <div class="form-actions inactive-members-filters">
                            <input type="search" id="inactiveMemberSearch" class="members-search" placeholder="Search name, contact, address, plan, date, or status">
                        </div>
                        <div class="table-scroll">
                            <table class="data-table modern compact" id="inactiveMembersTable">
                                <thead>
                                    <tr>
                                        <th>Planholder</th>
                                        <th>Contact</th>
                                        <th>Address</th>
                                        <th>Plan Type</th>
                                        <th>Last Paid</th>
                                        <th>Next Unpaid Due</th>
                                        <th class="text-right">Days</th>
                                        <th>Status</th>
                                        <th class="text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($members as $member)
                                        @php
                                            $part1 = $part1s[$member->part1_id] ?? null;
                                            $address = $addresses[$member->id] ?? null;
                                            $lifecycle = $paymentLifecycle[$member->part1_id] ?? [];
                                            $fullName = trim($member->first_name . ' ' . ($member->midle_name ?? '') . ' ' . $member->surname);
                                            $lastPaid = !empty($lifecycle['last_paid_at'])
                                                ? \Carbon\Carbon::parse($lifecycle['last_paid_at'])->format('M d, Y')
                                                : '-';
                                            $nextDue = !empty($lifecycle['next_unpaid_due_date'])
                                                ? \Carbon\Carbon::parse($lifecycle['next_unpaid_due_date'])->format('M d, Y')
                                                : '-';
                                        @endphp
                                        <tr
                                            class="payment-row-state-inactive inactive-member-row"
                                            data-search="{{ strtolower(trim($fullName . ' ' . ($address->contact_no ?? '') . ' ' . ($address->complete_address ?? '') . ' ' . ($part1->plan_type ?? '') . ' ' . $lastPaid . ' ' . $nextDue . ' inactive')) }}"
                                        >
                                            <td class="table-col-primary">{{ $fullName }}</td>
                                            <td>{{ $address->contact_no ?? '-' }}</td>
                                            <td>{{ $address->complete_address ?? '-' }}</td>
                                            <td>{{ $part1->plan_type ?? '-' }}</td>
                                            <td>{{ $lastPaid }}</td>
                                            <td>{{ $nextDue }}</td>
                                            <td class="text-right">{{ (int) ($lifecycle['days_without_payment'] ?? 30) }}</td>
                                            <td><span class="chip chip-muted">Inactive</span></td>
                                            <td class="table-action">
                                                <button
                                                    type="button"
                                                    class="button is-primary contestability-member"
                                                    data-member-id="{{ $member->id }}"
                                                    data-member-name="{{ $fullName }}"
                                                >Contestability</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="members-pagination" id="inactiveMembersPagination">
                            <div class="members-pagination-info" id="inactiveMembersPageInfo">Page 1 of 1</div>
                            <div class="members-page-numbers" id="inactiveMembersPageNumbers"></div>
                            <button type="button" class="button is-ghost" id="inactiveMembersNextPage">Next</button>
                        </div>
                    @endif
                </div>
            </section>
        </main>

        @include('partials.footer')
    </div>
    <div class="modal-overlay" id="contestabilityConfirmModal" aria-hidden="true">
        <div class="modal-card modal-card-narrow">
            <div class="modal-head">
                <div>
                    <div class="modal-title">Contestability</div>
                    <div class="modal-subtitle" id="contestabilityMemberName">Review contestability fee</div>
                </div>
                <button type="button" class="modal-close" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <ul class="modal-list">
                    <li>
                        <span>Contestability Fee</span>
                        <strong>PHP {{ number_format((float) ($contestabilityFee ?? 0), 2) }}</strong>
                    </li>
                    <li>
                        <span>Status after payment</span>
                        <strong>Active</strong>
                    </li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="button is-ghost" data-contestability-cancel>Cancel</button>
                <button type="button" class="button is-primary" data-contestability-confirm>Confirm</button>
            </div>
        </div>
    </div>
    <style>
        .inactive-members-filters {
            justify-content: flex-start;
            margin: 0 0 14px;
        }
    </style>
    <script>
        (() => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const rows = Array.from(document.querySelectorAll('.inactive-member-row'));
            const searchInput = document.getElementById('inactiveMemberSearch');
            const pageInfo = document.getElementById('inactiveMembersPageInfo');
            const pageNumbers = document.getElementById('inactiveMembersPageNumbers');
            const nextButton = document.getElementById('inactiveMembersNextPage');
            const contestabilityModal = document.getElementById('contestabilityConfirmModal');
            const contestabilityMemberName = document.getElementById('contestabilityMemberName');
            const contestabilityConfirm = document.querySelector('[data-contestability-confirm]');
            const contestabilityCancel = document.querySelector('[data-contestability-cancel]');
            const perPage = 8;
            let currentPage = 1;
            let selectedContestabilityButton = null;

            const toggleContestabilityModal = (show) => {
                if (!contestabilityModal) return;
                contestabilityModal.classList.toggle('is-visible', show);
                contestabilityModal.setAttribute('aria-hidden', show ? 'false' : 'true');
            };

            const getActiveRows = () => rows.filter(row => row.isConnected);
            const getFilteredRows = () => {
                const term = (searchInput?.value || '').toLowerCase().trim();
                const activeRows = getActiveRows();
                if (!term) return activeRows;
                return activeRows.filter(row => (row.dataset.search || '').includes(term));
            };

            const renderPageNumbers = (totalPages) => {
                if (!pageNumbers) return;
                pageNumbers.innerHTML = '';
                for (let page = 1; page <= totalPages; page++) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = `button is-ghost members-page-btn ${page === currentPage ? 'is-active' : ''}`;
                    btn.textContent = String(page);
                    btn.addEventListener('click', () => {
                        currentPage = page;
                        renderPage();
                    });
                    pageNumbers.appendChild(btn);
                }
            };

            const renderPage = () => {
                const activeRows = getActiveRows();
                if (!activeRows.length) {
                    if (pageInfo) pageInfo.textContent = 'Page 1 of 1';
                    if (nextButton) nextButton.disabled = true;
                    if (pageNumbers) pageNumbers.innerHTML = '';
                    return;
                }

                const filteredRows = getFilteredRows();
                const totalPages = Math.max(1, Math.ceil(filteredRows.length / perPage));
                if (currentPage > totalPages) currentPage = totalPages;

                const start = (currentPage - 1) * perPage;
                const visibleRows = new Set(filteredRows.slice(start, start + perPage));

                activeRows.forEach((row) => {
                    row.style.display = visibleRows.has(row) ? '' : 'none';
                });

                if (pageInfo) {
                    pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
                }
                if (nextButton) {
                    nextButton.disabled = currentPage >= totalPages;
                }
                renderPageNumbers(totalPages);
            };

            document.querySelectorAll('.contestability-member').forEach(btn => {
                btn.addEventListener('click', () => {
                    selectedContestabilityButton = btn;
                    if (contestabilityMemberName) {
                        contestabilityMemberName.textContent = btn.dataset.memberName || 'Review contestability fee';
                    }
                    toggleContestabilityModal(true);
                });
            });

            contestabilityConfirm?.addEventListener('click', async () => {
                const btn = selectedContestabilityButton;
                const memberId = btn?.dataset.memberId;
                if (!btn || !memberId) {
                    toggleContestabilityModal(false);
                    return;
                }

                contestabilityConfirm.setAttribute('disabled', 'true');
                btn.setAttribute('disabled', 'true');
                try {
                    const response = await fetch(`/members/${memberId}/contestability`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        const payload = await response.json().catch(() => ({}));
                        throw new Error(payload?.message || 'Contestability failed.');
                    }

                    btn.closest('tr')?.remove();
                    selectedContestabilityButton = null;
                    toggleContestabilityModal(false);
                    renderPage();
                } catch (err) {
                    alert(err.message || 'Contestability failed.');
                    btn.removeAttribute('disabled');
                } finally {
                    contestabilityConfirm.removeAttribute('disabled');
                }
            });

            contestabilityCancel?.addEventListener('click', () => {
                selectedContestabilityButton = null;
                toggleContestabilityModal(false);
            });
            contestabilityModal?.addEventListener('click', (event) => {
                if (event.target === contestabilityModal || event.target.classList.contains('modal-close')) {
                    selectedContestabilityButton = null;
                    toggleContestabilityModal(false);
                }
            });

            nextButton?.addEventListener('click', () => {
                currentPage += 1;
                renderPage();
            });
            searchInput?.addEventListener('input', () => {
                currentPage = 1;
                renderPage();
            });

            renderPage();
        })();
    </script>
</body>
</html>
