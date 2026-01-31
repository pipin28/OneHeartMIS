<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Members - Beneficiaries | OneHeart Life Plan</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/partials/nav.css') . '?v=' . filemtime(public_path('css/partials/nav.css')) }}">
    <style>
        .beneficiaries-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(220px, 1fr));
            gap: 16px;
            align-items: start;
            justify-items: stretch;
        }
        .beneficiary-card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 14px;
            background: #fff;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
            display: grid;
            gap: 10px;
            min-width: 240px;
        }
        .beneficiary-card label {
            display: block;
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 4px;
            color: #1f2937;
        }
        .beneficiary-card input,
        .beneficiary-card select {
            width: 100%;
        }
        .beneficiary-card .form-actions {
            padding-top: 6px;
        }
        .beneficiaries-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            align-items: center;
        }
        .saved-beneficiaries {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }
        .saved-beneficiaries .beneficiary-card {
            box-shadow: none;
        }
    </style>
</head>
<body class="has-shell">
    <div class="page">
        @include('partials.header')

        <main class="dashboard">
            <section class="wrap">
                <div class="progress-steps">
                    <a class="step-pill is-disabled" href="#">
                        <input type="radio" name="progress_step" aria-hidden="true">
                        <span>Member Enrollment</span>
                    </a>
                    <a class="step-pill is-disabled" href="#">
                        <input type="radio" name="progress_step" aria-hidden="true">
                        <span>Member Details</span>
                    </a>
                    <a class="step-pill is-disabled" href="#">
                        <input type="radio" name="progress_step" aria-hidden="true">
                        <span>Address</span>
                    </a>
                    <a class="step-pill is-current" href="#">
                        <input type="radio" name="progress_step" checked aria-hidden="true">
                        <span>Beneficiaries</span>
                    </a>
                </div>
                <div class="form-actions" style="justify-content: flex-start; margin-bottom: 8px;">
                    <a href="{{ route('add-members.part2.address', ['part1' => $part1Id, 'part2' => $part2Id]) . ($addressId ? '?address='.$addressId : '') }}" class="button" style="box-shadow: none;">? Back to Address</a>
                </div>
                <div class="eyebrow">Add Members</div>
                <div class="hero-title hero-small">Beneficiaries</div>
                <p class="hero-sub">Finish enrollment by adding the beneficiary information.</p>

                @if ($errors->any())
                    <div class="status status-error">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('add-members.part2.beneficiaries.store', ['part1' => $part1Id, 'part2' => $part2Id]) }}" class="form-grid" id="beneficiariesForm">
                    @csrf
                    <input type="hidden" name="part1_id" value="{{ $part1Id }}">
                    <input type="hidden" name="part2_id" value="{{ $part2Id }}">
                    <input type="hidden" name="par2_residential_address_id" value="{{ $addressId }}">

                    <div class="beneficiaries-grid" id="beneficiariesGrid">
                        @php
                            $oldNames = old('name');
                            if (!is_array($oldNames)) {
                                $oldNames = $oldNames ? [$oldNames] : [];
                            }
                            $oldBeneficiaries = collect($oldNames)->map(function ($_, $index) {
                                return [
                                    'type' => old("type.$index"),
                                    'name' => old("name.$index"),
                                    'age' => old("age.$index"),
                                    'address' => old("address.$index"),
                                    'relationship_to_planholder' => old("relationship_to_planholder.$index"),
                                ];
                            })->filter(fn($row) => !empty(array_filter($row)));
                            // Always start with a blank row unless re-populating validation errors
                            $rows = $oldBeneficiaries->isNotEmpty() ? $oldBeneficiaries : [[
                                'type' => '',
                                'name' => '',
                                'age' => '',
                                'address' => '',
                                'relationship_to_planholder' => '',
                            ]];
                        @endphp

                        @foreach ($rows as $index => $row)
                            <div class="beneficiary-card" data-beneficiary-row>
                                <div>
                                    <label>Type</label>
                                    <select name="type[]" required>
                                        @php $typeValue = $row['type'] ?? $row->type ?? '' @endphp
                                        <option value="" disabled {{ $typeValue === '' ? 'selected' : '' }}>Select type</option>
                                        <option value="primary beneficiaries" {{ $typeValue === 'primary beneficiaries' ? 'selected' : '' }}>Primary beneficiaries</option>
                                        <option value="contingent beneficiaries" {{ $typeValue === 'contingent beneficiaries' ? 'selected' : '' }}>Contingent beneficiaries</option>
                                    </select>
                                </div>
                                <div>
                                    <label>Name</label>
                                    <input type="text" name="name[]" value="{{ $row['name'] ?? $row->name ?? '' }}" required>
                                </div>
                                <div>
                                    <label>Age</label>
                                    <input type="number" name="age[]" value="{{ $row['age'] ?? $row->age ?? '' }}" required>
                                </div>
                                <div>
                                    <label>Address</label>
                                    <input type="text" name="address[]" value="{{ $row['address'] ?? $row->address ?? '' }}" required>
                                </div>
                                <div>
                                    <label>Relationship to Planholder</label>
                                    <input type="text" name="relationship_to_planholder[]" value="{{ $row['relationship_to_planholder'] ?? $row->relationship_to_planholder ?? '' }}" required>
                                </div>
                                <div class="form-actions" style="justify-content: flex-end; padding: 0;">
                                    <button type="button" class="button is-ghost remove-beneficiary">Remove</button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="beneficiaries-actions">
                        <button type="button" class="button is-ghost" id="addBeneficiary">+ Add another beneficiary</button>
                        <button type="submit">Save beneficiaries</button>
                    </div>

                    @if (!empty($beneficiaries) && count($beneficiaries))
                        <div class="data-table" style="grid-column: 1 / -1;">
                            <div class="hero-title hero-small">Saved beneficiaries</div>
                            <div class="saved-beneficiaries">
                                @foreach ($beneficiaries as $bene)
                                    <div class="beneficiary-card">
                                        <div class="eyebrow" style="margin-bottom: 6px;">{{ $bene->type ?? '-' }}</div>
                                        <div class="hero-sub" style="margin: 0 0 8px;">{{ $bene->name ?? '-' }}</div>
                                        <ul class="modal-list" style="list-style: none; padding: 0; margin: 0; display: grid; gap: 4px;">
                                            <li><span>Age</span> <strong>{{ $bene->age ?? '-' }}</strong></li>
                                            <li><span>Address</span> <strong>{{ $bene->address ?? '-' }}</strong></li>
                                            <li><span>Relationship</span> <strong>{{ $bene->relationship_to_planholder ?? '-' }}</strong></li>
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
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
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) closeModal();
                });
            } else {
                modal?.classList.remove('is-visible');
            }
        })();

        (() => {
            const grid = document.getElementById('beneficiariesGrid');
            const addBtn = document.getElementById('addBeneficiary');
            const template = () => {
                const wrapper = document.createElement('div');
                wrapper.className = 'beneficiary-card';
                wrapper.style.border = '1px solid #e5e7eb';
                wrapper.style.borderRadius = '10px';
                wrapper.style.padding = '12px';
                wrapper.style.background = '#fff';
                wrapper.style.minWidth = '220px';
                wrapper.style.width = '100%';
                wrapper.setAttribute('data-beneficiary-row', '');
                wrapper.innerHTML = `
                    <div>
                        <label>Type</label>
                        <select name="type[]" required>
                            <option value="" disabled selected>Select type</option>
                            <option value="primary beneficiaries">Primary beneficiaries</option>
                            <option value="contingent beneficiaries">Contingent beneficiaries</option>
                        </select>
                    </div>
                    <div>
                        <label>Name</label>
                        <input type="text" name="name[]" required>
                    </div>
                    <div>
                        <label>Age</label>
                        <input type="number" name="age[]" required>
                    </div>
                    <div>
                        <label>Address</label>
                        <input type="text" name="address[]" required>
                    </div>
                    <div>
                        <label>Relationship to Planholder</label>
                        <input type="text" name="relationship_to_planholder[]" required>
                    </div>
                    <div class="form-actions" style="justify-content: flex-end;">
                        <button type="button" class="button is-ghost remove-beneficiary">Remove</button>
                    </div>
                `;
                return wrapper;
            };

            const bindRemove = (container) => {
                container.querySelectorAll('.remove-beneficiary').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const rows = grid.querySelectorAll('[data-beneficiary-row]');
                        if (rows.length <= 1) return;
                        btn.closest('[data-beneficiary-row]')?.remove();
                    });
                });
            };

            addBtn?.addEventListener('click', () => {
                const node = template();
                grid?.appendChild(node);
                bindRemove(node);
            });

            if (grid) bindRemove(grid);
        })();
    </script>
</body>
</html>

