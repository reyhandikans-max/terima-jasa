<!-- ============================================================
     CUSTOMER DASHBOARD VIEW — TERIMA JASA
     ============================================================ -->
<?php
$activeOrders    = array_filter($orders ?? [], fn($o) => in_array($o->status, ['pending','accepted','in_progress']));
$completedOrders = array_filter($orders ?? [], fn($o) => $o->status === 'completed');
?>

<!-- Header Sambutan & Action -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1">Halo, <?= e($currentUser['name']) ?> 👋</h4>
        <p class="text-muted small mb-0">
            Selamat datang di Dashboard Customer Terima Jasa — Kelola pesanan dan kebutuhan teknisi Anda.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('customer/services') ?>" class="btn btn-outline-tj btn-sm d-inline-flex align-items-center gap-2 shadow-sm">
            <i class="bi bi-search"></i> Cari Jasa
        </a>
        <a href="<?= url('customer/orders/create') ?>" class="btn btn-primary-tj btn-sm d-inline-flex align-items-center gap-2 shadow-sm">
            <i class="bi bi-plus-circle-fill"></i> Buat Pesanan Baru
        </a>
    </div>
</div>

<!-- ============================================================
     SHORTCUT UTAMA: CARI JASA & TANYA AI
     ============================================================ -->
<div class="row g-3 mb-4">
    <!-- Shortcut 1: Cari Jasa -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 position-relative overflow-hidden" 
             style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); color: white;">
            <div class="d-flex align-items-start justify-content-between position-relative" style="z-index: 2;">
                <div>
                    <span class="badge bg-white bg-opacity-25 text-white mb-2 px-3 py-1 rounded-pill small">
                        <i class="bi bi-tools me-1"></i> Solusi Cepat
                    </span>
                    <h5 class="fw-bold mb-2 text-white">Eksplorasi Katalog Jasa</h5>
                    <p class="text-light text-opacity-75 small mb-3" style="max-width: 320px;">
                        Pilih kategori, cari layanan spesifik, dan temukan teknisi profesional terverifikasi untuk kebutuhan Anda.
                    </p>
                    <a href="<?= url('customer/services') ?>" class="btn btn-light text-primary btn-sm rounded-pill px-4 fw-semibold shadow-sm">
                        <i class="bi bi-search me-1"></i> Buka Katalog Jasa
                    </a>
                </div>
                <div class="rounded-circle d-none d-sm-flex align-items-center justify-content-center bg-white bg-opacity-10 text-white"
                     style="width: 64px; height: 64px; font-size: 2rem;">
                    <i class="bi bi-search-heart"></i>
                </div>
            </div>
            <!-- Decorative circle -->
            <div class="position-absolute end-0 bottom-0 translate-middle-y rounded-circle bg-white opacity-10" 
                 style="width: 160px; height: 160px; pointer-events: none;"></div>
        </div>
    </div>

    <!-- Shortcut 2: Tanya AI -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 position-relative overflow-hidden"
             style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: white;">
            <div class="d-flex align-items-start justify-content-between position-relative" style="z-index: 2;">
                <div>
                    <span class="badge bg-info bg-opacity-25 text-info mb-2 px-3 py-1 rounded-pill small">
                        <span class="pulse-dot me-1"></span> AI Diagnostic Assistant
                    </span>
                    <h5 class="fw-bold mb-2 text-white">Konsultasi Kendala Servis</h5>
                    <p class="text-light text-opacity-75 small mb-3" style="max-width: 320px;">
                        Bingung kerusakan alat rumah Anda? Tanyakan langsung ke AI untuk diagnosa cepat dan rekomendasi estimasi biaya.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-info text-dark btn-sm rounded-pill px-3 fw-semibold shadow-sm"
                                onclick="if(window.openChatbotWidget){window.openChatbotWidget();}else{alert('AI Assistant siap membantu Anda di widget bawah layar!');}">
                            <i class="bi bi-robot me-1"></i> Buka Chat AI
                        </button>
                        <a href="<?= url('customer/ai-assistant') ?>" class="btn btn-outline-light btn-sm rounded-pill px-3">
                            <i class="bi bi-cpu-fill me-1"></i> Diagnosa Lengkap
                        </a>
                    </div>
                </div>
                <div class="rounded-circle d-none d-sm-flex align-items-center justify-content-center bg-info bg-opacity-10 text-info"
                     style="width: 64px; height: 64px; font-size: 2rem;">
                    <i class="bi bi-cpu"></i>
                </div>
            </div>
            <!-- Decorative circle -->
            <div class="position-absolute end-0 bottom-0 translate-middle-y rounded-circle bg-info opacity-10" 
                 style="width: 160px; height: 160px; pointer-events: none;"></div>
        </div>
    </div>
</div>

<!-- ============================================================
     STATISTIK UTAMA (METRICS / KPI)
     ============================================================ -->
<div class="row g-3 mb-4">
    <!-- Total Pesanan -->
    <div class="col-xl-3 col-sm-6">
        <div class="tj-stat-card">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="text-muted small fw-semibold">Total Pesanan</span>
                <div class="tj-stat-icon bg-primary-subtle text-primary"><i class="bi bi-receipt"></i></div>
            </div>
            <h3 class="fw-bold mb-1"><?= $stats['total_orders'] ?? count($orders ?? []) ?></h3>
            <small class="text-muted">Semua riwayat pesanan Anda</small>
        </div>
    </div>

    <!-- Pesanan Aktif -->
    <div class="col-xl-3 col-sm-6">
        <div class="tj-stat-card border-warning border-opacity-25">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="text-muted small fw-semibold">Pesanan Aktif</span>
                <div class="tj-stat-icon bg-warning-subtle text-warning-emphasis"><i class="bi bi-hourglass-split"></i></div>
            </div>
            <h3 class="fw-bold mb-1 text-warning-emphasis"><?= $stats['active_orders'] ?? count($activeOrders) ?></h3>
            <small class="text-warning-emphasis fw-semibold">Dalam proses pengerjaan</small>
        </div>
    </div>

    <!-- Pesanan Selesai -->
    <div class="col-xl-3 col-sm-6">
        <div class="tj-stat-card border-success border-opacity-25">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="text-muted small fw-semibold">Pesanan Selesai</span>
                <div class="tj-stat-icon bg-success-subtle text-success"><i class="bi bi-check-circle-fill"></i></div>
            </div>
            <h3 class="fw-bold mb-1 text-success"><?= $stats['completed_orders'] ?? count($completedOrders) ?></h3>
            <small class="text-success fw-semibold">Layanan tuntas terselesaikan</small>
        </div>
    </div>

    <!-- Riwayat AI Chat -->
    <div class="col-xl-3 col-sm-6">
        <div class="tj-stat-card">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="text-muted small fw-semibold">Diagnosa AI</span>
                <div class="tj-stat-icon bg-info-subtle text-info"><i class="bi bi-chat-quote-fill"></i></div>
            </div>
            <h3 class="fw-bold mb-1"><?= $stats['total_ai_chats'] ?? count($aiChats ?? []) ?></h3>
            <small class="text-info fw-semibold">Sesi konsultasi pintar</small>
        </div>
    </div>
</div>

<!-- ============================================================
     KATEGORI JASA CEPAT & PESANAN AKTIF BERJALAN
     ============================================================ -->
<div class="row g-4 mb-4">
    <!-- Kategori Jasa Cepat -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-grid-fill text-primary me-2"></i>Pilihan Kategori Jasa
                </h6>
                <a href="<?= url('customer/services') ?>" class="small text-primary text-decoration-none fw-semibold">
                    Lihat Semua &rarr;
                </a>
            </div>

            <div class="d-flex flex-column gap-2">
                <?php foreach (array_slice($categories ?? [], 0, 5) as $cat): ?>
                    <a href="<?= url('customer/services?category_id=' . $cat->id) ?>" 
                       class="d-flex align-items-center justify-content-between p-2 rounded-3 border text-decoration-none text-dark bg-white hover-shadow"
                       style="transition: all .2s ease;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center text-primary"
                                 style="width: 38px; height: 38px; background: #eff6ff;">
                                <i class="bi <?= e($cat->icon ?? 'bi-tools') ?> fs-5"></i>
                            </div>
                            <div>
                                <span class="fw-semibold small d-block"><?= e($cat->name) ?></span>
                                <small class="text-muted" style="font-size: .75rem;"><?= count($cat->services()) ?> layanan tersedia</small>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted small"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Pesanan Aktif / Status Terkini -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-clock-history text-primary me-2"></i>Pesanan Aktif Terbaru
                </h6>
                <a href="<?= url('customer/orders') ?>" class="small text-primary text-decoration-none fw-semibold">
                    Semua Pesanan &rarr;
                </a>
            </div>

            <?php if (empty($activeOrders)): ?>
                <div class="text-center py-4 my-auto">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center bg-light text-muted mb-3"
                         style="width: 56px; height: 56px;">
                        <i class="bi bi-calendar-check fs-4"></i>
                    </div>
                    <p class="text-muted small mb-3">Saat ini tidak ada pesanan aktif yang sedang berjalan.</p>
                    <a href="<?= url('customer/orders/create') ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                        <i class="bi bi-plus-circle me-1"></i> Pesan Jasa Sekarang
                    </a>
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach (array_slice($activeOrders, 0, 3) as $actOrder): ?>
                        <?php 
                            $actSrv = $actOrder->service(); 
                            $badgeInfo = $actOrder->getStatusBadge();
                        ?>
                        <div class="p-3 rounded-3 border bg-light-subtle d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge bg-primary-subtle text-primary font-monospace small">
                                        #TJ-<?= str_pad($actOrder->id, 5, '0', STR_PAD_LEFT) ?>
                                    </span>
                                    <span class="badge <?= $badgeInfo['class'] ?> rounded-pill small">
                                        <i class="bi <?= $badgeInfo['icon'] ?> me-1"></i><?= $badgeInfo['label'] ?>
                                    </span>
                                </div>
                                <h6 class="fw-bold mb-1 small"><?= e($actSrv ? $actSrv->name : 'Layanan Servis') ?></h6>
                                <small class="text-muted d-block">
                                    <i class="bi bi-calendar-event me-1"></i><?= date('d M Y, H:i', strtotime($actOrder->schedule)) ?> &bull; 
                                    <i class="bi bi-geo-alt me-1"></i><?= e(strlen($actOrder->location) > 30 ? substr($actOrder->location, 0, 30).'…' : $actOrder->location) ?>
                                </small>
                            </div>
                            <a href="<?= url('customer/orders/' . $actOrder->id) ?>" class="btn btn-outline-primary btn-sm rounded-pill align-self-start align-self-sm-center text-nowrap">
                                Detail <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================================
     TABEL RIWAYAT PESANAN LENGKAP
     ============================================================ -->
<div class="tj-card-table shadow-sm">
    <div class="tj-card-header d-flex align-items-center justify-content-between">
        <div>
            <h6 class="fw-bold mb-0">Riwayat Pesanan Saya</h6>
            <small class="text-muted">Seluruh daftar pesanan servis yang telah Anda buat</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= url('customer/orders') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-list-ul me-1"></i> Kelola di Pesanan Saya
            </a>
        </div>
    </div>

    <?php if (empty($orders)): ?>
        <div class="text-center py-5">
            <i class="bi bi-receipt text-muted" style="font-size:3rem;opacity:.3;"></i>
            <p class="text-muted mt-3 mb-2">Belum ada riwayat pesanan servis.</p>
            <a href="<?= url('customer/orders/create') ?>" class="btn btn-primary-tj btn-sm">Buat Pesanan Pertama</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                        <th class="ps-4">No. Order</th>
                        <th>Layanan Servis</th>
                        <th>Teknisi</th>
                        <th>Jadwal Pengerjaan</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($orders, 0, 6) as $order): ?>
                        <?php
                            $service = $order->service();
                            $tech    = $order->technician();
                            $badgeInfo = $order->getStatusBadge();
                        ?>
                        <tr>
                            <td class="ps-4">
                                <span class="fw-bold small font-monospace text-primary">#TJ-<?= str_pad($order->id, 5, '0', STR_PAD_LEFT) ?></span>
                            </td>
                            <td>
                                <span class="fw-semibold small d-block"><?= e($service ? $service->name : 'Layanan Servis') ?></span>
                                <small class="text-muted"><?= e($order->location) ?></small>
                            </td>
                            <td>
                                <?php if ($tech): ?>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold"
                                             style="width: 28px; height: 28px; font-size: .75rem;">
                                            <?= strtoupper(substr($tech->name, 0, 1)) ?>
                                        </div>
                                        <span class="small fw-semibold"><?= e($tech->name) ?></span>
                                    </div>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary small">Menunggu Penugasan</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="small text-muted"><?= date('d M Y, H:i', strtotime($order->schedule)) ?></span>
                            </td>
                            <td>
                                <span class="badge <?= $badgeInfo['class'] ?> rounded-pill px-2 py-1 small">
                                    <i class="bi <?= $badgeInfo['icon'] ?> me-1"></i><?= $badgeInfo['label'] ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <a href="<?= url('customer/orders/' . $order->id) ?>" class="btn btn-outline-primary btn-sm py-1 px-3">
                                    Lihat Detail
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
