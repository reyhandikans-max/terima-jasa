<!-- ============================================================
     PERMINTAAN JASA MASUK — MITRA TEKNISI
     ============================================================ -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?= url('technician/dashboard') ?>">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Permintaan Jasa Masuk</li>
            </ol>
        </nav>
        <h4 class="fw-bold mb-1">Permintaan Jasa Masuk</h4>
        <p class="text-muted small mb-0">Daftar order pelanggan yang relevan dengan keahlian Anda dan siap untuk dikonfirmasi.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('technician/active-jobs') ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-tools me-1"></i> Pekerjaan Aktif
        </a>
    </div>
</div>

<!-- ============================================================
     DAFTAR PERMINTAAN JASA
     ============================================================ -->
<?php if (empty($pendingRequests)): ?>
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center my-4">
        <div class="rounded-circle d-inline-flex align-items-center justify-content-center bg-light text-muted mb-3 mx-auto"
             style="width: 72px; height: 72px;">
            <i class="bi bi-inbox fs-2"></i>
        </div>
        <h5 class="fw-bold mb-2">Belum Ada Permintaan Servis Baru</h5>
        <p class="text-muted small mb-4" style="max-width: 480px; margin: 0 auto;">
            Saat ini belum ada order servis baru yang masuk. Pastikan status ketersediaan Anda diatur ke <strong>Available</strong> agar sistem dapat mengirimkan penugasan baru.
        </p>
        <div>
            <a href="<?= url('technician/profile') ?>" class="btn btn-outline-success btn-sm rounded-pill px-4">
                <i class="bi bi-broadcast me-1"></i> Cek Status Ketersediaan
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($pendingRequests as $order): ?>
            <?php 
                $cust = $order->customer();
                $service = $order->service();
                $cat = $service ? $service->category() : null;
            ?>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-4 border-start border-4 border-warning position-relative">
                    <!-- Header Kartu Order -->
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="badge bg-warning-subtle text-warning-emphasis font-monospace px-3 py-1 rounded-pill small">
                            #TJ-<?= str_pad($order->id, 5, '0', STR_PAD_LEFT) ?> &bull; Menunggu Konfirmasi
                        </span>
                        <strong class="text-primary fs-5">
                            Rp <?= number_format($service ? $service->estimated_price : 0, 0, ',', '.') ?>
                        </strong>
                    </div>

                    <!-- Jenis Jasa & Kategori -->
                    <h5 class="fw-bold mb-1 text-dark"><?= e($service ? $service->name : 'Layanan Servis') ?></h5>
                    <span class="badge bg-light text-dark border rounded-pill mb-3 align-self-start small">
                        <i class="bi <?= e($cat->icon ?? 'bi-tools') ?> me-1"></i><?= e($cat ? $cat->name : 'Umum') ?>
                    </span>

                    <!-- Deskripsi Keluhan Pelanggan -->
                    <div class="p-3 rounded-3 bg-light border mb-3 small text-secondary">
                        <strong class="text-dark d-block mb-1"><i class="bi bi-chat-left-text me-1 text-primary"></i>Keluhan Pelanggan:</strong>
                        <?= nl2br(e($order->description)) ?>
                    </div>

                    <!-- Foto Masalah (Jika Ada) -->
                    <?php if (!empty($order->problem_image)): ?>
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1"><i class="bi bi-image me-1"></i>Lampiran Foto Masalah:</small>
                            <img src="<?= asset($order->problem_image) ?>" alt="Foto Kerusakan" class="rounded-3 border shadow-sm" style="max-height: 120px; object-fit: cover;">
                        </div>
                    <?php endif; ?>

                    <!-- Data Customer & Lokasi -->
                    <div class="d-flex flex-column gap-2 small text-muted mb-4 pb-2 border-bottom">
                        <div class="d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-person-fill text-primary me-1"></i>Customer:</span>
                            <strong class="text-dark"><?= e($cust ? $cust->name : 'Pelanggan') ?> (<?= e($cust ? $cust->phone : '-') ?>)</strong>
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-geo-alt-fill text-danger me-1"></i>Lokasi:</span>
                            <span class="text-dark fw-semibold text-truncate ms-2" style="max-width: 250px;"><?= e($order->location) ?></span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-calendar2-event-fill text-info me-1"></i>Jadwal Kedatangan:</span>
                            <span class="text-dark fw-semibold"><?= date('d M Y, H:i', strtotime($order->schedule)) ?> WIB</span>
                        </div>
                    </div>

                    <!-- Tombol Aksi: Accept vs Reject -->
                    <div class="d-flex gap-2 mt-auto">
                        <!-- Tombol Tolak / Decline -->
                        <button type="button" 
                                class="btn btn-outline-danger btn-sm flex-fill py-2" 
                                data-bs-toggle="modal" 
                                data-bs-target="#rejectModal<?= $order->id ?>">
                            <i class="bi bi-x-circle me-1"></i> Tolak Order
                        </button>

                        <!-- Tombol Terima / Accept -->
                        <form action="<?= url('technician/orders/' . $order->id . '/accept') ?>" method="POST" class="flex-fill m-0">
                            <button type="submit" class="btn btn-success btn-sm w-100 py-2 shadow-sm">
                                <i class="bi bi-check-circle-fill me-1"></i> Terima Pekerjaan
                            </button>
                        </form>
                    </div>

                    <!-- Modal Konfirmasi Tolak -->
                    <div class="modal fade" id="rejectModal<?= $order->id ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content rounded-4 border-0 shadow">
                                <div class="modal-header border-0 pb-0">
                                    <h5 class="modal-title fw-bold text-danger">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Tolak Permintaan Order?
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body py-3">
                                    <p class="mb-1">Apakah Anda yakin ingin menolak permintaan servis <strong>#TJ-<?= str_pad($order->id, 5, '0', STR_PAD_LEFT) ?></strong>?</p>
                                    <small class="text-muted d-block">Order akan dialihkan ke mitra teknisi lainnya yang tersedia di area pelanggan.</small>
                                </div>
                                <div class="modal-footer border-0 pt-0">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                    <form action="<?= url('technician/orders/' . $order->id . '/reject') ?>" method="POST">
                                        <button type="submit" class="btn btn-danger">
                                            Ya, Tolak Permintaan
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
