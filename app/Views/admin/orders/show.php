<!-- ============================================================
     ADMIN ORDER DETAIL VIEW: LIHAT DETAIL ORDER
     ============================================================ -->

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?= url('admin/dashboard') ?>" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= url('admin/orders') ?>" class="text-decoration-none">Order</a></li>
                <li class="breadcrumb-item active" aria-current="page">#<?= str_pad($order->id, 5, '0', STR_PAD_LEFT) ?></li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-3">
            <h4 class="fw-bold mb-0">Detail Order #<?= str_pad($order->id, 5, '0', STR_PAD_LEFT) ?></h4>
            <?php $badge = $order->getStatusBadge(); ?>
            <span class="badge <?= $badge['class'] ?> rounded-pill px-3 py-2 fs-6">
                <i class="bi <?= $badge['icon'] ?> me-1"></i> <?= $badge['label'] ?>
            </span>
        </div>
        <p class="text-muted small mb-0">Dibuat pada: <?= !empty($order->created_at) ? date('d F Y, H:i', strtotime($order->created_at)) : '-' ?> WIB</p>
    </div>
    <div>
        <a href="<?= url('admin/orders') ?>" class="btn btn-light border">
            &larr; Kembali ke List Order
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Service, Description, Problem Image, Review -->
    <div class="col-lg-8">
        <!-- Service & Order Info Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0">Layanan yang Dipesan</h6>
            </div>
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="fw-bold text-dark mb-1"><?= e($service ? $service->name : 'Layanan Servis') ?></h5>
                        <?php $category = $service ? $service->category() : null; ?>
                        <span class="badge bg-light text-dark border">
                            <i class="bi bi-tag text-primary me-1"></i><?= e($category ? $category->name : 'Kategori') ?>
                        </span>
                    </div>
                    <div class="text-end">
                        <small class="text-muted d-block">Estimasi Biaya</small>
                        <h4 class="fw-bold text-primary mb-0 font-monospace">
                            Rp <?= number_format($service->estimated_price ?? 0, 0, ',', '.') ?>
                        </h4>
                    </div>
                </div>

                <hr class="my-3">

                <!-- Problem Description -->
                <div class="mb-4">
                    <label class="fw-bold small text-muted text-uppercase mb-2" style="letter-spacing:.05em;">Keluhan & Deskripsi Masalah Pelanggan:</label>
                    <div class="p-3 bg-light rounded-3 text-dark">
                        <?= nl2br(e($order->description)) ?>
                    </div>
                </div>

                <!-- Problem Image (if uploaded) -->
                <?php if (!empty($order->problem_image)): ?>
                    <div class="mb-3">
                        <label class="fw-bold small text-muted text-uppercase mb-2" style="letter-spacing:.05em;">Foto Kerusakan / Kendala:</label>
                        <div>
                            <img src="<?= e($order->problem_image) ?>" alt="Problem Image" class="img-fluid rounded-3 border shadow-sm" style="max-height: 280px; object-fit: cover;">
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Schedule & Location Details -->
                <div class="row g-3 pt-3 border-top">
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 border bg-white h-100">
                            <div class="d-flex align-items-center gap-2 mb-1 text-primary">
                                <i class="bi bi-calendar2-check fs-5"></i>
                                <span class="fw-bold small text-uppercase">Jadwal Servis</span>
                            </div>
                            <div class="fw-semibold text-dark fs-6">
                                <?= date('l, d F Y', strtotime($order->schedule)) ?>
                            </div>
                            <div class="small text-muted">
                                Pukul <?= date('H:i', strtotime($order->schedule)) ?> WIB
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 border bg-white h-100">
                            <div class="d-flex align-items-center gap-2 mb-1 text-danger">
                                <i class="bi bi-geo-alt fs-5"></i>
                                <span class="fw-bold small text-uppercase">Lokasi Pengerjaan</span>
                            </div>
                            <div class="fw-semibold text-dark small">
                                <?= e($order->location) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Review & Rating (If order is completed and has review) -->
        <?php if ($review): ?>
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="fw-bold mb-0">Ulasan & Kepuasan Pelanggan</h6>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="text-warning fs-5">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi bi-star<?= $i <= (int)$review->rating ? '-fill' : '' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="fw-bold fs-5 text-dark"><?= $review->rating ?> / 5</span>
                    </div>
                    <p class="mb-0 text-muted fst-italic">"<?= e($review->comment ?: 'Tidak ada komentar tertulis.') ?>"</p>
                    <small class="text-muted d-block mt-2">Diberikan pada <?= date('d M Y, H:i', strtotime($review->created_at)) ?> WIB</small>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right Column: Customer Card & Technician Card -->
    <div class="col-lg-4">
        <!-- Customer Info Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0">Informasi Pelanggan</h6>
                <?php if ($customer): ?>
                    <a href="<?= url("admin/customers/{$customer->id}") ?>" class="small text-primary text-decoration-none fw-semibold">
                        Profil &rarr;
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body p-4">
                <?php if ($customer): ?>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white shadow-sm"
                             style="width:48px;height:48px;background:linear-gradient(135deg,#2563eb,#38bdf8);font-size:1.2rem;">
                            <?= strtoupper(substr($customer->name, 0, 1)) ?>
                        </div>
                        <div>
                            <div class="fw-bold text-dark"><?= e($customer->name) ?></div>
                            <small class="text-muted"><?= e($customer->email) ?></small>
                        </div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Nomor Telepon / WhatsApp:</small>
                        <span class="fw-semibold font-monospace"><?= e($customer->phone ?: 'Belum dicantumkan') ?></span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Status Akun:</small>
                        <span class="badge <?= ($customer->status === 'active') ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?> rounded-pill">
                            <?= ucfirst($customer->status ?? 'active') ?>
                        </span>
                    </div>
                <?php else: ?>
                    <div class="text-muted small">Data customer tidak ditemukan (ID: #<?= $order->customer_id ?>).</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Technician Info Card -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0">Teknisi yang Ditugaskan</h6>
                <?php if ($technician): ?>
                    <a href="<?= url("admin/technicians/{$technician->id}") ?>" class="small text-success text-decoration-none fw-semibold">
                        Profil &rarr;
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body p-4">
                <?php if ($technician): ?>
                    <?php $techProfile = $technician->technicianProfile(); ?>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white shadow-sm"
                             style="width:48px;height:48px;background:linear-gradient(135deg,#059669,#10b981);font-size:1.2rem;">
                            <?= strtoupper(substr($technician->name, 0, 1)) ?>
                        </div>
                        <div>
                            <div class="fw-bold text-dark"><?= e($technician->name) ?></div>
                            <small class="text-muted"><?= e($technician->email) ?></small>
                        </div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Keahlian:</small>
                        <span class="fw-semibold small"><?= e($techProfile ? $techProfile->expertise : 'Spesialis Servis') ?></span>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">No. Kontak Teknisi:</small>
                        <span class="fw-semibold font-monospace"><?= e($technician->phone ?: '-') ?></span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Status Verifikasi:</small>
                        <?php
                            $vStat = $techProfile ? ($techProfile->verification_status ?? 'pending') : 'pending';
                            $vClass = match($vStat) {
                                'verified' => 'bg-success-subtle text-success',
                                'rejected' => 'bg-danger-subtle text-danger',
                                default    => 'bg-warning-subtle text-warning-emphasis'
                            };
                        ?>
                        <span class="badge <?= $vClass ?> rounded-pill">
                            <?= ucfirst($vStat) ?>
                        </span>
                    </div>
                <?php else: ?>
                    <div class="p-3 bg-warning-subtle text-warning-emphasis rounded-3 text-center">
                        <i class="bi bi-hourglass-split fs-4 d-block mb-1"></i>
                        <div class="fw-bold small">Belum Ditugaskan</div>
                        <small>Pesanan masih menunggu konfirmasi atau pengambilan oleh mitra teknisi.</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
