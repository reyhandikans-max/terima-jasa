/**
 * Terima Jasa - Integrated Notification Engine
 * File: public/assets/js/notifications.js
 * 
 * Features:
 * - Smart background polling (25s interval, pauses when tab is inactive)
 * - Anti-tampering / Anti-IDOR (Uses session cookies, never trusts frontend user_id)
 * - Dynamic badge counter update (Header bell & Sidebar)
 * - Interactive Dropdown preview with quick read actions
 * - Seamless integration with Real-Time business events
 */

(function () {
    'use strict';

    const POLLING_INTERVAL = 25000; // 25 detik
    let pollingTimer = null;
    let isFetchingList = false;

    // Element Selectors
    const headerBadge = document.getElementById('tj-notif-badge');
    const sidebarBadge = document.getElementById('tj-sidebar-notif-badge');
    const countLabel = document.getElementById('tj-notif-count-label');
    const itemsContainer = document.getElementById('tj-notif-items-container');
    const dropdownBtn = document.getElementById('tjNotifDropdownBtn');
    const markAllReadBtn = document.getElementById('tj-mark-all-read-btn');

    const BASE_URL = (typeof window.APP_BASE_URL !== 'undefined') ? window.APP_BASE_URL : '';

    /**
     * Update Tampilan Badge di Navbar & Sidebar
     */
    function updateBadgeDisplay(count) {
        const num = parseInt(count, 10) || 0;

        if (headerBadge) {
            if (num > 0) {
                headerBadge.textContent = num > 99 ? '99+' : num;
                headerBadge.classList.remove('d-none');
            } else {
                headerBadge.classList.add('d-none');
            }
        }

        if (sidebarBadge) {
            if (num > 0) {
                sidebarBadge.textContent = num > 99 ? '99+' : num;
                sidebarBadge.classList.remove('d-none');
            } else {
                sidebarBadge.classList.add('d-none');
            }
        }

        if (countLabel) {
            countLabel.textContent = num > 0 ? `${num} baru` : '0 baru';
            if (num > 0) {
                countLabel.className = 'badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill small';
            } else {
                countLabel.className = 'badge bg-light text-muted border rounded-pill small';
            }
        }
    }

    /**
     * Ambil Jumlah Notifikasi Belum Dibaca
     */
    async function fetchUnreadCount() {
        try {
            const res = await fetch(`${BASE_URL}/api/notifications/unread-count`, {
                headers: { 'Accept': 'application/json' },
                cache: 'no-cache'
            });

            if (!res.ok) return;

            const data = await res.json();
            if (data.status === 'success') {
                updateBadgeDisplay(data.unread_count);
            }
        } catch (err) {
            // Silently handle network glitches during background polling
            console.debug('Notification poll skipped:', err.message);
        }
    }

    /**
     * Ambil & Render Daftar Notifikasi Terbaru ke Dropdown
     */
    async function loadDropdownList() {
        if (!itemsContainer || isFetchingList) return;
        isFetchingList = true;

        itemsContainer.innerHTML = `
            <div class="p-4 text-center text-muted" id="tj-notif-loading">
                <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
                <div class="small">Memuat notifikasi...</div>
            </div>
        `;

        try {
            const res = await fetch(`${BASE_URL}/api/notifications?limit=8`, {
                headers: { 'Accept': 'application/json' },
                cache: 'no-cache'
            });

            if (!res.ok) {
                itemsContainer.innerHTML = `
                    <div class="p-3 text-center text-muted small">
                        Gagal memuat notifikasi.
                    </div>
                `;
                isFetchingList = false;
                return;
            }

            const json = await res.json();
            isFetchingList = false;

            if (json.status !== 'success' || !json.data || json.data.length === 0) {
                itemsContainer.innerHTML = `
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-bell-slash fs-2 d-block mb-1 text-secondary"></i>
                        <div class="small fw-semibold text-dark">Belum ada notifikasi</div>
                        <div class="text-muted" style="font-size:0.75rem;">Aktivitas pesanan Anda akan muncul di sini.</div>
                    </div>
                `;
                updateBadgeDisplay(0);
                return;
            }

            updateBadgeDisplay(json.unread_count || 0);

            let html = '<div class="list-group list-group-flush">';
            json.data.forEach(item => {
                const isUnread = !item.is_read;
                const bgClass = isUnread ? 'bg-primary-subtle bg-opacity-10' : 'bg-white';
                const unreadDot = isUnread ? '<span class="position-absolute top-50 end-0 translate-middle-y me-3 p-1 bg-primary border border-light rounded-circle" title="Belum dibaca"></span>' : '';
                const actionUrl = item.action_url || `${BASE_URL}/notifications`;

                html += `
                    <div class="list-group-item list-group-item-action p-3 position-relative border-bottom ${bgClass} tj-dropdown-notif-item" 
                         data-id="${item.id}" data-url="${actionUrl}">
                        <div class="d-flex align-items-start gap-2 pe-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 ${item.icon_class}"
                                 style="width: 32px; height: 32px; font-size: 0.9rem;">
                                <i class="bi ${item.icon}"></i>
                            </div>
                            <div class="flex-grow-1 overflow-hidden" style="cursor: pointer;">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <h6 class="mb-0 fw-semibold text-truncate ${isUnread ? 'text-dark' : 'text-secondary'}" style="font-size: 0.84rem;">
                                        ${escapeHtml(item.title)}
                                    </h6>
                                    <span class="text-muted ms-2 flex-shrink-0" style="font-size: 0.7rem;">${item.time_ago}</span>
                                </div>
                                <p class="text-muted mb-0 small text-truncate" style="font-size: 0.77rem; line-height: 1.35;">
                                    ${escapeHtml(item.message)}
                                </p>
                            </div>
                        </div>
                        ${unreadDot}
                    </div>
                `;
            });
            html += '</div>';

            itemsContainer.innerHTML = html;

            // Tambahkan event click ke setiap item notifikasi di dropdown
            itemsContainer.querySelectorAll('.tj-dropdown-notif-item').forEach(el => {
                el.addEventListener('click', async function (e) {
                    const notifId = this.getAttribute('data-id');
                    const targetUrl = this.getAttribute('data-url');

                    // Mark as read di background
                    if (notifId) {
                        try {
                            fetch(`${BASE_URL}/api/notifications/${notifId}/read`, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' }
                            }).catch(() => {});
                        } catch (err) {}
                    }

                    if (targetUrl) {
                        window.location.href = targetUrl;
                    }
                });
            });

        } catch (err) {
            isFetchingList = false;
            console.error('Failed to load notifications list:', err);
            itemsContainer.innerHTML = `
                <div class="p-3 text-center text-muted small">
                    Terjadi kesalahan saat memuat notifikasi.
                </div>
            `;
        }
    }

    /**
     * Tandai Semua Notifikasi Sebagai Dibaca
     */
    async function markAllAsRead() {
        if (!markAllReadBtn) return;

        try {
            markAllReadBtn.disabled = true;
            markAllReadBtn.textContent = 'Memproses...';

            const res = await fetch(`${BASE_URL}/api/notifications/read-all`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });

            const data = await res.json();
            if (data.status === 'success') {
                updateBadgeDisplay(0);
                // Refresh list if open
                loadDropdownList();
            }
        } catch (err) {
            console.error('Failed to mark all as read:', err);
        } finally {
            if (markAllReadBtn) {
                markAllReadBtn.disabled = false;
                markAllReadBtn.textContent = 'Tandai Semua Dibaca';
            }
        }
    }

    /**
     * Helper Escape HTML
     */
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /**
     * Mulai Polling Berkala (Berhenti jika tab inactive untuk efisiensi jaringan & baterai)
     */
    function startPolling() {
        stopPolling();
        fetchUnreadCount();
        pollingTimer = setInterval(() => {
            if (!document.hidden) {
                fetchUnreadCount();
            }
        }, POLLING_INTERVAL);
    }

    function stopPolling() {
        if (pollingTimer) {
            clearInterval(pollingTimer);
            pollingTimer = null;
        }
    }

    // Event Listener Tab Visibility
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            fetchUnreadCount();
        }
    });

    // Inisialisasi saat DOM siap
    document.addEventListener('DOMContentLoaded', () => {
        // Dropdown toggle listener
        if (dropdownBtn) {
            dropdownBtn.addEventListener('show.bs.dropdown', () => {
                loadDropdownList();
            });
        }

        // Tombol Tandai Semua Dibaca
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                markAllAsRead();
            });
        }

        // Start polling
        startPolling();
    });

    // Ekspos helper global untuk modul lain jika diperlukan
    window.refreshNotificationBadge = fetchUnreadCount;

})();
