<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('images/logo-oneheart.png') }}">
    <title>Show Members | OneHeart Life Plan</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/partials/nav.css') . '?v=' . filemtime(public_path('css/partials/nav.css')) }}">
</head>
<body class="has-shell" data-readonly="{{ !empty($isReadOnly) ? '1' : '0' }}">
    <div class="page">
        @include('partials.header')

        <div class="form-actions members-filters">
                            
                            <div class="button-group members-mode-group" role="group" aria-label="Filter members by payment mode">
                                <button type="button" class="button is-ghost member-mode-filter is-active" data-mode="all" aria-pressed="true">All</button>
                                <button type="button" class="button is-ghost member-mode-filter" data-mode="monthly" aria-pressed="false">Monthly</button>
                                <button type="button" class="button is-ghost member-mode-filter" data-mode="quarterly" aria-pressed="false">Quarterly</button>
                                <button type="button" class="button is-ghost member-mode-filter" data-mode="semi-annual" aria-pressed="false">Semi-Annual</button>
                                <button type="button" class="button is-ghost member-mode-filter" data-mode="annual" aria-pressed="false">Annual</button>
                                <button type="button" class="button is-ghost member-mode-filter" data-mode="one-time" aria-pressed="false">One-time</button>
                            </div>
                            <input type="search" id="memberSearch" class="members-search" placeholder="Search name, age, or date">
                            <div class="members-page-size">
                                <label for="memberPageSize">Show Page</label>
                                <select id="memberPageSize">
                                    <option value="10" selected>10</option>
                                    <option value="20">20</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                            <button type="button" class="button" id="exportMembersBtn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="vertical-align: text-bottom; margin-right: 6px;">
                                    <path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M14 2v5h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M9 11l6 6M15 11l-6 6" stroke="#1d6f42" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Save to Excel
                            </button>
                        </div>

        <main class="dashboard">
            <section class="wrap">
                <div class="eyebrow">Members</div>
                <div class="hero-title hero-small">Member directory</div>
                <p class="hero-sub">Member details pulled from enrollment. Use this table to review key info.</p>
                <div class="card">
                    <div class="card-header table-toolbar">
                        <div>
                            <div class="card-title">Member Details</div>
                            <div class="card-subtitle">Latest submissions first</div>
                        </div>
                        
                        <div class="table-stats">
                            @php
                                $latestDate = $members->first()?->created_at;
                            @endphp
                            <span class="stat-pill soft">Latest: <strong>{{ $latestDate ? \Carbon\Carbon::parse($latestDate)->diffForHumans() : '-' }}</strong></span>
                            <span class="stat-pill soft">Scope: <strong>{{ $scopeLabel ?? 'Role-based' }}</strong></span>
                            @if (!empty($percentageTotal) || $percentageTotal === 0.0)
                                <span class="stat-pill">Total % Amount: <strong>{{ number_format($percentageTotal, 2) }}</strong></span>
                            @endif
                        </div>
                    </div>

                    @if ($members->isEmpty())
                        <div class="empty-state">
                            <div class="empty-title">No member details yet</div>
                            <p class="empty-body">Add a member to see their info appear here.</p>
                        </div>
                    @else
                        <div class="table-scroll">
                            <table class="data-table modern compact" id="membersTable">
                                <thead>
                                    <tr>
                                        <th>Planholder</th>
                                        <th>Age</th>
                                        <th>Sex</th>
                                        <th>Civil Status</th>
                                        <th>Contact</th>
                                        <th>Email</th>
                                        <th>Employer</th>
                                        <th>Office Address</th>
                                        <th>Nationality</th>
                                        <th>Created</th>
                                        <th class="text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($members as $member)
                                        @php
                                            $part1 = $part1s[$member->part1_id] ?? null;
                                            $address = $addresses[$member->id] ?? null;
                                            $bene = $beneficiaries[$member->id][0] ?? null;
                                            $assignment = $part1 && $part1->member_assignment_id
                                                ? ($assignments[$part1->member_assignment_id] ?? null)
                                                : null;
                                            $fullName = trim($member->first_name . ' ' . ($member->midle_name ?? '') . ' ' . $member->surname);
                                            $modeValue = strtolower(trim((string) ($part1->mode_of_payment ?? '')));
                                            $modeValue = match ($modeValue) {
                                                'semi annual' => 'semi-annual',
                                                'yearly' => 'annual',
                                                'one time', 'one_time' => 'one-time',
                                                default => $modeValue,
                                            };
                                            $createdDate = $member->created_at ? \Carbon\Carbon::parse($member->created_at)->format('M d, Y') : '-';
                                            $applicationDate = $part1?->application_date ?? '-';
                                        @endphp
                                        <tr data-mode="{{ $modeValue }}" data-name="{{ strtolower($fullName) }}" data-age="{{ (string) $member->age }}" data-created="{{ strtolower($createdDate) }}" data-application="{{ strtolower((string) $applicationDate) }}">
                                            <td class="table-col-primary">{{ $fullName }}</td>
                                            <td>{{ $member->age }}</td>
                                            <td>{{ $member->sex_at_birth }}</td>
                                            <td>{{ $member->civil_status }}</td>
                                            <td>{{ $member->cellular_no }}</td>
                                            <td>{{ $member->email_address }}</td>
                                            <td>{{ $member->name_of_employer }}</td>
                                            <td>{{ $member->office_address }}</td>
                                            <td>{{ $member->nationality }}</td>
                                            <td>{{ $createdDate }}</td>
                                            <td class="table-action">
                                                <button
                                                    type="button"
                                                    class="button is-ghost view-member"
                                                    data-member='@json($member)'
                                                    data-part1='@json($part1)'
                                                    data-address='@json($address)'
                                                    data-assignment='@json($assignment)'
                                                    data-beneficiaries='@json($beneficiaries[$member->id] ?? [])'
                                                    data-paid-installments="{{ (int) ($paidInstallmentsByPart1[$member->part1_id] ?? 0) }}"
                                                    data-paid-amount="{{ (float) ($paidAmountByPart1[$member->part1_id] ?? 0) }}"
                                                >View</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="members-pagination" id="membersPagination">
                            <div class="members-pagination-info" id="membersPageInfo">Page 1 of 1</div>
                            <div class="members-page-numbers" id="membersPageNumbers"></div>
                            <button type="button" class="button is-ghost" id="membersNextPage">Next</button>
                        </div>
                    @endif
                </div>
            </section>
        </main>

        @include('partials.footer')
    </div>

        <div class="modal-overlay" id="memberModal" aria-hidden="true">
        <div class="modal-card">
            <div class="modal-head">
                <div class="modal-head-left">
                    <div class="modal-title">Member Snapshot</div>
                    <div class="modal-subtitle" id="modalName"></div>
                    <div class="modal-meta">
                        <span class="chip chip-neutral" id="modalPlanType">Plan</span>
                        <span class="chip chip-accent" id="modalPayment">Payment Status</span>
                        <span class="chip chip-muted" id="modalCreated">Created</span>
                    </div>
                </div>
            <div class="modal-head-actions">
                    <button type="button" class="button is-primary modal-print-trigger">Print ID</button>
                    @if (empty($isReadOnly))
                        <button type="button" class="button is-warning modal-edit-trigger">Update</button>
                        <button type="button" class="button is-danger modal-delete-trigger">Delete</button>
                    @endif
                    <button type="button" class="modal-close" aria-label="Close">&times;</button>
            </div>
            </div>
            <div class="modal-grid">
                <div class="modal-section">
                    <div class="modal-label">Member Enrollment</div>
                    <ul class="modal-list" id="modalPart1"></ul>
                </div>
                <div class="modal-section">
                    <div class="modal-label">Member Details</div>
                    <ul class="modal-list" id="modalMember"></ul>
                </div>
                <div class="modal-section">
                    <div class="modal-label">Address</div>
                    <ul class="modal-list" id="modalAddress"></ul>
                </div>
                <div class="modal-section">
                    <div class="modal-label">Staff Info</div>
                    <ul class="modal-list" id="modalStaff"></ul>
                </div>
                <div class="modal-section">
                    <div class="modal-label">Beneficiaries</div>
                    <ul class="modal-list" id="modalBeneficiary"></ul>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="editSelectorModal" aria-hidden="true">
        <div class="modal-card modal-card-narrow">
            <div class="modal-head">
                <div class="modal-title">WHAT TO EDIT</div>
                <div class="modal-subtitle">CHOOSE WHAT YOU WANT TO CHANGE</div>
                <button type="button" class="modal-close" aria-label="Close">&times;</button>
            </div>
            <div class="edit-picker-grid">
                <button type="button" class="edit-card" data-section="enrollment">
                    <div class="edit-card-title">Member Enrollment</div>
                    <p class="edit-card-copy">Plan and payment terms</p>
                </button>
                <button type="button" class="edit-card" data-section="member">
                    <div class="edit-card-title">Member Details</div>
                    <p class="edit-card-copy">Personal information</p>
                </button>
                <button type="button" class="edit-card" data-section="address">
                    <div class="edit-card-title">Address</div>
                    <p class="edit-card-copy">Contact and ID numbers</p>
                </button>
                <button type="button" class="edit-card" data-section="beneficiary">
                    <div class="edit-card-title">Beneficiaries</div>
                    <p class="edit-card-copy">Primary beneficiary</p>
                </button>
                <button type="button" class="edit-card" data-section="staff">
                    <div class="edit-card-title">Staff Info</div>
                    <p class="edit-card-copy">Collector, agent, manager</p>
                </button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="editFormModal" aria-hidden="true">
        <div class="modal-card modal-card-wide">
            <div class="modal-head">
                <div class="modal-head-left">
                    <div class="modal-title" id="editFormTitle">Edit Section</div>
                    <div class="modal-subtitle" id="editFormSubtitle">Review ug update ang datos.</div>
                    <div class="modal-alert modal-alert-success" id="editSuccessBanner" role="status">Changes saved.</div>
                </div>
                <div class="modal-head-actions">
                    <button type="button" class="button is-ghost modal-back-selector">Back</button>
                    <button type="button" class="modal-close" aria-label="Close">&times;</button>
                </div>
            </div>
            <form class="modal-form" id="editFormBody"></form>
            <div class="modal-footer">
                <button type="button" class="button is-ghost modal-back-selector">Back</button>
                <button type="button" class="button modal-save">Save changes</button>
            </div>
        </div>
    </div>

    <div class="status-modal" id="editSuccessModal" aria-hidden="true">
        <div class="status-card">
            <div class="status-title">Changes saved</div>
            <p class="status-body">Na-save na ang imong updates.</p>
            <button type="button" class="button status-close" data-close-success>Close</button>
        </div>
    </div>

    <div class="modal-overlay" id="deleteConfirmModal" aria-hidden="true">
        <div class="modal-card modal-card-narrow">
            <div class="modal-head">
                <div>
                    <div class="modal-title">Delete Member</div>
                    <div class="modal-subtitle">Are you sure you want to delete this data?</div>
                </div>
                <button type="button" class="modal-close" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <p class="modal-copy">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="button is-ghost" data-delete-cancel>Cancel</button>
                <button type="button" class="button is-danger" data-delete-confirm>Delete</button>
            </div>
        </div>
    </div>

    <div class="toast" id="globalToast" role="status" aria-live="polite"></div>

    @php
        $staffUsersPayload = [
            'collectors' => $collectors ?? [],
            'agents' => $agents ?? [],
            'managers' => $managers ?? [],
        ];
    @endphp
    <script type="application/json" id="staffUsersPayload">@json($staffUsersPayload)</script>
    <script type="application/json" id="planSettingsPayload">@json($planSettings ?? [])</script>

        <script>
        (() => {
            const modal = document.getElementById("memberModal");
            const modalName = document.getElementById("modalName");
            const modalPart1 = document.getElementById("modalPart1");
            const modalMember = document.getElementById("modalMember");
            const modalAddress = document.getElementById("modalAddress");
            const modalStaff = document.getElementById("modalStaff");
            const modalBeneficiary = document.getElementById("modalBeneficiary");
            const modalPlan = document.getElementById("modalPlanType");
            const modalPayment = document.getElementById("modalPayment");
            const modalCreated = document.getElementById("modalCreated");
            const editTrigger = document.querySelector(".modal-edit-trigger");
            const deleteTrigger = document.querySelector(".modal-delete-trigger");
            const deleteConfirmModal = document.getElementById("deleteConfirmModal");
            const deleteConfirmBtn = document.querySelector("[data-delete-confirm]");
            const deleteCancelBtn = document.querySelector("[data-delete-cancel]");
            const selectorModal = document.getElementById("editSelectorModal");
            const formModal = document.getElementById("editFormModal");
            const editFormTitle = document.getElementById("editFormTitle");
            const editFormSubtitle = document.getElementById("editFormSubtitle");
            const editFormBody = document.getElementById("editFormBody");
            const formBackButtons = document.querySelectorAll(".modal-back-selector");
            const formSaveButtons = document.querySelectorAll(".modal-save");
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
            const isReadOnly = document.body.dataset.readonly === "1";
            const membersTable = document.getElementById("membersTable");
            const memberRows = membersTable ? membersTable.querySelectorAll("tbody tr") : [];
            const memberSearch = document.getElementById("memberSearch");
            const memberModeFilters = document.querySelectorAll(".member-mode-filter");
            const memberPageSize = document.getElementById("memberPageSize");
            const membersPageInfo = document.getElementById("membersPageInfo");
            const membersPageNumbers = document.getElementById("membersPageNumbers");
            const membersNextPage = document.getElementById("membersNextPage");
            const exportMembersBtn = document.getElementById("exportMembersBtn");
            const staffUsers = (() => {
                const payload = document.getElementById("staffUsersPayload")?.textContent || "{}";
                try {
                    return JSON.parse(payload);
                } catch {
                    return { collectors: [], agents: [], managers: [] };
                }
            })();
            const planSettings = (() => {
                const payload = document.getElementById("planSettingsPayload")?.textContent || "{}";
                try {
                    return JSON.parse(payload);
                } catch {
                    return {};
                }
            })();
            let currentTriggerBtn = null;
            let currentRow = null;
            const editSuccessBanner = document.getElementById("editSuccessBanner");
            const editSuccessModal = document.getElementById("editSuccessModal");
            const editSuccessClose = document.querySelector("[data-close-success]");
            const toast = document.getElementById("globalToast");
            const printTrigger = document.querySelector(".modal-print-trigger");

            const currentContext = {
                member: null,
                part1: null,
                address: null,
                assignment: null,
                benes: [],
                bene: null,
            };
            let currentSection = null;

            const paymentStatusDisplay = (statusRaw) => {
                const normalized = (statusRaw || "").toString().toLowerCase();
                switch (normalized) {
                    case "paid":
                        return { label: "Paid", className: "chip chip-accent" };
                    case "pending":
                    case "":
                        return { label: "Pending", className: "chip chip-neutral" };
                    case "overdue":
                        return { label: "Overdue", className: "chip chip-muted" };
                    default:
                        return { label: statusRaw, className: "chip chip-neutral" };
                }
            };

            const applyPaymentChip = (status) => {
                if (!modalPayment) return;
                const { label, className } = paymentStatusDisplay(status);
                modalPayment.textContent = label;
                modalPayment.className = className;
            };

            const parseMonthsFromTerms = (termsRaw) => {
                const terms = (termsRaw || "").toString().toLowerCase();
                const monthMatch = terms.match(/(\d+)\s*month/);
                if (monthMatch) return Number(monthMatch[1]) || 0;
                const yearMatch = terms.match(/(\d+)\s*year/);
                if (yearMatch) return (Number(yearMatch[1]) || 0) * 12;
                return 0;
            };

            const getMonthsPaidLabel = (part1 = {}) => {
                const paidInstallments = Number(part1.paid_installments || 0);
                const mode = (part1.mode_of_payment || "").toString().toLowerCase().trim();
                let months = 0;
                switch (mode) {
                    case "quarterly":
                        months = paidInstallments * 3;
                        break;
                    case "semi-annual":
                    case "semi annual":
                        months = paidInstallments * 6;
                        break;
                    case "annual":
                    case "yearly":
                        months = paidInstallments * 12;
                        break;
                    case "one-time":
                    case "one time":
                    case "one_time":
                        months = paidInstallments > 0 ? (parseMonthsFromTerms(part1.terms_of_payment) || 1) : 0;
                        break;
                    default:
                        months = paidInstallments;
                        break;
                }
                return `${months} month(s)`;
            };

            const formatCurrency = (value) => {
                const num = Number(value || 0);
                return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            };

            const writeList = (node, entries) => {
                if (!node) return;
                if (!entries || entries.length === 0) {
                    node.innerHTML = '<li class="muted">No data</li>';
                    return;
                }
                node.innerHTML = entries
                    .map(([label, value]) => `<li><span>${label}</span><strong>${value ?? '-'}</strong></li>`)
                    .join('');
            };

            const closeModal = () => {
                modal?.classList.remove("is-visible");
                modal?.setAttribute("aria-hidden", "true");
            };

            const toggleOverlay = (node, isOpen) => {
                if (!node) return;
                node.classList.toggle("is-visible", Boolean(isOpen));
                if (!isOpen && node.contains(document.activeElement)) {
                    document.activeElement?.blur();
                }
                node.setAttribute("aria-hidden", isOpen ? "false" : "true");
            };

            const showSuccessModal = () => {
                toggleOverlay(editSuccessModal, true);
            };

            const showToast = (message = "") => {
                if (!toast) return;
                toast.textContent = message || "Saved";
                toast.classList.add("is-visible");
                setTimeout(() => toast.classList.remove("is-visible"), 2200);
            };

            let activeModeFilter = "all";
            let currentPage = 1;

            const getFilteredRows = () => {
                const searchTerm = (memberSearch?.value || "").toLowerCase().trim();
                return Array.from(memberRows).filter((row) => {
                    const rowMode = (row.dataset.mode || "").toLowerCase();
                    const matchesMode = activeModeFilter === "all" || rowMode === activeModeFilter;
                    const haystack = [
                        row.dataset.name || "",
                        row.dataset.age || "",
                        row.dataset.created || "",
                        row.dataset.application || "",
                    ].join(" ");
                    const matchesSearch = !searchTerm || haystack.includes(searchTerm);
                    return matchesMode && matchesSearch;
                });
            };

            const renderPageNumbers = (totalPages) => {
                if (!membersPageNumbers) return;
                membersPageNumbers.innerHTML = "";
                for (let page = 1; page <= totalPages; page++) {
                    const btn = document.createElement("button");
                    btn.type = "button";
                    btn.className = `button is-ghost members-page-btn ${page === currentPage ? "is-active" : ""}`;
                    btn.textContent = String(page);
                    btn.addEventListener("click", () => {
                        currentPage = page;
                        applyMemberFilters();
                    });
                    membersPageNumbers.appendChild(btn);
                }
            };

            const applyMemberFilters = () => {
                if (!memberRows.length) return;

                const filtered = getFilteredRows();
                const perPage = Math.max(1, Number(memberPageSize?.value || 10));
                const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
                if (currentPage > totalPages) currentPage = totalPages;

                const start = (currentPage - 1) * perPage;
                const end = start + perPage;
                const visibleSet = new Set(filtered.slice(start, end));

                memberRows.forEach((row) => {
                    row.style.display = visibleSet.has(row) ? "" : "none";
                });

                if (membersPageInfo) {
                    membersPageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
                }
                if (membersNextPage) {
                    membersNextPage.disabled = currentPage >= totalPages;
                }
                renderPageNumbers(totalPages);
            };

            const toCsvCell = (value) => {
                const text = String(value ?? "").replace(/"/g, '""');
                return `"${text}"`;
            };

            const exportVisibleRowsToCsv = () => {
                if (!membersTable) return;

                const headerCells = Array.from(membersTable.querySelectorAll("thead th"));
                const exportIndexes = [];
                const headers = [];

                headerCells.forEach((th, index) => {
                    const label = (th.textContent || "").trim();
                    if (!label || label.toLowerCase() === "action") return;
                    exportIndexes.push(index);
                    headers.push(label);
                });

                const visibleRows = Array.from(memberRows).filter((row) => row.style.display !== "none");
                if (!visibleRows.length) {
                    showToast("No filtered rows to export.");
                    return;
                }

                const lines = [];
                lines.push(headers.map(toCsvCell).join(","));

                visibleRows.forEach((row) => {
                    const cells = Array.from(row.querySelectorAll("td"));
                    const values = exportIndexes.map((i) => (cells[i]?.textContent || "").trim());
                    lines.push(values.map(toCsvCell).join(","));
                });

                const now = new Date();
                const pad = (n) => String(n).padStart(2, "0");
                const stamp = `${now.getFullYear()}${pad(now.getMonth() + 1)}${pad(now.getDate())}_${pad(now.getHours())}${pad(now.getMinutes())}${pad(now.getSeconds())}`;
                const filename = `members_filtered_${stamp}.csv`;
                const csv = "\uFEFF" + lines.join("\r\n");

                const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
                const url = URL.createObjectURL(blob);
                const a = document.createElement("a");
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                a.remove();
                URL.revokeObjectURL(url);

                showToast("Filtered table exported.");
            };

            memberModeFilters.forEach((btn) => {
                btn.addEventListener("click", () => {
                    memberModeFilters.forEach((item) => {
                        item.classList.remove("is-active");
                        item.setAttribute("aria-pressed", "false");
                    });
                    btn.classList.add("is-active");
                    btn.setAttribute("aria-pressed", "true");
                    activeModeFilter = btn.dataset.mode || "all";
                    currentPage = 1;
                    applyMemberFilters();
                });
            });
            memberSearch?.addEventListener("input", () => {
                currentPage = 1;
                applyMemberFilters();
            });
            memberPageSize?.addEventListener("change", () => {
                currentPage = 1;
                applyMemberFilters();
            });
            membersNextPage?.addEventListener("click", () => {
                currentPage += 1;
                applyMemberFilters();
            });
            exportMembersBtn?.addEventListener("click", exportVisibleRowsToCsv);
            applyMemberFilters();

            const textField = (label, name, value = "") => {
                return `
                    <label class="modal-field">
                        <span>${label}</span>
                        <input type="text" name="${name}" value="${value ?? ""}" />
                    </label>
                `;
            };
            const selectField = (label, name, options = [], value = "") => {
                const opts = options
                    .map(opt => {
                        const selected = String(value ?? "") === String(opt.id) ? "selected" : "";
                        return `<option value="${opt.id}" ${selected}>${opt.name}</option>`;
                    })
                    .join("");
                return `
                    <label class="modal-field">
                        <span>${label}</span>
                        <select name="${name}">
                            <option value="" disabled>Select ${label.toLowerCase()}</option>
                            ${opts}
                        </select>
                    </label>
                `;
            };

            const buildForm = (section) => {
                const { member, part1, address, benes } = currentContext;
                switch (section) {
                    case "enrollment":
                        return {
                            title: "Member Enrollment",
                            subtitle: "Update the plan and payment settings.",
                            body: `
                                <div class="modal-form-grid">
                                    ${textField("LPAF No.", "lpaf_no", part1?.lpaf_no)}
                                    ${textField("Plan Type", "plan_type", part1?.plan_type)}
                                    ${textField("Application Date", "application_date", part1?.application_date)}
                                    ${textField("Gross Contact Price", "gross_contact_price", part1?.gross_contact_price)}
                                    ${textField("Mode of Payment", "mode_of_payment", part1?.mode_of_payment)}
                                    ${textField("Terms of Payment", "terms_of_payment", part1?.terms_of_payment)}
                                    ${textField("Due Date", "due_date", part1?.due_date)}
                                    ${textField("Amount", "amount", part1?.amount)}
                                </div>
                            `,
                        };
                    case "member":
                        return {
                            title: "Member Details",
                            subtitle: "Personal and employment details.",
                            body: `
                                <div class="modal-form-grid">
                                    ${textField("Surname", "surname", member?.surname)}
                                    ${textField("First Name", "first_name", member?.first_name)}
                                    ${textField("Middle Name", "midle_name", member?.midle_name)}
                                    ${textField("Birthplace", "place_of_birth", member?.place_of_birth)}
                                    ${textField("Birthdate", "date_of_birth", member?.date_of_birth)}
                                    ${textField("Age", "age", member?.age)}
                                    ${textField("Sex at Birth", "sex_at_birth", member?.sex_at_birth)}
                                    ${textField("Civil Status", "civil_status", member?.civil_status)}
                                    ${textField("Cellular No.", "cellular_no", member?.cellular_no)}
                                    ${textField("Email", "email_address", member?.email_address)}
                                    ${textField("Nationality", "nationality", member?.nationality)}
                                    ${textField("Institution Name", "institution_name", member?.institution_name)}
                                    ${textField("Institution No.", "institution_no", member?.institution_no)}
                                    ${textField("Occupation", "occupation", member?.occupation)}
                                    ${textField("Employer", "name_of_employer", member?.name_of_employer)}
                                    ${textField("Office Address", "office_address", member?.office_address)}
                                    ${textField("Office No.", "office_no", member?.office_no)}
                                </div>
                            `,
                        };
                    case "address":
                        return {
                            title: "Address",
                            subtitle: "Contact and supporting IDs.",
                            body: `
                                <div class="modal-form-grid">
                                    ${textField("Lot/House No.", "lot_house_numer", address?.lot_house_numer)}
                                    ${textField("Street", "street", address?.street)}
                                    ${textField("Barangay", "barangay", address?.barangay)}
                                    ${textField("Province", "province", address?.province)}
                                    ${textField("Zip Code", "zip_code", address?.zip_code)}
                                    ${textField("Contact No.", "contact_no", address?.contact_no)}
                                    ${textField("SSS/GSIS No.", "sss_gsis_no", address?.sss_gsis_no)}
                                    ${textField("TIN", "tin_no", address?.tin_no)}
                                    ${textField("Source of Funds", "source_of_funds_if_not_imployed", address?.source_of_funds_if_not_imployed)}
                                </div>
                            `,
                        };
                    case "beneficiary":
                        const beneCards = (benes || []).map((b, idx) => {
                            const label = b?.name || `Beneficiary ${idx + 1}`;
                            const rel = b?.relationship_to_planholder ? `(${b.relationship_to_planholder})` : '';
                            return `
                                <label class="bene-choice">
                                    <input type="radio" name="beneficiary_choice" value="${b?.id ?? ''}" ${idx === 0 ? 'checked' : ''}>
                                    <div>
                                        <div class="bene-choice-title">${label}</div>
                                        <div class="bene-choice-sub">${rel || 'Tap to edit this beneficiary'}</div>
                                    </div>
                                </label>
                            `;
                        }).join('') || '<p class="muted">No beneficiaries yet.</p>';

                        const firstBene = (benes && benes.length) ? benes[0] : {};

                        return {
                            title: "Beneficiaries",
                            subtitle: "Pick which beneficiary to edit.",
                            body: `
                                <div class="bene-picker">${beneCards}</div>
                                <input type="hidden" name="beneficiary_id" id="beneficiaryId" value="${firstBene?.id ?? ''}">
                                <div class="modal-form-grid">
                                    <label class="modal-field">
                                        <span>Type</span>
                                        <select name="type">
                                            <option value="">Select type</option>
                                            <option value="primary beneficiaries" ${firstBene?.type === 'primary beneficiaries' ? 'selected' : ''}>Primary beneficiaries</option>
                                            <option value="contingent beneficiaries" ${firstBene?.type === 'contingent beneficiaries' ? 'selected' : ''}>Contingent beneficiaries</option>
                                        </select>
                                    </label>
                                    ${textField("Name", "name", firstBene?.name)}
                                    ${textField("Age", "age", firstBene?.age)}
                                    ${textField("Address", "address", firstBene?.address)}
                                    ${textField("Relationship", "relationship_to_planholder", firstBene?.relationship_to_planholder)}
                                </div>
                            `,
                        };
                    case "staff":
                        return {
                            title: "Staff Info",
                            subtitle: "Update assigned staff accounts.",
                            body: `
                                <div class="modal-form-grid">
                                    ${selectField("Collector", "collector_user_id", staffUsers.collectors || [], currentContext.assignment?.collector_user_id)}
                                    ${selectField("Agent", "agent_user_id", staffUsers.agents || [], currentContext.assignment?.agent_user_id)}
                                    ${selectField("Manager", "manager_user_id", staffUsers.managers || [], currentContext.assignment?.manager_user_id)}
                                </div>
                            `,
                        };
                    default:
                        return { title: "Edit Section", subtitle: "Select a section to edit.", body: "" };
                }
            };

            const openFormModal = (section) => {
                const { title, subtitle, body } = buildForm(section);
                currentSection = section;
                editFormTitle.textContent = title;
                editFormSubtitle.textContent = subtitle;
                editFormBody.innerHTML = body;
                editFormBody.setAttribute("data-section", section);
                if (section === "beneficiary") {
                    const radios = editFormBody.querySelectorAll('input[name="beneficiary_choice"]');
                    const hiddenId = editFormBody.querySelector('#beneficiaryId');
                    const syncFields = (bene) => {
                        const typeInput = editFormBody.querySelector('select[name="type"]');
                        const nameInput = editFormBody.querySelector('input[name="name"]');
                        const ageInput = editFormBody.querySelector('input[name="age"]');
                        const addressInput = editFormBody.querySelector('input[name="address"]');
                        const relationInput = editFormBody.querySelector('input[name="relationship_to_planholder"]');
                        if (hiddenId) hiddenId.value = bene?.id ?? '';
                        if (typeInput) typeInput.value = bene?.type || "";
                        if (nameInput) nameInput.value = bene?.name || "";
                        if (ageInput) ageInput.value = bene?.age || "";
                        if (addressInput) addressInput.value = bene?.address || "";
                        if (relationInput) relationInput.value = bene?.relationship_to_planholder || "";
                    };
                    radios.forEach(r => {
                        r.addEventListener('change', () => {
                            const id = r.value;
                            const match = (currentContext.benes || []).find(b => String(b.id) === String(id));
                            syncFields(match || {});
                        });
                    });
                }
                toggleOverlay(selectorModal, false);
                toggleOverlay(formModal, true);
            };

            const renderModalDetails = () => {
                const member = currentContext.member || {};
                const part1 = currentContext.part1 || {};
                const address = currentContext.address || {};
                const assignment = currentContext.assignment || {};
                const benes = currentContext.benes || [];
                const primaryBene = currentContext.bene || {};

                modalName.textContent = [member.first_name, member.midle_name, member.surname].filter(Boolean).join(' ');
                modalPlan.textContent = part1.plan_type || "No plan";
                applyPaymentChip(part1.payment_status || "Pending");
                modalCreated.textContent = member.created_at ? `Added ${member.created_at}` : "No date";

                writeList(modalPart1, [
                    ["LPAF No.", part1.lpaf_no ?? "-"],
                    ["Plan Type", part1.plan_type ?? "-"],
                    ["Application Date", part1.application_date ?? "-"],
                    ["Gross Contact Price", part1.gross_contact_price ?? "-"],
                    ["Legacy Monthly (After One-time)", part1.plan_type === "Legacy Care" ? formatCurrency(planSettings?.["Legacy Care"]?.legacy_monthly_amount) : "-"],
                    ["How many months paid", getMonthsPaidLabel(part1)],
                    ["Total paid", formatCurrency(part1.paid_amount_total)],
                    ["Mode of Payment", part1.mode_of_payment ?? "-"],
                    ["Terms of Payment", part1.terms_of_payment ?? "-"],
                    ["Due Date", part1.due_date ?? "-"],
                    ["Amount", part1.amount ?? "-"],
                ]);

                writeList(modalMember, [
                    ["Surname", member.surname],
                    ["First Name", member.first_name],
                    ["Middle Name", member.midle_name],
                    ["Birthplace", member.place_of_birth],
                    ["Birthdate", member.date_of_birth],
                    ["Age", member.age],
                    ["Sex at Birth", member.sex_at_birth],
                    ["Civil Status", member.civil_status],
                    ["Cellular No.", member.cellular_no],
                    ["Email", member.email_address],
                    ["Nationality", member.nationality],
                    ["Institution Name", member.institution_name],
                    ["Institution No.", member.institution_no],
                    ["Occupation", member.occupation],
                    ["Employer", member.name_of_employer],
                    ["Office Address", member.office_address],
                    ["Office No.", member.office_no],
                ]);

                writeList(modalAddress, [
                    ["Lot/House No.", address.lot_house_numer ?? "-"],
                    ["Street", address.street ?? "-"],
                    ["Barangay", address.barangay ?? "-"],
                    ["Province", address.province ?? "-"],
                    ["Zip Code", address.zip_code ?? "-"],
                    ["Contact No.", address.contact_no ?? "-"],
                    ["SSS/GSIS No.", address.sss_gsis_no ?? "-"],
                    ["TIN", address.tin_no ?? "-"],
                    ["Source of Funds", address.source_of_funds_if_not_imployed ?? "-"],
                ]);

                writeList(modalStaff, [
                    ["Collector Name", assignment.collector_name ?? "-"],
                    ["Agent Name", assignment.agent_name ?? "-"],
                    ["Manager Name", assignment.manager_name ?? "-"],
                ]);

                if (!benes.length) {
                    writeList(modalBeneficiary, []);
                } else {
                    const beneItems = benes.map((b, idx) => {
                        const type = (b.type || 'Beneficiary').toLowerCase();
                        return `
                            <li class="bene-card">
                                <div class="bene-card-header">${type}</div>
                                <div class="bene-block">
                                    <div><em>Name</em> ${b.name ?? '-'}</div>
                                    <div><em>Age</em> ${b.age ?? '-'}</div>
                                    <div><em>Address</em> ${b.address ?? '-'}</div>
                                    <div><em>Relationship</em> ${b.relationship_to_planholder ?? '-'}</div>
                                </div>
                            </li>
                        `;
                    });
                    modalBeneficiary.innerHTML = beneItems.join('');
                }
            };

            document.querySelectorAll(".view-member").forEach(btn => {
                btn.addEventListener("click", () => {
                    const member = JSON.parse(btn.dataset.member || "{}");
                    const part1 = JSON.parse(btn.dataset.part1 || "{}");
                    part1.paid_installments = Number(btn.dataset.paidInstallments || 0);
                    part1.paid_amount_total = Number(btn.dataset.paidAmount || 0);
                    const address = JSON.parse(btn.dataset.address || "{}");
                    const assignment = JSON.parse(btn.dataset.assignment || "{}");
                    const benes = JSON.parse(btn.dataset.beneficiaries || "[]");

                    currentTriggerBtn = btn;
                    currentRow = btn.closest("tr");
                    currentContext.member = member;
                    currentContext.part1 = part1;
                    currentContext.address = address;
                    currentContext.assignment = assignment;
                    currentContext.benes = benes || [];
                    currentContext.bene = (benes && benes.length) ? benes[0] : null;
                    renderModalDetails();

                    modal?.classList.add("is-visible");
                    modal?.setAttribute("aria-hidden", "false");
                });
            });

            if (!isReadOnly) {
                editTrigger?.addEventListener("click", () => {
                    if (!currentContext.member) return;
                    toggleOverlay(selectorModal, true);
                });

                deleteTrigger?.addEventListener("click", () => {
                    if (!currentContext.member) {
                        showToast("Select a member first.");
                        return;
                    }
                    toggleOverlay(deleteConfirmModal, true);
                });

                deleteConfirmBtn?.addEventListener("click", async () => {
                    if (!currentContext.member) {
                        showToast("Select a member first.");
                        toggleOverlay(deleteConfirmModal, false);
                        return;
                    }
                    const memberId = currentContext.member.id;
                    if (!memberId) {
                        showToast("Missing member id.");
                        toggleOverlay(deleteConfirmModal, false);
                        return;
                    }

                    deleteConfirmBtn.setAttribute("disabled", "true");
                    deleteTrigger?.setAttribute("disabled", "true");
                    try {
                        const response = await fetch(`/members/${memberId}`, {
                            method: "DELETE",
                            headers: {
                                "Accept": "application/json",
                                "X-CSRF-TOKEN": csrfToken,
                            },
                        });

                        if (!response.ok) {
                            const payload = await response.json().catch(() => ({}));
                            throw new Error(payload?.message || "Delete failed.");
                        }

                        currentRow?.remove();
                        closeModal();
                        toggleOverlay(deleteConfirmModal, false);
                        showToast("Member deleted.");
                    } catch (err) {
                        showToast(err.message || "Delete failed.");
                    } finally {
                        deleteConfirmBtn.removeAttribute("disabled");
                        deleteTrigger?.removeAttribute("disabled");
                    }
                });

                deleteCancelBtn?.addEventListener("click", () => {
                    toggleOverlay(deleteConfirmModal, false);
                });
            }

            modal?.addEventListener("click", (e) => {
                if (e.target === modal || e.target.classList.contains("modal-close")) closeModal();
            });

            if (!isReadOnly) {
                deleteConfirmModal?.addEventListener("click", (e) => {
                    if (e.target === deleteConfirmModal || e.target.classList.contains("modal-close")) {
                        toggleOverlay(deleteConfirmModal, false);
                    }
                });

                selectorModal?.addEventListener("click", (e) => {
                    if (e.target === selectorModal || e.target.classList.contains("modal-close")) {
                        toggleOverlay(selectorModal, false);
                    }
                });

                selectorModal?.querySelectorAll("[data-section]").forEach(btn => {
                    btn.addEventListener("click", () => openFormModal(btn.dataset.section));
                });

                formModal?.addEventListener("click", (e) => {
                    if (e.target === formModal || e.target.classList.contains("modal-close")) {
                        toggleOverlay(formModal, false);
                    }
                });

                formBackButtons.forEach(btn => {
                    btn.addEventListener("click", () => {
                        toggleOverlay(formModal, false);
                        toggleOverlay(selectorModal, true);
                    });
                });

                formSaveButtons.forEach(btn => {
                    btn.addEventListener("click", async () => {
                        if (!currentContext.member || !currentSection) {
                            alert("Select a member and section first.");
                            return;
                        }

                        const formData = new FormData(editFormBody);
                        formData.append("_token", csrfToken);
                        formData.append("section", currentSection);
                        formData.append("part1_id", currentContext.part1?.id || currentContext.member?.part1_id || "");
                        formData.append("part2_id", currentContext.member?.id || "");
                        formData.append("assignment_id", currentContext.assignment?.id || currentContext.part1?.member_assignment_id || "");
                        if (currentContext.address?.id) {
                            formData.append("par2_residential_address_id", currentContext.address.id);
                        }
                        if (currentSection === "beneficiary") {
                            const beneId = editFormBody.querySelector('[name="beneficiary_id"]')?.value;
                            if (beneId) formData.append("beneficiary_id", beneId);
                        }

                        try {
                            const response = await fetch(`/members/${currentContext.member.id}/update`, {
                                method: "POST",
                                headers: { "Accept": "application/json" },
                                body: formData,
                            });

                            const payload = await response.json().catch(() => ({}));
                            if (!response.ok) {
                                const message = payload?.message || "Something went wrong while saving.";
                                throw new Error(message);
                            }

                            const entries = Object.fromEntries(formData.entries());
                            const updated = { ...entries };
                            switch (currentSection) {
                                case "enrollment":
                                    currentContext.part1 = { ...(currentContext.part1 || {}), ...updated };
                                    break;
                                case "member":
                                    currentContext.member = { ...(currentContext.member || {}), ...updated };
                                    break;
                                case "address":
                                    currentContext.address = { ...(currentContext.address || {}), ...updated };
                                    break;
                                case "beneficiary":
                                    {
                                        const beneId = updated.beneficiary_id || updated.id;
                                        if (beneId && currentContext.benes) {
                                            const target = currentContext.benes.find(b => String(b.id) === String(beneId));
                                            if (target) {
                                                Object.assign(target, updated);
                                                currentContext.bene = target;
                                            }
                                        }
                                    }
                                    break;
                                case "staff":
                                    currentContext.assignment = { ...(currentContext.assignment || {}), ...updated };
                                    {
                                        const findName = (list, id) => (list || []).find(u => String(u.id) === String(id))?.name;
                                        const cName = findName(staffUsers.collectors, updated.collector_user_id);
                                        const aName = findName(staffUsers.agents, updated.agent_user_id);
                                        const mName = findName(staffUsers.managers, updated.manager_user_id);
                                        if (cName) currentContext.assignment.collector_name = cName;
                                        if (aName) currentContext.assignment.agent_name = aName;
                                        if (mName) currentContext.assignment.manager_name = mName;
                                    }
                                    break;
                            }

                            if (currentRow && currentSection === "member") {
                                const cells = currentRow.querySelectorAll("td");
                                const m = currentContext.member || {};
                                if (cells[0]) cells[0].textContent = [m.first_name, m.midle_name, m.surname].filter(Boolean).join(" ");
                                if (cells[1]) cells[1].textContent = m.age || "";
                                if (cells[2]) cells[2].textContent = m.sex_at_birth || "";
                                if (cells[3]) cells[3].textContent = m.civil_status || "";
                                if (cells[4]) cells[4].textContent = m.cellular_no || "";
                                if (cells[5]) cells[5].textContent = m.email_address || "";
                                if (cells[6]) cells[6].textContent = m.name_of_employer || "";
                                if (cells[7]) cells[7].textContent = m.office_address || "";
                                if (cells[8]) cells[8].textContent = m.nationality || "";
                            }

                            renderModalDetails();
                            if (editSuccessBanner) {
                                editSuccessBanner.classList.add("is-visible");
                                setTimeout(() => editSuccessBanner.classList.remove("is-visible"), 2000);
                            }
                            showToast("Changes saved.");
                            showSuccessModal();
                            toggleOverlay(formModal, false);
                            toggleOverlay(selectorModal, false);
                        } catch (err) {
                            showToast(err.message || "Save failed. Please try again.");
                        }
                    });
                });

                editSuccessModal?.addEventListener("click", (e) => {
                    if (e.target === editSuccessModal) toggleOverlay(editSuccessModal, false);
                });

                editSuccessClose?.addEventListener("click", () => toggleOverlay(editSuccessModal, false));
            }

            printTrigger?.addEventListener("click", () => {
                if (!currentContext.member) {
                    showToast("Select a member first.");
                    return;
                }
                const m = currentContext.member || {};
                const p = currentContext.part1 || {};
                const b = currentContext.bene || {};
                const cardHtml = `
                    <style>
                        body { margin: 0; font-family: Arial, sans-serif; display: grid; place-items: center; background: #f3f4f6; }
                        .id-card {
                            width: 320px;
                            border: 1px solid #dcdcdc;
                            border-radius: 12px;
                            padding: 18px;
                            background: #ffffff;
                            box-shadow: 0 14px 40px rgba(0,0,0,0.08);
                        }
                        .id-title { font-weight: 800; font-size: 18px; margin: 0 0 6px; color: #1f2733; }
                        .id-name { font-weight: 700; font-size: 16px; margin: 0 0 10px; color: #1f2733; }
                        .id-meta { font-size: 13px; margin: 0 0 6px; color: #444; }
                        .id-chip { display: inline-block; padding: 4px 8px; border-radius: 999px; background: #ff9f1c; color: #1f2733; font-weight: 700; font-size: 12px; }
                        .id-section { margin-top: 10px; font-size: 12px; color: #444; }
                    </style>
                    <div class="id-card">
                        <div class="id-title">OneHeart Life Plan</div>
                        <div class="id-name">${[m.first_name, m.midle_name, m.surname].filter(Boolean).join(" ")}</div>
                        <div class="id-meta">Plan: ${p.plan_type || "-"}</div>
                        <div class="id-meta">Payment: ${p.mode_of_payment || "-"}</div>
                        <div class="id-meta">Age: ${m.age || "-"}</div>
                        <div class="id-section">
                            Beneficiary: <span class="id-chip">${b?.name || "N/A"}</span>
                        </div>
                    </div>
                    <script>window.onload = () => { window.print(); setTimeout(() => window.close(), 400); }<\/script>
                `;
                const win = window.open("", "_blank", "width=380,height=520");
                if (!win) return;
                win.document.write(cardHtml);
                win.document.close();
            });
        })();
    </script>
</body>
</html>

