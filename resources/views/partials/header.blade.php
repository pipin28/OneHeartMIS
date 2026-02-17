<header class="site-header">
    <div class="brand">OneHeart Life Plan</div>

    @include('partials.nav')

    @php
        $role = auth()->user()->role ?? null;
        $canOpenSettings = in_array($role, ['admin', 'manager'], true);
        $canOpenRegister = $role === 'manager';
    @endphp

    <nav class="nav-links">
        <div class="profile-menu" id="profileMenu">
            <button type="button" class="profile-trigger" id="profileMenuTrigger" aria-expanded="false" aria-haspopup="true" aria-label="Open profile menu">
                <span class="profile-name">{{ auth()->user()->name ?? 'Account' }}</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m6 9 6 6 6-6"></path>
                </svg>
            </button>
            <div class="profile-dropdown" id="profileDropdown" aria-hidden="true">
                <a href="{{ route('profile') }}" class="profile-dropdown-item">Profile</a>
                @if ($canOpenSettings)
                    <a href="{{ route('settings') }}" class="profile-dropdown-item">Settings</a>
                @endif
                @if ($canOpenRegister)
                    <a href="{{ route('register') }}" class="profile-dropdown-item">Register</a>
                @endif
                <button type="button" class="profile-dropdown-item profile-dropdown-logout" id="openLogoutModal">Logout</button>
            </div>
        </div>
    </nav>
</header>

<div class="modal-overlay" id="logoutConfirmModal" aria-hidden="true">
    <div class="modal-card modal-card-narrow">
        <div class="modal-head">
            <div>
                <div class="modal-title">Logout</div>
                <div class="modal-subtitle">Are you sure you want to logout?</div>
            </div>
            <button type="button" class="modal-close" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
            <p class="modal-copy">You will be returned to the login page.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="button is-ghost" data-logout-cancel>Cancel</button>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="button is-danger" data-logout-confirm>Logout</button>
            </form>
        </div>
    </div>
</div>

<script>
    (() => {
        const menu = document.getElementById('profileMenu');
        const trigger = document.getElementById('profileMenuTrigger');
        const dropdown = document.getElementById('profileDropdown');
        if (!menu || !trigger || !dropdown) return;

        const openMenu = () => {
            menu.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
            dropdown.setAttribute('aria-hidden', 'false');
        };
        const closeMenu = () => {
            menu.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
            dropdown.setAttribute('aria-hidden', 'true');
        };

        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            if (menu.classList.contains('is-open')) {
                closeMenu();
            } else {
                openMenu();
            }
        });

        document.addEventListener('click', (e) => {
            if (!menu.contains(e.target)) {
                closeMenu();
            }
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeMenu();
        });
    })();
</script>

<script>
    (() => {
        const trigger = document.getElementById('openLogoutModal');
        const modal = document.getElementById('logoutConfirmModal');
        if (!trigger || !modal) return;
        const closeBtn = modal.querySelector('.modal-close');
        const cancelBtn = modal.querySelector('[data-logout-cancel]');
        const open = () => modal.classList.add('is-visible');
        const close = () => modal.classList.remove('is-visible');

        trigger.addEventListener('click', open);
        closeBtn?.addEventListener('click', close);
        cancelBtn?.addEventListener('click', close);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) close();
        });
    })();
</script>

<script>
    (() => {
        const pingUrl = "{{ route('activity.ping') }}";
        const pingIntervalMs = 30000;
        const activityWindowMs = 30000;
        let lastActivityAt = Date.now();
        let lastPingAt = 0;

        const ping = async () => {
            const now = Date.now();
            if (now - lastPingAt < pingIntervalMs) return;
            lastPingAt = now;

            try {
                const res = await fetch(pingUrl, { credentials: 'same-origin' });
                if (res.status === 401) {
                    window.location.href = "{{ route('login') }}";
                }
            } catch (err) {
                // Ignore network errors; next activity will retry.
            }
        };

        const onActivity = () => {
            lastActivityAt = Date.now();
            ping();
        };

        ['click', 'keydown', 'mousemove', 'scroll', 'touchstart', 'input'].forEach((eventName) => {
            window.addEventListener(eventName, onActivity, { passive: true });
        });

        setInterval(() => {
            const now = Date.now();
            if (now - lastActivityAt <= activityWindowMs) {
                ping();
            }
        }, pingIntervalMs);
    })();
</script>
