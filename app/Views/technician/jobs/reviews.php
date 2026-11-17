<!-- ============================================================
     ULASAN & REPUTASI TEKNISI — TERIMA JASA
     ============================================================ -->
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= url('technician/dashboard') ?>">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Rating & Ulasan</li>
        </ol>
    </nav>
    <h4 class="fw-bold mb-1">Reputasi & Ulasan Pelanggan</h4>
    <p class="text-muted small mb-0">Ulasan kepuasan dan rating yang diberikan oleh pelanggan atas servis yang telah Anda kerjakan.</p>
</div>

<!-- ============================================================
     RINGKASAN SKOR & DISTRIBUSI BINTANG
     ============================================================ -->
<div class="row g-4 mb-4">
    <!-- Skor Rata-Rata -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100 d-flex flex-column justify-content-center">
            <small class="text-muted text-uppercase fw-semibold mb-2" style="font-size: .75rem; letter-spacing: .05em;">Skor Kepuasan Servis</small>
            <h1 class="display-3 fw-bold text-warning mb-0">★ <?= number_format($avgRating, 1) ?></h1>
            <div class="text-warning mb-2">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="bi bi-star-fill fs-5 <?= ($i <= round($avgRating)) ? 'text-warning' : 'text-secondary opacity-25' ?>"></i>
                <?php endfor; ?>
            </div>
            <p class="text-muted small mb-0">Berdasarkan <strong><?= $totalReviews ?></strong> ulasan pelanggan</p>
        </div>
    </div>

    <!-- Distribusi Bintang -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <h6 class="fw-bold mb-3"><i class="bi bi-bar-chart-fill text-primary me-2"></i>Distribusi Penilaian</h6>

            <div class="d-flex flex-column gap-2">
                <?php for ($star = 5; $star >= 1; $star--): ?>
                    <?php 
                        $count = $starCounts[$star] ?? 0;
                        $pct = ($totalReviews > 0) ? round(($count / $totalReviews) * 100) : 0;
                    ?>
                    <div class="d-flex align-items-center gap-3 small">
                        <span class="text-nowrap fw-semibold" style="width: 70px;">
                            <i class="bi bi-star-fill text-warning me-1"></i><?= $star ?> Bintang
                        </span>
                        <div class="progress flex-grow-1" style="height: 8px;">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $pct ?>%;"></div>
                        </div>
                        <span class="text-muted text-end" style="width: 50px;"><?= $count ?> ulasan</span>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     DAFTAR ULASAN PELANGGAN LENGKAP
     ============================================================ -->
<div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
    <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
        <h5 class="fw-bold mb-0">Semua Ulasan dari Pelanggan</h5>
        <span class="badge bg-light text-dark border px-3 py-2 rounded-pill small"><?= $totalReviews ?> Ulasan Masuk</span>
    </div>

    <?php if (empty($reviews)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-chat-square-quote text-secondary opacity-50" style="font-size: 3rem;"></i>
            <h5 class="fw-bold mt-3 mb-1">Belum Ada Ulasan Diterima</h5>
            <p class="small">Selesaikan pekerjaan servis Anda dan kepuasan pelanggan akan muncul di halaman ini.</p>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-3">
            <?php foreach ($reviews as $rev): ?>
                <?php 
                    $cust = $rev->customer();
                    $order = $rev->order();
                    $service = $order ? $order->service() : null;
                ?>
                <div class="p-3 p-md-4 rounded-4 border bg-white shadow-sm">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-2 pb-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold"
                                 style="width: 36px; height: 36px; font-size: .85rem;">
                                <?= strtoupper(substr($cust ? $cust->name : 'P', 0, 1)) ?>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark"><?= e($cust ? $cust->name : 'Pelanggan') ?></h6>
                                <small class="text-muted" style="font-size: .72rem;">
                                    Servis: <?= e($service ? $service->name : 'Layanan') ?> &bull; <?= date('d M Y, H:i', strtotime($rev->created_at)) ?>
                                </small>
                            </div>
                        </div>

                        <!-- Rating Bintang -->
                        <div class="text-warning">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi bi-star-fill <?= ($i <= (int)$rev->rating) ? 'text-warning' : 'text-secondary opacity-25' ?>"></i>
                            <?php endfor; ?>
                            <span class="fw-bold text-dark ms-1 small">(<?= $rev->rating ?>/5)</span>
                        </div>
                    </div>

                    <p class="text-secondary small mb-0 fst-italic" style="line-height: 1.6;">
                        "<?= e($rev->comment ?? 'Pelayanan sangat baik dan memuaskan!') ?>"
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
