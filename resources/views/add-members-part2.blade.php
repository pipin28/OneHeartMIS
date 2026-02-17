<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('images/logo-oneheart.png') }}">
    <title>Add Members - Member Details | OneHeart Life Plan</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
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
                    $hasPart2 = !empty($part2?->id);
                    $addressId = $address->id ?? null;
                    $beneficiaryId = $beneficiary->id ?? null;
                    $assignmentId = $assignment->id ?? null;
                @endphp
                <div class="progress-steps">
                    @if ($isDraft)
                        <a class="step-pill" href="{{ route('add-members.draft.staff') }}">
                            <input type="radio" name="progress_step" aria-hidden="true">
                            <span>Staff Info</span>
                        </a>
                        <a class="step-pill" href="{{ route('add-members.draft.enrollment') }}">
                            <input type="radio" name="progress_step" aria-hidden="true">
                            <span>Member Enrollment</span>
                        </a>
                        <a class="step-pill is-current" href="#">
                            <input type="radio" name="progress_step" checked aria-hidden="true">
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
                    @else
                        <a class="step-pill {{ $assignmentId ? '' : 'is-disabled' }}" href="{{ $assignmentId ? route('add-members.staff', ['assignment' => $assignmentId]) : '#' }}">
                            <input type="radio" name="progress_step" aria-hidden="true">
                            <span>Staff Info</span>
                        </a>
                        <a class="step-pill" href="{{ route('add-members.edit', ['part1' => $part1Id]) }}">
                            <input type="radio" name="progress_step" aria-hidden="true">
                            <span>Member Enrollment</span>
                        </a>
                        <a class="step-pill is-current" href="#">
                            <input type="radio" name="progress_step" checked aria-hidden="true">
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
                    @endif
                </div>
                <div class="form-actions" style="justify-content: flex-start; margin-bottom: 8px;">
                    <a href="{{ $isDraft ? route('add-members.draft.enrollment') : route('add-members.edit', ['part1' => $part1Id]) }}" class="button" style="box-shadow: none;">Back to Member Enrollment</a>
                </div>
                <div class="eyebrow">Add Members</div>
                <div class="hero-title hero-small">Member Details</div>
                <p class="hero-sub">Continue enrollment by completing the personal and employment information.</p>

                @if (session('status'))
                    <div class="status">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="status status-error">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ $isDraft ? '#' : route('add-members.part2.store', $part1Id) }}" class="form-grid" id="memberDetailsForm">
                    @csrf
                    @if (! $isDraft)
                        <input type="hidden" name="part1_id" value="{{ $part1Id }}">
                        <input type="hidden" name="part2_id" value="{{ $part2->id ?? '' }}">
                    @endif
                    <div>
                        <label for="surname">Surname</label>
                        <input type="text" id="surname" name="surname" value="{{ old('surname', $part2->surname ?? '') }}" required>
                    </div>
                    <div>
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $part2->first_name ?? '') }}" required>
                    </div>
                    <div>
                        <label for="midle_name">Middle Name</label>
                        <input type="text" id="midle_name" name="midle_name" value="{{ old('midle_name', $part2->midle_name ?? '') }}">
                    </div>
                    <div>
                        <label for="place_of_birth">Place of Birth</label>
                        <input type="text" id="place_of_birth" name="place_of_birth" value="{{ old('place_of_birth', $part2->place_of_birth ?? '') }}" required>
                    </div>
                    <div>
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', $part2->date_of_birth ?? '') }}" required>
                    </div>
                    <div>
                        <label for="age">Age</label>
                        <input type="number" id="age" name="age" value="{{ old('age', $part2->age ?? '') }}" required readonly>
                    </div>
                    <div>
                        <label for="sex_at_birth">Sex at Birth</label>
                        <select id="sex_at_birth" name="sex_at_birth" required>
                            <option value="" disabled {{ old('sex_at_birth', $part2->sex_at_birth ?? '') === null ? 'selected' : '' }}>Select</option>
                            @foreach (['Male', 'Female'] as $sex)
                                <option value="{{ $sex }}" {{ old('sex_at_birth', $part2->sex_at_birth ?? '') === $sex ? 'selected' : '' }}>{{ $sex }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="civil_status">Civil Status</label>
                        <select id="civil_status" name="civil_status" required>
                            <option value="" disabled {{ old('civil_status', $part2->civil_status ?? '') === null ? 'selected' : '' }}>Select</option>
                            @foreach (['Single','Married','Widowed','Separated','Divorced','Annulled','Domestic Partnership','Common-law'] as $status)
                                <option value="{{ $status }}" {{ old('civil_status', $part2->civil_status ?? '') === $status ? 'selected' : '' }}>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="cellular_no">Cellular No.</label>
                        <input type="text" id="cellular_no" name="cellular_no" value="{{ old('cellular_no', $part2->cellular_no ?? '') }}" required>
                    </div>
                    <div>
                        <label for="email_address">Email Address</label>
                        <input type="email" id="email_address" name="email_address" value="{{ old('email_address', $part2->email_address ?? '') }}" required>
                    </div>
                    <div>
                        <label for="nationality">Nationality</label>
                        <input type="text" id="nationality" name="nationality" value="{{ old('nationality', $part2->nationality ?? '') }}" required>
                    </div>
                    <div>
                        <label for="institution_name">Institution Name</label>
                        <input type="text" id="institution_name" name="institution_name" value="{{ old('institution_name', $part2->institution_name ?? '') }}" required>
                    </div>
                    <div>
                        <label for="institution_no">Institution No.</label>
                        <input type="number" id="institution_no" name="institution_no" value="{{ old('institution_no', $part2->institution_no ?? '') }}" required>
                    </div>
                    <div>
                        <label for="occupation">Occupation</label>
                        <input type="text" id="occupation" name="occupation" value="{{ old('occupation', $part2->occupation ?? '') }}" required>
                    </div>
                    <div>
                        <label for="name_of_employer">Name of Employer</label>
                        <input type="text" id="name_of_employer" name="name_of_employer" value="{{ old('name_of_employer', $part2->name_of_employer ?? '') }}" required>
                    </div>
                    <div>
                        <label for="office_address">Office Address</label>
                        <input type="text" id="office_address" name="office_address" value="{{ old('office_address', $part2->office_address ?? '') }}" required>
                    </div>
                    <div>
                        <label for="office_no">Office No.</label>
                        <input type="number" id="office_no" name="office_no" value="{{ old('office_no', $part2->office_no ?? '') }}" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit">{{ $isDraft ? 'Next' : 'Save member details' }}</button>
                    </div>
                </form>
            </section>
        </main>

        @include('partials.footer')
    </div>

    <div class="status-modal {{ session('status') ? 'is-visible' : '' }}" id="statusModal" data-message="{{ session('status') }}">
        <div class="status-card">
            <div class="status-title">Success</div>
            <p class="status-body">{{ session('status') }}</p>
            <button type="button" class="status-close" aria-label="Close">Close</button>
        </div>
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
            const modal = document.getElementById('statusModal');
            const closeBtn = modal?.querySelector('.status-close');
            const closeModal = () => modal?.classList.remove('is-visible');
            if (modal && modal.dataset.message) {
                modal.classList.add('is-visible');
                closeBtn?.addEventListener('click', closeModal);
                modal?.addEventListener('click', (e) => {
                    if (e.target === modal) closeModal();
                });
            } else {
                modal?.classList.remove('is-visible');
            }
        })();
        (() => {
            const dob = document.getElementById('date_of_birth');
            const age = document.getElementById('age');
            const computeAge = (val) => {
                if (!val) return '';
                const today = new Date();
                const birth = new Date(val);
                let years = today.getFullYear() - birth.getFullYear();
                const m = today.getMonth() - birth.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
                    years--;
                }
                return years >= 0 ? years : '';
            };
            dob?.addEventListener('change', () => {
                const v = computeAge(dob.value);
                if (age) age.value = v;
            });
            // initial fill on load
            if (dob?.value && age) {
                const v = computeAge(dob.value);
                if (!age.value) age.value = v;
            }
        })();
        (() => {
            const isDraft = document.body.dataset.draft === "1";
            if (!isDraft) return;
            const DRAFT_KEY = "oneheart_member_draft_v1";
            const form = document.getElementById('memberDetailsForm');
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
                const member = readDraft().member || {};
                Object.entries(member).forEach(([key, val]) => {
                    const el = form?.querySelector(`[name="${key}"]`);
                    if (el && val !== undefined && val !== null) el.value = val;
                });
            };
            const saveDraft = () => {
                const draft = readDraft();
                draft.member = { ...(draft.member || {}), ...getFormValues(form) };
                writeDraft(draft);
            };

            form?.addEventListener('submit', (e) => {
                e.preventDefault();
                saveDraft();
                window.location.href = "{{ route('add-members.draft.address') }}";
            });

            document.querySelectorAll('.progress-steps a, .form-actions a').forEach(link => {
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

