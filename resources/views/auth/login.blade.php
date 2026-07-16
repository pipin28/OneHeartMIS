<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ $appBrandLogoUrl }}">
    <title>Login | {{ $appBrandName }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/app.css') . '?v=' . filemtime(public_path('css/app.css')) }}">
    <style>
        .login-logo {
            max-width: 180px;
            height: auto;
        }

        .login-logo-title {
            margin-top: 10px;
            font-weight: 900;
            letter-spacing: 0.16em;
            font-size: 13px;
            text-transform: uppercase;
            background: linear-gradient(90deg, #f59e0b 0%, #ef4444 48%, #16a34a 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
        }

        .password-wrap {
            position: relative;
        }

        .password-wrap input {
            padding-right: 42px;
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #5b6470;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 2px;
            line-height: 1;
        }

        .password-toggle:hover {
            color: #1f2937;
        }

        .password-toggle svg {
            width: 18px;
            height: 18px;
        }

    </style>
</head>
<body class="auth-smoke {{ $errors->any() ? 'has-auth-error' : '' }}">
    <div class="frame frame-auth">
        <div class="grid">
            <div class="panel form-panel">
                <div style="text-align: center; margin-bottom: 16px;">
                    <img src="{{ $appBrandLogoUrl }}" alt="{{ $appBrandName }} logo" class="login-logo">
                    <div class="login-logo-title">{{ strtoupper($appBrandName) }}</div>
                </div>
                <form method="POST" action="{{ route('login.submit') }}">
                    @csrf
                    <div>
                        <label for="username">Username</label>
                        <input id="username" type="text" name="username" placeholder="your.username" required value="{{ old('username') }}">
                    </div>
                    <div>
                        <label for="password">Password</label>
                        <div class="password-wrap">
                            <input id="password" type="password" name="password" placeholder="********" required>
                            <button type="button" id="togglePassword" class="password-toggle" aria-label="Show password" aria-pressed="false">
                                <svg id="eyeOpen" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg id="eyeClosed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:none;">
                                    <path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a21.86 21.86 0 0 1 5.06-5.94"></path>
                                    <path d="M1 1l22 22"></path>
                                    <path d="M9.53 9.53A3 3 0 0 0 14.47 14.47"></path>
                                    <path d="M14.12 5.88A10.94 10.94 0 0 1 23 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="actions">
                        <label class="remember">
                            <input type="checkbox" name="remember" class="checkbox-accent">
                            Remember me
                        </label>
                        <a href="#" class="link-accent link-small">Forgot?</a>
                    </div>
                    <button type="submit">Sign in and continue</button>
                </form>
               
        </div>
    </div>
    <script>
        (() => {
            const passwordField = document.getElementById('password');
            const toggle = document.getElementById('togglePassword');
            const eyeOpen = document.getElementById('eyeOpen');
            const eyeClosed = document.getElementById('eyeClosed');
            if (!passwordField || !toggle) return;

            toggle.addEventListener('click', () => {
                const showing = passwordField.type === 'text';
                passwordField.type = showing ? 'password' : 'text';
                toggle.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
                toggle.setAttribute('aria-pressed', showing ? 'false' : 'true');
                if (eyeOpen && eyeClosed) {
                    eyeOpen.style.display = showing ? '' : 'none';
                    eyeClosed.style.display = showing ? 'none' : '';
                }
            });
        })();
    </script>
</body>
</html>

