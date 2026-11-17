<!-- ============================================================
     ADMIN DASHBOARD VIEW — Terima Jasa
     ============================================================ -->

<!-- Flash messages -->
<?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4 d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-check-circle-fill text-success fs-5"></i>
        <div><?= e($_SESSION['flash_success']) ?></div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-exclamation-octagon-fill text-danger fs-5"></i>
        <div><?= e($_SESSION['flash_error']) ?></div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<!-- Page Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold mb-0">Dashboard Administrator</h4>
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 font-monospace" style="font-size:.7rem;">SYSTEM ADMIN</span>
        </div>
        <p class="text-muted small mb-0">
            Pusat monitoring & operasional platform Terima Jasa — <?= date('d M Y, H:i') ?> WIB
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= url('admin/reports') ?>" class="btn btn-outline-danger d-inline-flex align-items-center gap-2">
            <i class="bi bi-graph-up-arrow"></i> Laporan & Statistik
        </a>
        <a href="<?= url('admin/profile') ?>" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
            <i class="bi bi-person-gear"></i> Profil Saya
        </a>
        <a href="<?= url('admin/orders') ?>" class="btn btn-danger d-inline-flex align-items-center gap-2">
            <i class="bi bi-receipt"></i> Kelola Order
        </a>
    </div>
</div>

<!-- ============================================================
     STATISTIK DASHBOARD (8 INDIKATOR LENGKAP SESUAI SPESIFIKASI)
     ============================================================ -->
<div class="row g-3 mb-4">
    <!-- 1. Total Customer -->
    <div class="col-xl-3 col-sm-6">
        <div class="tj-stat-card border-0 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: .04em;">Total Customer</span>
                <div class="tj-stat-icon bg-primary-subtle text-primary"><i class="bi bi-person-fill"></i></div>
            </div>
            <h3 class="fw-bold mb-1"><?= number_format($stats['total_customers']) ?></h3>
            <div class="d-flex align-items-center justify-content-between">
                <small class="text-muted">Pelanggan terdaftar</small>
                <a href="<?= url('admin/customers') ?>" class="text-primary text-decoration-none small fw-semibold">Kelola &rarr;</a>
            </div>
        </div>
    </div>

    <!-- 2. Total Teknisi -->
    <div class="col-xl-3 col-sm-6">
        <div class="tj-stat-card border-0 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: .04em;">Total Teknisi</span>
                <div class="tj-stat-icon bg-success-subtle text-success"><i class="bi bi-tools"></i></div>
            </div>
            <h3 class="fw-bold mb-1"><?= number_format($stats['total_technicians']) ?></h3>
            <div class="d-flex align-items-center justify-content-between">
                <small class="text-muted">Mitra teknisi terdaftar</small>
                <a href="<?= url('admin/technicians') ?>" class="text-success text-decoration-none small fw-semibold">Kelola &rarr;</a>
            </div>
        </div>
    </div>

    <!-- 3. Teknisi Menunggu Verifikasi -->
    <div class="col-xl-3 col-sm-6">
        <div class="tj-stat-card border-0 shadow-sm h-100 <?= $stats['pending_verifs'] > 0 ? 'border border-warning' : '' ?>">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: .04em;">Menunggu Verifikasi</span>
                <div class="tj-stat-icon bg-warning-subtle text-warning-emphasis"><i class="bi bi-patch-exclamation-fill"></i></div>
            </div>
            <h3 class="fw-bold mb-1 text-warning-emphasis"><?= number_format($stats['pending_verifs']) ?></h3>
            <div class="d-flex align-items-center justify-content-between">
                <small class="<?= $stats['pending_verifs'] > 0 ? 'text-warning-emphasis fw-bold' : 'text-muted' ?>">
                    <?= $stats['pending_verifs'] > 0 ? 'Butuh approval segera' : 'Semua aman' ?>
                </small>
                <a href="<?= url('admin/technicians?verification=pending') ?>" class="text-warning-emphasis text-decoration-none small fw-semibold">Tinjau &rarr;</a>
            </div>
        </div>
    </div>

    <!-- 4. Total Jasa -->
    <div class="col-xl-3 col-sm-6">
        <div class="tj-stat-card border-0 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: .04em;">Total Jasa</span>
                <div class="tj-stat-icon bg-info-subtle text-info"><i class="bi bi-grid-3x3-gap-fill"></i></div>
            </div>
            <h3 class="fw-bold mb-1"><?= number_format($stats['total_services']) ?></h3>
            <div class="d-flex align-items-center justify-content-between">
                <small class="text-muted">Katalog servis aktif</small>
                <a href="<?= url('admin/services') ?>" class="text-info text-decoration-none small fw-semibold">Katalog &rarr;</a>
            </div>
        </div>
    </div>

    <!-- 5. Total Order -->
    <div class="col-xl-3 col-sm-6">
        <div class="tj-stat-card border-0 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: .04em;">Total Order</span>
                <div class="tj-stat-icon bg-secondary-subtle text-dark"><i class="bi bi-receipt-cutoff"></i></div>
            </div>
            <h3 class="fw-bold mb-1"><?= number_format($stats['total_orders']) ?></h3>
            <div class="d-flex align-items-center justify-content-between">
                <small class="text-muted">Seluruh transaksi</small>
                <a href="<?= url('admin/orders') ?>" class="text-dark text-decoration-none small fw-semibold">Semua &rarr;</a>
            </div>
        </div>
    </div>

    <!-- 6. Order Pending -->
    <div class="col-xl-3 col-sm-6">
        <div class="tj-stat-card border-0 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: .04em;">Order Pending</span>
                <div class="tj-stat-icon bg-warning-subtle text-warning"><i class="bi bi-clock-history"></i></div>
            </div>
            <h3 class="fw-bold mb-1 text-warning"><?= number_format($stats['pending_orders']) ?></h3>
            <div class="d-flex align-items-center justify-content-between">
                <small class="text-muted">Menunggu konfirmasi</small>
                <a href="<?= url('admin/orders?status=pending') ?>" class="text-warning text-decoration-none small fw-semibold">Filter &rarr;</a>
            </div>
        </div>
    </div>

    <!-- 7. Order Aktif -->
    <div class="col-xl-3 col-sm-6">
        <div class="tj-stat-card border-0 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: .04em;">Order Aktif</span>
                <div class="tj-stat-icon bg-primary-subtle text-primary"><i class="bi bi-gear-wide-connected"></i></div>
            </div>
            <h3 class="fw-bold mb-1 text-primary"><?= number_format($stats['active_orders']) ?></h3>
            <div class="d-flex align-items-center justify-content-between">
                <small class="text-muted">Diterima / Pengerjaan</small>
                <a href="<?= url('admin/orders?status=in_progress') ?>" class="text-primary text-decoration-none small fw-semibold">Cek &rarr;</a>
            </div>
        </div>
    </div>

    <!-- 8. Order Selesai -->
    <div class="col-xl-3 col-sm-6">
        <div class="tj-stat-card border-0 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: .04em;">Order Selesai</span>
                <div class="tj-stat-icon bg-success-subtle text-success"><i class="bi bi-check2-all"></i></div>
            </div>
            <h3 class="fw-bold mb-1 text-success"><?= number_format($stats['completed_orders']) ?></h3>
            <div class="d-flex align-items-center justify-content-between">
                <small class="text-muted">Sukses diselesaikan</small>
                <a href="<?= url('admin/orders?status=completed') ?>" class="text-success text-decoration-none small fw-semibold">Arsip &rarr;</a>
            </div>
        </div>
    </div>
</div>

<!-- Banner Admin AI Copilot & Platform Intelligence -->
<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 60%, #4338ca 100%); color: white;">
    <div class="card-body p-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-white bg-opacity-20 text-white flex-shrink-0" style="width: 52px; height: 52px; font-size: 1.6rem;">
                <i class="bi bi-cpu-fill text-warning"></i>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h5 class="fw-bold mb-0 text-white">Admin AI Intelligence Copilot</h5>
                    <span class="badge bg-danger text-white fw-bold rounded-pill" style="font-size: .65rem;">BARU</span>
                </div>
                <p class="mb-0 text-white-50 small">
                    Pusat analisis strategis platform: evaluasi metrik pesanan, SOP verifikasi berkas teknisi, resolusi sengketa komplain pelanggan, dan benchmarking tarif layanan.
                </p>
            </div>
        </div>
        <div class="d-flex gap-2 flex-shrink-0">
            <a href="<?= url('admin/ai-assistant') ?>" class="btn btn-warning text-dark fw-bold rounded-pill px-4 shadow-sm">
                <i class="bi bi-robot me-1"></i> Buka AI Copilot Admin
            </a>
        </div>
    </div>
</div>

<!-- ============================================================
     TABEL ORDER TERBARU
     ============================================================ -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <div class="p-2 bg-danger-subtle text-danger rounded-3"><i class="bi bi-receipt"></i></div>
            <div>
                <h6 class="fw-bold mb-0">Order Terbaru</h6>
                <small class="text-muted">Aktivitas pemesanan servis terkini dari customer</small>
            </div>
        </div>
        <a href="<?= url('admin/orders') ?>" class="btn btn-outline-danger btn-sm rounded-pill px-3">
            Lihat Semua Order <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                    <th class="ps-4">No. Order</th>
                    <th>Customer</th>
                    <th>Layanan Jasa</th>
                    <th>Teknisi Bertugas</th>
                    <th>Jadwal Servis</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($latestOrders)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted small">Belum ada order tercatat di sistem.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($latestOrders as $order): ?>
                        <?php
                            $customer = $order->customer();
                            $tech     = $order->technician();
                            $service  = $order->service();
                            $badge    = $order->getStatusBadge();
                        ?>
                        <tr>
                            <td class="ps-4">
                                <span class="fw-bold font-monospace text-danger">#<?= str_pad($order->id, 5, '0', STR_PAD_LEFT) ?></span>
                            </td>
                            <td>
                                <div class="fw-semibold small"><?= e($customer ? $customer->name : 'Customer #' . $order->customer_id) ?></div>
                                <small class="text-muted"><?= e($customer->phone ?? '-') ?></small>
                            </td>
                            <td>
                                <div class="fw-semibold small"><?= e($service ? $service->name : 'Layanan Servis') ?></div>
                                <small class="text-muted">Rp <?= number_format($service->estimated_price ?? 0, 0, ',', '.') ?></small>
                            </td>
                            <td>
                                <?php if ($tech): ?>
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-person-check text-success me-1"></i><?= e($tech->name) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis">
                                        <i class="bi bi-hourglass-split me-1"></i>Belum Ditugaskan
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="small text-muted"><?= date('d M Y, H:i', strtotime($order->schedule)) ?></span>
                            </td>
                            <td>
                                <span class="badge <?= $badge['class'] ?> rounded-pill px-2 py-1">
                                    <i class="bi <?= $badge['icon'] ?> me-1"></i><?= $badge['label'] ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <a href="<?= url("admin/orders/{$order->id}") ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================================
     TABEL TEKNISI TERBARU & CUSTOMER TERBARU
     ============================================================ -->
<div class="row g-4">
    <!-- Teknisi Terbaru -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 bg-success-subtle text-success rounded-3"><i class="bi bi-tools"></i></div>
                    <div>
                        <h6 class="fw-bold mb-0">Teknisi Terbaru</h6>
                        <small class="text-muted">Mitra teknisi yang baru mendaftar</small>
                    </div>
                </div>
                <a href="<?= url('admin/technicians') ?>" class="btn btn-outline-success btn-sm rounded-pill px-3">
                    Semua <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                            <th class="ps-4">Nama Teknisi</th>
                            <th>Keahlian</th>
                            <th>Verifikasi</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($latestTechnicians)): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted small">Belum ada teknisi terdaftar.</td></tr>
                        <?php else: ?>
                            <?php foreach ($latestTechnicians as $techUser): ?>
                                <?php
                                    $prof = $techUser->technicianProfile();
                                    $vStatus = $prof ? $prof->verification_status : 'pending';
                                    $vBadge = match($vStatus) {
                                        'verified' => ['bg-success-subtle text-success', 'bi-check-circle', 'Verified'],
                                        'rejected' => ['bg-danger-subtle text-danger', 'bi-x-circle', 'Rejected'],
                                        default    => ['bg-warning-subtle text-warning-emphasis', 'bi-hourglass', 'Pending']
                                    };
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                                                 style="width:32px;height:32px;background:linear-gradient(135deg,#059669,#10b981);font-size:.8rem;flex-shrink:0;">
                                                <?= strtoupper(substr($techUser->name, 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-semibold small"><?= e($techUser->name) ?></div>
                                                <small class="text-muted"><?= e($techUser->phone ?? '-') ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="small text-truncate d-inline-block" style="max-width: 140px;">
                                            <?= e($prof ? $prof->expertise : 'Belum diisi') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $vBadge[0] ?> rounded-pill">
                                            <i class="bi <?= $vBadge[1] ?> me-1"></i><?= $vBadge[2] ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="<?= url("admin/technicians/{$techUser->id}") ?>" class="btn btn-sm btn-light border rounded-pill px-2 py-0" style="font-size:.78rem;">
                                            Tinjau
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Customer Terbaru -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 bg-primary-subtle text-primary rounded-3"><i class="bi bi-people-fill"></i></div>
                    <div>
                        <h6 class="fw-bold mb-0">Customer Terbaru</h6>
                        <small class="text-muted">Pelanggan yang baru membuat akun</small>
                    </div>
                </div>
                <a href="<?= url('admin/customers') ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                    Semua <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                            <th class="ps-4">Nama Pelanggan</th>
                            <th>Kontak</th>
                            <th>Status Akun</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($latestCustomers)): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted small">Belum ada customer terdaftar.</td></tr>
                        <?php else: ?>
                            <?php foreach ($latestCustomers as $custUser): ?>
                                <?php
                                    $cStatus = $custUser->status ?? 'active';
                                    $cBadge = match($cStatus) {
                                        'active'    => ['bg-success-subtle text-success', 'Aktif'],
                                        'inactive'  => ['bg-secondary-subtle text-secondary', 'Nonaktif'],
                                        default     => ['bg-danger-subtle text-danger', 'Suspended']
                                    };
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                                                 style="width:32px;height:32px;background:linear-gradient(135deg,#2563eb,#38bdf8);font-size:.8rem;flex-shrink:0;">
                                                <?= strtoupper(substr($custUser->name, 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-semibold small"><?= e($custUser->name) ?></div>
                                                <small class="text-muted"><?= e($custUser->email) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="small text-muted"><?= e($custUser->phone ?? '-') ?></span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $cBadge[0] ?> rounded-pill">
                                            <?= $cBadge[1] ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="<?= url("admin/customers/{$custUser->id}") ?>" class="btn btn-sm btn-light border rounded-pill px-2 py-0" style="font-size:.78rem;">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
