<!-- ============================================================
     RIWAYAT PEKERJAAN SELESAI — MITRA TEKNISI
     ============================================================ -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?= url('technician/dashboard') ?>">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Riwayat Selesai</li>
            </ol>
        </nav>
        <h4 class="fw-bold mb-1">Riwayat Pekerjaan Selesai</h4>
        <p class="text-muted small mb-0">Daftar seluruh pekerjaan servis yang telah berhasil Anda tuntaskan.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('technician/reviews') ?>" class="btn btn-outline-warning text-dark btn-sm shadow-sm">
            <i class="bi bi-star-fill text-warning me-1"></i> Lihat Semua Ulasan
        </a>
    </div>
</div>

<!-- ============================================================
     TABEL RIWAYAT PEKERJAAN SELESAI
     ============================================================ -->
<div class="tj-card-table shadow-sm">
    <div class="tj-card-header d-flex align-items-center justify-content-between">
        <div>
            <h6 class="fw-bold mb-0">Pekerjaan yang Telah Diselesaikan</h6>
            <small class="text-muted">Total <?= count($completedJobs) ?> pesanan berhasil dituntaskan</small>
        </div>
        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill">
            <i class="bi bi-check-all me-1"></i>Status: Completed
        </span>
    </div>

    <?php if (empty($completedJobs)): ?>
        <div class="text-center py-5">
            <div class="rounded-circle d-inline-flex align-items-center justify-content-center bg-light text-muted mb-3" style="width: 72px; height: 72px;">
                <i class="bi bi-check2-circle fs-2 text-secondary opacity-50"></i>
            </div>
            <h5 class="fw-bold mb-1">Belum Ada Riwayat Pekerjaan Selesai</h5>
            <p class="text-muted small mb-3">Selesaikan pekerjaan aktif Anda untuk menambahkan data ke riwayat servis.</p>
            <a href="<?= url('technician/active-jobs') ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                Buka Pekerjaan Aktif
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-muted text-uppercase" style="font-size: .72rem; letter-spacing: .05em;">
                        <th class="ps-4">No. Order</th>
                        <th>Layanan Servis</th>
                        <th>Customer</th>
                        <th>Tanggal Selesai</th>
                        <th>Biaya Servis</th>
                        <th class="text-end pe-4">Rating & Review</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($completedJobs as $job): ?>
                        <?php
                            $cust = $job->customer();
                            $service = $job->service();
                            $cat = $service ? $service->category() : null;
                            $review = $job->review();
                        ?>
                        <tr>
                            <!-- No. Order -->
                            <td class="ps-4">
                                <span class="fw-bold small font-monospace text-success">#TJ-<?= str_pad($job->id, 5, '0', STR_PAD_LEFT) ?></span>
                                <span class="badge bg-success text-white rounded-pill ms-1" style="font-size: .65rem;">Selesai</span>
                            </td>

                            <!-- Layanan Servis -->
                            <td>
                                <strong class="small text-dark d-block"><?= e($service ? $service->name : 'Layanan Servis') ?></strong>
                                <small class="text-muted"><?= e($cat ? $cat->name : 'Umum') ?> &bull; <?= e($job->location) ?></small>
                            </td>

                            <!-- Customer -->
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold"
                                         style="width: 28px; height: 28px; font-size: .75rem;">
                                        <?= strtoupper(substr($cust ? $cust->name : 'C', 0, 1)) ?>
                                    </div>
                                    <div>
                                        <span class="small fw-semibold d-block"><?= e($cust ? $cust->name : 'Pelanggan') ?></span>
                                        <small class="text-muted" style="font-size: .72rem;"><?= e($cust ? $cust->phone : '-') ?></small>
                                    </div>
                                </div>
                            </td>

                            <!-- Tanggal Selesai -->
                            <td>
                                <span class="small text-dark d-block"><?= date('d M Y', strtotime($job->updated_at ?? $job->schedule)) ?></span>
                                <small class="text-muted" style="font-size: .72rem;"><?= date('H:i', strtotime($job->schedule)) ?> WIB</small>
                            </td>

                            <!-- Biaya Servis -->
                            <td>
                                <strong class="small text-primary">Rp <?= number_format($service ? $service->estimated_price : 0, 0, ',', '.') ?></strong>
                            </td>

                            <!-- Rating & Review -->
                            <td class="text-end pe-4">
                                <?php if ($review): ?>
                                    <div class="d-inline-block text-end">
                                        <div class="text-warning small mb-1">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="bi bi-star-fill <?= ($i <= (int)$review->rating) ? 'text-warning' : 'text-secondary opacity-25' ?>"></i>
                                            <?php endfor; ?>
                                            <span class="fw-bold text-dark ms-1">(<?= $review->rating ?>)</span>
                                        </div>
                                        <small class="text-muted d-block fst-italic text-truncate" style="max-width: 200px; font-size: .72rem;" title="<?= e($review->comment) ?>">
                                            "<?= e($review->comment) ?>"
                                        </small>
                                    </div>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border small" style="font-size: .72rem;">
                                        <i class="bi bi-clock me-1"></i>Menunggu Ulasan
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
