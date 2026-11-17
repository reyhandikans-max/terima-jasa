<?php
/**
 * View: notifications/index.php
 * Halaman Pusat Notifikasi Pengguna — Terima Jasa
 *
 * Variables passed from NotificationController:
 * - $notifications : array of \App\Models\Notification
 * - $unreadCount   : int
 * - $activeFilter  : string ('all', 'unread', 'read')
 * - $currentPage   : int
 * - $currentUser   : array
 */
?>

<div class="container-fluid px-0">
    <!-- Page Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h3 class="fw-bold mb-0 text-dark">Pusat Notifikasi</h3>
                <?php if ($unreadCount > 0): ?>
                    <span class="badge bg-danger rounded-pill px-3 py-2 fs-7" id="page-unread-count-badge">
                        <?= $unreadCount ?> Belum Dibaca
                    </span>
                <?php else: ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 fs-7">
                        <i class="bi bi-check2-all me-1"></i> Semua Terbaca
                    </span>
                <?php endif; ?>
            </div>
            <p class="text-muted mb-0 small">
                Pantau setiap aktivitas pesanan, status pengerjaan, dan pembaruan sistem secara langsung.
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <?php if ($unreadCount > 0): ?>
                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" id="page-mark-all-btn">
                    <i class="bi bi-check2-all me-1"></i> Tandai Semua Dibaca
                </button>
            <?php endif; ?>
            <a href="javascript:location.reload()" class="btn btn-light btn-sm rounded-circle border shadow-sm" title="Segarkan Halaman" style="width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center;">
                <i class="bi bi-arrow-clockwise"></i>
            </a>
        </div>
    </div>

    <!-- Filter Nav Tabs -->
    <div class="card border-0 shadow-sm mb-4 rounded-4 overflow-hidden">
        <div class="card-body p-2 bg-white">
            <ul class="nav nav-pills gap-1">
                <li class="nav-item">
                    <a class="nav-link rounded-pill px-3 py-1-5 small <?= ($activeFilter === 'all' || empty($activeFilter)) ? 'active fw-semibold' : 'text-secondary' ?>" 
                       href="<?= url('notifications') ?>">
                        <i class="bi bi-bell me-1"></i> Semua
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link rounded-pill px-3 py-1-5 small <?= $activeFilter === 'unread' ? 'active fw-semibold' : 'text-secondary' ?>" 
                       href="<?= url('notifications?filter=unread') ?>">
                        <i class="bi bi-envelope me-1"></i> Belum Dibaca
                        <?php if ($unreadCount > 0): ?>
                            <span class="badge bg-danger-subtle text-danger ms-1"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link rounded-pill px-3 py-1-5 small <?= $activeFilter === 'read' ? 'active fw-semibold' : 'text-secondary' ?>" 
                       href="<?= url('notifications?filter=read') ?>">
                        <i class="bi bi-envelope-open me-1"></i> Sudah Dibaca
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Notifications List -->
    <?php if (empty($notifications)): ?>
        <div class="card border-0 shadow-sm rounded-4 text-center py-5">
            <div class="card-body py-5">
                <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <i class="bi bi-bell-slash text-muted fs-1"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1">Belum Ada Notifikasi</h5>
                <p class="text-muted small max-w-sm mx-auto mb-4" style="max-width: 360px;">
                    <?php if ($activeFilter === 'unread'): ?>
                        Hebat! Anda tidak memiliki notifikasi baru yang belum dibaca saat ini.
                    <?php elseif ($activeFilter === 'read'): ?>
                        Belum ada notifikasi yang ditandai sebagai sudah dibaca.
                    <?php else: ?>
                        Aktivitas transaksi, pembaruan pesanan, dan informasi sistem akan muncul di sini secara otomatis.
                    <?php endif; ?>
                </p>
                <a href="<?= url() ?>" class="btn btn-primary btn-sm rounded-pill px-4">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Beranda
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="list-group list-group-flush" id="notification-page-list">
                <?php foreach ($notifications as $notif): 
                    $iconConfig = $notif->getIconConfig();
                    $isUnread = !$notif->isRead();
                ?>
                    <div class="list-group-item p-3 p-md-4 border-bottom <?= $isUnread ? 'bg-primary-subtle bg-opacity-10 border-start border-start-4 border-start-primary' : 'bg-white' ?>" 
                         id="notif-row-<?= $notif->id ?>"
                         data-id="<?= $notif->id ?>">
                        <div class="d-flex align-items-start gap-3">
                            <!-- Icon -->
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 <?= $iconConfig['class'] ?>" 
                                 style="width: 44px; height: 44px; font-size: 1.15rem;">
                                <i class="bi <?= $iconConfig['icon'] ?>"></i>
                            </div>

                            <!-- Content -->
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-1 mb-1">
                                    <div class="d-flex align-items-center gap-2">
                                        <h6 class="mb-0 fw-bold <?= $isUnread ? 'text-dark' : 'text-secondary' ?>" style="font-size: 0.95rem;">
                                            <?= e($notif->title) ?>
                                        </h6>
                                        <?php if ($isUnread): ?>
                                            <span class="badge bg-primary rounded-pill" style="font-size: 0.65rem;">Baru</span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-muted small" style="font-size: 0.75rem;">
                                        <i class="bi bi-clock me-1"></i><?= $notif->getTimeAgo() ?>
                                    </span>
                                </div>

                                <p class="text-secondary small mb-2 text-break" style="line-height: 1.45;">
                                    <?= e($notif->message) ?>
                                </p>

                                <div class="d-flex align-items-center gap-2 flex-wrap pt-1">
                                    <?php if ($notif->getActionUrl()): ?>
                                        <a href="<?= e($notif->getActionUrl()) ?>" 
                                           class="btn btn-primary btn-sm rounded-pill px-3 py-1 tj-open-detail-btn"
                                           data-id="<?= $notif->id ?>"
                                           style="font-size: 0.78rem;">
                                            <i class="bi bi-box-arrow-up-right me-1"></i> Buka Rincian
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($isUnread): ?>
                                        <button type="button" 
                                                class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1 tj-mark-read-item-btn" 
                                                data-id="<?= $notif->id ?>"
                                                style="font-size: 0.78rem;">
                                            <i class="bi bi-check2 me-1"></i> Tandai Dibaca
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted small" style="font-size: 0.75rem;">
                                            <i class="bi bi-check2-all text-success me-1"></i> Dibaca: <?= date('d M Y, H:i', strtotime($notif->read_at)) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tombol Tandai Semua Dibaca pada Halaman
    const pageMarkAllBtn = document.getElementById('page-mark-all-btn');
    if (pageMarkAllBtn) {
        pageMarkAllBtn.addEventListener('click', async function() {
            try {
                pageMarkAllBtn.disabled = true;
                pageMarkAllBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';
                
                const response = await fetch('<?= url('api/notifications/read-all') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                const res = await response.json();
                if (res.status === 'success') {
                    location.reload();
                } else {
                    alert(res.message || 'Gagal menandai notifikasi.');
                    pageMarkAllBtn.disabled = false;
                    pageMarkAllBtn.innerHTML = '<i class="bi bi-check2-all me-1"></i> Tandai Semua Dibaca';
                }
            } catch (err) {
                console.error('Error marking all as read:', err);
                pageMarkAllBtn.disabled = false;
                pageMarkAllBtn.innerHTML = '<i class="bi bi-check2-all me-1"></i> Tandai Semua Dibaca';
            }
        });
    }

    // Tombol Tandai Satu Dibaca
    document.querySelectorAll('.tj-mark-read-item-btn').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            const notifId = this.getAttribute('data-id');
            if (!notifId) return;

            try {
                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

                const response = await fetch('<?= url('api/notifications/') ?>' + notifId + '/read', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                const res = await response.json();
                if (res.status === 'success') {
                    // Update tampilan baris
                    const row = document.getElementById('notif-row-' + notifId);
                    if (row) {
                        row.classList.remove('bg-primary-subtle', 'bg-opacity-10', 'border-start', 'border-start-4', 'border-start-primary');
                        row.classList.add('bg-white');
                        const badge = row.querySelector('.badge.bg-primary');
                        if (badge) badge.remove();
                        this.outerHTML = '<span class="text-muted small" style="font-size: 0.75rem;"><i class="bi bi-check2-all text-success me-1"></i> Baru saja dibaca</span>';
                    }
                    if (window.refreshNotificationBadge) {
                        window.refreshNotificationBadge();
                    }
                } else {
                    alert(res.message || 'Gagal menandai notifikasi.');
                    this.disabled = false;
                    this.innerHTML = '<i class="bi bi-check2 me-1"></i> Tandai Dibaca';
                }
            } catch (err) {
                console.error('Error marking as read:', err);
                this.disabled = false;
                this.innerHTML = '<i class="bi bi-check2 me-1"></i> Tandai Dibaca';
            }
        });
    });

    // Otomatis tandai dibaca saat klik tombol "Buka Rincian"
    document.querySelectorAll('.tj-open-detail-btn').forEach(link => {
        link.addEventListener('click', function(e) {
            const notifId = this.getAttribute('data-id');
            if (notifId) {
                fetch('<?= url('api/notifications/') ?>' + notifId + '/read', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                }).catch(() => {});
            }
        });
    });
});
</script>
