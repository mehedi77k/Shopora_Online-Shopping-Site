(() => {
    'use strict';

    const qs = (selector, root = document) => root.querySelector(selector);
    const qsa = (selector, root = document) => Array.from(root.querySelectorAll(selector));

    document.addEventListener('DOMContentLoaded', () => {
        const menuToggle = qs('[data-menu-toggle]');
        const menu = qs('[data-menu]');
        if (menuToggle && menu) {
            menuToggle.addEventListener('click', () => menu.classList.toggle('is-open'));
        }

        qsa('[data-confirm]').forEach((el) => {
            el.addEventListener('click', (event) => {
                if (!confirm(el.dataset.confirm || 'Are you sure?')) event.preventDefault();
            });
        });

        qsa('[data-qty]').forEach((group) => {
            const input = qs('input[type="number"]', group);
            const minus = qs('[data-minus]', group);
            const plus = qs('[data-plus]', group);
            if (!input) return;
            minus?.addEventListener('click', () => {
                input.value = Math.max(Number(input.min || 1), Number(input.value || 1) - 1);
            });
            plus?.addEventListener('click', () => {
                const max = Number(input.max || 9999);
                input.value = Math.min(max, Number(input.value || 1) + 1);
            });
        });

        const year = qs('[data-current-year]');
        if (year) year.textContent = new Date().getFullYear();

        initImageManagers();
        initProfileMobileNumbers();
        initLiveExchangeRate();
        initRealtime();
        initRealtimeForms();
    });

    function initImageManagers() {
        qsa('[data-image-manager]').forEach((manager) => {
            const input = qs('[data-image-input]', manager);
            const preview = qs('[data-image-preview]', manager);
            const fileName = qs('[data-image-file-name]', manager);
            const remove = qs('[data-image-remove]', manager);
            if (!input || !preview) return;

            const originalSrc = preview.src;
            const placeholder = preview.dataset.placeholder || originalSrc;

            input.addEventListener('change', () => {
                const file = input.files && input.files[0];
                if (!file) {
                    preview.src = remove?.checked ? placeholder : originalSrc;
                    if (fileName) fileName.textContent = 'No new image selected';
                    return;
                }
                if (fileName) fileName.textContent = file.name;
                if (remove) remove.checked = false;
                const objectUrl = URL.createObjectURL(file);
                preview.src = objectUrl;
                preview.onload = () => URL.revokeObjectURL(objectUrl);
            });

            remove?.addEventListener('change', () => {
                if (remove.checked) {
                    input.value = '';
                    preview.src = placeholder;
                    if (fileName) fileName.textContent = 'Current image will be removed';
                } else {
                    preview.src = originalSrc;
                    if (fileName) fileName.textContent = 'No new image selected';
                }
            });
        });
    }

    function initProfileMobileNumbers() {
        qsa('[data-mobile-list]').forEach((section) => {
            const rows = qs('[data-mobile-rows]', section);
            const add = qs('[data-add-mobile]', section);
            if (!rows || !add) return;

            const bindRemove = (row) => {
                qs('[data-remove-mobile]', row)?.addEventListener('click', () => {
                    const currentRows = qsa('[data-mobile-row]', rows);
                    if (currentRows.length <= 1) {
                        qsa('input', row).forEach((input) => { input.value = ''; });
                        return;
                    }
                    row.remove();
                });
            };
            qsa('[data-mobile-row]', rows).forEach(bindRemove);

            add.addEventListener('click', () => {
                if (qsa('[data-mobile-row]', rows).length >= 8) return;
                const row = document.createElement('div');
                row.className = 'mobile-number-row';
                row.dataset.mobileRow = '';
                row.innerHTML = `
                    <input class="form-control" name="mobile_labels[]" maxlength="40" placeholder="Label (e.g. Work)" value="Mobile">
                    <input class="form-control" name="mobile_numbers[]" maxlength="30" placeholder="Mobile number" autocomplete="tel">
                    <button class="btn btn-ghost btn-small" type="button" data-remove-mobile>Remove</button>`;
                rows.appendChild(row);
                bindRemove(row);
                qs('input[name="mobile_numbers[]"]', row)?.focus();
            });
        });
    }

    const baseUrl = () => (qs('meta[name="shopora-base-url"]')?.content || '').replace(/\/$/, '');
    const sessionContext = () => qs('meta[name="shopora-session-context"]')?.content || 'main';
    const contextualUrl = (target) => {
        const parsed = new URL(target, window.location.origin);
        const context = sessionContext();
        if (context && context !== 'main') parsed.searchParams.set('ctx', context);
        return parsed.pathname + parsed.search + parsed.hash;
    };
    const currentUserId = () => Number(document.body?.dataset.userId || 0);
    const currentRole = () => document.body?.dataset.userRole || '';
    const isStaff = () => ['admin', 'super_admin'].includes(currentRole());

    async function fetchJson(url, options = {}) {
        const response = await fetch(url, {
            credentials: 'same-origin',
            cache: 'no-store',
            ...options,
        });
        let data = null;
        try {
            data = await response.json();
        } catch (_) {
            throw new Error('The server returned an unexpected response.');
        }
        if (!response.ok || !data?.ok) {
            throw new Error(data?.error || 'The request could not be completed.');
        }
        return data;
    }

    function formatUsdRate(rate) {
        return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 4 }).format(rate);
    }

    function initLiveExchangeRate() {
        let lastRate = null;
        let timer = null;

        const refresh = async () => {
            try {
                const data = await fetchJson(contextualUrl(`${baseUrl()}/api/exchange_rate.php`));
                const rate = Number(data.rate || 0);
                if (!(rate > 0)) return;

                qsa('[data-live-exchange-rate]').forEach((el) => {
                    const mode = el.dataset.rateMode || 'pair';
                    el.dataset.rateValue = String(rate);
                    el.textContent = mode === 'usd'
                        ? `$${formatUsdRate(rate)}`
                        : `€1 = $${formatUsdRate(rate)}`;
                });
                qsa('[data-live-rate-date]').forEach((el) => {
                    el.textContent = data.source_date || 'Latest available';
                });
                qsa('[data-live-rate-checked]').forEach((el) => {
                    if (data.last_checked_at) el.textContent = data.last_checked_at;
                });

                if (lastRate !== null && Math.abs(lastRate - rate) >= 0.000001 && qsa('.dual-price').length) {
                    schedulePageReload(250);
                }
                lastRate = rate;

                const next = Math.max(15000, Number(data.poll_ms || 60000));
                if (timer) window.clearTimeout(timer);
                timer = window.setTimeout(refresh, next);
            } catch (_) {
                if (timer) window.clearTimeout(timer);
                timer = window.setTimeout(refresh, 60000);
            }
        };

        refresh();
    }

    async function refreshRealtimeState() {
        try {
            const state = await fetchJson(contextualUrl(`${baseUrl()}/api/realtime_state.php`));
            qsa('.cart-badge').forEach((el) => { el.textContent = String(state.cart_count ?? 0); });

            qsa('[data-support-user-badge]').forEach((el) => {
                const value = Number(state.support?.unread || 0);
                el.textContent = String(value);
                el.hidden = value < 1;
            });
            qsa('[data-support-staff-badge]').forEach((el) => {
                const value = Number(state.staff_support_unread || 0);
                el.textContent = String(value);
                el.hidden = value < 1;
            });
            qsa('[data-support-staff-label]').forEach((el) => {
                el.textContent = `${Number(state.staff_support_unread || 0)} unread`;
            });

            const total = qs('[data-support-total]');
            const active = qs('[data-support-active]');
            const unread = qs('[data-support-unread]');
            if (total) total.textContent = String(state.support?.total ?? 0);
            if (active) active.textContent = String(state.support?.active ?? 0);
            if (unread) unread.textContent = String(state.support?.unread ?? 0);
        } catch (_) {
            // Real-time state refresh is optional. Normal page navigation remains functional.
        }
    }

    function createMessageElement(message) {
        const staffMessage = message.message_type === 'staff';
        const item = document.createElement('div');
        item.className = `support-message ${staffMessage ? 'staff-message' : 'requester-message'}`;
        item.dataset.messageId = String(message.message_id || '');

        const meta = document.createElement('div');
        meta.className = 'support-message-meta';
        const name = document.createElement('strong');
        name.textContent = message.sender_name || '';
        const details = document.createElement('span');
        const prefix = isStaff()
            ? (staffMessage ? 'Staff reply · ' : 'Requester · ')
            : (staffMessage ? 'Support staff · ' : '');
        details.textContent = `${prefix}${message.role_label || ''} · ${message.created_at_display || ''}`;
        meta.append(name, details);

        const body = document.createElement('div');
        body.className = 'support-message-body';
        body.textContent = message.message_text || '';

        item.append(meta, body);
        return item;
    }

    async function refreshSupportThread() {
        const thread = qs('[data-support-thread]');
        if (!thread) return;
        const id = Number(thread.dataset.conversationId || 0);
        if (!id) return;

        try {
            const data = await fetchJson(contextualUrl(`${baseUrl()}/api/support_thread.php?id=${encodeURIComponent(id)}`));
            const fragment = document.createDocumentFragment();
            (data.messages || []).forEach((message) => fragment.appendChild(createMessageElement(message)));
            thread.replaceChildren(fragment);

            qsa('[data-support-status]').forEach((statusEl) => {
                const status = data.conversation.status || 'Open';
                statusEl.textContent = status;
                statusEl.classList.remove('open', 'answered', 'closed');
                statusEl.classList.add(status.toLowerCase());
            });

            const statusSelect = qs('form[data-realtime-form="support-status"] select[name="status"]');
            if (statusSelect) statusSelect.value = data.conversation.status || 'Open';

            if (!isStaff()) {
                const closed = (data.conversation.status || 'Open') === 'Closed';
                const openArea = qs('[data-support-reply-open]');
                const closedArea = qs('[data-support-reply-closed]');
                if (openArea) openArea.hidden = closed;
                if (closedArea) closedArea.hidden = !closed;
            }

            await refreshRealtimeState();
        } catch (_) {
            // Keep the existing server-rendered thread if live refresh fails.
        }
    }

    let reloadTimer = null;
    function schedulePageReload(delay = 450) {
        if (reloadTimer) return;
        reloadTimer = window.setTimeout(() => window.location.reload(), delay);
    }

    function eventBelongsToCurrentUser(data) {
        const eventUser = Number(data?.user_id || 0);
        const me = currentUserId();
        return !eventUser || !me || eventUser === me;
    }

    function handleRealtimeEvent(message) {
        const event = message?.event || '';
        const data = message?.data || {};
        if (!event || event === 'realtime.heartbeat' || event === 'realtime.connected') return;

        refreshRealtimeState();
        const path = window.location.pathname.toLowerCase();
        const thread = qs('[data-support-thread]');

        if (event === 'support.updated') {
            const eventConversation = Number(data.conversation_id || 0);
            if (thread) {
                const currentConversation = Number(thread.dataset.conversationId || 0);
                if (!eventConversation || eventConversation === currentConversation) refreshSupportThread();
                return;
            }
            if (qs('[data-support-list-page]') && (isStaff() || eventBelongsToCurrentUser(data))) {
                schedulePageReload();
            }
            return;
        }

        if (event === 'cart.updated') {
            if (path.endsWith('/cart.php') && eventBelongsToCurrentUser(data)) schedulePageReload();
            return;
        }

        if (event === 'order.updated') {
            const staffOrderPage = isStaff() && (
                path.endsWith('/admin/index.php') || path.endsWith('/admin/orders.php') || path.endsWith('/admin/order_view.php')
            );
            const ownOrderPage = eventBelongsToCurrentUser(data) && (
                path.endsWith('/account.php') || path.endsWith('/order_details.php')
            );
            if (staffOrderPage || ownOrderPage) schedulePageReload();
            return;
        }

        if (['product.updated', 'category.updated', 'currency.updated', 'review.updated'].includes(event)) {
            const liveCatalogPage = path.endsWith('/shop.php') || path.endsWith('/product.php') ||
                path.endsWith('/index.php') || path.endsWith('/online_shop/') ||
                path.endsWith('/cart.php') || path.endsWith('/checkout.php') ||
                path.endsWith('/admin/products.php') || path.endsWith('/admin/categories.php') ||
                path.endsWith('/admin/index.php') || path.endsWith('/admin/currency_settings.php');
            if (liveCatalogPage) schedulePageReload();
            return;
        }

        if (event === 'account.updated' && isStaff()) {
            if (path.endsWith('/admin/users.php') || path.endsWith('/admin/admins.php') || path.endsWith('/admin/user_history.php')) {
                schedulePageReload();
            }
        }
    }

    function initRealtime() {
        const enabled = qs('meta[name="shopora-realtime-enabled"]')?.content === '1';
        const port = Number(qs('meta[name="shopora-ws-port"]')?.content || 0);
        if (!enabled || !port || !('WebSocket' in window)) return;

        let socket = null;
        let retry = 0;
        let reconnectTimer = null;

        const connect = () => {
            const scheme = window.location.protocol === 'https:' ? 'wss' : 'ws';
            const url = `${scheme}://${window.location.hostname}:${port}`;
            try {
                socket = new WebSocket(url);
            } catch (_) {
                scheduleReconnect();
                return;
            }

            socket.addEventListener('open', () => {
                retry = 0;
                refreshRealtimeState();
                refreshSupportThread();
            });

            socket.addEventListener('message', (event) => {
                try {
                    handleRealtimeEvent(JSON.parse(event.data));
                } catch (_) {
                    // Ignore malformed/non-JSON broadcast frames.
                }
            });

            socket.addEventListener('close', scheduleReconnect);
            socket.addEventListener('error', () => {
                try { socket.close(); } catch (_) {}
            });
        };

        const scheduleReconnect = () => {
            if (reconnectTimer) return;
            const delay = Math.min(15000, 1000 * Math.pow(2, Math.min(retry, 4)));
            retry += 1;
            reconnectTimer = window.setTimeout(() => {
                reconnectTimer = null;
                connect();
            }, delay);
        };

        connect();
    }

    function initRealtimeForms() {
        qsa('form[data-realtime-form]').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const button = qs('button[type="submit"], button:not([type])', form);
                const errorEl = qs('[data-form-error]', form);
                if (errorEl) {
                    errorEl.hidden = true;
                    errorEl.textContent = '';
                }
                if (button) button.disabled = true;

                try {
                    await fetchJson(form.action || window.location.href, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });
                    if (form.dataset.realtimeForm === 'support-reply') {
                        const textarea = qs('textarea[name="message"]', form);
                        if (textarea) textarea.value = '';
                    }
                    await refreshSupportThread();
                    await refreshRealtimeState();
                } catch (error) {
                    if (errorEl) {
                        errorEl.textContent = error.message || 'The request could not be completed.';
                        errorEl.hidden = false;
                    }
                } finally {
                    if (button) button.disabled = false;
                }
            });
        });
    }
})();
