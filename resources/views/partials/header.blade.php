<header class="site-header">
    <div class="brand">OneHeart Life Plan</div>

    @include('partials.nav')

    <nav class="nav-links">
        <button type="button" class="logout-icon" aria-label="Logout" id="openLogoutModal">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 2v10"></path>
                <path d="M5.5 6.5a8 8 0 1 0 13 0"></path>
            </svg>
        </button>
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
            <a href="{{ route('login') }}" class="button is-danger" data-logout-confirm>Logout</a>
        </div>
    </div>
</div>

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
