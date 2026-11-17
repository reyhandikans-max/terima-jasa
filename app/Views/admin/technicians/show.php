<!-- ============================================================
     ADMIN TECHNICIAN MANAGEMENT: DETAIL & VERIFIKASI
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

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?= url('admin/dashboard') ?>" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= url('admin/technicians') ?>" class="text-decoration-none">Teknisi</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($techUser->name) ?></li>
            </ol>
        </nav>
        <h4 class="fw-bold mb-0">Detail & Verifikasi Mitra Teknisi</h4>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('admin/technicians') ?>" class="btn btn-light border">
            &larr; Kembali ke List
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Profil Card & Action Verifikasi -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4 text-center">
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center fw-bold text-white mb-3 shadow"
                     style="width:84px;height:84px;background:linear-gradient(135deg,#059669,#10b981);font-size:2rem;">
                    <?= strtoupper(substr($techUser->name, 0, 1)) ?>
                </div>
                <h5 class="fw-bold mb-1"><?= e($techUser->name) ?></h5>
                <p class="text-muted small mb-3"><?= e($techUser->email) ?></p>

                <!-- Status Verifikasi Badge -->
                <?php
                    $vStatus = $profile ? ($profile->verification_status ?? 'pending') : 'pending';
                    $vBadge = match($vStatus) {
                        'verified' => ['bg-success-subtle text-success border-success-subtle', 'bi-patch-check-fill', 'TERVERIFIKASI (VERIFIED)'],
                        'rejected' => ['bg-danger-subtle text-danger border-danger-subtle', 'bi-x-circle-fill', 'VERIFIKASI DITOLAK (REJECTED)'],
                        default    => ['bg-warning-subtle text-warning-emphasis border-warning-subtle', 'bi-hourglass-split', 'MENUNGGU VERIFIKASI (PENDING)']
                    };
                ?>
                <div class="mb-3">
                    <span class="badge <?= $vBadge[0] ?> border rounded-pill px-3 py-2 fw-semibold">
                        <i class="bi <?= $vBadge[1] ?> me-1"></i> <?= $vBadge[2] ?>
                    </span>
                </div>

                <!-- Tombol Aksi Verifikasi / Tolak -->
                <div class="d-grid gap-2 mb-3">
                    <?php if ($vStatus !== 'verified'): ?>
                        <form method="POST" action="<?= url("admin/technicians/{$techUser->id}/verify") ?>">
                            <button type="submit" class="btn btn-success w-100 fw-semibold shadow-sm">
                                <i class="bi bi-patch-check-fill me-1"></i> Setujui & Verifikasi Teknisi
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($vStatus !== 'rejected'): ?>
                        <form method="POST" action="<?= url("admin/technicians/{$techUser->id}/reject") ?>" onsubmit="return confirm('Apakah Anda yakin ingin MENOLAK verifikasi mitra teknisi ini?');">
                            <button type="submit" class="btn btn-outline-danger w-100 fw-semibold">
                                <i class="bi bi-x-circle me-1"></i> Tolak Verifikasi
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <hr class="my-4">

                <!-- Profile Info List -->
                <div class="text-start">
                    <div class="mb-3">
                        <small class="text-muted d-block">Bidang Keahlian Spesifik</small>
                        <span class="fw-semibold text-dark"><?= e($profile ? $profile->expertise : 'Belum diisi') ?></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Lama Pengalaman</small>
                        <span class="fw-semibold text-dark"><?= e($profile ? $profile->experience : 'Belum diisi') ?></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Wilayah / Lokasi Kerja</small>
                        <span class="fw-semibold text-dark"><i class="bi bi-geo-alt text-danger me-1"></i><?= e($profile ? $profile->location : 'Belum diisi') ?></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Alamat Lengkap</small>
                        <span class="small text-secondary"><?= e($profile ? ($profile->address ?: 'Belum dicantumkan') : 'Belum dicantumkan') ?></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">No. Telepon / WhatsApp</small>
                        <span class="fw-semibold font-monospace"><?= e($profile->phone ?? $techUser->phone ?? '-') ?></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Ketersediaan Saat Ini</small>
                        <?php
                            $avail = $profile ? ($profile->availability ?? 'offline') : 'offline';
                            $availBadge = match($avail) {
                                'available' => 'bg-success text-white',
                                'busy'      => 'bg-warning text-dark',
                                default     => 'bg-secondary text-white'
                            };
                        ?>
                        <span class="badge <?= $availBadge ?> rounded-pill px-2 py-1"><?= ucfirst($avail) ?></span>
                    </div>
                </div>

                <div class="pt-3 border-top text-start">
                    <form method="POST" action="<?= url("admin/technicians/{$techUser->id}/toggle-status") ?>">
                        <button type="submit" class="btn btn-outline-secondary btn-sm w-100 rounded-pill">
                            <i class="bi <?= ($techUser->status === 'active') ? 'bi-lock' : 'bi-unlock' ?> me-1"></i>
                            <?= ($techUser->status === 'active') ? 'Nonaktifkan Akun Teknisi' : 'Aktifkan Akun Teknisi' ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats, Pekerjaan & Rating -->
    <div class="col-lg-8">
        <!-- Stats Mini Cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <span class="text-muted small">Total Ditugaskan</span>
                    <h3 class="fw-bold mb-0 text-dark"><?= $totalJobs ?></h3>
                    <small class="text-muted">Pesanan masuk</small>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <span class="text-muted small">Pekerjaan Selesai</span>
                    <h3 class="fw-bold mb-0 text-success"><?= count($completedJobs) ?></h3>
                    <small class="text-success"><?= count($activeJobs) ?> sedang aktif</small>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <span class="text-muted small">Rating Reputasi</span>
                    <div class="d-flex align-items-center gap-1">
                        <h3 class="fw-bold mb-0 text-warning"><?= $avgRating > 0 ? $avgRating : '-' ?></h3>
                        <i class="bi bi-star-fill text-warning fs-5"></i>
                    </div>
                    <small class="text-muted"><?= count($reviews) ?> ulasan pelanggan</small>
                </div>
            </div>
        </div>

        <!-- Orders Assigned Table -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0">Daftar Pekerjaan / Order Ditugaskan</h6>
                <span class="badge bg-light text-dark border"><?= count($orders) ?> Order</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small text-muted text-uppercase" style="font-size:.72rem;">
                            <th class="ps-4">No. Order</th>
                            <th>Layanan Jasa</th>
                            <th>Customer</th>
                            <th>Jadwal</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted small">
                                    Belum ada pekerjaan atau order yang ditugaskan ke teknisi ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <?php
                                    $srv = $order->service();
                                    $cust = $order->customer();
                                    $badge = $order->getStatusBadge();
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <span class="fw-bold font-monospace text-danger">#<?= str_pad($order->id, 5, '0', STR_PAD_LEFT) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold small"><?= e($srv ? $srv->name : 'Layanan') ?></div>
                                    </td>
                                    <td>
                                        <span class="small"><?= e($cust ? $cust->name : 'Customer') ?></span>
                                    </td>
                                    <td>
                                        <span class="small text-muted"><?= date('d M Y, H:i', strtotime($order->schedule)) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $badge['class'] ?> rounded-pill px-2 py-1">
                                            <?= $badge['label'] ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="<?= url("admin/orders/{$order->id}") ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-0" style="font-size:.78rem;">
                                            Lihat
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Ulasan & Review Card -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0">Ulasan & Rating Dari Pelanggan</h6>
            </div>
            <div class="card-body p-4">
                <?php if (empty($reviews)): ?>
                    <div class="text-center py-3 text-muted small">
                        Belum ada ulasan yang diberikan untuk teknisi ini.
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($reviews as $rev): ?>
                            <div class="p-3 bg-light rounded-3">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <div class="fw-semibold small">Pelanggan #<?= $rev->customer_id ?></div>
                                    <div class="text-warning small">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi bi-star<?= $i <= (int)$rev->rating ? '-fill' : '' ?>"></i>
                                        <?php endfor; ?>
                                        <span class="ms-1 fw-bold text-dark"><?= $rev->rating ?>/5</span>
                                    </div>
                                </div>
                                <p class="mb-0 small text-muted"><?= e($rev->comment ?: 'Tidak ada komentar tertulis.') ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
