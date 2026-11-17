<aside class="tj-sidebar" id="dashboardSidebar">
    <!-- Brand -->
    <a href="<?= url() ?>" class="tj-sidebar-brand">
        <span class="tj-brand-badge">
            <i class="bi bi-wrench-adjustable-circle"></i>
        </span>
        <span>Terima<span class="text-primary">Jasa</span></span>
        <button class="btn btn-sm btn-link text-muted ms-auto d-lg-none" id="sidebarCloseBtn">
            <i class="bi bi-x-lg"></i>
        </button>
    </a>

    <!-- Navigation links -->
    <div class="tj-sidebar-nav">
        <div class="tj-sidebar-heading">Menu Utama</div>
        <a href="<?= url('dashboard') ?>" class="tj-sidebar-link active">
            <i class="bi bi-speedometer2"></i>
            <span>Overview Dashboard</span>
        </a>

        <?php if (($activeRole ?? 'customer') === 'customer'): ?>
            <!-- Customer Specific Menus -->
            <div class="tj-sidebar-heading mt-3">Layanan Saya</div>
            <a href="#" class="tj-sidebar-link">
                <i class="bi bi-calendar2-check"></i>
                <span>Pesanan Servis Aktif</span>
            </a>
            <a href="#" class="tj-sidebar-link">
                <i class="bi bi-clock-history"></i>
                <span>Riwayat Servis</span>
            </a>
            <a href="#" class="tj-sidebar-link">
                <i class="bi bi-heart"></i>
                <span>Teknisi Favorit</span>
            </a>
            <a href="<?= url('customer/ai-assistant') ?>" class="tj-sidebar-link <?= is_active('customer/ai-assistant') ?>">
                <i class="bi bi-robot text-info"></i>
                <span>Konsultasi & Diagnosa AI</span>
            </a>

        <?php elseif (($activeRole ?? 'customer') === 'teknisi'): ?>
            <!-- Teknisi Specific Menus -->
            <div class="tj-sidebar-heading mt-3">Pekerjaan & Order</div>
            <a href="#" class="tj-sidebar-link">
                <i class="bi bi-inbox-fill"></i>
                <span>Order Masuk <span class="badge bg-primary ms-auto">4 Baru</span></span>
            </a>
            <a href="#" class="tj-sidebar-link">
                <i class="bi bi-tools"></i>
                <span>Jadwal Pengerjaan</span>
            </a>
            <a href="#" class="tj-sidebar-link">
                <i class="bi bi-wallet2"></i>
                <span>Pendapatan & Saldo</span>
            </a>
            <a href="#" class="tj-sidebar-link">
                <i class="bi bi-star"></i>
                <span>Ulasan & Reputasi</span>
            </a>

        <?php elseif (($activeRole ?? 'customer') === 'admin'): ?>
            <!-- Admin Specific Menus -->
            <div class="tj-sidebar-heading mt-3">Manajemen Platform</div>
            <a href="<?= url('admin/customers') ?>" class="tj-sidebar-link <?= is_active('admin/customers') ?>">
                <i class="bi bi-people-fill"></i>
                <span>Kelola Customer</span>
            </a>
            <a href="<?= url('admin/technicians') ?>" class="tj-sidebar-link <?= is_active('admin/technicians') ?>">
                <i class="bi bi-patch-check"></i>
                <span>Verifikasi & Teknisi</span>
            </a>
            <a href="<?= url('admin/categories') ?>" class="tj-sidebar-link <?= is_active('admin/categories') ?>">
                <i class="bi bi-tags"></i>
                <span>Katalog Kategori</span>
            </a>
            <a href="<?= url('admin/services') ?>" class="tj-sidebar-link <?= is_active('admin/services') ?>">
                <i class="bi bi-grid-3x3-gap"></i>
                <span>Katalog Layanan</span>
            </a>
            <a href="<?= url('admin/orders') ?>" class="tj-sidebar-link <?= is_active('admin/orders') ?>">
                <i class="bi bi-receipt-cutoff"></i>
                <span>Kelola Order</span>
            </a>
            <a href="<?= url('admin/reports') ?>" class="tj-sidebar-link <?= is_active('admin/reports') ?>">
                <i class="bi bi-graph-up"></i>
                <span>Laporan & Analitik</span>
            </a>
        <?php endif; ?>

        <!-- Switch Role Quick Menu -->
        <div class="tj-sidebar-heading mt-4">Simulasi Peran (Role)</div>
        <a href="<?= url('dashboard?role=customer') ?>" class="tj-sidebar-link <?= ($activeRole ?? '') === 'customer' ? 'text-primary fw-bold' : '' ?>">
            <i class="bi bi-person-circle"></i>
            <span>Role: Customer</span>
        </a>
        <a href="<?= url('dashboard?role=teknisi') ?>" class="tj-sidebar-link <?= ($activeRole ?? '') === 'teknisi' ? 'text-success fw-bold' : '' ?>">
            <i class="bi bi-tools"></i>
            <span>Role: Teknisi</span>
        </a>
        <a href="<?= url('dashboard?role=admin') ?>" class="tj-sidebar-link <?= ($activeRole ?? '') === 'admin' ? 'text-danger fw-bold' : '' ?>">
            <i class="bi bi-shield-lock"></i>
            <span>Role: Admin</span>
        </a>

        <!-- General Links -->
        <div class="tj-sidebar-heading mt-4">Akun & Sistem</div>
        <a href="<?= url() ?>" class="tj-sidebar-link">
            <i class="bi bi-box-arrow-up-right"></i>
            <span>Kembali ke Landing</span>
        </a>
        <a href="<?= url('logout') ?>" class="tj-sidebar-link text-danger">
            <i class="bi bi-box-arrow-right"></i>
            <span>Keluar (Logout)</span>
        </a>
    </div>

    <!-- User Mini Profile Footer -->
    <div class="p-3 border-top bg-light">
        <div class="d-flex align-items-center gap-2">
            <img src="<?= e($currentUser['avatar'] ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150') ?>" alt="User Avatar" class="rounded-circle" width="38" height="38" style="object-fit: cover;">
            <div class="overflow-hidden">
                <div class="fw-bold small text-truncate"><?= e($currentUser['name'] ?? 'Pengguna') ?></div>
                <div class="text-muted small" style="font-size: 0.75rem;"><?= e($currentUser['badge'] ?? ucfirst($activeRole ?? 'customer')) ?></div>
            </div>
        </div>
    </div>
</aside>
