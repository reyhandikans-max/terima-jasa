<!-- ============================================================
     DETAIL PESANAN & REVIEW — TERIMA JASA
     ============================================================ -->
<?php
$badgeInfo = $order->getStatusBadge();
$statusSteps = [
    'pending'     => ['step' => 1, 'label' => 'Menunggu Konfirmasi', 'icon' => 'bi-hourglass-split'],
    'accepted'    => ['step' => 2, 'label' => 'Teknisi Ditugaskan', 'icon' => 'bi-person-check'],
    'in_progress' => ['step' => 3, 'label' => 'Sedang Dikerjakan', 'icon' => 'bi-tools'],
    'completed'   => ['step' => 4, 'label' => 'Selesai', 'icon' => 'bi-check-circle-fill'],
    'cancelled'   => ['step' => 0, 'label' => 'Dibatalkan', 'icon' => 'bi-x-circle-fill']
];

$currentStep = $statusSteps[$order->status]['step'] ?? 1;
?>

<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= url('customer/dashboard') ?>">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= url('customer/orders') ?>">Pesanan Saya</a></li>
            <li class="breadcrumb-item active" aria-current="page">Pesanan #TJ-<?= str_pad($order->id, 5, '0', STR_PAD_LEFT) ?></li>
        </ol>
    </nav>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h4 class="fw-bold mb-0">Pesanan #TJ-<?= str_pad($order->id, 5, '0', STR_PAD_LEFT) ?></h4>
                <span class="badge <?= $badgeInfo['class'] ?> rounded-pill px-3 py-1">
                    <i class="bi <?= $badgeInfo['icon'] ?> me-1"></i><?= $badgeInfo['label'] ?>
                </span>
            </div>
            <p class="text-muted small mb-0">Dibuat pada <?= date('d F Y, H:i', strtotime($order->created_at)) ?> WIB</p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="<?= url('customer/orders') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Pesanan
            </a>
            
            <?php if ($order->canCancel()): ?>
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
                    <i class="bi bi-x-circle me-1"></i> Batalkan Pesanan
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================================
     STEPPER TIMELINE STATUS PROGRESS
     ============================================================ -->
<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <h6 class="fw-bold mb-4"><i class="bi bi-signpost-split-fill text-primary me-2"></i>Status Perkembangan Pengerjaan</h6>

    <?php if ($order->isCancelled()): ?>
        <div class="alert alert-danger mb-0 d-flex align-items-center gap-3 rounded-3" role="alert">
            <i class="bi bi-x-octagon-fill fs-3 text-danger"></i>
            <div>
                <strong>Pesanan Dibatalkan</strong>
                <p class="small mb-0 text-danger-emphasis">Pesanan ini telah dibatalkan. Jika Anda masih membutuhkan perbaikan, silakan buat pesanan baru melalui katalog layanan.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="position-relative mb-2 px-2">
            <!-- Progress Line -->
            <div class="progress" style="height: 4px;">
                <div class="progress-bar bg-success" role="progressbar" 
                     style="width: <?= (($currentStep - 1) / 3) * 100 ?>%;" 
                     aria-valuenow="<?= (($currentStep - 1) / 3) * 100 ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>

            <!-- Steps Grid -->
            <div class="d-flex justify-content-between position-relative" style="margin-top: -18px;">
                <!-- Step 1: Pending -->
                <div class="text-center" style="width: 100px;">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm <?= ($currentStep >= 1) ? 'bg-success text-white' : 'bg-light text-muted border' ?>"
                         style="width: 32px; height: 32px; font-size: .85rem;">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <small class="d-block fw-semibold mt-2 <?= ($currentStep >= 1) ? 'text-dark' : 'text-muted' ?>" style="font-size: .75rem;">
                        Pending
                    </small>
                    <span class="text-muted d-none d-sm-block" style="font-size: .68rem;">Menunggu Konfirmasi</span>
                </div>

                <!-- Step 2: Accepted -->
                <div class="text-center" style="width: 100px;">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm <?= ($currentStep >= 2) ? 'bg-success text-white' : 'bg-light text-muted border' ?>"
                         style="width: 32px; height: 32px; font-size: .85rem;">
                        <i class="bi bi-person-check"></i>
                    </div>
                    <small class="d-block fw-semibold mt-2 <?= ($currentStep >= 2) ? 'text-dark' : 'text-muted' ?>" style="font-size: .75rem;">
                        Diterima
                    </small>
                    <span class="text-muted d-none d-sm-block" style="font-size: .68rem;">Teknisi Ditugaskan</span>
                </div>

                <!-- Step 3: In Progress -->
                <div class="text-center" style="width: 100px;">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm <?= ($currentStep >= 3) ? 'bg-success text-white' : 'bg-light text-muted border' ?>"
                         style="width: 32px; height: 32px; font-size: .85rem;">
                        <i class="bi bi-tools"></i>
                    </div>
                    <small class="d-block fw-semibold mt-2 <?= ($currentStep >= 3) ? 'text-dark' : 'text-muted' ?>" style="font-size: .75rem;">
                        In Progress
                    </small>
                    <span class="text-muted d-none d-sm-block" style="font-size: .68rem;">Pengerjaan Berjalan</span>
                </div>

                <!-- Step 4: Completed -->
                <div class="text-center" style="width: 100px;">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm <?= ($currentStep >= 4) ? 'bg-success text-white' : 'bg-light text-muted border' ?>"
                         style="width: 32px; height: 32px; font-size: .85rem;">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <small class="d-block fw-semibold mt-2 <?= ($currentStep >= 4) ? 'text-success' : 'text-muted' ?>" style="font-size: .75rem;">
                        Selesai
                    </small>
                    <span class="text-muted d-none d-sm-block" style="font-size: .68rem;">Servis Tuntas</span>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="row g-4 mb-4">
    <!-- Kolom Kiri: Rincian Layanan & Deskripsi -->
    <div class="col-lg-8">
        <!-- Rincian Layanan Card -->
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 mb-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-receipt-cutoff text-primary me-2"></i>Rincian Servis</h5>

            <div class="p-3 rounded-3 bg-light border mb-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-primary-subtle text-primary rounded-pill small">
                        <?= e($category ? $category->name : 'Umum') ?>
                    </span>
                    <strong class="text-primary fs-5">Rp <?= number_format($service ? $service->estimated_price : 0, 0, ',', '.') ?></strong>
                </div>
                <h6 class="fw-bold mb-1"><?= e($service ? $service->name : 'Layanan Servis') ?></h6>
                <small class="text-muted"><?= e($service ? $service->description : '') ?></small>
            </div>

            <!-- Detail Keluhan -->
            <h6 class="fw-bold mb-2">Deskripsi Keluhan Pelanggan:</h6>
            <div class="p-3 rounded-3 border bg-white mb-4 text-secondary small" style="line-height: 1.6;">
                <?= nl2br(e($order->description)) ?>
            </div>

            <!-- Lokasi & Jadwal -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="p-3 rounded-3 border bg-light h-100">
                        <small class="text-muted d-block mb-1"><i class="bi bi-geo-alt-fill text-danger me-1"></i>Lokasi Pengerjaan:</small>
                        <span class="fw-semibold small text-dark d-block"><?= e($order->location) ?></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 rounded-3 border bg-light h-100">
                        <small class="text-muted d-block mb-1"><i class="bi bi-calendar2-event-fill text-primary me-1"></i>Jadwal Kedatangan:</small>
                        <span class="fw-semibold small text-dark d-block"><?= date('l, d F Y', strtotime($order->schedule)) ?></span>
                        <small class="text-muted">Pukul <?= date('H:i', strtotime($order->schedule)) ?> WIB</small>
                    </div>
                </div>
            </div>

            <!-- Foto Masalah (Jika Diupload) -->
            <?php if (!empty($order->problem_image)): ?>
                <h6 class="fw-bold mb-2"><i class="bi bi-image text-primary me-1"></i>Foto Masalah Terlampir:</h6>
                <div class="p-2 border rounded-3 bg-light d-inline-block mb-3">
                    <img src="<?= asset($order->problem_image) ?>" alt="Foto Masalah" class="img-fluid rounded" style="max-height: 240px; object-fit: contain;">
                </div>
            <?php endif; ?>
        </div>

        <!-- ============================================================
             SECTION: RATING & REVIEW (Jika Completed)
             ============================================================ -->
        <div id="reviewSection" class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
            <h5 class="fw-bold mb-3"><i class="bi bi-star-fill text-warning me-2"></i>Ulasan & Penilaian Servis</h5>

            <?php if (!$order->isCompleted()): ?>
                <div class="text-center py-4 bg-light rounded-3 text-muted">
                    <i class="bi bi-shield-lock fs-2 d-block mb-2 text-secondary opacity-50"></i>
                    <p class="small mb-0">Kolom ulasan dan rating akan otomatis terbuka setelah pekerjaan teknisi berstatus <strong>Selesai (Completed)</strong>.</p>
                </div>
            <?php elseif ($review): ?>
                <!-- Tampilan Review yang Sudah Ada -->
                <div class="p-4 rounded-4 border bg-light-subtle">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1 small">
                                <i class="bi bi-check-circle-fill me-1"></i>Ulasan Terkirim
                            </span>
                            <small class="text-muted"><?= date('d M Y, H:i', strtotime($review->created_at)) ?></small>
                        </div>
                        <div class="text-warning fs-5">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi bi-star-fill <?= ($i <= $review->rating) ? 'text-warning' : 'text-secondary opacity-25' ?>"></i>
                            <?php endfor; ?>
                            <span class="ms-1 fw-bold text-dark fs-6">(<?= $review->rating ?>/5)</span>
                        </div>
                    </div>
                    <blockquote class="blockquote mb-0 small text-secondary fst-italic">
                        "<?= e($review->comment) ?>"
                    </blockquote>
                </div>
            <?php else: ?>
                <!-- Form Berikan Ulasan Baru -->
                <p class="text-muted small mb-4">
                    Pekerjaan servis telah selesai! Mohon berikan penilaian atas performa teknisi untuk membantu peningkatan kualitas layanan kami.
                </p>

                <form action="<?= url('customer/orders/' . $order->id . '/review') ?>" method="POST">
                    <!-- Rating Bintang -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Pilih Nilai Kepuasan (Rating Bintang) <span class="text-danger">*</span></label>
                        <div class="d-flex align-items-center gap-2" id="starRatingContainer">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <label style="cursor: pointer;" title="Bintang <?= $s ?>">
                                    <input type="radio" name="rating" value="<?= $s ?>" class="d-none" <?= ($s === 5) ? 'checked' : '' ?> onchange="updateStarDisplay(<?= $s ?>)">
                                    <i class="bi bi-star-fill text-warning fs-2" id="star_<?= $s ?>"></i>
                                </label>
                            <?php endfor; ?>
                            <span class="fw-bold ms-2 text-dark fs-5" id="ratingText">5 Bintang (Sangat Puas)</span>
                        </div>
                    </div>

                    <!-- Komentar / Review -->
                    <div class="mb-4">
                        <label for="reviewComment" class="form-label small fw-bold">Komentar & Ulasan Anda</label>
                        <textarea class="form-control" 
                                  id="reviewComment" 
                                  name="comment" 
                                  rows="3" 
                                  placeholder="Ceritakan pengalaman Anda mengenai kerapihan, ketepatan waktu, dan keramahan teknisi..."
                                  required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary-tj px-4 py-2 shadow-sm">
                        <i class="bi bi-send-fill me-2"></i> Kirim Ulasan & Rating
                    </button>
                </form>

                <script>
                function updateStarDisplay(rating) {
                    const ratingLabels = {
                        1: '1 Bintang (Kurang Puas)',
                        2: '2 Bintang (Cukup)',
                        3: '3 Bintang (Baik)',
                        4: '4 Bintang (Puas)',
                        5: '5 Bintang (Sangat Puas)'
                    };
                    document.getElementById('ratingText').innerText = ratingLabels[rating] || (rating + ' Bintang');
                    for (let i = 1; i <= 5; i++) {
                        const star = document.getElementById('star_' + i);
                        if (i <= rating) {
                            star.className = 'bi bi-star-fill text-warning fs-2';
                        } else {
                            star.className = 'bi bi-star text-secondary opacity-50 fs-2';
                        }
                    }
                }
                </script>
            <?php endif; ?>
        </div>
    </div>

    <!-- Kolom Kanan: Informasi Teknisi Menangani & Customer -->
    <div class="col-lg-4">
        <!-- Teknisi Menangani Card -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-person-badge-fill text-primary me-2"></i>Teknisi yang Menangani</h6>

            <?php if ($technician): ?>
                <?php 
                    $techProfile = $technician->technicianProfile(); 
                    $techReviews = $technician->reviewsReceived();
                    $avgStars = !empty($techReviews) ? round(array_sum(array_map(fn($r) => (int)$r->rating, $techReviews)) / count($techReviews), 1) : 4.9;
                ?>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm"
                         style="width: 56px; height: 56px; background: linear-gradient(135deg, #10b981, #059669); font-size: 1.35rem; flex-shrink: 0;">
                        <?= strtoupper(substr($technician->name, 0, 1)) ?>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0"><?= e($technician->name) ?></h6>
                        <small class="text-muted d-block"><?= e($techProfile ? $techProfile->expertise : 'Mitra Teknisi Terverifikasi') ?></small>
                        <div class="d-flex align-items-center gap-1 text-warning mt-1" style="font-size: .8rem;">
                            <i class="bi bi-star-fill"></i>
                            <span class="text-dark fw-bold"><?= number_format($avgStars, 1) ?></span>
                            <span class="text-muted">(<?= count($techReviews) ?> ulasan)</span>
                        </div>
                    </div>
                </div>

                <div class="p-3 rounded-3 bg-light border mb-3 small">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Pengalaman:</span>
                        <span class="fw-semibold"><?= e($techProfile ? $techProfile->experience : '4+ Tahun') ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">No. Kontak:</span>
                        <span class="fw-semibold"><?= e($technician->phone ?? '0821-xxxx-xxxx') ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Wilayah Kerja:</span>
                        <span class="fw-semibold"><?= e($techProfile ? $techProfile->location : 'Jabodetabek') ?></span>
                    </div>
                </div>

                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $technician->phone ?? '6282198765432') ?>?text=Halo%20<?= urlencode($technician->name) ?>%2C%20saya%20pelanggan%20Terima%20Jasa%20dengan%20No.%20Order%20TJ-<?= str_pad($order->id, 5, '0', STR_PAD_LEFT) ?>" 
                   target="_blank" 
                   class="btn btn-outline-success btn-sm w-100 py-2">
                    <i class="bi bi-whatsapp me-2"></i> Hubungi via WhatsApp
                </a>
            <?php else: ?>
                <div class="text-center py-4 bg-light rounded-3">
                    <i class="bi bi-hourglass-split text-warning fs-2 d-block mb-2"></i>
                    <p class="small text-muted mb-0">Teknisi sedang dijadwalkan oleh sistem dispatch Terima Jasa.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Bantuan & Garansi -->
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-shield-check text-success me-2"></i>Jaminan Terima Jasa</h6>
            <div class="d-flex flex-column gap-2 small text-muted">
                <div class="d-flex align-items-start gap-2">
                    <i class="bi bi-check2-circle text-success fs-5"></i>
                    <span><strong>Garansi Servis 30 Hari:</strong> Komplain kerusakan yang sama akan diservis ulang tanpa biaya tambahan.</span>
                </div>
                <div class="d-flex align-items-start gap-2">
                    <i class="bi bi-check2-circle text-success fs-5"></i>
                    <span><strong>Teknisi Terseleksi:</strong> Seluruh mitra telah melalui uji kompetensi dan verifikasi identitas resmi.</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL KONFIRMASI BATAL PESANAN
     ============================================================ -->
<?php if ($order->canCancel()): ?>
<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Pembatalan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <p class="mb-2">Apakah Anda yakin ingin membatalkan pesanan <strong>#TJ-<?= str_pad($order->id, 5, '0', STR_PAD_LEFT) ?></strong>?</p>
                <div class="p-3 bg-light rounded-3 small text-muted">
                    Layanan: <strong><?= e($service ? $service->name : 'Layanan Servis') ?></strong><br>
                    Jadwal: <?= date('d M Y, H:i', strtotime($order->schedule)) ?> WIB
                </div>
                <small class="text-danger d-block mt-2">Perhatian: Pesanan yang telah dibatalkan tidak dapat diaktifkan kembali.</small>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Kembali</button>
                <form action="<?= url('customer/orders/' . $order->id . '/cancel') ?>" method="POST">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash-fill me-1"></i> Ya, Batalkan Pesanan Ini
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
