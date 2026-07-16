<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ $appBrandLogoUrl }}">
    <title>Add Members - Address | {{ $appBrandName }}</title>
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
                    $beneficiaryId = $beneficiary->id ?? null;
                    $assignmentId = $assignment->id ?? null;
                @endphp
                <div class="progress-steps">
                    @if ($isDraft)
                        <a class="step-pill" href="{{ route('add-members.draft.staff') }}">
                            <input type="radio" name="progress_step" aria-hidden="true">
                            <span>Staff Info</span>
                        </a>
                        <a class="step-pill" href="{{ route('add-members.draft.part2') }}">
                            <input type="radio" name="progress_step" aria-hidden="true">
                            <span>Member Details</span>
                        </a>
                        <a class="step-pill is-current" href="#">
                            <input type="radio" name="progress_step" checked aria-hidden="true">
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
                        <a class="step-pill {{ $assignmentId ? '' : 'is-disabled' }}" href="{{ $assignmentId ? route('add-members.staff', ['assignment' => $assignmentId]) : '#' }}">
                            <input type="radio" name="progress_step" aria-hidden="true">
                            <span>Staff Info</span>
                        </a>
                        <a class="step-pill" href="{{ route('add-members.part2', ['part1' => $part1Id]) . '?part2=' . $part2Id }}">
                            <input type="radio" name="progress_step" aria-hidden="true">
                            <span>Member Details</span>
                        </a>
                        <a class="step-pill is-current" href="#">
                            <input type="radio" name="progress_step" checked aria-hidden="true">
                            <span>Address</span>
                        </a>
                        <a class="step-pill is-disabled" href="#">
                            <input type="radio" name="progress_step" aria-hidden="true">
                            <span>Beneficiaries</span>
                        </a>
                        <a class="step-pill" href="{{ route('add-members.edit', ['part1' => $part1Id]) }}">
                            <input type="radio" name="progress_step" aria-hidden="true">
                            <span>Member Enrollment</span>
                        </a>
                    @endif
                </div>
                <div class="form-actions" style="justify-content: flex-start; margin-bottom: 8px;">
                    <a href="{{ $isDraft ? route('add-members.draft.part2') : route('add-members.part2', ['part1' => $part1Id]) . '?part2=' . $part2Id }}" class="button" style="box-shadow: none;">Back to Member Details</a>
                </div>
                <div class="eyebrow">Add Members</div>
                <div class="hero-title hero-small">Residential address</div>
               

                @if (session('status'))
                    <div class="status">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="status status-error">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ $isDraft ? '#' : route('add-members.part2.address.store', ['part1' => $part1Id, 'part2' => $part2Id]) }}" class="form-grid" id="addressForm">
                    @csrf
                    @if (! $isDraft)
                        <input type="hidden" name="part1_id" value="{{ $part1Id }}">
                        <input type="hidden" name="part2_id" value="{{ $part2Id }}">
                    @endif

                    <div>
                        <label for="complete_address">Complete Address</label>
                        <input type="text" id="complete_address" name="complete_address" value="{{ old('complete_address', $address->complete_address ?? '') }}" required>
                    </div>
                    <div>
                        <label for="contact_no">Contact No.</label>
                        <input type="text" id="contact_no" name="contact_no" value="{{ old('contact_no', $address->contact_no ?? '') }}" required>
                    </div>
                    <div>
                        <label for="religion">Religion</label>
                        <input type="text" id="religion" name="religion" value="{{ old('religion', $address->religion ?? '') }}" required>
                    </div>
                    <div>
                        <label for="occupation_livelihood">Occupation/Livelihood</label>
                        <input type="text" id="occupation_livelihood" name="occupation_livelihood" value="{{ old('occupation_livelihood', $address->occupation_livelihood ?? '') }}" required>
                    </div>
                    <div>
                        <label for="valid_id">Valid ID</label>
                        <input type="text" id="valid_id" name="valid_id" value="{{ old('valid_id', $address->valid_id ?? '') }}" required>
                    </div>
                    <div>
                        <label for="valid_id_no">Valid ID #</label>
                        <input type="text" id="valid_id_no" name="valid_id_no" value="{{ old('valid_id_no', $address->valid_id_no ?? '') }}" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit">{{ $isDraft ? 'Next' : 'Save address' }}</button>
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
            const form = document.getElementById('addressForm');
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
                const address = readDraft().address || {};
                Object.entries(address).forEach(([key, val]) => {
                    const el = form?.querySelector(`[name="${key}"]`);
                    if (el && val !== undefined && val !== null) el.value = val;
                });
            };
            const saveDraft = () => {
                const draft = readDraft();
                draft.address = { ...(draft.address || {}), ...getFormValues(form) };
                writeDraft(draft);
            };

            form?.addEventListener('submit', (e) => {
                e.preventDefault();
                saveDraft();
                window.location.href = "{{ route('add-members.draft.beneficiaries') }}";
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

