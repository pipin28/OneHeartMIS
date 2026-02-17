<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('images/logo-oneheart.png') }}">
    <title>Users | OneHeart Life Plan</title>
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
                <div class="eyebrow">Users</div>
                <div class="hero-title hero-small">User directory</div>
                <p class="hero-sub">Manage accounts, roles, and permissions here.</p>

                <div class="form-actions form-actions-split">
                    <div></div>
                    <a href="{{ route('register') }}" class="button is-primary">Register user</a>
                </div>

                @if (session('status'))
                    <div class="status">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="status status-error">{{ $errors->first() }}</div>
                @endif

                <div class="card">
                    <div class="card-header table-toolbar">
                        <div>
                            <div class="card-title">Users</div>
                            <div class="card-subtitle">Latest registrations first</div>
                        </div>
                    </div>

                    @if ($users->isEmpty())
                        <div class="empty-state">
                            <div class="empty-title">No users yet</div>
                            <p class="empty-body">Create a user to see them listed here.</p>
                        </div>
                    @else
                        <div class="table-scroll">
                            <table class="data-table modern compact">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Username</th>
                                        <th>Role</th>
                                        <th>Created</th>
                                        <th class="text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($users as $user)
                                        <tr>
                                            <td class="table-col-primary">{{ $user->name }}</td>
                                            <td>{{ $user->username }}</td>
                                            <td>{{ $user->role }}</td>
                                            <td>{{ $user->created_at ? \Carbon\Carbon::parse($user->created_at)->format('M d, Y') : '-' }}</td>
                                            <td class="table-action">
                                                <button
                                                    type="button"
                                                    class="button is-warning user-edit-trigger"
                                                    data-id="{{ $user->id }}"
                                                    data-name="{{ $user->name }}"
                                                    data-username="{{ $user->username }}"
                                                    data-role="{{ $user->role }}"
                                                >Update</button>
                                                <form method="POST" action="{{ route('users.delete', $user) }}" class="inline-form" data-delete-form>
                                                    @csrf
                                                    <button type="submit" class="button is-danger">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>
        </main>

        @include('partials.footer')
    </div>

    <div class="modal-overlay" id="userEditModal" aria-hidden="true">
        <div class="modal-card modal-card-narrow">
            <div class="modal-head">
                <div>
                    <div class="modal-title">Update user</div>
                    <div class="modal-subtitle" id="userEditSubtitle">Edit user details.</div>
                </div>
                <button type="button" class="modal-close" aria-label="Close">&times;</button>
            </div>
            <form method="POST" action="{{ route('users.update', ['user' => 0]) }}" class="form-grid" id="userEditForm">
                @csrf
                <div>
                    <label for="edit_name">Full name</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>
                <div>
                    <label for="edit_username">Username</label>
                    <input type="text" id="edit_username" name="username" required>
                </div>
                <div>
                    <label for="edit_role">Role</label>
                    <select id="edit_role" name="role" required>
                        @foreach (['encoder' => 'Encoder', 'admin' => 'Admin', 'collector' => 'Collector', 'agent' => 'Agent', 'manager' => 'Manager'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="edit_password">New password (optional)</label>
                    <input type="password" id="edit_password" name="password" placeholder="********">
                </div>
                <div>
                    <label for="edit_password_confirmation">Confirm password</label>
                    <input type="password" id="edit_password_confirmation" name="password_confirmation" placeholder="********">
                </div>
                <div class="form-actions">
                    <button type="submit" class="button is-warning">Update user</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (() => {
            const modal = document.getElementById('userEditModal');
            const form = document.getElementById('userEditForm');
            const closeBtn = modal?.querySelector('.modal-close');
            const subtitle = document.getElementById('userEditSubtitle');
            const nameInput = document.getElementById('edit_name');
            const usernameInput = document.getElementById('edit_username');
            const roleSelect = document.getElementById('edit_role');
            const passwordInput = document.getElementById('edit_password');
            const passwordConfirmInput = document.getElementById('edit_password_confirmation');
            const triggers = document.querySelectorAll('.user-edit-trigger');

            if (!modal || !form) return;

            const open = () => modal.classList.add('is-visible');
            const close = () => modal.classList.remove('is-visible');

            triggers.forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.dataset.id;
                    const name = btn.dataset.name;
                    const username = btn.dataset.username;
                    const role = btn.dataset.role;
                    form.action = `{{ url('/users') }}/${id}/update`;
                    if (subtitle) subtitle.textContent = `Editing ${name}`;
                    if (nameInput) nameInput.value = name || '';
                    if (usernameInput) usernameInput.value = username || '';
                    if (roleSelect) roleSelect.value = role || 'user';
                    if (passwordInput) passwordInput.value = '';
                    if (passwordConfirmInput) passwordConfirmInput.value = '';
                    open();
                });
            });

            closeBtn?.addEventListener('click', close);
            modal?.addEventListener('click', (e) => {
                if (e.target === modal) close();
            });
        })();

        (() => {
            document.querySelectorAll('[data-delete-form]').forEach(form => {
                form.addEventListener('submit', (e) => {
                    if (!confirm('Are you sure you want to delete this user?')) {
                        e.preventDefault();
                    }
                });
            });
        })();
    </script>
</body>
</html>

