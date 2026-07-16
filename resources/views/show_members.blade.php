<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ $appBrandLogoUrl }}">
    <title>Show Members | {{ $appBrandName }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/app.css') . '?v=' . filemtime(public_path('css/app.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/partials/nav.css') . '?v=' . filemtime(public_path('css/partials/nav.css')) }}">
</head>
@php
    $canRedoPayments = in_array(strtolower((string) (auth()->user()->role ?? '')), ['admin', 'manager'], true);
    $canEditMembers = !empty($canEditMembers);
@endphp
<body class="has-shell" data-readonly="{{ !empty($isReadOnly) ? '1' : '0' }}" data-can-redo-payments="{{ $canRedoPayments ? '1' : '0' }}" data-members-base-url="{{ url('/members') }}">
    <div class="page">
        @include('partials.header')

        <div class="form-actions members-filters">
                            
                            <div class="button-group members-mode-group" role="group" aria-label="Filter members by payment mode">
                                <button type="button" class="button is-ghost member-mode-filter is-active" data-mode="all" aria-pressed="true">All</button>
                                <button type="button" class="button is-ghost member-mode-filter" data-mode="monthly" aria-pressed="false">Monthly</button>
                                <button type="button" class="button is-ghost member-mode-filter" data-mode="quarterly" aria-pressed="false">Quarterly</button>
                                <button type="button" class="button is-ghost member-mode-filter" data-mode="semi-annual" aria-pressed="false">Semi-Annual</button>
                                <button type="button" class="button is-ghost member-mode-filter" data-mode="annual" aria-pressed="false">Annual</button>
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
                <div class="eyebrow">Member directory</div>
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
                        <div class="member-row-legend" aria-label="Member row color guide">
                            <span class="legend-item">
                                <span class="legend-swatch legend-swatch-unpaid" aria-hidden="true"></span>
                                No payment yet
                            </span>
                            <span class="legend-item">
                                <span class="legend-swatch legend-swatch-danger" aria-hidden="true"></span>
                                25+ days without payment
                            </span>
                            <span class="legend-item">
                                <span class="legend-swatch legend-swatch-normal" aria-hidden="true"></span>
                                Paid or normal
                            </span>
                        </div>
                        <div class="table-scroll">
                            <table class="data-table modern compact members-table" id="membersTable">
                                <thead>
                                    <tr>
                                        <th>Planholder</th>
                                        <th>Age</th>
                                        <th>Sex</th>
                                        <th>Contact</th>
                                        <th>Complete Address</th>
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
                                            $lifecycle = $paymentLifecycle[$member->part1_id] ?? [];
                                            $rowState = $lifecycle['row_state'] ?? 'normal';
                                        @endphp
                                        <tr class="payment-row-state-{{ $rowState }}" data-mode="{{ $modeValue }}" data-name="{{ strtolower($fullName) }}" data-age="{{ (string) $member->age }}" data-created="{{ strtolower($createdDate) }}" data-application="{{ strtolower((string) $applicationDate) }}">
                                            <td class="table-col-primary">{{ $fullName }}</td>
                                            <td>{{ $member->age }}</td>
                                            <td>{{ $member->sex_at_birth }}</td>
                                            <td>{{ $address->contact_no ?? '-' }}</td>
                                            <td>{{ $address->complete_address ?? '-' }}</td>
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
                                                    data-payments='@json(($paymentHistories[$member->part1_id] ?? collect())->values())'
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
                        <span class="chip chip-neutral" id="modalPlanType">Age Category</span>
                        <span class="chip chip-accent" id="modalPayment">Payment Status</span>
                        <span class="chip chip-muted" id="modalCreated">Created</span>
                    </div>
                </div>
            <div class="modal-head-actions">
                    <button type="button" class="button is-primary modal-print-trigger">Print ID</button>
                    <button type="button" class="button is-ghost modal-ledger-trigger">Payment Ledger</button>
                    @if (empty($isReadOnly))
                        <button type="button" class="button is-primary modal-pay-trigger">Pay</button>
                        <button type="button" class="button is-primary modal-claim-trigger">Claim</button>
                    @endif
                    @if ($canEditMembers)
                        <button type="button" class="button is-warning modal-edit-trigger">Update</button>
                        <button type="button" class="button is-warning modal-inactive-trigger">Inactive</button>
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

    <div class="modal-overlay" id="paymentLedgerModal" aria-hidden="true">
        <div class="modal-card modal-card-wide">
            <div class="modal-head">
                <div class="modal-head-left">
                    <div class="modal-title">Payment Ledger</div>
                    <div class="modal-subtitle" id="ledgerSubtitle"></div>
                </div>
                <button type="button" class="modal-close" aria-label="Close">&times;</button>
            </div>
            <div class="table-scroll" style="max-height: 420px;">
                <table class="data-table modern compact">
                    <thead>
                        <tr>
                            <th>Coverage</th>
                            <th>Type</th>
                            <th class="text-right">Amount</th>
                            <th class="text-right">Deductions</th>
                            <th class="text-right">Net</th>
                            <th>Status</th>
                            <th>Paid At</th>
                            @if ($canRedoPayments)
                                <th class="text-right">Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody id="ledgerRows">
                        <tr><td colspan="{{ $canRedoPayments ? 8 : 7 }}" class="muted text-center">No payments yet.</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="button is-ghost modal-close">Close</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="payConfirmModal" aria-hidden="true">
        <div class="modal-card modal-card-narrow">
            <div class="modal-head">
                <div>
                    <div class="modal-title">Confirm Payment</div>
                    <div class="modal-subtitle" id="payConfirmSubtitle">Review payment details</div>
                </div>
                <button type="button" class="modal-close" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <ul class="modal-list" id="payConfirmDetails"></ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="button is-ghost modal-close">Cancel</button>
                <button type="button" class="button is-primary" id="payConfirmSubmit">Pay</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="payChoiceModal" aria-hidden="true">
        <div class="modal-card modal-card-narrow">
            <div class="modal-head">
                <div>
                    <div class="modal-title">Choose Payment</div>
                    <div class="modal-subtitle" id="payChoiceSubtitle">Select what to pay</div>
                </div>
                <button type="button" class="modal-close" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="pay-choice-grid">
                    <button type="button" class="pay-choice-card" data-pay-choice="registration">
                        <span class="pay-choice-title">Registration Fee</span>
                        <span class="pay-choice-sub" id="payChoiceRegistration">Initial registration payment</span>
                    </button>
                    <button type="button" class="pay-choice-card" data-pay-choice="contribution">
                        <span class="pay-choice-title">Contribution</span>
                        <span class="pay-choice-sub" id="payChoiceContribution">Next contribution payment</span>
                    </button>
                    <button type="button" class="pay-choice-card" data-pay-choice="renewal">
                        <span class="pay-choice-title">Renewal</span>
                        <span class="pay-choice-sub" id="payChoiceRenewal">Registration or renewal payment</span>
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="button is-ghost modal-close">Cancel</button>
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
                    <p class="edit-card-copy">Dates, mode, and status</p>
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

    <div class="status-modal payment-success-modal" id="paymentSuccessModal" aria-hidden="true">
        <div class="payment-success-card">
            <div class="cash-flight" aria-hidden="true">
                <span>&#8369;</span>
                <span>&#8369;</span>
                <span>&#8369;</span>
                <span>&#8369;</span>
                <span>&#8369;</span>
                <span>&#8369;</span>
            </div>
            <div class="payment-check" aria-hidden="true">
                <svg viewBox="0 0 64 64" role="img" focusable="false">
                    <circle cx="32" cy="32" r="29"></circle>
                    <path d="M19 33.5 28 42 46 23"></path>
                </svg>
            </div>
            <div class="status-title" id="paymentSuccessTitle">Payment Successful</div>
            <p class="status-body" id="paymentSuccessMessage">Payment recorded.</p>
            <button type="button" class="button status-close" data-close-payment-success>Close</button>
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

    <div class="modal-overlay" id="actionConfirmModal" aria-hidden="true">
        <div class="modal-card modal-card-narrow">
            <div class="modal-head">
                <div>
                    <div class="modal-title" id="actionConfirmTitle">Confirm Action</div>
                    <div class="modal-subtitle" id="actionConfirmMessage">Please confirm this action.</div>
                </div>
                <button type="button" class="modal-close" aria-label="Close" data-action-confirm-cancel>&times;</button>
            </div>
            <div class="modal-footer">
                <button type="button" class="button is-ghost" data-action-confirm-cancel>Cancel</button>
                <button type="button" class="button is-primary" id="actionConfirmSubmit">Confirm</button>
            </div>
        </div>
    </div>

    <div class="toast" id="globalToast" role="status" aria-live="polite"></div>

    @php
        $staffUsersPayload = [
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
            const ledgerTrigger = document.querySelector(".modal-ledger-trigger");
            const ledgerModal = document.getElementById("paymentLedgerModal");
            const ledgerSubtitle = document.getElementById("ledgerSubtitle");
            const ledgerRows = document.getElementById("ledgerRows");
            const payTrigger = document.querySelector(".modal-pay-trigger");
            const payChoiceModal = document.getElementById("payChoiceModal");
            const payChoiceSubtitle = document.getElementById("payChoiceSubtitle");
            const payChoiceRegistration = document.getElementById("payChoiceRegistration");
            const payChoiceContribution = document.getElementById("payChoiceContribution");
            const payChoiceRenewal = document.getElementById("payChoiceRenewal");
            const payChoiceButtons = document.querySelectorAll("[data-pay-choice]");
            const payConfirmModal = document.getElementById("payConfirmModal");
            const payConfirmSubtitle = document.getElementById("payConfirmSubtitle");
            const payConfirmDetails = document.getElementById("payConfirmDetails");
            const payConfirmSubmit = document.getElementById("payConfirmSubmit");
            const claimTrigger = document.querySelector(".modal-claim-trigger");
            const editTrigger = document.querySelector(".modal-edit-trigger");
            const inactiveTrigger = document.querySelector(".modal-inactive-trigger");
            const deleteTrigger = document.querySelector(".modal-delete-trigger");
            const deleteConfirmModal = document.getElementById("deleteConfirmModal");
            const deleteConfirmBtn = document.querySelector("[data-delete-confirm]");
            const deleteCancelBtn = document.querySelector("[data-delete-cancel]");
            const actionConfirmModal = document.getElementById("actionConfirmModal");
            const actionConfirmTitle = document.getElementById("actionConfirmTitle");
            const actionConfirmMessage = document.getElementById("actionConfirmMessage");
            const actionConfirmSubmit = document.getElementById("actionConfirmSubmit");
            const actionConfirmCancelButtons = document.querySelectorAll("[data-action-confirm-cancel]");
            const selectorModal = document.getElementById("editSelectorModal");
            const formModal = document.getElementById("editFormModal");
            const editFormTitle = document.getElementById("editFormTitle");
            const editFormSubtitle = document.getElementById("editFormSubtitle");
            const editFormBody = document.getElementById("editFormBody");
            const formBackButtons = document.querySelectorAll(".modal-back-selector");
            const formSaveButtons = document.querySelectorAll(".modal-save");
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
            const membersBaseUrl = document.body.dataset.membersBaseUrl || "/members";
            const isReadOnly = document.body.dataset.readonly === "1";
            const canRedoPayments = document.body.dataset.canRedoPayments === "1";
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
                    return { agents: [], managers: [] };
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
            const paymentSuccessModal = document.getElementById("paymentSuccessModal");
            const paymentSuccessTitle = document.getElementById("paymentSuccessTitle");
            const paymentSuccessMessage = document.getElementById("paymentSuccessMessage");
            const paymentSuccessClose = document.querySelector("[data-close-payment-success]");
            const toast = document.getElementById("globalToast");
            const printTrigger = document.querySelector(".modal-print-trigger");

            const currentContext = {
                member: null,
                part1: null,
                address: null,
                assignment: null,
                benes: [],
                bene: null,
                payments: [],
            };
            let selectedPayPaymentId = null;
            let selectedPayPaymentIds = [];
            let visibleContributionCount = 1;
            let selectedPayCategory = null;
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
                    case "inactive":
                        return { label: "Inactive", className: "chip chip-muted" };
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

            const getPaidContributionMonths = (part1 = {}) => {
                const paidInstallments = Number(part1.paid_installments || 0);
                const mode = (part1.mode_of_payment || "").toString().toLowerCase().trim();
                switch (mode) {
                    case "quarterly":
                        return paidInstallments * 3;
                    case "semi-annual":
                    case "semi annual":
                        return paidInstallments * 6;
                    case "annual":
                    case "yearly":
                        return paidInstallments * 12;
                    case "one-time":
                    case "one time":
                    case "one_time":
                        return paidInstallments > 0 ? (parseMonthsFromTerms(part1.terms_of_payment) || 1) : 0;
                    default:
                        return paidInstallments;
                }
            };

            const getMonthsPaidLabel = (part1 = {}) => {
                const months = getPaidContributionMonths(part1);
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

            const showPaymentSuccess = (payment = null, options = {}) => {
                if (paymentSuccessTitle) {
                    paymentSuccessTitle.textContent = options.title || "Payment Successful";
                }
                if (paymentSuccessMessage) {
                    if (options.message) {
                        paymentSuccessMessage.textContent = options.message;
                    } else {
                        const label = payment ? paymentTypeLabel(payment) : "Payment";
                        const amount = payment ? ` ${formatMoney(payment.amount)}` : "";
                        paymentSuccessMessage.textContent = `${label}${amount} recorded successfully.`;
                    }
                }
                paymentSuccessModal?.classList.toggle("is-cashless", Boolean(options.hideCash));
                toggleOverlay(paymentSuccessModal, true);
            };

            let actionConfirmResolve = null;
            const closeActionConfirm = (value = false) => {
                toggleOverlay(actionConfirmModal, false);
                if (actionConfirmResolve) {
                    actionConfirmResolve(value);
                    actionConfirmResolve = null;
                }
            };

            const openActionConfirm = ({
                title = "Confirm Action",
                message = "Please confirm this action.",
                confirmText = "Confirm",
                cancelText = "Cancel",
                confirmClass = "button is-primary",
                showCancel = true,
            } = {}) => new Promise(resolve => {
                actionConfirmResolve = resolve;
                if (actionConfirmTitle) actionConfirmTitle.textContent = title;
                if (actionConfirmMessage) actionConfirmMessage.textContent = message;
                if (actionConfirmSubmit) {
                    actionConfirmSubmit.textContent = confirmText;
                    actionConfirmSubmit.className = confirmClass;
                }
                actionConfirmCancelButtons.forEach(button => {
                    button.textContent = button.classList.contains("modal-close") ? "×" : cancelText;
                    button.style.display = showCancel ? "" : "none";
                });
                toggleOverlay(actionConfirmModal, true);
            });

            const openActionNotice = (title, message, confirmText = "OK") => openActionConfirm({
                title,
                message,
                confirmText,
                showCancel: false,
            });

            actionConfirmSubmit?.addEventListener("click", () => closeActionConfirm(true));
            actionConfirmCancelButtons.forEach(button => {
                button.addEventListener("click", () => closeActionConfirm(false));
            });
            actionConfirmModal?.addEventListener("click", (e) => {
                if (e.target === actionConfirmModal) closeActionConfirm(false);
            });

            const formatMoney = (value) => {
                const amount = Number(value || 0);
                if (!Number.isFinite(amount)) return "-";
                return amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            };

            const formatLedgerDate = (value) => {
                if (!value) return "-";
                const date = new Date(value);
                if (Number.isNaN(date.getTime())) return value;
                return date.toLocaleDateString(undefined, { year: "numeric", month: "short", day: "2-digit" });
            };

            const parseDateOnly = (value) => {
                if (!value) return null;
                const parts = value.toString().slice(0, 10).split("-").map(Number);
                if (parts.length !== 3 || parts.some(Number.isNaN)) return null;
                return new Date(parts[0], parts[1] - 1, parts[2]);
            };

            const addMonthsClamped = (date, months) => {
                const next = new Date(date.getFullYear(), date.getMonth() + months, 1);
                const lastDay = new Date(next.getFullYear(), next.getMonth() + 1, 0).getDate();
                next.setDate(Math.min(date.getDate(), lastDay));
                return next;
            };

            const paymentCoverageLabel = (payment) => {
                const start = parseDateOnly(payment?.due_date);
                if (!start) return formatLedgerDate(payment?.due_date);

                const type = (payment?.payment_type || "regular").toString().toLowerCase();
                if (type === "contestability") return formatLedgerDate(payment?.paid_at || payment?.due_date);

                const mode = (currentContext.part1?.mode_of_payment || "monthly").toString().toLowerCase().trim();
                const intervalMonths = type === "registration_renewal"
                    ? 12
                    : mode === "quarterly"
                    ? 3
                    : (mode === "semi-annual" || mode === "semi annual")
                        ? 6
                        : (mode === "annual" || mode === "yearly")
                            ? 12
                            : 1;
                const end = addMonthsClamped(start, intervalMonths);
                end.setDate(end.getDate() - 1);

                return `${formatLedgerDate(start)} - ${formatLedgerDate(end)}`;
            };

            const ledgerStatusChip = (statusRaw) => {
                const status = (statusRaw || "pending").toString().toLowerCase();
                if (status === "paid") return '<span class="chip chip-accent">Paid</span>';
                if (status === "overdue") return '<span class="chip chip-muted">Overdue</span>';
                return '<span class="chip chip-neutral">Pending</span>';
            };

            const paymentTypeLabel = (payment) => {
                const type = (payment?.payment_type || "regular").toString().toLowerCase();
                if (type === "contestability") return "Contestability Fee";
                if (type !== "registration_renewal") return "Contribution";

                const rows = Array.isArray(currentContext.payments) ? currentContext.payments : [];
                const firstRegistration = rows
                    .filter(row => (row.payment_type || "regular").toString().toLowerCase() === "registration_renewal")
                    .sort((a, b) => Date.parse(a.due_date || "") - Date.parse(b.due_date || ""))[0];

                return firstRegistration && String(firstRegistration.id) === String(payment?.id)
                    ? "Registration Fee"
                    : "Renew";
            };

            const firstRegistrationPayment = () => {
                const rows = Array.isArray(currentContext.payments) ? [...currentContext.payments] : [];
                return rows
                    .filter(row => (row.payment_type || "regular").toString().toLowerCase() === "registration_renewal")
                    .sort((a, b) => Date.parse(a.due_date || "") - Date.parse(b.due_date || ""))[0] || null;
            };

            const getPayablePayments = (category = null) => {
                const rows = Array.isArray(currentContext.payments) ? [...currentContext.payments] : [];
                const isPayable = row => (row.status || "").toString().toLowerCase() !== "paid";
                const unpaid = rows.filter(isPayable);
                const initialRegistration = firstRegistrationPayment();

                const nextRenewals = unpaid.filter(row => {
                    const type = (row.payment_type || "regular").toString().toLowerCase();
                    return type === "registration_renewal" && String(row.id) !== String(initialRegistration?.id);
                }).sort((a, b) => Date.parse(a.due_date || "") - Date.parse(b.due_date || ""));
                const nextContributions = unpaid
                    .filter(row => {
                        const type = (row.payment_type || "regular").toString().toLowerCase();
                        return type === "regular";
                    })
                    .sort((a, b) => Date.parse(a.due_date || "") - Date.parse(b.due_date || ""));

                if (category === "registration") return initialRegistration && isPayable(initialRegistration) ? [initialRegistration] : [];
                if (category === "renewal") return nextRenewals;
                if (category === "contribution") return nextContributions;

                if (initialRegistration && isPayable(initialRegistration)) return [initialRegistration];
                return [...nextRenewals.slice(0, 1), nextContributions[0]].filter(Boolean)
                    .sort((a, b) => Date.parse(a.due_date || "") - Date.parse(b.due_date || ""));
            };

            const nextPayablePayment = () => {
                return getPayablePayments()[0] || null;
            };

            const refreshPayments = async () => {
                const memberId = currentContext.member?.id;
                if (!memberId) return;

                const response = await fetch(`${membersBaseUrl}/${memberId}/payments`, {
                    headers: {
                        "Accept": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(payload?.message || "Unable to load payments.");
                }

                currentContext.payments = Array.isArray(payload.payments) ? payload.payments : [];
                if (currentTriggerBtn) {
                    currentTriggerBtn.dataset.payments = JSON.stringify(currentContext.payments);
                }
            };

            const summarizePaymentChoice = (payment, emptyText) => {
                if (!payment) return emptyText;

                return `${formatMoney(payment.amount)} due ${formatLedgerDate(payment.due_date)}`;
            };

            const renderPayChoices = () => {
                const member = currentContext.member || {};
                const initialRegistration = firstRegistrationPayment();
                const registration = (
                    initialRegistration
                    && (initialRegistration.status || "").toString().toLowerCase() !== "paid"
                ) ? initialRegistration : null;
                const contribution = getPayablePayments("contribution")[0] || null;
                const renewal = getPayablePayments("renewal")[0] || null;
                if (payChoiceSubtitle) {
                    payChoiceSubtitle.textContent = [member.first_name, member.midle_name, member.surname].filter(Boolean).join(" ") || "Member";
                }
                if (payChoiceRegistration) {
                    payChoiceRegistration.textContent = summarizePaymentChoice(registration, "No registration fee due.");
                }
                if (payChoiceContribution) {
                    payChoiceContribution.textContent = summarizePaymentChoice(contribution, "Click to advance contribution payment.");
                }
                if (payChoiceRenewal) {
                    payChoiceRenewal.textContent = summarizePaymentChoice(renewal, "Click to advance renewal payment.");
                }

                payChoiceButtons.forEach(button => {
                    const category = button.dataset.payChoice;
                    const hasPayment = Boolean(getPayablePayments(category)[0]);
                    button.hidden = category === "registration" && !registration;
                    button.disabled = category === "registration" && !hasPayment;
                    button.classList.toggle("is-disabled", category === "registration" && !hasPayment);
                });
            };

            const renderPayConfirm = () => {
                const allPayments = getPayablePayments(selectedPayCategory);
                const multiplePayment = ["contribution", "renewal"].includes(selectedPayCategory);
                const categoryLabel = selectedPayCategory === "renewal" ? "renewal" : "contribution";
                const maxVisibleCount = Math.max(1, allPayments.length);
                visibleContributionCount = Math.min(maxVisibleCount, Math.max(1, Number(visibleContributionCount || 1)));
                const payments = multiplePayment
                    ? allPayments.slice(0, visibleContributionCount)
                    : allPayments;
                const member = currentContext.member || {};
                if (payConfirmSubtitle) {
                    payConfirmSubtitle.textContent = [member.first_name, member.midle_name, member.surname].filter(Boolean).join(" ") || "Member";
                }
                if (!payConfirmDetails) return;

                selectedPayPaymentId = payments[0]?.id || null;
                selectedPayPaymentIds = multiplePayment
                    ? payments.map(payment => String(payment.id))
                    : (payments[0]?.id ? [String(payments[0].id)] : []);
                if (!payments.length) {
                    payConfirmDetails.innerHTML = '<li><span>Status</span><strong>No payable schedule selected.</strong></li>';
                    return;
                }

                payConfirmDetails.innerHTML = payments.map((payment, index) => {
                    const deductions = Number(payment?.insurance_total || 0);
                    const net = payment?.net_amount ?? (Number(payment?.amount || 0) - deductions);
                    const checked = multiplePayment || index === 0 ? "checked" : "";
                    const inputType = multiplePayment ? "checkbox" : "radio";
                    const inputName = multiplePayment ? "pay_payment_ids" : "pay_payment_id";
                    return `
                        <li>
                            <label style="display:grid;grid-template-columns:auto 1fr;gap:10px;align-items:flex-start;width:100%;cursor:pointer;">
                                <input type="${inputType}" name="${inputName}" value="${payment.id}" ${checked} style="margin-top:4px;">
                                <span style="display:grid;gap:4px;">
                                    <strong>${paymentTypeLabel(payment)} - ${formatMoney(payment.amount)}</strong>
                                    <span>Coverage: ${paymentCoverageLabel(payment)} | Status: ${(payment.status || "Pending").toString()}</span>
                                    <span>Deductions: ${formatMoney(deductions)} | Net: ${formatMoney(net)}</span>
                                </span>
                            </label>
                        </li>
                    `;
                }).join("");

                if (multiplePayment) {
                    const selectedTotal = payments.reduce((sum, payment) => sum + Number(payment.amount || 0), 0);
                    payConfirmDetails.insertAdjacentHTML("beforeend", `
                        <li class="pay-advance-control">
                            <span>Advance ${categoryLabel}s</span>
                            <div class="pay-stepper" aria-label="Advance ${categoryLabel} count">
                                <button type="button" class="button is-ghost pay-step-button" id="payRemovePayment" ${visibleContributionCount <= 1 ? "disabled" : ""}>-</button>
                                <input type="number" id="payAdvanceCount" min="1" max="${allPayments.length}" value="${visibleContributionCount}">
                                <button type="button" class="button is-ghost pay-step-button" id="payAddPayment" ${visibleContributionCount >= allPayments.length ? "disabled" : ""}>+</button>
                            </div>
                        </li>
                        <li>
                            <span>Total selected</span>
                            <strong id="paySelectedTotal">${formatMoney(selectedTotal)}</strong>
                        </li>
                    `);
                }

                const syncSelectedPayments = () => {
                    if (multiplePayment) {
                        const inputs = Array.from(payConfirmDetails.querySelectorAll('input[name="pay_payment_ids"]'));
                        const lastCheckedIndex = inputs.reduce((lastIndex, input, index) => input.checked ? index : lastIndex, -1);
                        inputs.forEach((input, index) => {
                            input.checked = lastCheckedIndex >= 0 && index <= lastCheckedIndex;
                        });
                        selectedPayPaymentIds = inputs.filter(input => input.checked).map(input => input.value);
                        selectedPayPaymentId = selectedPayPaymentIds[0] || null;
                        const selectedTotal = payments
                            .filter(payment => selectedPayPaymentIds.includes(String(payment.id)))
                            .reduce((sum, payment) => sum + Number(payment.amount || 0), 0);
                        const totalNode = document.getElementById("paySelectedTotal");
                        if (totalNode) totalNode.textContent = formatMoney(selectedTotal);
                        return;
                    }

                    selectedPayPaymentIds = selectedPayPaymentId ? [String(selectedPayPaymentId)] : [];
                };

                document.getElementById("payAddPayment")?.addEventListener("click", () => {
                    visibleContributionCount = Math.min(allPayments.length, visibleContributionCount + 1);
                    renderPayConfirm();
                });
                document.getElementById("payRemovePayment")?.addEventListener("click", () => {
                    visibleContributionCount = Math.max(1, visibleContributionCount - 1);
                    renderPayConfirm();
                });
                document.getElementById("payAdvanceCount")?.addEventListener("change", (event) => {
                    const requestedCount = Number(event.target.value || 1);
                    visibleContributionCount = Math.min(allPayments.length, Math.max(1, requestedCount));
                    renderPayConfirm();
                });

                payConfirmDetails.querySelectorAll('input[name="pay_payment_id"], input[name="pay_payment_ids"]').forEach(input => {
                    input.addEventListener("change", () => {
                        if (multiplePayment && !input.checked) {
                            const inputs = Array.from(payConfirmDetails.querySelectorAll('input[name="pay_payment_ids"]'));
                            const uncheckedIndex = inputs.findIndex(item => item === input);
                            visibleContributionCount = Math.max(1, uncheckedIndex);
                            renderPayConfirm();
                            return;
                        }

                        selectedPayPaymentId = input.value;
                        syncSelectedPayments();
                    });
                });
                syncSelectedPayments();
            };

            const renderPaymentLedger = () => {
                const rows = (Array.isArray(currentContext.payments) ? [...currentContext.payments] : [])
                    .filter(row => (row.status || "").toString().toLowerCase() === "paid");
                const member = currentContext.member || {};
                if (ledgerSubtitle) {
                    ledgerSubtitle.textContent = [member.first_name, member.midle_name, member.surname].filter(Boolean).join(" ") || "Member";
                }
                if (!ledgerRows) return;
                if (!rows.length) {
                    ledgerRows.innerHTML = `<tr><td colspan="${canRedoPayments ? 8 : 7}" class="muted text-center">No paid payments yet.</td></tr>`;
                    return;
                }

                const sortedRows = rows
                    .sort((a, b) => {
                        const dateDiff = Date.parse(a.due_date || "") - Date.parse(b.due_date || "");
                        if (dateDiff !== 0) return dateDiff;
                        const aType = (a.payment_type || "regular").toString().toLowerCase() === "registration_renewal" ? 0 : 1;
                        const bType = (b.payment_type || "regular").toString().toLowerCase() === "registration_renewal" ? 0 : 1;
                        return aType - bType;
                    });
                const latestRedoPaymentId = sortedRows[sortedRows.length - 1]?.id;

                ledgerRows.innerHTML = sortedRows
                    .map(row => {
                        const deductions = row.insurance_total ?? 0;
                        const net = row.net_amount ?? (Number(row.amount || 0) - Number(deductions || 0));
                        const canRedoRow = String(row.id) === String(latestRedoPaymentId);
                        const actionCell = canRedoPayments
                            ? `<td class="text-right"><button type="button" class="button is-ghost payment-redo-trigger" data-payment-id="${row.id}" ${canRedoRow ? "" : "disabled"} title="${canRedoRow ? "Redo this payment" : "Redo latest payment first"}">Redo</button></td>`
                            : "";
                        return `
                            <tr>
                                <td>${paymentCoverageLabel(row)}</td>
                                <td>${paymentTypeLabel(row)}</td>
                                <td class="text-right">${formatMoney(row.amount)}</td>
                                <td class="text-right">${formatMoney(deductions)}</td>
                                <td class="text-right">${formatMoney(net)}</td>
                                <td>${ledgerStatusChip(row.status)}</td>
                                <td>${formatLedgerDate(row.paid_at)}</td>
                                ${actionCell}
                            </tr>
                        `;
                    }).join("");
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
                            subtitle: "Update enrollment dates and contribution status.",
                            body: `
                                <div class="modal-form-grid">
                                    ${textField("Application Date", "application_date", part1?.application_date)}
                                    ${textField("Approved Date", "approved_date", part1?.approved_date)}
                                    ${selectField("Contribution", "mode_of_payment", [
                                        { id: "Monthly", name: "Monthly" },
                                        { id: "Quarterly", name: "Quarterly" },
                                        { id: "Semi-Annual", name: "Semi-Annual" },
                                        { id: "Annual", name: "Annual" },
                                    ], part1?.mode_of_payment)}
                                    ${textField("Due Date", "due_date", part1?.due_date)}
                                </div>
                            `,
                        };
                    case "member":
                        return {
                            title: "Member Details",
                            subtitle: "Personal and employment details.",
                            body: `
                                <div class="modal-form-grid">
                                    ${textField("Reference Number", "reference_number", part1?.reference_number)}
                                    ${textField("Surname", "surname", member?.surname)}
                                    ${textField("First Name", "first_name", member?.first_name)}
                                    ${textField("Middle Name", "midle_name", member?.midle_name)}
                                    ${textField("Birthplace", "place_of_birth", member?.place_of_birth)}
                                    ${textField("Birthdate", "date_of_birth", member?.date_of_birth)}
                                    ${textField("Age", "age", member?.age)}
                                    ${textField("Sex at Birth", "sex_at_birth", member?.sex_at_birth)}
                                </div>
                            `,
                        };
                    case "address":
                        return {
                            title: "Address",
                            subtitle: "Contact and supporting IDs.",
                            body: `
                                <div class="modal-form-grid">
                                    ${textField("Complete Address", "complete_address", address?.complete_address)}
                                    ${textField("Contact No.", "contact_no", address?.contact_no)}
                                    ${textField("Religion", "religion", address?.religion)}
                                    ${textField("Occupation/Livelihood", "occupation_livelihood", address?.occupation_livelihood)}
                                    ${textField("Valid ID", "valid_id", address?.valid_id)}
                                    ${textField("Valid ID #", "valid_id_no", address?.valid_id_no)}
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
                                    ${textField("Name", "name", firstBene?.name)}
                                    ${textField("Address", "address", firstBene?.address)}
                                    ${textField("Relationship", "relationship_to_planholder", firstBene?.relationship_to_planholder)}
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
                        const nameInput = editFormBody.querySelector('input[name="name"]');
                        const addressInput = editFormBody.querySelector('input[name="address"]');
                        const relationInput = editFormBody.querySelector('input[name="relationship_to_planholder"]');
                        if (hiddenId) hiddenId.value = bene?.id ?? '';
                        if (nameInput) nameInput.value = bene?.name || "";
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
                modalPlan.textContent = part1.plan_type || "No age category";
                applyPaymentChip(part1.payment_status || "Pending");
                modalCreated.textContent = member.created_at ? `Added ${member.created_at}` : "No date";

                writeList(modalPart1, [
                    ["Reference Number", part1.reference_number ?? "-"],
                    ["Added By", part1.added_by_name ?? "-"],
                    ["Age Category", part1.plan_type ?? "-"],
                    ["Application Date", part1.application_date ?? "-"],
                    ["Approved Date", part1.approved_date ?? "-"],
                    ["How many months paid", getMonthsPaidLabel(part1)],
                    ["Total paid", formatCurrency(part1.paid_amount_total)],
                    ["Contribution", part1.mode_of_payment ?? "-"],
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
                ]);

                writeList(modalAddress, [
                    ["Complete Address", address.complete_address ?? "-"],
                    ["Contact No.", address.contact_no ?? "-"],
                    ["Religion", address.religion ?? "-"],
                    ["Occupation/Livelihood", address.occupation_livelihood ?? "-"],
                    ["Valid ID", address.valid_id ?? "-"],
                    ["Valid ID #", address.valid_id_no ?? "-"],
                ]);

                writeList(modalStaff, [
                    ["Unit Name", assignment.unit_name ?? "-"],
                    ["Agent Name", assignment.agent_name ?? "-"],
                    ["Unit Manager", assignment.manager_name ?? "-"],
                    ["Sales Associate", assignment.sales_associate ?? "-"],
                    ["Contact", assignment.staff_contact ?? "-"],
                ]);

                if (!benes.length) {
                    writeList(modalBeneficiary, []);
                } else {
                    const beneItems = benes.map((b, idx) => {
                        const label = `Beneficiary ${idx + 1}`;
                        return `
                            <li class="bene-card">
                                <div class="bene-card-header">${label}</div>
                                <div class="bene-block">
                                    <div><em>Name</em> ${b.name ?? '-'}</div>
                                    <div><em>Address</em> ${b.address ?? '-'}</div>
                                    <div><em>Relationship</em> ${b.relationship_to_planholder ?? '-'}</div>
                                </div>
                            </li>
                        `;
                    });
                    modalBeneficiary.innerHTML = beneItems.join('');
                }
            };

            const loadContextFromButton = (btn) => {
                    const member = JSON.parse(btn.dataset.member || "{}");
                    const part1 = JSON.parse(btn.dataset.part1 || "{}");
                    part1.paid_installments = Number(btn.dataset.paidInstallments || 0);
                    part1.paid_amount_total = Number(btn.dataset.paidAmount || 0);
                    const address = JSON.parse(btn.dataset.address || "{}");
                    const assignment = JSON.parse(btn.dataset.assignment || "{}");
                    const benes = JSON.parse(btn.dataset.beneficiaries || "[]");
                    const paymentsPayload = JSON.parse(btn.dataset.payments || "[]");
                    const payments = Array.isArray(paymentsPayload)
                        ? paymentsPayload
                        : Object.values(paymentsPayload || {});

                    currentTriggerBtn = btn;
                    currentRow = btn.closest("tr");
                    currentContext.member = member;
                    currentContext.part1 = part1;
                    currentContext.address = address;
                    currentContext.assignment = assignment;
                    currentContext.benes = benes || [];
                    currentContext.bene = (benes && benes.length) ? benes[0] : null;
                    currentContext.payments = payments || [];
                    renderModalDetails();
            };

            document.querySelectorAll(".view-member").forEach(btn => {
                btn.addEventListener("click", () => {
                    loadContextFromButton(btn);

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

                inactiveTrigger?.addEventListener("click", async () => {
                    const memberId = currentContext.member?.id;
                    if (!memberId) {
                        showToast("Select a member first.");
                        return;
                    }

                    const shouldInactive = await openActionConfirm({
                        title: "Mark Inactive",
                        message: "Move this member to inactive for testing?",
                        confirmText: "Inactive",
                        confirmClass: "button is-warning",
                    });
                    if (!shouldInactive) {
                        return;
                    }

                    inactiveTrigger.setAttribute("disabled", "true");
                    try {
                        const response = await fetch(`/members/${memberId}/inactive`, {
                            method: "POST",
                            headers: {
                                "Accept": "application/json",
                                "X-CSRF-TOKEN": csrfToken,
                                "X-Requested-With": "XMLHttpRequest",
                            },
                        });

                        if (!response.ok) {
                            const payload = await response.json().catch(() => ({}));
                            throw new Error(payload?.message || "Inactive failed.");
                        }

                        currentRow?.remove();
                        closeModal();
                        showToast("Member moved to inactive.");
                    } catch (err) {
                        showToast(err.message || "Inactive failed.");
                    } finally {
                        inactiveTrigger.removeAttribute("disabled");
                    }
                });

                payTrigger?.addEventListener("click", async () => {
                    if (!currentContext.member?.id) {
                        showToast("Select a member first.");
                        return;
                    }

                    payTrigger.setAttribute("disabled", "true");
                    try {
                        await refreshPayments();
                        renderPayChoices();
                        toggleOverlay(payChoiceModal, true);
                    } catch (err) {
                        showToast(err.message || "Unable to load payments.");
                    } finally {
                        payTrigger.removeAttribute("disabled");
                    }
                });

                payChoiceButtons.forEach(button => {
                    button.addEventListener("click", async () => {
                        selectedPayCategory = button.dataset.payChoice || null;
                        button.setAttribute("disabled", "true");
                        try {
                            await refreshPayments();
                            visibleContributionCount = 1;
                            renderPayConfirm();
                            toggleOverlay(payChoiceModal, false);
                            toggleOverlay(payConfirmModal, true);
                        } catch (err) {
                            showToast(err.message || "Unable to load payments.");
                        } finally {
                            button.removeAttribute("disabled");
                        }
                    });
                });

                payChoiceModal?.addEventListener("click", (e) => {
                    if (e.target === payChoiceModal || e.target.classList.contains("modal-close")) {
                        toggleOverlay(payChoiceModal, false);
                    }
                });

                payConfirmSubmit?.addEventListener("click", async () => {
                    const memberId = currentContext.member?.id;
                    if (!memberId) {
                        showToast("Select a member first.");
                        toggleOverlay(payConfirmModal, false);
                        return;
                    }

                    payConfirmSubmit.setAttribute("disabled", "true");
                    payTrigger?.setAttribute("disabled", "true");
                    try {
                        const paymentIds = ["contribution", "renewal"].includes(selectedPayCategory)
                            ? selectedPayPaymentIds
                            : (selectedPayPaymentId ? [String(selectedPayPaymentId)] : []);
                        if (!paymentIds.length) {
                            throw new Error("Select at least one payment.");
                        }

                        const response = await fetch(`${membersBaseUrl}/${memberId}/pay-next`, {
                            method: "POST",
                            headers: {
                                "Accept": "application/json",
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": csrfToken,
                                "X-Requested-With": "XMLHttpRequest",
                            },
                            body: JSON.stringify({
                                payment_id: paymentIds[0],
                                payment_ids: paymentIds,
                            }),
                        });

                        const payload = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            throw new Error(payload?.message || "Payment failed.");
                        }

                        const paidCategory = selectedPayCategory;
                        currentContext.payments = payload.payments || [];
                        currentContext.part1 = {
                            ...(currentContext.part1 || {}),
                            payment_status: payload.payment_status || "pending",
                            paid_installments: Number(payload.paid_installments || 0),
                            paid_amount_total: Number(payload.paid_amount_total || 0),
                        };
                        if (currentTriggerBtn) {
                            currentTriggerBtn.dataset.payments = JSON.stringify(currentContext.payments);
                            currentTriggerBtn.dataset.paidInstallments = String(currentContext.part1.paid_installments || 0);
                            currentTriggerBtn.dataset.paidAmount = String(currentContext.part1.paid_amount_total || 0);
                        }

                        renderModalDetails();
                        renderPaymentLedger();
                        toggleOverlay(payConfirmModal, false);
                        selectedPayCategory = null;
                        visibleContributionCount = 1;
                        const paidIds = (payload.payment_ids || [payload.payment_id]).map(id => String(id));
                        const paidPayments = currentContext.payments.filter(row => paidIds.includes(String(row.id)));
                        const paidPayment = paidPayments[0] || null;
                        if (paidPayments.length > 1) {
                            const total = paidPayments.reduce((sum, payment) => sum + Number(payment.amount || 0), 0);
                            const typeLabel = paidCategory === "renewal" ? "renewals" : "contributions";
                            showPaymentSuccess(null, {
                                message: `${paidPayments.length} ${typeLabel} ${formatMoney(total)} recorded successfully.`,
                            });
                        } else {
                            showPaymentSuccess(paidPayment);
                        }
                    } catch (err) {
                        showToast(err.message || "Payment failed.");
                    } finally {
                        payConfirmSubmit.removeAttribute("disabled");
                        payTrigger?.removeAttribute("disabled");
                    }
                });

                payConfirmModal?.addEventListener("click", (e) => {
                    if (e.target === payConfirmModal || e.target.classList.contains("modal-close")) {
                        toggleOverlay(payConfirmModal, false);
                        selectedPayCategory = null;
                        visibleContributionCount = 1;
                    }
                });

                claimTrigger?.addEventListener("click", async () => {
                    const memberId = currentContext.member?.id;
                    if (!memberId) {
                        showToast("Select a member first.");
                        return;
                    }

                    const shouldClaim = await openActionConfirm({
                        title: "Claim Member",
                        message: "Mark this member as claimed?",
                        confirmText: "Claim",
                    });
                    if (!shouldClaim) {
                        return;
                    }

                    let includeBurial = false;
                    if (getPaidContributionMonths(currentContext.part1 || {}) >= 24) {
                        includeBurial = await openActionConfirm({
                            title: "Burial Claim",
                            message: "I-claim apil ang burial assistance?",
                            confirmText: "Claim Burial",
                            cancelText: "No Burial",
                        });
                    }

                    claimTrigger.setAttribute("disabled", "true");
                    try {
                        const response = await fetch(`/members/${memberId}/claim`, {
                            method: "POST",
                            headers: {
                                "Accept": "application/json",
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": csrfToken,
                                "X-Requested-With": "XMLHttpRequest",
                            },
                            body: JSON.stringify({
                                include_burial: includeBurial,
                            }),
                        });

                        if (!response.ok) {
                            const payload = await response.json().catch(() => ({}));
                            throw new Error(payload?.message || "Claim failed.");
                        }

                        currentRow?.remove();
                        closeModal();
                        showToast("Member claimed.");
                    } catch (err) {
                        showToast(err.message || "Claim failed.");
                    } finally {
                        claimTrigger.removeAttribute("disabled");
                    }
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
                            await openActionNotice("Missing Selection", "Select a member and section first.");
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
                        if (currentSection === "enrollment") {
                            const selectedMode = (formData.get("mode_of_payment") || "").toString();
                            const currentMode = (currentContext.part1?.mode_of_payment || "").toString();
                            const normalized = selectedMode.toLowerCase().trim();
                            if (selectedMode && selectedMode !== currentMode) {
                                const now = new Date();
                                const month = now.getMonth() + 1;
                                const monthsRemaining = 12 - month + 1;
                                let message = "";
                                if (normalized === "annual" && month !== 1) {
                                    message = "Annual mode can only be changed in January.";
                                } else if ((normalized === "semi-annual" || normalized === "semi annual") && monthsRemaining < 6) {
                                    message = "Semi-Annual mode needs at least 6 months remaining in the year.";
                                } else if (normalized === "quarterly" && monthsRemaining < 3) {
                                    message = "Quarterly mode needs at least 3 months remaining in the year.";
                                }

                                if (message) {
                                    await openActionNotice("Payment Mode Not Allowed", message);
                                    return;
                                }

                                const amountMap = { monthly: 1, quarterly: 3, "semi-annual": 6, "semi annual": 6, annual: 12 };
                                const multiplier = amountMap[normalized] || 1;
                                const baseAmount = Number(currentContext.part1?.amount || currentContext.part1?.gross_contact_price || 0);
                                await openActionNotice(
                                    "Contribution Updated",
                                    `Contribution will change to ${selectedMode}. New payment amount will be ${Number(baseAmount * multiplier).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}.`
                                );
                            }
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
                                    if (updated.reference_number !== undefined) {
                                        currentContext.part1 = { ...(currentContext.part1 || {}), reference_number: updated.reference_number };
                                    }
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
                                        const aName = findName(staffUsers.agents, updated.agent_user_id);
                                        const mName = findName(staffUsers.managers, updated.manager_user_id);
                                        if (aName) currentContext.assignment.agent_name = aName;
                                        if (mName) currentContext.assignment.manager_name = mName;
                                    }
                                    break;
                            }

                            if (currentRow && (currentSection === "member" || currentSection === "address")) {
                                const cells = currentRow.querySelectorAll("td");
                                const m = currentContext.member || {};
                                const a = currentContext.address || {};
                                if (cells[0]) cells[0].textContent = [m.first_name, m.midle_name, m.surname].filter(Boolean).join(" ");
                                if (cells[1]) cells[1].textContent = m.age || "";
                                if (cells[2]) cells[2].textContent = m.sex_at_birth || "";
                                if (cells[3]) cells[3].textContent = a.contact_no || "-";
                                if (cells[4]) cells[4].textContent = a.complete_address || "-";
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
                            await openActionNotice("Save Failed", err.message || "Save failed. Please try again.");
                            showToast(err.message || "Save failed. Please try again.");
                        }
                    });
                });

                editSuccessModal?.addEventListener("click", (e) => {
                    if (e.target === editSuccessModal) toggleOverlay(editSuccessModal, false);
                });

                editSuccessClose?.addEventListener("click", () => toggleOverlay(editSuccessModal, false));
                paymentSuccessModal?.addEventListener("click", (e) => {
                    if (e.target === paymentSuccessModal) toggleOverlay(paymentSuccessModal, false);
                });

                paymentSuccessClose?.addEventListener("click", () => toggleOverlay(paymentSuccessModal, false));
            }

            ledgerTrigger?.addEventListener("click", () => {
                if (!currentContext.member) {
                    showToast("Select a member first.");
                    return;
                }

                renderPaymentLedger();
                toggleOverlay(ledgerModal, true);
            });

            ledgerModal?.addEventListener("click", async (e) => {
                if (e.target === ledgerModal || e.target.classList.contains("modal-close")) {
                    toggleOverlay(ledgerModal, false);
                }

                const redoButton = e.target.closest?.(".payment-redo-trigger");
                if (!redoButton) return;

                const memberId = currentContext.member?.id;
                const paymentId = redoButton.dataset.paymentId;
                if (!memberId || !paymentId) {
                    showToast("Select a payment first.");
                    return;
                }

                const shouldRedo = await openActionConfirm({
                    title: "Redo Payment",
                    message: "Redo this payment and mark it unpaid again?",
                    confirmText: "Redo",
                    confirmClass: "button is-warning",
                });
                if (!shouldRedo) {
                    return;
                }

                redoButton.setAttribute("disabled", "true");
                fetch(`/members/${memberId}/payments/${paymentId}/redo`, {
                    method: "POST",
                    headers: {
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                        "X-Requested-With": "XMLHttpRequest",
                    },
                })
                    .then(async response => {
                        const payload = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            throw new Error(payload?.message || "Redo failed.");
                        }

                        currentContext.payments = payload.payments || [];
                        currentContext.part1 = {
                            ...(currentContext.part1 || {}),
                            payment_status: payload.payment_status || "pending",
                            paid_installments: Number(payload.paid_installments || 0),
                            paid_amount_total: Number(payload.paid_amount_total || 0),
                        };
                        if (currentTriggerBtn) {
                            currentTriggerBtn.dataset.payments = JSON.stringify(currentContext.payments);
                            currentTriggerBtn.dataset.paidInstallments = String(currentContext.part1.paid_installments || 0);
                            currentTriggerBtn.dataset.paidAmount = String(currentContext.part1.paid_amount_total || 0);
                        }

                        renderModalDetails();
                        renderPaymentLedger();
                        showPaymentSuccess(null, {
                            title: "Redo Successful",
                            message: "Payment reopened successfully.",
                            hideCash: true,
                        });
                    })
                    .catch(err => {
                        showToast(err.message || "Redo failed.");
                        redoButton.removeAttribute("disabled");
                    });
            });

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
                        <div class="id-title">{{ $appBrandName }}</div>
                        <div class="id-name">${[m.first_name, m.midle_name, m.surname].filter(Boolean).join(" ")}</div>
                        <div class="id-meta">Age Category: ${p.plan_type || "-"}</div>
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
