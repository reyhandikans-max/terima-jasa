<!-- ============================================================
     DETAIL LAYANAN JASA — TERIMA JASA
     ============================================================ -->
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= url('customer/dashboard') ?>">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= url('customer/services') ?>">Katalog Layanan</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($service->name) ?></li>
        </ol>
    </nav>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h4 class="fw-bold mb-1"><?= e($service->name) ?></h4>
            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 small">
                <i class="bi <?= e($category->icon ?? 'bi-tools') ?> me-1"></i><?= e($category ? $category->name : 'Kategori Umum') ?>
            </span>
        </div>
        <a href="<?= url('customer/orders/create?service_id=' . $service->id) ?>" class="btn btn-primary-tj px-4 py-2 shadow-sm d-inline-flex align-items-center gap-2">
            <i class="bi bi-cart-plus-fill"></i> Pesan Jasa Ini Sekarang
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Kolom Kiri: Detail Spesifikasi Jasa & Deskripsi -->
    <div class="col-lg-8">
        <!-- Card Detail Layanan -->
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 mb-4">
            <div class="d-flex align-items-center justify-content-between pb-3 border-bottom mb-4">
                <div>
                    <small class="text-muted text-uppercase fw-semibold" style="font-size: .75rem; letter-spacing: .05em;">Estimasi Biaya Pengerjaan</small>
                    <h2 class="fw-bold text-primary mb-0">Rp <?= number_format($service->estimated_price, 0, ',', '.') ?></h2>
                </div>
                <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill small border border-success-subtle">
                    <i class="bi bi-shield-check me-1"></i>Garansi Servis 30 Hari
                </span>
            </div>

            <!-- Deskripsi Lengkap -->
            <h5 class="fw-bold mb-3">Deskripsi Layanan</h5>
            <div class="text-secondary mb-4" style="line-height: 1.7; font-size: .95rem;">
                <p><?= nl2br(e($service->description)) ?></p>
            </div>

            <!-- Apa yang termasuk dalam layanan ini -->
            <h6 class="fw-bold mb-3"><i class="bi bi-check2-circle text-primary me-2"></i>Cakupan Pengerjaan Standar:</h6>
            <div class="row g-2 mb-4">
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-2 p-2 rounded-3 bg-light border">
                        <i class="bi bi-check-lg text-success fw-bold"></i>
                        <span class="small">Pemeriksaan & diagnosa menyeluruh</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-2 p-2 rounded-3 bg-light border">
                        <i class="bi bi-check-lg text-success fw-bold"></i>
                        <span class="small">Peralatan teknisi standar profesional</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-2 p-2 rounded-3 bg-light border">
                        <i class="bi bi-check-lg text-success fw-bold"></i>
                        <span class="small">Pengujian fungsi setelah perbaikan</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-2 p-2 rounded-3 bg-light border">
                        <i class="bi bi-check-lg text-success fw-bold"></i>
                        <span class="small">Laporan pengerjaan & nota digital</span>
                    </div>
                </div>
            </div>

            <div class="p-3 rounded-3 bg-primary-subtle text-primary border border-primary-subtle small d-flex align-items-center gap-2">
                <i class="bi bi-info-circle-fill fs-5"></i>
                <span>Biaya final dapat disesuaikan jika membutuhkan penggantian suku cadang (*spare part*) tambahan yang disetujui pelanggan.</span>
            </div>
        </div>

        <!-- Card Teknisi yang Tersedia -->
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h5 class="fw-bold mb-1">Mitra Teknisi yang Tersedia</h5>
                    <p class="text-muted small mb-0">Teknisi tersertifikasi yang siap menangani layanan ini</p>
                </div>
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2">
                    <i class="bi bi-circle-fill me-1" style="font-size: .5rem;"></i><?= count($availableTechnicians) ?> Teknisi Siap
                </span>
            </div>

            <div class="d-flex flex-column gap-3">
                <?php foreach ($availableTechnicians as $tech): ?>
                    <?php 
                        $techUser = $tech->user(); 
                        $reviews = $techUser ? $techUser->reviewsReceived() : [];
                        $ratingAvg = !empty($reviews) ? round(array_sum(array_map(fn($r) => (int)$r->rating, $reviews)) / count($reviews), 1) : 4.9;
                    ?>
                    <div class="p-3 rounded-4 border bg-white d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm"
                                 style="width: 52px; height: 52px; background: linear-gradient(135deg, #10b981, #059669); font-size: 1.25rem;">
                                <?= strtoupper(substr($techUser ? $techUser->name : 'T', 0, 1)) ?>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <h6 class="fw-bold mb-0"><?= e($techUser ? $techUser->name : 'Mitra Teknisi') ?></h6>
                                    <span class="badge bg-primary-subtle text-primary rounded-pill small">
                                        <i class="bi bi-patch-check-fill me-1"></i>Terverifikasi
                                    </span>
                                </div>
                                <div class="text-muted small mb-1">
                                    <i class="bi bi-briefcase me-1"></i><?= e($tech->expertise) ?> &bull; <?= e($tech->experience) ?>
                                </div>
                                <div class="small text-muted">
                                    <i class="bi bi-geo-alt me-1"></i><?= e($tech->location) ?>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-column align-items-md-end gap-1">
                            <div class="d-flex align-items-center gap-1 text-warning fw-bold">
                                <i class="bi bi-star-fill"></i>
                                <span class="text-dark small"><?= number_format($ratingAvg, 1) ?> / 5.0</span>
                                <small class="text-muted">(<?= count($reviews) ?> ulasan)</small>
                            </div>
                            <span class="badge bg-success-subtle text-success small">
                                <i class="bi bi-check-circle me-1"></i>Siap Ditugaskan
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Ringkasan Pemesanan Cepat & Keunggulan -->
    <div class="col-lg-4">
        <!-- Ringkasan CTA Card -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 sticky-top" style="top: 100px;">
            <h6 class="fw-bold mb-3"><i class="bi bi-lightning-charge-fill text-warning me-2"></i>Pesan Layanan Cepat</h6>
            
            <div class="mb-3">
                <small class="text-muted d-block">Nama Jasa:</small>
                <strong class="d-block text-dark"><?= e($service->name) ?></strong>
            </div>

            <div class="mb-3">
                <small class="text-muted d-block">Kategori:</small>
                <span class="badge bg-light text-dark border"><?= e($category ? $category->name : 'Umum') ?></span>
            </div>

            <div class="mb-4 pb-3 border-bottom">
                <small class="text-muted d-block">Estimasi Biaya Dasar:</small>
                <h4 class="fw-bold text-primary mb-0">Rp <?= number_format($service->estimated_price, 0, ',', '.') ?></h4>
            </div>

            <a href="<?= url('customer/orders/create?service_id=' . $service->id) ?>" class="btn btn-primary-tj w-100 py-3 fw-bold mb-3 shadow-sm">
                <i class="bi bi-calendar2-plus-fill me-2"></i> Lanjutkan Pemesanan
            </a>

            <div class="d-flex flex-column gap-2 small text-muted">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-shield-fill-check text-success"></i>
                    <span>Jaminan garansi pengerjaan 30 hari</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history text-primary"></i>
                    <span>Respon konfirmasi jadwal cepat</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-credit-card-2-front text-info"></i>
                    <span>Pembayaran transparan tanpa biaya siluman</span>
                </div>
            </div>
        </div>
    </div>
</div>
