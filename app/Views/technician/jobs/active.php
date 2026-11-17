<!-- ============================================================
     PEKERJAAN AKTIF — MITRA TEKNISI
     ============================================================ -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?= url('technician/dashboard') ?>">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Pekerjaan Aktif</li>
            </ol>
        </nav>
        <h4 class="fw-bold mb-1">Pekerjaan Aktif</h4>
        <p class="text-muted small mb-0">Kelola dan update status pengerjaan servis yang telah Anda terima dari pelanggan.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('technician/requests') ?>" class="btn btn-outline-success btn-sm">
            <i class="bi bi-inbox-fill me-1"></i> Permintaan Masuk
        </a>
        <a href="<?= url('technician/history') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-clock-history me-1"></i> Riwayat Selesai
        </a>
    </div>
</div>

<!-- ============================================================
     DAFTAR PEKERJAAN AKTIF
     ============================================================ -->
<?php if (empty($activeJobs)): ?>
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center my-4">
        <div class="rounded-circle d-inline-flex align-items-center justify-content-center bg-light text-muted mb-3 mx-auto"
             style="width: 72px; height: 72px;">
            <i class="bi bi-check2-circle fs-2 text-success"></i>
        </div>
        <h5 class="fw-bold mb-2">Tidak Ada Pekerjaan yang Sedang Berjalan</h5>
        <p class="text-muted small mb-4" style="max-width: 480px; margin: 0 auto;">
            Semua pekerjaan telah selesai diservis! Silakan periksa permintaan order masuk baru untuk mendapatkan pesanan servis berikutnya.
        </p>
        <div>
            <a href="<?= url('technician/requests') ?>" class="btn btn-success btn-sm rounded-pill px-4 shadow-sm">
                <i class="bi bi-inbox-fill me-1"></i> Periksa Permintaan Baru
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="d-flex flex-column gap-4">
        <?php foreach ($activeJobs as $job): ?>
            <?php 
                $cust = $job->customer();
                $service = $job->service();
                $cat = $service ? $service->category() : null;
                $isAccepted = ($job->status === 'accepted');
                $isInProgress = ($job->status === 'in_progress');
            ?>
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 pb-3 border-bottom mb-4">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-primary-subtle text-primary font-monospace px-3 py-1 rounded-pill small">
                                #TJ-<?= str_pad($job->id, 5, '0', STR_PAD_LEFT) ?>
                            </span>
                            <?php if ($isAccepted): ?>
                                <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-3 py-1 small">
                                    <i class="bi bi-person-check-fill me-1"></i>Diterima (Menunggu Mulai)
                                </span>
                            <?php elseif ($isInProgress): ?>
                                <span class="badge bg-primary text-white rounded-pill px-3 py-1 small">
                                    <i class="bi bi-gear-wide-connected me-1"></i>Sedang Dikerjakan
                                </span>
                            <?php endif; ?>
                        </div>
                        <h4 class="fw-bold mb-0 text-dark"><?= e($service ? $service->name : 'Layanan Servis') ?></h4>
                    </div>

                    <div class="text-md-end">
                        <small class="text-muted d-block" style="font-size: .75rem;">Estimasi Biaya Jasa:</small>
                        <h3 class="fw-bold text-primary mb-0">Rp <?= number_format($service ? $service->estimated_price : 0, 0, ',', '.') ?></h3>
                    </div>
                </div>

                <!-- STEPPER PROGRESS TAHAPAN PEKERJAAN -->
                <div class="p-3 rounded-4 bg-light border mb-4">
                    <small class="fw-bold text-muted d-block mb-3" style="font-size: .75rem; letter-spacing: .05em;">
                        TAHAPAN PENGERJAAN TEKNISI:
                    </small>

                    <div class="row text-center g-2">
                        <!-- Step 1: Accepted -->
                        <div class="col-4">
                            <div class="p-2 rounded-3 <?= $isAccepted ? 'bg-info bg-opacity-25 border border-info' : 'bg-success text-white' ?>">
                                <i class="bi bi-check-circle-fill d-block fs-5"></i>
                                <span class="small fw-semibold">1. Diterima</span>
                            </div>
                        </div>

                        <!-- Step 2: In Progress -->
                        <div class="col-4">
                            <div class="p-2 rounded-3 <?= $isInProgress ? 'bg-primary text-white shadow-sm' : ($isAccepted ? 'bg-white border text-muted' : 'bg-success text-white') ?>">
                                <i class="bi bi-tools d-block fs-5"></i>
                                <span class="small fw-semibold">2. Sedang Dikerjakan</span>
                            </div>
                        </div>

                        <!-- Step 3: Complete -->
                        <div class="col-4">
                            <div class="p-2 rounded-3 bg-white border text-muted opacity-75">
                                <i class="bi bi-flag-fill d-block fs-5"></i>
                                <span class="small fw-semibold">3. Selesai</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <!-- Rincian Keluhan & Foto -->
                    <div class="col-lg-7">
                        <h6 class="fw-bold mb-2">Deskripsi Masalah Pelanggan:</h6>
                        <div class="p-3 rounded-3 border bg-white small text-secondary mb-3">
                            <?= nl2br(e($job->description)) ?>
                        </div>

                        <?php if (!empty($job->problem_image)): ?>
                            <div class="mb-3">
                                <small class="text-muted d-block mb-1"><i class="bi bi-image me-1"></i>Foto Masalah Terlampir:</small>
                                <img src="<?= asset($job->problem_image) ?>" alt="Foto Masalah" class="rounded-3 border shadow-sm" style="max-height: 140px; object-fit: cover;">
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Customer & Lokasi Pengerjaan -->
                    <div class="col-lg-5">
                        <div class="p-3 rounded-3 border bg-light h-100">
                            <h6 class="fw-bold mb-3 small"><i class="bi bi-person-fill text-primary me-1"></i>Data Kontak Pelanggan</h6>
                            <div class="d-flex flex-column gap-2 small">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Nama:</span>
                                    <strong class="text-dark"><?= e($cust ? $cust->name : 'Pelanggan') ?></strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">No. Telepon:</span>
                                    <span class="fw-semibold"><?= e($cust ? $cust->phone : '-') ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Jadwal:</span>
                                    <span class="fw-semibold text-primary"><?= date('d M Y, H:i', strtotime($job->schedule)) ?> WIB</span>
                                </div>
                                <div class="border-top pt-2 mt-1">
                                    <span class="text-muted d-block mb-1">Alamat Pengerjaan:</span>
                                    <span class="text-dark fw-semibold d-block"><?= e($job->location) ?></span>
                                </div>
                            </div>

                            <!-- Tombol WhatsApp -->
                            <?php if ($cust && !empty($cust->phone)): ?>
                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $cust->phone) ?>?text=Halo%20<?= urlencode($cust->name) ?>%2C%20saya%20teknisi%20Terima%20Jasa%20mengenai%20pesanan%20TJ-<?= str_pad($job->id, 5, '0', STR_PAD_LEFT) ?>" 
                                   target="_blank" 
                                   class="btn btn-outline-success btn-sm w-100 rounded-pill mt-3">
                                    <i class="bi bi-whatsapp me-2"></i> Hubungi Customer di WhatsApp
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- TOMBOL AKSI TRANSISI STATUS -->
                <div class="pt-3 border-top d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                    <span class="text-muted small">
                        Pastikan pengerjaan sesuai dengan SOP keselamatan dan kebersihan lokasi pelanggan.
                    </span>

                    <div class="d-flex gap-2">
                        <?php if ($isAccepted): ?>
                            <!-- Transisi: accepted -> in_progress -->
                            <form action="<?= url('technician/orders/' . $job->id . '/start') ?>" method="POST" class="m-0">
                                <button type="submit" class="btn btn-primary px-4 py-2 rounded-pill shadow-sm">
                                    <i class="bi bi-play-circle-fill me-2"></i> Mulai Pengerjaan Sekarang
                                </button>
                            </form>
                        <?php elseif ($isInProgress): ?>
                            <!-- Transisi: in_progress -> completed -->
                            <button type="button" class="btn btn-success px-4 py-2 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#completeModal<?= $job->id ?>">
                                <i class="bi bi-check-circle-fill me-2"></i> Selesaikan Pekerjaan Ini
                            </button>

                            <!-- Modal Konfirmasi Selesai -->
                            <div class="modal fade" id="completeModal<?= $job->id ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content rounded-4 border-0 shadow">
                                        <div class="modal-header border-0 pb-0">
                                            <h5 class="modal-title fw-bold text-success">
                                                <i class="bi bi-check-circle-fill me-2"></i>Konfirmasi Penyelesaian Servis
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body py-3">
                                            <p class="mb-2">Apakah Anda telah selesai melakukan seluruh perbaikan pada pesanan <strong>#TJ-<?= str_pad($job->id, 5, '0', STR_PAD_LEFT) ?></strong>?</p>
                                            <small class="text-muted d-block">Setelah ditandai selesai, status pesanan akan dialihkan ke Riwayat dan pelanggan dapat memberikan penilaian serta rating bintang.</small>
                                        </div>
                                        <div class="modal-footer border-0 pt-0">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Belum, Masih Bekerja</button>
                                            <form action="<?= url('technician/orders/' . $job->id . '/complete') ?>" method="POST">
                                                <button type="submit" class="btn btn-success">
                                                    Ya, Tandai Selesai
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
