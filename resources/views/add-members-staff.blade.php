<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ $appBrandLogoUrl }}">
    <title>Add Members - Staff Info | {{ $appBrandName }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/app.css') . '?v=' . filemtime(public_path('css/app.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/partials/nav.css') . '?v=' . filemtime(public_path('css/partials/nav.css')) }}">
</head>
@php
    $isDraft = $isDraft ?? false;
@endphp
<body class="has-shell" data-draft="{{ $isDraft ? '1' : '0' }}">
    <div class="page">
        @include('partials.header')

        <main class="dashboard">
            <section class="wrap">
                @php
                    $assignmentId = $assignment->id ?? null;
                @endphp
                <div class="progress-steps">
                    @if ($isDraft)
                        <a class="step-pill is-current" href="#">
                            <input type="radio" name="progress_step" checked aria-hidden="true">
                            <span>Staff Info</span>
                        </a>
                        <a class="step-pill" href="{{ route('add-members.draft.part2') }}">
                            <input type="radio" name="progress_step" aria-hidden="true">
                            <span>Member Details</span>
                        </a>
                        <a class="step-pill" href="{{ route('add-members.draft.address') }}">
                            <input type="radio" name="progress_step" aria-hidden="true">
                            <span>Address</span>
                        </a>
                        <a class="step-pill" href="{{ route('add-members.draft.beneficiaries') }}">
                            <input type="radio" name="progress_step" aria-hidden="true">
                            <span>Beneficiaries</span>
                        </a>
                        <a class="step-pill" href="{{ route('add-members.draft.enrollment') }}">
                            <input type="radio" name="progress_step" aria-hidden="true">
                            <span>Member Enrollment</span>
                        </a>
                    @else
                        <a class="step-pill is-current" href="#">
                            <input type="radio" name="progress_step" checked aria-hidden="true">
                            <span>Staff Info</span>
                        </a>
                        <a class="step-pill is-disabled" href="#">
                            <input type="radio" name="progress_step" aria-hidden="true">
                            <span>Member Details</span>
                        </a>
                        <a class="step-pill is-disabled" href="#">
                            <input type="radio" name="progress_step" aria-hidden="true">
                            <span>Address</span>
                        </a>
                        <a class="step-pill is-disabled" href="#">
                            <input type="radio" name="progress_step" aria-hidden="true">
                            <span>Beneficiaries</span>
                        </a>
                        <a class="step-pill {{ $assignmentId ? '' : 'is-disabled' }}" href="{{ $assignmentId ? route('add-members', ['assignment' => $assignmentId]) : '#' }}">
                            <input type="radio" name="progress_step" aria-hidden="true">
                            <span>Member Enrollment</span>
                        </a>
                    @endif
                </div>
                <div class="eyebrow">Add Members</div>
                <div class="hero-title hero-small">Staff information</div>


                @if (session('status'))
                    <div class="status">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="status status-error">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ $isDraft ? '#' : route('add-members.staff.store') }}" class="form-grid" id="staffForm">
                    @csrf
                    <input type="hidden" name="assignment_id" value="{{ $assignmentId }}">
                    <div>
                        <label for="unit_name">Unit Name</label>
                        <input type="text" id="unit_name" name="unit_name" value="{{ old('unit_name', $assignment->unit_name ?? '') }}" placeholder="Unit name" required>
                    </div>
                    <div>
                        <label for="agent_user_id">Agent</label>
                        <select id="agent_user_id" name="agent_user_id" required>
                            <option value="" disabled {{ old('agent_user_id', $assignment->agent_user_id ?? '') ? '' : 'selected' }}>Select agent</option>
                            @foreach ($agents ?? [] as $user)
                                <option value="{{ $user->id }}" {{ (string) old('agent_user_id', $assignment->agent_user_id ?? '') === (string) $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="manager_user_id">Unit Manager</label>
                        <select id="manager_user_id" name="manager_user_id" required>
                            <option value="" disabled {{ old('manager_user_id', $assignment->manager_user_id ?? '') ? '' : 'selected' }}>Select unit manager</option>
                            @foreach ($managers ?? [] as $user)
                                <option value="{{ $user->id }}" {{ (string) old('manager_user_id', $assignment->manager_user_id ?? '') === (string) $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="sales_associate">Sales Associate</label>
                        <input type="text" id="sales_associate" name="sales_associate" value="{{ old('sales_associate', $assignment->sales_associate ?? '') }}" placeholder="Sales associate" required>
                    </div>
                    <div>
                        <label for="staff_contact">Contact</label>
                        <input type="text" id="staff_contact" name="staff_contact" value="{{ old('staff_contact', $assignment->staff_contact ?? '') }}" placeholder="Contact number" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit">{{ $isDraft ? 'Next' : 'Save & next' }}</button>
                    </div>
                </form>
            </section>
        </main>

        @include('partials.footer')
    </div>

    <script>
        document.querySelectorAll('.progress-steps input[type="radio"]').forEach(radio => {
            radio.addEventListener('click', (e) => {
                e.preventDefault();
                const link = radio.closest('a');
                if (!link || link.classList.contains('is-disabled')) return;
                document.querySelectorAll('.progress-steps input[type="radio"]').forEach(r => r.checked = false);
                radio.checked = true;
                window.location.href = link.href;
            });
        });

        (() => {
            const isDraft = document.body.dataset.draft === "1";
            if (!isDraft) return;
            const DRAFT_KEY = "oneheart_member_draft_v1";
            const form = document.getElementById('staffForm');
            const readDraft = () => {
                try {
                    return JSON.parse(localStorage.getItem(DRAFT_KEY)) || {};
                } catch {
                    return {};
                }
            };
            const writeDraft = (data) => localStorage.setItem(DRAFT_KEY, JSON.stringify(data));
            const getFormValues = (node) => {
                const data = {};
                node?.querySelectorAll('input, select, textarea').forEach(el => {
                    if (!el.name || el.name.endsWith('[]')) return;
                    if (el.type === 'radio') {
                        if (el.checked) data[el.name] = el.value;
                        return;
                    }
                    if (el.type === 'checkbox') {
                        data[el.name] = el.checked ? (el.value || true) : '';
                        return;
                    }
                    data[el.name] = el.value;
                });
                return data;
            };
            const fillForm = () => {
                const staff = readDraft().staff || {};
                Object.entries(staff).forEach(([key, val]) => {
                    const el = form?.querySelector(`[name="${key}"]`);
                    if (el && val !== undefined && val !== null) el.value = val;
                });
            };
            const saveDraft = () => {
                const draft = readDraft();
                draft.staff = { ...(draft.staff || {}), ...getFormValues(form) };
                writeDraft(draft);
            };

            form?.addEventListener('submit', (e) => {
                e.preventDefault();
                saveDraft();
                window.location.href = "{{ route('add-members.draft.part2') }}";
            });

            document.querySelectorAll('.progress-steps a').forEach(link => {
                link.addEventListener('click', (e) => {
                    if (!link.href || link.href.endsWith('#')) return;
                    e.preventDefault();
                    saveDraft();
                    window.location.href = link.href;
                });
            });

            fillForm();
        })();
    </script>
</body>
</html>
