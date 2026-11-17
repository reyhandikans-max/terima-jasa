<!-- ========================================================
     ROLE QUICK SWITCHER BAR (Simulasi 3 Role)
     ======================================================== -->
<div class="tj-role-switcher-bar">
    <div class="d-flex align-items-center gap-2">
        <div class="bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
            <i class="bi bi-person-gear"></i>
        </div>
        <div>
            <div class="fw-bold text-dark small">Simulasi Tampilan Role: <span class="badge bg-primary ms-1"><?= ucfirst($activeRole) ?></span></div>
            <div class="text-muted" style="font-size: 0.78rem;">
                Ganti peran dengan cepat untuk melihat pengalaman antarmuka Customer, Teknisi, atau Admin.
            </div>
        </div>
    </div>

    <!-- Quick Role Switch Buttons -->
    <div class="btn-group shadow-sm" role="group">
        <a href="<?= url('dashboard?role=customer') ?>" class="btn btn-sm <?= $activeRole === 'customer' ? 'btn-primary' : 'btn-outline-primary bg-white' ?>">
            <i class="bi bi-person me-1"></i> Customer
        </a>
        <a href="<?= url('dashboard?role=teknisi') ?>" class="btn btn-sm <?= $activeRole === 'teknisi' ? 'btn-success' : 'btn-outline-success bg-white' ?>">
            <i class="bi bi-tools me-1"></i> Teknisi
        </a>
        <a href="<?= url('dashboard?role=admin') ?>" class="btn btn-sm <?= $activeRole === 'admin' ? 'btn-danger' : 'btn-outline-danger bg-white' ?>">
            <i class="bi bi-shield-lock me-1"></i> Admin
        </a>
    </div>
</div>

<!-- ========================================================
     GREETING & SUMMARY HEADER
     ======================================================== -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1">
            Halo, <?= e($currentUser['name']) ?> 👋
        </h3>
        <p class="text-muted small mb-0">
            Berikut ringkasan aktivitas pada akun Anda per hari ini, <?= date('d F Y') ?>.
        </p>
    </div>

    <div class="d-flex gap-2">
        <?php if ($activeRole === 'customer'): ?>
            <a href="<?= url('#kategori-jasa') ?>" class="btn btn-primary-tj btn-sm d-flex align-items-center gap-2">
                <i class="bi bi-plus-circle"></i>
                <span>Pesan Servis Baru</span>
            </a>
        <?php elseif ($activeRole === 'teknisi'): ?>
            <button class="btn btn-success btn-sm d-flex align-items-center gap-2">
                <i class="bi bi-broadcast"></i>
                <span>Status: Siap Menerima Order</span>
            </button>
        <?php elseif ($activeRole === 'admin'): ?>
            <button class="btn btn-danger btn-sm d-flex align-items-center gap-2">
                <i class="bi bi-download"></i>
                <span>Export Laporan Sistem</span>
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- ========================================================
     KPI STATS CARDS
     ======================================================== -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6 col-12">
        <div class="tj-stat-card">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="text-muted small fw-semibold"><?= e($stats['primary_title']) ?></span>
                <div class="tj-stat-icon bg-primary-subtle text-primary">
                    <i class="bi bi-activity"></i>
                </div>
            </div>
            <h4 class="fw-bold mb-1"><?= e($stats['primary_value']) ?></h4>
            <div class="text-success small fw-semibold">
                <i class="bi bi-arrow-up-right me-1"></i> Terpantau real-time
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 col-12">
        <div class="tj-stat-card">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="text-muted small fw-semibold"><?= e($stats['secondary_title']) ?></span>
                <div class="tj-stat-icon bg-success-subtle text-success">
                    <i class="bi bi-check2-circle"></i>
                </div>
            </div>
            <h4 class="fw-bold mb-1"><?= e($stats['secondary_value']) ?></h4>
            <div class="text-muted small">
                <i class="bi bi-clock-history me-1"></i> Terverifikasi platform
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 col-12">
        <div class="tj-stat-card">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="text-muted small fw-semibold"><?= e($stats['accent_title']) ?></span>
                <div class="tj-stat-icon bg-info-subtle text-info">
                    <i class="bi bi-robot"></i>
                </div>
            </div>
            <h4 class="fw-bold mb-1"><?= e($stats['accent_value']) ?></h4>
            <div class="text-info small fw-semibold">
                <i class="bi bi-stars me-1"></i> Modul Pintar
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 col-12">
        <div class="tj-stat-card">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="text-muted small fw-semibold"><?= e($stats['metric_title']) ?></span>
                <div class="tj-stat-icon bg-warning-subtle text-warning-emphasis">
                    <i class="bi bi-award"></i>
                </div>
            </div>
            <h4 class="fw-bold mb-1"><?= e($stats['metric_value']) ?></h4>
            <div class="text-muted small">
                <i class="bi bi-shield-check me-1"></i> Standar Kualitas
            </div>
        </div>
    </div>
</div>

<!-- ========================================================
     AI SERVICE ARCHITECTURE STUB CARD
     ======================================================== -->
<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #090e17 0%, #1e293b 100%);">
    <div class="card-body p-4 text-white">
        <div class="row align-items-center g-3">
            <div class="col-lg-8">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-info text-dark fw-bold rounded-pill">
                        <i class="bi bi-cpu-fill me-1"></i> AI Service Module
                    </span>
                    <span class="small text-light opacity-75">Arsitektur Terpisah (Bukan Role Pengguna)</span>
                </div>
                <h5 class="fw-bold text-white mb-2">
                    Pondasi Integrasi Asisten AI Siap Terhubung
                </h5>
                <p class="text-light opacity-75 small mb-0" style="max-width: 680px;">
                    Sesuai arsitektur sistem, modul <strong>App\Services\AiService</strong> disiapkan secara decoupled. Di tahap integrasi berikutnya, modul ini akan menangani automatic issue diagnosis, cost prediction, serta rekomendasi teknisi terbaik berbasis semantic matching.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="d-inline-flex flex-column align-items-lg-end">
                    <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill small mb-2">
                        <i class="bi bi-check-circle-fill me-1"></i> Class: App\Services\AiService
                    </span>
                    <span class="text-muted small" style="font-size: 0.78rem;">Status: Service Contract Prepared</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================
     RECENT ORDERS & ACTIVITY TABLE (PLACEHOLDER)
     ======================================================== -->
<div class="tj-card-table mb-4">
    <div class="tj-card-header">
        <div>
            <h5 class="fw-bold mb-0">Aktivitas & Pesanan Servis Terbaru</h5>
            <small class="text-muted">Data simulasi pesanan pada ekosistem Terima Jasa</small>
        </div>
        <span class="badge bg-light text-dark border">3 Transaksi Terbaru</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                    <th class="ps-4">No. Order</th>
                    <th>Nama Layanan</th>
                    <th>Customer / Teknisi</th>
                    <th>Kategori</th>
                    <th>Waktu Pengerjaan</th>
                    <th>Status</th>
                    <th>Estimasi Biaya</th>
                    <th class="pe-4 text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td class="ps-4">
                            <span class="fw-bold small text-primary font-monospace"><?= e($order['code']) ?></span>
                        </td>
                        <td>
                            <div class="fw-semibold small"><?= e($order['service']) ?></div>
                            <small class="text-muted" style="font-size: 0.75rem;">
                                <i class="bi bi-robot text-info me-1"></i> AI Diag: <?= e($order['ai_diag']) ?>
                            </small>
                        </td>
                        <td>
                            <div class="small fw-semibold"><?= e($order['customer']) ?></div>
                            <small class="text-muted">Teknisi: <?= e($order['technician']) ?></small>
                        </td>
                        <td>
                            <span class="badge bg-light text-secondary border small"><?= e($order['category']) ?></span>
                        </td>
                        <td class="small text-muted"><?= e($order['date']) ?></td>
                        <td>
                            <span class="badge <?= e($order['status_badge']) ?> rounded-pill small px-2 py-1">
                                <?= e($order['status']) ?>
                            </span>
                        </td>
                        <td class="fw-bold small text-dark"><?= e($order['total']) ?></td>
                        <td class="pe-4 text-end">
                            <button class="btn btn-sm btn-outline-secondary py-1 px-2" title="Detail Pesanan">
                                <i class="bi bi-eye"></i> Detail
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ========================================================
     BOTTOM ROW: QUICK SHORTCUTS & CATEGORY DIRECTORY
     ======================================================== -->
<div class="row g-4">
    <div class="col-lg-6 col-12">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
            <h5 class="fw-bold mb-3"><i class="bi bi-grid-3x3-gap-fill text-primary me-2"></i> Kategori Servis Terkait</h5>
            <div class="row g-2">
                <?php foreach (array_slice($categories, 0, 4) as $cat): ?>
                    <div class="col-6">
                        <div class="p-3 rounded-3 border bg-light d-flex align-items-center gap-3">
                            <div class="tj-cat-icon mb-0" style="width: 40px; height: 40px; font-size: 1.2rem; background-color: <?= e($cat['bg_light']) ?>; color: <?= e($cat['color']) ?>;">
                                <i class="bi <?= e($cat['icon']) ?>"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="fw-bold small text-truncate"><?= e($cat['name']) ?></div>
                                <div class="text-muted small" style="font-size: 0.72rem;"><?= e($cat['technicians']) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6 col-12">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
            <h5 class="fw-bold mb-3"><i class="bi bi-shield-check text-success me-2"></i> Keamanan & Standar Pelayanan</h5>
            <ul class="list-unstyled d-flex flex-column gap-3 mb-0">
                <li class="d-flex align-items-start gap-3">
                    <i class="bi bi-patch-check-fill text-primary fs-5 mt-1"></i>
                    <div>
                        <strong class="d-block small">Garansi Servis Terjamin</strong>
                        <span class="text-muted small">Semua pengerjaan dilindungi garansi 30 hari langsung dari sistem Terima Jasa.</span>
                    </div>
                </li>
                <li class="d-flex align-items-start gap-3">
                    <i class="bi bi-telephone-inbound-fill text-success fs-5 mt-1"></i>
                    <div>
                        <strong class="d-block small">Bantuan Call Center 24/7</strong>
                        <span class="text-muted small">Tim operasional siap memandu konsultasi atau eskalasi kendala setiap saat.</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</div>
