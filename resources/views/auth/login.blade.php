<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | OneHeart Life Plan</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="auth-smoke {{ $errors->any() ? 'has-auth-error' : '' }}">
    <div class="frame frame-auth">
        <div class="grid">
            <div class="panel form-panel">
                <div class="pill">Secure Access</div>
                <h2 class="hero-title hero-small">Welcome back, strategist.</h2>
                @if ($errors->any())
                    <p class="foot error-message">{{ $errors->first() }}</p>
                @endif
                @if (session('status'))
                    <p class="foot status-message">{{ session('status') }}</p>
                @endif
                <form method="POST" action="{{ route('login.submit') }}">
                    @csrf
                    <div>
                        <label for="username">Username</label>
                        <input id="username" type="text" name="username" placeholder="your.username" required value="{{ old('username') }}">
                    </div>
                    <div>
                        <label for="password">Password</label>
                        <input id="password" type="password" name="password" placeholder="********" required>
                    </div>
                    <div class="actions">
                        <label class="remember">
                            <input type="checkbox" name="remember" class="checkbox-accent">
                            Remember my focus
                        </label>
                        <a href="#" class="link-accent link-small">Forgot?</a>
                    </div>
                    <button type="submit">Sign in and continue</button>
                </form>
                <p class="foot">No account yet? <a class="link-accent" href="{{ route('register') }}">Create one here</a></p>
            </div>
        </div>
    </div>
</body>
</html>
