document.addEventListener('DOMContentLoaded', function () {
    document.body.classList.add('loaded');

    const themeSwitchers = document.querySelectorAll('[data-theme-switcher]');
    const themeKey = 'coffee_theme';
    const soundKey = 'coffee_sound_enabled';

    function getAutoTheme() {
        const hour = new Date().getHours();
        return hour >= 6 && hour < 18 ? 'light' : 'dark';
    }

    function applyTheme(theme) {
        const actualTheme = theme === 'auto' ? getAutoTheme() : theme;
        document.body.dataset.theme = actualTheme;
        document.body.classList.toggle('theme-dark', actualTheme === 'dark');
        document.body.classList.toggle('theme-light', actualTheme === 'light');
    }

    function updateThemeButton(themeSwitcher, theme) {
        const button = themeSwitcher.querySelector('.theme-button');
        if (button) {
            button.textContent = theme === 'auto' ? 'Theme: Auto' : `Theme: ${theme.charAt(0).toUpperCase()}${theme.slice(1)}`;
        }
    }

    function setupThemeSwitcher(themeSwitcher) {
        const button = themeSwitcher.querySelector('.theme-button');
        const menu = themeSwitcher.querySelector('.theme-menu');
        if (!button || !menu) return;

        button.addEventListener('click', function () {
            menu.classList.toggle('open');
            const expanded = menu.classList.contains('open');
            button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        });

        menu.querySelectorAll('button[data-theme]').forEach(option => {
            option.addEventListener('click', function () {
                const theme = this.dataset.theme || 'auto';
                localStorage.setItem(themeKey, theme);
                applyTheme(theme);
                updateThemeButton(themeSwitcher, theme);
                menu.classList.remove('open');
            });
        });
    }

    if (themeSwitchers.length) {
        const storedTheme = localStorage.getItem(themeKey) || 'auto';
        applyTheme(storedTheme);
        themeSwitchers.forEach(themeSwitcher => {
            setupThemeSwitcher(themeSwitcher);
            updateThemeButton(themeSwitcher, storedTheme);
        });
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
            const currentTheme = localStorage.getItem(themeKey) || 'auto';
            if (currentTheme === 'auto') {
                applyTheme('auto');
            }
        });
    }

    document.addEventListener('click', function (event) {
        themeSwitchers.forEach(themeSwitcher => {
            const menu = themeSwitcher.querySelector('.theme-menu');
            const button = themeSwitcher.querySelector('.theme-button');
            if (!menu || !button) return;
            if (!themeSwitcher.contains(event.target)) {
                menu.classList.remove('open');
                button.setAttribute('aria-expanded', 'false');
            }
        });
    });

    function playSound(type) {
        if (localStorage.getItem(soundKey) === 'off') return;
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = ctx.createOscillator();
            const gain = ctx.createGain();
            oscillator.type = 'sine';
            oscillator.frequency.value = type === 'notify' ? 760 : 440;
            gain.gain.value = 0.05;
            oscillator.connect(gain);
            gain.connect(ctx.destination);
            oscillator.start();
            setTimeout(() => {
                oscillator.stop();
                ctx.close();
            }, 120);
        } catch (error) {
            // Audio not available; fail silently.
        }
    }

    function showToast(message) {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 20);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 2400);
    }

    const links = document.querySelectorAll('.site-nav a');
    const currentPath = window.location.pathname.split('/').pop();
    links.forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPath || href === window.location.href) {
            link.classList.add('active');
        }
    });

    const adminTabButtons = document.querySelectorAll('.admin-tab-button');
    const adminSections = document.querySelectorAll('.admin-panel-section');

    function setAdminTab(tabName) {
        adminTabButtons.forEach(button => {
            button.classList.toggle('active', button.dataset.tab === tabName);
        });
        adminSections.forEach(section => {
            section.classList.toggle('active', section.dataset.section === tabName);
        });
    }

    if (adminTabButtons.length) {
        adminTabButtons.forEach(button => {
            button.addEventListener('click', () => {
                window.location.hash = button.dataset.tab;
                setAdminTab(button.dataset.tab);
            });
        });
        const hashTab = window.location.hash.replace('#', '');
        const activeServerTab = document.querySelector('.admin-tab-button.active');
        const initialTab = hashTab || (activeServerTab ? activeServerTab.dataset.tab : 'product-form');
        setAdminTab(initialTab);
    }

    const modal = document.getElementById('receiptModal');
    const modalBody = modal ? modal.querySelector('.modal-body') : null;
    const closeButton = modal ? modal.querySelector('.modal-close') : null;

    function closeReceiptModal() {
        if (!modal) return;
        modal.classList.remove('open');
        if (modalBody) {
            modalBody.innerHTML = '<div class="alert alert-info">Open a receipt from recent orders to review it here.</div>';
        }
    }

    if (modal && closeButton) {
        closeButton.addEventListener('click', closeReceiptModal);
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeReceiptModal();
            }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.classList.contains('open')) {
                closeReceiptModal();
            }
        });
    }

    const loginConfirmModal = document.getElementById('loginConfirmModal');
    const loginForm = document.getElementById('loginForm');
    const loginEmail = document.getElementById('loginEmail');
    const loginPassword = document.getElementById('loginPassword');
    const togglePassword = document.getElementById('togglePassword');
    const openLoginModal = document.getElementById('openLoginModal');
    const closeLoginModal = document.getElementById('closeLoginModal');
    const cancelLoginModal = document.getElementById('cancelLoginModal');
    const confirmLoginSubmit = document.getElementById('confirmLoginSubmit');
    const confirmEmailText = document.getElementById('confirmEmailText');

    function closeLoginConfirmModal() {
        if (!loginConfirmModal) return;
        loginConfirmModal.classList.remove('open');
        loginConfirmModal.setAttribute('aria-hidden', 'true');
    }

    function openLoginConfirmModal() {
        if (!loginConfirmModal) return;
        confirmEmailText.textContent = loginEmail.value || 'No email entered';
        loginConfirmModal.classList.add('open');
        loginConfirmModal.setAttribute('aria-hidden', 'false');
    }

    if (togglePassword && loginPassword) {
        togglePassword.addEventListener('click', function () {
            const type = loginPassword.getAttribute('type') === 'password' ? 'text' : 'password';
            loginPassword.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁️' : '🙈';
            this.setAttribute('aria-label', type === 'password' ? 'Show password' : 'Hide password');
        });
    }

    if (openLoginModal) {
        openLoginModal.addEventListener('click', openLoginConfirmModal);
    }

    if (loginForm) {
        loginForm.addEventListener('submit', function (event) {
            if (!loginConfirmModal || !loginConfirmModal.classList.contains('open')) {
                event.preventDefault();
                openLoginConfirmModal();
            }
        });
    }

    if (closeLoginModal) {
        closeLoginModal.addEventListener('click', closeLoginConfirmModal);
    }

    if (cancelLoginModal) {
        cancelLoginModal.addEventListener('click', closeLoginConfirmModal);
    }

    if (confirmLoginSubmit && loginForm) {
        confirmLoginSubmit.addEventListener('click', function () {
            closeLoginConfirmModal();
            loginForm.submit();
        });
    }

    const productImageLink = document.getElementById('productImageLink');
    const productImagePreview = document.getElementById('productImagePreview');

    if (productImageLink && productImagePreview) {
        productImageLink.addEventListener('input', function () {
            const value = this.value.trim();
            productImagePreview.src = value || 'https://images.unsplash.com/photo-1511920170033-f8396924c348?auto=format&fit=crop&w=800&q=80';
        });
    }

    if (modal && modalBody) {
        document.querySelectorAll('.btn-view-receipt').forEach(button => {
            button.addEventListener('click', function () {
                const orderId = this.dataset.orderId;
                if (!orderId) return;

                modal.classList.add('open');
                modalBody.innerHTML = '<div class="alert alert-info">Loading receipt...</div>';

                fetch(`receipt.php?order_id=${orderId}&modal=1`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Receipt load failed.');
                        }
                        return response.text();
                    })
                    .then(html => {
                        modalBody.innerHTML = html;
                    })
                    .catch(() => {
                        modalBody.innerHTML = '<div class="alert alert-error">Unable to load receipt. Please try again.</div>';
                    });
            });
        });
    }

    const productForms = document.querySelectorAll('.product-action');
    const cartLink = document.querySelector('.site-nav a[href="cart.php"]');
    productForms.forEach(form => {
        form.addEventListener('submit', function (event) {
            if (!cartLink) return;
            const card = form.closest('.product-card');
            const image = card ? card.querySelector('img') : null;
            if (!image) return;
            event.preventDefault();

            const flyItem = image.cloneNode(true);
            const rect = image.getBoundingClientRect();
            const targetRect = cartLink.getBoundingClientRect();
            flyItem.className = 'fly-item';
            flyItem.style.top = `${rect.top}px`;
            flyItem.style.left = `${rect.left}px`;
            flyItem.style.width = `${rect.width}px`;
            flyItem.style.height = `${rect.height}px`;
            document.body.appendChild(flyItem);

            requestAnimationFrame(() => {
                flyItem.style.transform = `translate(${targetRect.left - rect.left}px, ${targetRect.top - rect.top}px) scale(0.2)`;
                flyItem.style.opacity = '0.2';
            });

            setTimeout(() => {
                flyItem.remove();
                form.submit();
            }, 420);
        });
    });

    const recommendationForm = document.getElementById('recommendationForm');
    const recommendationResult = document.getElementById('recommendationResult');
    if (recommendationForm && recommendationResult) {
        recommendationForm.querySelectorAll('.pill-group').forEach(group => {
            group.addEventListener('click', function (event) {
                const button = event.target.closest('.pill');
                if (!button) return;
                group.querySelectorAll('.pill').forEach(pill => pill.classList.remove('active'));
                button.classList.add('active');
            });
        });

        const productCards = Array.from(document.querySelectorAll('.product-card'));
        function findProduct(keyword) {
            const match = productCards.find(card => card.dataset.productName && card.dataset.productName.toLowerCase().includes(keyword));
            return match ? match.dataset.productName : null;
        }

        document.getElementById('runRecommendation')?.addEventListener('click', function () {
            const flavor = recommendationForm.querySelector('.pill-group:nth-of-type(1) .pill.active')?.dataset.value || 'sweet';
            const temp = recommendationForm.querySelector('.pill-group:nth-of-type(2) .pill.active')?.dataset.value || 'iced';
            let pick = null;
            if (flavor === 'sweet' && temp === 'iced') {
                pick = findProduct('latte') || findProduct('cold') || findProduct('cappuccino');
            } else if (flavor === 'sweet' && temp === 'hot') {
                pick = findProduct('cappuccino') || findProduct('latte');
            } else if (flavor === 'bitter' && temp === 'hot') {
                pick = findProduct('espresso');
            } else {
                pick = findProduct('cold') || findProduct('brew');
            }
            recommendationResult.textContent = pick ? `You might like ${pick}.` : 'Try a Latte or Espresso for a bold pick.';
            recommendationResult.classList.add('active');
        });
    }

    const customBuilder = document.getElementById('customBuilder');
    if (customBuilder) {
        const summary = document.getElementById('builderSummary');
        const updateSummary = () => {
            const selections = Array.from(customBuilder.querySelectorAll('select')).map(select => select.value);
            const addons = Array.from(customBuilder.querySelectorAll('input[type="checkbox"]:checked')).map(input => input.value);
            const addonText = addons.length ? `Add-ons: ${addons.join(', ')}` : 'No add-ons';
            summary.textContent = `Milk: ${selections[0]}, Sugar: ${selections[1]}, Ice: ${selections[2]}. ${addonText}.`;
        };
        customBuilder.addEventListener('change', updateSummary);
        updateSummary();
    }

    const walletPanel = document.querySelector('[data-wallet]');
    if (walletPanel) {
        const balanceEl = document.getElementById('walletBalance');
        const topUpButton = document.getElementById('walletTopUp');
        const useToggle = document.getElementById('walletUse');
        const noteEl = document.getElementById('walletNote');
        const checkoutButton = document.querySelector('.js-checkout');
        const totalEl = document.querySelector('.cart-total');
        const walletKey = 'coffee_wallet_balance';

        const formatPeso = amount => `₱${amount.toFixed(2)}`;
        const getBalance = () => parseFloat(localStorage.getItem(walletKey) || '0');
        const setBalance = value => {
            localStorage.setItem(walletKey, value.toString());
            balanceEl.textContent = formatPeso(value);
        };

        setBalance(getBalance());

        topUpButton?.addEventListener('click', () => {
            const current = getBalance();
            setBalance(current + 200);
            noteEl.textContent = 'Wallet topped up with ₱200.';
            playSound('click');
        });

        checkoutButton?.addEventListener('click', event => {
            if (!useToggle?.checked) return;
            const total = parseFloat(totalEl?.dataset.total || '0');
            const balance = getBalance();
            if (balance < total) {
                event.preventDefault();
                noteEl.textContent = 'Insufficient wallet balance. Please top up.';
                showToast('Wallet balance is low.');
                playSound('notify');
                return;
            }
            setBalance(balance - total);
            noteEl.textContent = 'Wallet payment ready. Processing order...';
            playSound('click');
        });
    }

    const receiptCard = document.querySelector('.receipt-card[data-last-order]');
    if (receiptCard) {
        try {
            const payload = JSON.parse(receiptCard.dataset.lastOrder || '[]');
            if (Array.isArray(payload) && payload.length) {
                localStorage.setItem('last_order_summary', JSON.stringify(payload));
            }
        } catch (error) {
            // Ignore local storage errors.
        }
    }

    const lastOrderSection = document.getElementById('lastOrderSection');
    if (lastOrderSection) {
        try {
            const payload = JSON.parse(localStorage.getItem('last_order_summary') || '[]');
            if (Array.isArray(payload) && payload.length) {
                lastOrderSection.innerHTML = '';
                payload.slice(0, 3).forEach(item => {
                    const card = document.createElement('div');
                    card.className = 'info-card';
                    const detail = item.customization ? `<p class="muted">${item.customization}</p>` : '';
                    card.innerHTML = `<h3>${item.name}</h3>
                        <p class="muted">Last ordered: ${item.quantity} cup(s).</p>
                        ${detail}`;
                    lastOrderSection.appendChild(card);
                });
            }
        } catch (error) {
            // ignore
        }
    }

    const salesChart = document.getElementById('salesChart');
    if (salesChart && window.Chart) {
        const labels = JSON.parse(salesChart.dataset.labels || '[]');
        const values = JSON.parse(salesChart.dataset.values || '[]');
        new Chart(salesChart.getContext('2d'), {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Sales',
                    data: values,
                    borderColor: '#6b4c3b',
                    backgroundColor: 'rgba(107, 76, 59, 0.2)',
                    fill: true,
                    tension: 0.35
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: { ticks: { callback: value => `₱${value}` } }
                }
            }
        });
    }

    const qrButton = document.getElementById('generateQr');
    if (qrButton) {
        qrButton.addEventListener('click', function () {
            const input = document.getElementById('qrTableId');
            const value = input?.value.trim() || 'Coffee Ordering';
            const image = document.getElementById('qrImage');
            if (image) {
                image.src = `https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${encodeURIComponent(value)}`;
            }
        });
    }

    const menuOrderList = document.getElementById('menuOrderList');
    if (menuOrderList) {
        let draggedItem = null;
        menuOrderList.addEventListener('dragstart', event => {
            draggedItem = event.target.closest('.menu-order-item');
            if (draggedItem) {
                draggedItem.classList.add('dragging');
                event.dataTransfer.effectAllowed = 'move';
            }
        });
        menuOrderList.addEventListener('dragend', () => {
            if (draggedItem) {
                draggedItem.classList.remove('dragging');
                draggedItem = null;
            }
        });
        menuOrderList.addEventListener('dragover', event => {
            event.preventDefault();
            const target = event.target.closest('.menu-order-item');
            if (!target || target === draggedItem) return;
            const rect = target.getBoundingClientRect();
            const shouldInsertAfter = event.clientY > rect.top + rect.height / 2;
            menuOrderList.insertBefore(draggedItem, shouldInsertAfter ? target.nextSibling : target);
        });

        document.getElementById('saveMenuOrder')?.addEventListener('click', () => {
            const order = Array.from(menuOrderList.querySelectorAll('.menu-order-item'))
                .map(item => item.dataset.productId);
            fetch('admin.php?ajax=save_menu_order', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order })
            }).then(() => {
                const status = document.getElementById('menuOrderStatus');
                if (status) {
                    status.textContent = 'Menu order saved!';
                }
                showToast('Menu order updated.');
                playSound('notify');
            }).catch(() => {
                const status = document.getElementById('menuOrderStatus');
                if (status) {
                    status.textContent = 'Unable to save order. Please try again.';
                }
            });
        });
    }

    const queueList = document.getElementById('queueList');
    if (queueList) {
        let lastCount = 0;
        let initialLoaded = false;
        const fetchOrders = () => {
            fetch('admin.php?ajax=orders_count')
                .then(response => response.json())
                .then(data => {
                    if (typeof data.total !== 'number') return;
                    if (initialLoaded && data.total > lastCount) {
                        showToast('New order received!');
                        playSound('notify');
                    }
                    lastCount = data.total;
                    initialLoaded = true;
                    queueList.innerHTML = `<div class="queue-item">Total orders today: ${data.total}</div>
                        <div class="queue-item">Latest order #${data.last_id || '-'}</div>`;
                })
                .catch(() => {});
        };
        fetchOrders();
        setInterval(fetchOrders, 15000);
    }

    const soundToggle = document.getElementById('soundToggle');
    if (soundToggle) {
        const stored = localStorage.getItem(soundKey);
        soundToggle.checked = stored !== 'off';
        soundToggle.addEventListener('change', () => {
            localStorage.setItem(soundKey, soundToggle.checked ? 'on' : 'off');
            showToast(soundToggle.checked ? 'Sound effects on.' : 'Sound effects muted.');
        });
    }

});
