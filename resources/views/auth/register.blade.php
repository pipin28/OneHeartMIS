<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ $appBrandLogoUrl }}">
    <title>Register | {{ $appBrandName }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/app.css') . '?v=' . filemtime(public_path('css/app.css')) }}">
</head>
<body class="auth-smoke">
    <div class="frame frame-auth">
        <div class="grid">
            <div class="panel form-panel">
                <div class="pill">Create access</div>
                <h2 class="hero-title hero-small">Join the OneHeart mission.</h2>
                @if ($errors->any())
                    <div class="foot error-message">
                        {{ $errors->first() }}
                    </div>
                @endif
                <form method="POST" action="{{ route('register.submit') }}">
                    @csrf
                    <div>
                        <label for="name">Full name</label>
                        <input id="name" type="text" name="name" placeholder="Alex Rivera" required value="{{ old('name') }}">
                    </div>
                    <div>
                        <label for="username">Username</label>
                        <input id="username" type="text" name="username" placeholder="your.username" required value="{{ old('username') }}">
                    </div>
                    <div>
                        <label for="role">Role</label>
                        <select id="role" name="role" required>
                            <option value="" disabled {{ old('role') ? '' : 'selected' }}>Select role</option>
                            @foreach (['encoder' => 'Encoder', 'admin' => 'Admin', 'agent' => 'Agent', 'manager' => 'Manager'] as $value => $label)
                                <option value="{{ $value }}" {{ old('role') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="password">Password</label>
                        <input id="password" type="password" name="password" placeholder="********" required>
                    </div>
                    <div>
                        <label for="password_confirmation">Confirm password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" placeholder="********" required>
                    </div>
                    <button type="submit">Create account</button>
                    <p class="foot">Already have an account? <a class="link-accent" href="{{ route('login') }}">Sign in</a></p>
                </form>
            </div>
        </div>
    </div>
    <div class="status-modal {{ session('status') ? 'is-visible' : '' }}" id="registerSuccessModal" data-message="{{ session('status') }}">
        <div class="status-card">
            <div class="status-title">Registered successfully!</div>
            <p class="status-body">{{ session('status') }}</p>
            <button type="button" class="status-close" aria-label="Close">Close</button>
        </div>
    </div>
    <script>
        (() => {
            const modal = document.getElementById('registerSuccessModal');
            const closeBtn = modal?.querySelector('.status-close');
            const closeModal = () => modal?.classList.remove('is-visible');

            if (modal && modal.dataset.message) {
                modal.classList.add('is-visible');
                closeBtn?.addEventListener('click', closeModal);
                modal.addEventListener('click', (event) => {
                    if (event.target === modal) closeModal();
                });
            } else {
                modal?.classList.remove('is-visible');
            }
        })();
    </script>
</body>
</html>

