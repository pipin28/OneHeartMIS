<nav class="nav-bar">
    <button class="nav-btn nav-toggle" type="button" aria-expanded="true" aria-label="Toggle navigation">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="5" y="6" width="14" height="2" rx="0.5"/>
            <rect x="5" y="11" width="14" height="2" rx="0.5"/>
            <rect x="5" y="16" width="14" height="2" rx="0.5"/>
        </svg>
    </button>
    @php
        $role = auth()->user()->role ?? null;
        $isEncoder = $role === 'encoder';
        $isAdmin = $role === 'admin';
        $isCollector = $role === 'collector';
        $isAgent = $role === 'agent';
        $isManager = $role === 'manager';
    @endphp
    <div class="nav-items">
        <div class="nav-highlight" aria-hidden="true"></div>
        <a href="{{ route('dashboard') }}" class="nav-btn {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5V21a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>
            HOME
        </a>
        @if ($isAdmin)
            <a href="{{ route('add-members.draft.staff') }}" class="nav-btn {{ request()->is('add-members*') ? 'is-active' : '' }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z"/></svg>
                ADD MEMBERS
            </a>
            <a href="{{ url('/show-members') }}" class="nav-btn {{ request()->is('show-members*') ? 'is-active' : '' }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="4"/><path d="M5.5 21a6.5 6.5 0 0 1 13 0"/></svg>
                SHOW MEMBERS
            </a>
            <a href="{{ url('/users') }}" class="nav-btn {{ request()->is('users*') ? 'is-active' : '' }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="4"/><path d="M5.5 21a6.5 6.5 0 0 1 13 0"/></svg>
                USERS
            </a>
        @elseif ($isCollector || $isAgent)
            <a href="{{ url('/show-members') }}" class="nav-btn {{ request()->is('show-members*') ? 'is-active' : '' }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="4"/><path d="M5.5 21a6.5 6.5 0 0 1 13 0"/></svg>
                SHOW MEMBERS
            </a>
        @elseif ($isManager)
            <a href="{{ url('/payment') }}" class="nav-btn {{ request()->is('payment*') ? 'is-active' : '' }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h3"/></svg>
                PAYMENT
            </a>
            <a href="{{ url('/show-members') }}" class="nav-btn {{ request()->is('show-members*') ? 'is-active' : '' }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="4"/><path d="M5.5 21a6.5 6.5 0 0 1 13 0"/></svg>
                SHOW MEMBERS
            </a>
            <a href="{{ url('/report') }}" class="nav-btn {{ request()->is('report*') ? 'is-active' : '' }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="14" rx="2"/><path d="M7 7h10M7 11h6"/><path d="M9 17l3 4 3-4"/></svg>
                REPORT
            </a>
        @elseif ($isEncoder)
            <a href="{{ route('add-members.draft.staff') }}" class="nav-btn {{ request()->is('add-members*') ? 'is-active' : '' }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z"/></svg>
                ADD MEMBERS
            </a>
            <a href="{{ url('/show-members') }}" class="nav-btn {{ request()->is('show-members*') ? 'is-active' : '' }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="4"/><path d="M5.5 21a6.5 6.5 0 0 1 13 0"/></svg>
                SHOW MEMBERS
            </a>
        @else
            <a href="{{ route('add-members.draft.staff') }}" class="nav-btn {{ request()->is('add-members*') ? 'is-active' : '' }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z"/></svg>
                ADD MEMBERS
            </a>
            <a href="{{ url('/payment') }}" class="nav-btn {{ request()->is('payment*') ? 'is-active' : '' }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h3"/></svg>
                PAYMENT
            </a>
            <a href="{{ url('/show-members') }}" class="nav-btn {{ request()->is('show-members*') ? 'is-active' : '' }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="4"/><path d="M5.5 21a6.5 6.5 0 0 1 13 0"/></svg>
                SHOW MEMBERS
            </a>
            <a href="{{ url('/users') }}" class="nav-btn {{ request()->is('users*') ? 'is-active' : '' }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="4"/><path d="M5.5 21a6.5 6.5 0 0 1 13 0"/></svg>
                USERS
            </a>
        @endif
    </div>
</nav>

<script>
    (() => {
        const nav = document.querySelector('.nav-bar');
        if (!nav) return;
        const navItems = nav.querySelector('.nav-items');
        const highlight = navItems?.querySelector('.nav-highlight');
        const toggle = nav.querySelector('.nav-toggle');
        const headerToggle = document.querySelector('.menu-toggle');

        const transitionValue = 'transform 0.25s ease, width 0.25s ease, height 0.25s ease, opacity 0.2s ease';
        const moveHighlight = (target, animate = true) => {
            if (!highlight || !navItems || !target) return;
            if (!animate) highlight.style.transition = 'none';
            const btnRect = target.getBoundingClientRect();
            const listRect = navItems.getBoundingClientRect();
            const left = btnRect.left - listRect.left + navItems.scrollLeft;
            const top = btnRect.top - listRect.top + navItems.scrollTop;
            highlight.style.width = `${btnRect.width}px`;
            highlight.style.height = `${btnRect.height}px`;
            highlight.style.transform = `translate(${left}px, ${top}px)`;
            highlight.style.opacity = '1';
            if (!animate) {
                requestAnimationFrame(() => {
                    highlight.style.transition = transitionValue;
                });
            }
        };

        const activeBtn = navItems?.querySelector('.nav-btn.is-active') || navItems?.querySelector('.nav-btn:not(.nav-toggle)');
        if (activeBtn) requestAnimationFrame(() => moveHighlight(activeBtn, false));

        navItems?.querySelectorAll('.nav-btn').forEach(btn => {
            btn.addEventListener('click', () => moveHighlight(btn));
        });
        navItems?.addEventListener('scroll', () => {
            const current = navItems.querySelector('.nav-btn.is-active');
            if (current) moveHighlight(current);
        });
        window.addEventListener('resize', () => {
            const current = navItems?.querySelector('.nav-btn.is-active');
            if (current) moveHighlight(current);
        });

        if (toggle) {
            const icon = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="6" width="14" height="2" rx="0.5"/><rect x="5" y="11" width="14" height="2" rx="0.5"/><rect x="5" y="16" width="14" height="2" rx="0.5"/></svg>`;
            let hidden = false;
            toggle.innerHTML = icon;

            const syncLabel = () => {
                const label = hidden ? 'Show navigation' : 'Hide navigation';
                toggle.setAttribute('aria-expanded', (!hidden).toString());
                toggle.setAttribute('aria-label', label);
                if (headerToggle) {
                    headerToggle.setAttribute('aria-expanded', (!hidden).toString());
                    headerToggle.setAttribute('aria-label', label);
                }
            };

            const handleToggle = () => {
                hidden = !hidden;
                nav.classList.toggle('nav-collapsed', hidden);
                if (hidden && highlight) {
                    highlight.style.opacity = '0';
                } else {
                    const current = navItems?.querySelector('.nav-btn.is-active') || navItems?.querySelector('.nav-btn:not(.nav-toggle)');
                    // reflow first so widths are correct after expanding
                    requestAnimationFrame(() => {
                        if (current) moveHighlight(current, false);
                    });
                }
                syncLabel();
            };

            toggle.addEventListener('click', handleToggle);
            headerToggle?.addEventListener('click', handleToggle);
            syncLabel();
        }
    })();
</script>
