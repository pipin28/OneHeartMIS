<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | OneHeart Life Plan</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/partials/nav.css') . '?v=' . filemtime(public_path('css/partials/nav.css')) }}">
</head>
<body class="has-shell" data-show-welcome="{{ session('status') ? '1' : '0' }}" data-user-name="{{ Auth::user()->name ?? '' }}">
    <div class="page">
        @include('partials.header')

        <main class="dashboard">
            <section class="wrap dashboard-hero">
                <div class="hero-meta">
                    <span class="eyebrow">System overview</span>
                    <span class="pill">Live sample</span>
                </div>
                <div class="hero-title">OneHeart Control Center</div>
                <p class="hero-sub">Quick snapshot of inventory, customers, and sales velocity. Charts below use sample data so you can see the layout before wiring it to the backend.</p>
                <div class="status-grid">
                    <div class="status-card">
                        <div class="label">Monthly revenue</div>
                        <div class="value">$128,400</div>
                        <div class="trend positive">+12.4% vs last month</div>
                    </div>
                    <div class="status-card">
                        <div class="label">Open orders</div>
                        <div class="value">248</div>
                        <div class="trend neutral">Steady throughput</div>
                    </div>
                    <div class="status-card">
                        <div class="label">Inventory value</div>
                        <div class="value">$864,200</div>
                        <div class="trend warning">Restock 18 SKUs soon</div>
                    </div>
                </div>
            </section>

            <section class="dashboard-grid">
                <article class="card chart-card wide">
                    <div class="card-head">
                        <div>
                            <div class="card-eyebrow">Sales</div>
                            <h3>Monthly sales trend</h3>
                        </div>
                        <span class="chip chip-positive">+9.3%</span>
                    </div>
                    <canvas id="salesChart" height="140"></canvas>
                </article>

                <article class="card chart-card">
                    <div class="card-head">
                        <div>
                            <div class="card-eyebrow">Inventory</div>
                            <h3>Top categories</h3>
                        </div>
                        <span class="chip chip-neutral">Sample data</span>
                    </div>
                    <canvas id="stockChart" height="120"></canvas>
                </article>

                <article class="card chart-card">
                    <div class="card-head">
                        <div>
                            <div class="card-eyebrow">Customers</div>
                            <h3>Acquisition sources</h3>
                        </div>
                        <span class="chip chip-warning">Live soon</span>
                    </div>
                    <canvas id="customerChart" height="120"></canvas>
                </article>
            </section>

            <section class="card insights">
                <div class="insight">
                    <div class="icon-dot success"></div>
                    <div>
                        <div class="title">Purchase orders</div>
                        <p>Suppliers delivered 92% on-time this month; watch lead time on hardware.</p>
                    </div>
                    <a href="{{ url('/purchases') }}" class="link-accent">View</a>
                </div>
                <div class="insight">
                    <div class="icon-dot warning"></div>
                    <div>
                        <div class="title">Low stock</div>
                        <p>SKUs in Accessories dipped below safety levels; reorder threshold triggered.</p>
                    </div>
                    <a href="{{ url('/report') }}" class="link-accent">Report</a>
                </div>
                <div class="insight">
                    <div class="icon-dot info"></div>
                    <div>
                        <div class="title">New customers</div>
                        <p>Bulk signups are trending from referral links—consider a loyalty push.</p>
                    </div>
                    <a href="{{ url('/customer') }}" class="link-accent">Open</a>
                </div>
            </section>
        </main>

        @include('partials.footer')
    </div>

    <div class="loading-overlay" id="pageLoader">
        <div class="spinner"></div>
        <div class="loading-text">Signing you in...</div>
    </div>

    <div class="welcome-modal" id="welcomeModal">
        <div class="welcome-card">
            <div class="welcome-badge">Welcome</div>
            <h2 class="welcome-title">Great to see you, <span class="welcome-name"></span>!</h2>
            <p class="welcome-copy">Your dashboard is ready. Let’s check today’s numbers.</p>
            <button type="button" class="welcome-close" aria-label="Close welcome">
                <span class="welcome-btn-text">Enter dashboard</span>
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const withCtx = (id) => {
                const el = document.getElementById(id);
                return el ? el.getContext('2d') : null;
            };

            const salesCtx = withCtx('salesChart');
            if (salesCtx) {
                new Chart(salesCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                        datasets: [{
                            label: 'Sales',
                            data: [12, 14, 16, 18, 22, 25, 24, 27, 29, 30, 34, 38],
                            backgroundColor: 'rgba(255, 159, 28, 0.35)',
                            borderColor: '#ff9f1c',
                            borderWidth: 2,
                            borderRadius: 8,
                            barPercentage: 0.7
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1f2733',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                cornerRadius: 10,
                            }
                        },
                        scales: {
                            y: { ticks: { callback: (v) => `$${v}k` }, grid: { color: 'rgba(0,0,0,0.06)' }, beginAtZero: true },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }

            const stockCtx = withCtx('stockChart');
            if (stockCtx) {
                new Chart(stockCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Accessories', 'Appliances', 'Hardware', 'Consumables'],
                        datasets: [{
                            data: [35, 22, 28, 15],
                            backgroundColor: ['#ff9f1c', '#ffd166', '#9be7c4', '#cdd9ed'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        cutout: '62%',
                        plugins: {
                            legend: { position: 'bottom', labels: { boxWidth: 12 } }
                        }
                    }
                });
            }

            const customerCtx = withCtx('customerChart');
            if (customerCtx) {
                new Chart(customerCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Referral', 'Walk-in', 'Marketplace', 'Direct'],
                        datasets: [{
                            label: 'New customers',
                            data: [48, 32, 60, 28],
                            backgroundColor: 'rgba(103, 150, 255, 0.3)',
                            borderColor: '#6796ff',
                            borderWidth: 2,
                            borderRadius: 8,
                            barPercentage: 0.6
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            x: { grid: { color: 'rgba(0,0,0,0.06)' }, beginAtZero: true },
                            y: { grid: { display: false } }
                        }
                    }
                });
            }

            const body = document.body;
            const showWelcome = body.dataset.showWelcome === '1';
            const userName = body.dataset.userName || 'OneHeart member';
            const loader = document.getElementById('pageLoader');
            const modal = document.getElementById('welcomeModal');
            const nameTarget = modal?.querySelector('.welcome-name');
            const badge = modal?.querySelector('.welcome-badge');
            const title = modal?.querySelector('.welcome-title');
            const copy = modal?.querySelector('.welcome-copy');
            const closeBtn = modal?.querySelector('.welcome-close');
            let welcomeTyped = false;

            const typeText = (el, text, speed = 26, highlightName = false) => {
                return new Promise(resolve => {
                    if (!el) return resolve();
                    el.style.opacity = '1';
                    el.classList.add('is-typing');
                    let i = 0;
                    el.textContent = '';
                    const tick = () => {
                        el.textContent = text.slice(0, i);
                        i++;
                        if (i <= text.length) {
                            setTimeout(tick, speed);
                        } else {
                            if (highlightName && userName && el.textContent.includes(userName)) {
                                const full = el.textContent;
                                el.innerHTML = full.replace(userName, `<span class="welcome-name">${userName}</span>`);
                            }
                            el.classList.remove('is-typing');
                            resolve();
                        }
                    };
                    tick();
                });
            };

            const resetWelcomeText = () => {
                [badge, title, copy].forEach(el => {
                    if (el) {
                        el.style.opacity = '0';
                        el.textContent = '';
                    }
                });
            };

            const openModal = async () => {
                if (!modal || welcomeTyped) return;
                resetWelcomeText();
                modal.classList.add('is-visible');
                const titleText = `Great to see you, ${userName}!`;
                const copyText = `Your dashboard is ready. Let’s check today’s numbers.`;
                await typeText(badge, 'Welcome', 34);
                await typeText(title, titleText, 26, true);
                await typeText(copy, copyText, 20);
                welcomeTyped = true;
            };

            const closeModal = () => modal?.classList.remove('is-visible');

            closeBtn?.addEventListener('click', closeModal);
            modal?.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });

            if (showWelcome) {
                loader?.classList.add('is-active');
                setTimeout(() => {
                    loader?.classList.remove('is-active');
                    setTimeout(openModal, 250);
                }, 900);
            }
        });
    </script>
</body>
</html>
