<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Dashboard — Terima Jasa') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/chatbot.css') ?>">
</head>
<body>

<?php
$currentUser = $currentUser ?? \App\Core\Auth::user();
$activeRole  = $currentUser['role'] ?? 'customer';

// Map role → accent color class
$roleAccent = match($activeRole) {
    'admin'   => 'danger',
    'teknisi' => 'success',
    default   => 'primary'
};
$roleBadgeClass = "bg-{$roleAccent}-subtle text-{$roleAccent} border border-{$roleAccent}-subtle";
?>

<div class="tj-dashboard-wrapper">

    <!-- ============================================================
         SIDEBAR (Contextual by Role)
         ============================================================ -->
    <aside class="tj-sidebar" id="dashboardSidebar">
        <!-- Brand -->
        <a href="<?= url() ?>" class="tj-sidebar-brand">
            <span class="tj-brand-badge"><i class="bi bi-wrench-adjustable-circle"></i></span>
            <span>Terima<span class="text-primary">Jasa</span></span>
            <button class="btn btn-sm btn-link text-muted ms-auto d-lg-none p-0" id="sidebarCloseBtn">
                <i class="bi bi-x-lg fs-5"></i>
            </button>
        </a>

        <div class="tj-sidebar-nav">

            <!-- ─── CUSTOMER MENU ─── -->
            <?php if ($activeRole === 'customer'): ?>
                <div class="tj-sidebar-heading">Menu Customer</div>
                <a href="<?= url('customer/dashboard') ?>" class="tj-sidebar-link <?= is_active('customer/dashboard') ?>">
                    <i class="bi bi-speedometer2"></i> Overview Dashboard
                </a>
                <a href="<?= url('customer/services') ?>" class="tj-sidebar-link <?= is_active('customer/services') ?>">
                    <i class="bi bi-search"></i> Katalog & Cari Jasa
                </a>
                <a href="<?= url('customer/orders') ?>" class="tj-sidebar-link <?= is_active('customer/orders') ?>">
                    <i class="bi bi-calendar2-check"></i> Pesanan Saya
                </a>
                <a href="<?= url('customer/orders/create') ?>" class="tj-sidebar-link <?= is_active('customer/orders/create') ?>">
                    <i class="bi bi-plus-circle"></i> Buat Pesanan Baru
                </a>
                <a href="<?= url('customer/ai-assistant') ?>" class="tj-sidebar-link <?= is_active('customer/ai-assistant') ?>">
                    <i class="bi bi-robot text-info"></i> Konsultasi AI
                </a>

            <!-- ─── TEKNISI MENU ─── -->
            <?php elseif ($activeRole === 'teknisi'): ?>
                <div class="tj-sidebar-heading">Menu Mitra Teknisi</div>
                <a href="<?= url('technician/dashboard') ?>" class="tj-sidebar-link <?= is_active('technician/dashboard') ?>">
                    <i class="bi bi-speedometer2"></i> Overview Dashboard
                </a>
                <a href="<?= url('technician/requests') ?>" class="tj-sidebar-link <?= is_active('technician/requests') ?>">
                    <i class="bi bi-inbox-fill"></i> Permintaan Jasa
                </a>
                <a href="<?= url('technician/active-jobs') ?>" class="tj-sidebar-link <?= is_active('technician/active-jobs') ?>">
                    <i class="bi bi-tools"></i> Pekerjaan Aktif
                </a>
                <a href="<?= url('technician/history') ?>" class="tj-sidebar-link <?= is_active('technician/history') ?>">
                    <i class="bi bi-clock-history"></i> Riwayat Selesai
                </a>
                <a href="<?= url('technician/reviews') ?>" class="tj-sidebar-link <?= is_active('technician/reviews') ?>">
                    <i class="bi bi-star"></i> Rating & Ulasan
                </a>
                <a href="<?= url('technician/profile') ?>" class="tj-sidebar-link <?= is_active('technician/profile') ?>">
                    <i class="bi bi-person-badge"></i> Profil & Ketersediaan
                </a>
                <a href="<?= url('technician/ai-assistant') ?>" class="tj-sidebar-link <?= is_active('technician/ai-assistant') ?>">
                    <i class="bi bi-robot text-success"></i> Asisten AI Teknisi
                </a>

            <!-- ─── ADMIN MENU ─── -->
            <?php elseif ($activeRole === 'admin'): ?>
                <div class="tj-sidebar-heading">Menu Administrator</div>
                <a href="<?= url('admin/dashboard') ?>" class="tj-sidebar-link <?= is_active('admin/dashboard') ?>">
                    <i class="bi bi-speedometer2"></i> Overview Dashboard
                </a>
                <a href="<?= url('admin/customers') ?>" class="tj-sidebar-link <?= is_active('admin/customers') ?>">
                    <i class="bi bi-people-fill"></i> Kelola Customer
                </a>
                <a href="<?= url('admin/technicians') ?>" class="tj-sidebar-link <?= is_active('admin/technicians') ?>">
                    <i class="bi bi-patch-check"></i> Verifikasi & Teknisi
                </a>
                <a href="<?= url('admin/categories') ?>" class="tj-sidebar-link <?= is_active('admin/categories') ?>">
                    <i class="bi bi-tags"></i> Kategori Jasa
                </a>
                <a href="<?= url('admin/services') ?>" class="tj-sidebar-link <?= is_active('admin/services') ?>">
                    <i class="bi bi-grid-3x3-gap"></i> Katalog Layanan
                </a>
                <a href="<?= url('admin/orders') ?>" class="tj-sidebar-link <?= is_active('admin/orders') ?>">
                    <i class="bi bi-receipt-cutoff"></i> Kelola Order
                </a>
                <a href="<?= url('admin/reports') ?>" class="tj-sidebar-link <?= is_active('admin/reports') ?>">
                    <i class="bi bi-graph-up"></i> Laporan & Statistik
                </a>
                <a href="<?= url('admin/ai-assistant') ?>" class="tj-sidebar-link <?= is_active('admin/ai-assistant') ?>">
                    <i class="bi bi-robot text-danger"></i> AI Copilot Admin
                </a>
                <a href="<?= url('admin/ai-settings') ?>" class="tj-sidebar-link <?= is_active('admin/ai-settings') ?>">
                    <i class="bi bi-cpu text-warning"></i> Konfigurasi AI
                </a>
                <a href="<?= url('admin/whatsapp') ?>" class="tj-sidebar-link <?= is_active('admin/whatsapp') ?>">
                    <i class="bi bi-whatsapp text-success"></i> WhatsApp Gateway & OTP
                </a>
                <a href="<?= url('admin/profile') ?>" class="tj-sidebar-link <?= is_active('admin/profile') ?>">
                    <i class="bi bi-person-gear text-primary"></i> Profil Administrator
                </a>
            <?php endif; ?>

            <!-- ─── ACCOUNT SECTION ─── -->
            <div class="tj-sidebar-heading mt-4">Akun & Sistem</div>
            <a href="<?= url('notifications') ?>" class="tj-sidebar-link <?= is_active('notifications') ?>">
                <i class="bi bi-bell"></i> Notifikasi
                <span class="badge bg-danger rounded-pill ms-auto d-none" id="tj-sidebar-notif-badge">0</span>
            </a>
            <a href="<?= url() ?>" class="tj-sidebar-link">
                <i class="bi bi-box-arrow-up-right"></i> Kembali ke Beranda
            </a>
            <a href="<?= url('logout') ?>" class="tj-sidebar-link text-danger">
                <i class="bi bi-box-arrow-right"></i> Keluar (Logout)
            </a>
        </div>

        <!-- User mini profile at bottom -->
        <div class="p-3 border-top bg-light">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                     style="width:38px;height:38px;background:linear-gradient(135deg,#1d4ed8,#06b6d4);font-size:.95rem;flex-shrink:0;">
                    <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="overflow-hidden">
                    <div class="fw-bold small text-truncate"><?= e($currentUser['name'] ?? 'Pengguna') ?></div>
                    <div class="text-muted" style="font-size:.72rem;"><?= e($currentUser['badge'] ?? ucfirst($activeRole)) ?></div>
                </div>
            </div>
        </div>
    </aside>

    <!-- ============================================================
         MAIN PANEL
         ============================================================ -->
    <div class="tj-dashboard-main">

        <!-- TOP NAV BAR -->
        <header class="tj-dashboard-header">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggleBtn">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div>
                    <h5 class="mb-0 fw-bold">Dashboard Panel</h5>
                    <small class="text-muted">Kelola akun dan aktivitas Anda</small>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <!-- AI pill -->
                <div class="tj-ai-pill d-none d-md-inline-flex" style="cursor:pointer;" onclick="if(window.openChatbotWidget){window.openChatbotWidget();}" title="Buka AI Copilot">
                    <span class="pulse-dot"></span> AI Copilot Ready
                </div>

                <!-- Role badge -->
                <span class="badge rounded-pill <?= $roleBadgeClass ?> px-3 py-2 fw-semibold">
                    <i class="bi bi-shield-check me-1"></i> <?= ucfirst($activeRole) ?>
                </span>

                <!-- Notification Bell Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-light position-relative rounded-circle p-2 border d-flex align-items-center justify-content-center shadow-sm"
                            type="button" id="tjNotifDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false" style="width: 38px; height: 38px;" title="Notifikasi Aktivitas">
                        <i class="bi bi-bell-fill text-secondary fs-6"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none shadow-sm" id="tj-notif-badge" style="font-size: 0.65rem; padding: 0.25em 0.5em;">
                            0
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-0 mt-2" aria-labelledby="tjNotifDropdownBtn" style="width: 340px; max-width: 90vw; border-radius: 12px; overflow: hidden; z-index: 1060;">
                        <div class="p-3 bg-light border-bottom d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <h6 class="mb-0 fw-bold fs-6">Notifikasi</h6>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill small" id="tj-notif-count-label">0 baru</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-link text-primary text-decoration-none p-0 fw-semibold" id="tj-mark-all-read-btn" style="font-size: 0.78rem;">
                                Tandai Semua Dibaca
                            </button>
                        </div>
                        <div class="tj-notif-scroll-list overflow-auto" id="tj-notif-items-container" style="max-height: 350px;">
                            <div class="p-4 text-center text-muted" id="tj-notif-loading">
                                <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
                                <div class="small">Memuat notifikasi...</div>
                            </div>
                        </div>
                        <div class="p-2 border-top text-center bg-light">
                            <a href="<?= url('notifications') ?>" class="text-decoration-none small fw-semibold text-primary d-block py-1">
                                Lihat Semua Notifikasi <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Profile dropdown -->
                <div class="dropdown">
                    <button class="btn btn-light rounded-pill p-1 pe-3 d-flex align-items-center gap-2 border"
                            type="button" data-bs-toggle="dropdown">
                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                             style="width:34px;height:34px;background:linear-gradient(135deg,#1d4ed8,#06b6d4);font-size:.85rem;flex-shrink:0;">
                            <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
                        </div>
                        <span class="fw-semibold small d-none d-sm-inline"><?= e($currentUser['name'] ?? 'Pengguna') ?></span>
                        <i class="bi bi-chevron-down small text-muted"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                        <li class="px-3 py-2 border-bottom">
                            <small class="text-muted d-block">Masuk sebagai</small>
                            <strong class="small"><?= e($currentUser['email'] ?? '') ?></strong>
                            <br>
                            <span class="badge <?= $roleBadgeClass ?> rounded-pill mt-1"><?= ucfirst($activeRole) ?></span>
                        </li>
                        <?php if ($activeRole === 'admin'): ?>
                            <li><a class="dropdown-item py-2" href="<?= url('admin/profile') ?>">
                                <i class="bi bi-person-gear me-2 text-danger"></i> Profil Saya
                            </a></li>
                            <li><hr class="dropdown-divider my-1"></li>
                        <?php elseif ($activeRole === 'teknisi'): ?>
                            <li><a class="dropdown-item py-2" href="<?= url('technician/profile') ?>">
                                <i class="bi bi-person-gear me-2 text-success"></i> Profil & Ketersediaan
                            </a></li>
                            <li><hr class="dropdown-divider my-1"></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item py-2" href="<?= url('logout') ?>">
                            <i class="bi bi-box-arrow-right me-2 text-danger"></i> Keluar
                        </a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- CONTENT -->
        <div class="tj-dashboard-content">

            <!-- Flash Messages -->
            <?php foreach (['flash_success' => 'success', 'flash_error' => 'danger', 'flash_info' => 'info', 'flash_warning' => 'warning'] as $key => $type): ?>
                <?php if (!empty($_SESSION[$key])): ?>
                    <div class="alert alert-<?= $type ?> alert-dismissible fade show shadow-sm mb-4" role="alert">
                        <i class="bi bi-<?= $type === 'success' ? 'check-circle-fill' : ($type === 'danger' ? 'exclamation-triangle-fill' : 'info-circle-fill') ?> me-2"></i>
                        <?= e($_SESSION[$key]) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION[$key]); ?>
                <?php endif; ?>
            <?php endforeach; ?>

            <?= $content ?? '' ?>
        </div>
    </div><!-- /.tj-dashboard-main -->

</div><!-- /.tj-dashboard-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/dashboard.js') ?>"></script>

<!-- Multi-Role AI Chatbot Widget -->
<script>
    window.APP_BASE_URL = '<?= rtrim(base_url(), '/') ?>';
    window.CURRENT_USER_ID = '<?= (int)($currentUser['id'] ?? 0) ?>';
    window.CURRENT_USER_ROLE = '<?= $activeRole ?>';
    window.CURRENT_USER_NAME = '<?= addslashes(e($currentUser['name'] ?? '')) ?>';
</script>
<script src="<?= asset('js/chatbot.js') ?>"></script>
<script src="<?= asset('js/notifications.js') ?>"></script>
</body>
</html>
