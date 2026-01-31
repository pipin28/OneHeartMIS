<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Members - Address | OneHeart Life Plan</title>
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
                @php
                    $beneficiaryId = $beneficiary->id ?? null;
                @endphp
                <div class="progress-steps">
                    <a class="step-pill is-disabled" href="#">
                        <input type="radio" name="progress_step" aria-hidden="true">
                        <span>Part 1</span>
                    </a>
                    <a class="step-pill is-disabled" href="#">
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
                </div>
                <div class="form-actions" style="justify-content: flex-start; margin-bottom: 8px;">
                    <a href="{{ route('add-members.part2', ['part1' => $part1Id]) . '?part2=' . $part2Id }}" class="button" style="box-shadow: none;">← Back to Member Details</a>
                </div>
                <div class="eyebrow">Add Members</div>
                <div class="hero-title hero-small">Residential address</div>
                <p class="hero-sub">Finalize enrollment with the member's address and identification details.</p>

                @if (session('status'))
                    <div class="status">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="status status-error">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('add-members.part2.address.store', ['part1' => $part1Id, 'part2' => $part2Id]) }}" class="form-grid">
                    @csrf
                    <input type="hidden" name="part1_id" value="{{ $part1Id }}">
                    <input type="hidden" name="part2_id" value="{{ $part2Id }}">

                    <div>
                        <label for="lot_house_numer">Lot/House Number</label>
                        <input type="text" id="lot_house_numer" name="lot_house_numer" value="{{ old('lot_house_numer', $address->lot_house_numer ?? '') }}" required>
                    </div>
                    <div>
                        <label for="street">Street</label>
                        <input type="text" id="street" name="street" value="{{ old('street', $address->street ?? '') }}" required>
                    </div>
                    <div>
                        <label for="barangay">Barangay</label>
                        <input type="text" id="barangay" name="barangay" value="{{ old('barangay', $address->barangay ?? '') }}" required>
                    </div>
                    <div>
                        <label for="province">Province</label>
                        <input type="text" id="province" name="province" value="{{ old('province', $address->province ?? '') }}" required>
                    </div>
                    <div>
                        <label for="zip_code">Zip Code</label>
                        <input type="text" id="zip_code" name="zip_code" value="{{ old('zip_code', $address->zip_code ?? '') }}" required>
                    </div>
                    <div>
                        <label for="contact_no">Contact No.</label>
                        <input type="text" id="contact_no" name="contact_no" value="{{ old('contact_no', $address->contact_no ?? '') }}" required>
                    </div>
                    <div>
                        <label for="sss_gsis_no">SSS/GSIS No.</label>
                        <input type="text" id="sss_gsis_no" name="sss_gsis_no" value="{{ old('sss_gsis_no', $address->sss_gsis_no ?? '') }}" required>
                    </div>
                    <div>
                        <label for="tin_no">TIN No.</label>
                        <input type="text" id="tin_no" name="tin_no" value="{{ old('tin_no', $address->tin_no ?? '') }}" required>
                    </div>
                    <div>
                        <label for="source_of_funds_if_not_imployed">Source of Funds (if not employed)</label>
                        <input type="text" id="source_of_funds_if_not_imployed" name="source_of_funds_if_not_imployed" value="{{ old('source_of_funds_if_not_imployed', $address->source_of_funds_if_not_imployed ?? '') }}" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit">Save address</button>
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
    </script>
</body>
</html>
