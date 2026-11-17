<!-- ============================================================
     DAFTAR PESANAN SAYA — TERIMA JASA
     ============================================================ -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?= url('customer/dashboard') ?>">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Pesanan Saya</li>
            </ol>
        </nav>
        <h4 class="fw-bold mb-1">Daftar Pesanan Servis Saya</h4>
        <p class="text-muted small mb-0">Pantau perkembangan status servis dan penugasan teknisi Anda secara real-time.</p>
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
     FILTER STATUS PILLS
     ============================================================ -->
<div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
    <div class="d-flex align-items-center gap-2 overflow-auto" style="white-space: nowrap;">
        <span class="small fw-semibold text-muted me-2"><i class="bi bi-funnel me-1"></i>Status:</span>
        
        <a href="<?= url('customer/orders') ?>" 
           class="badge rounded-pill text-decoration-none px-3 py-2 <?= ($statusFilter === 'all') ? 'bg-primary text-white' : 'bg-light text-dark border' ?>">
            Semua (<?= $stats['total'] ?>)
        </a>
        
        <a href="<?= url('customer/orders?status=pending') ?>" 
           class="badge rounded-pill text-decoration-none px-3 py-2 <?= ($statusFilter === 'pending') ? 'bg-warning text-dark' : 'bg-light text-dark border' ?>">
            <i class="bi bi-hourglass-split me-1"></i>Menunggu Konfirmasi (<?= $stats['pending'] ?>)
        </a>

        <a href="<?= url('customer/orders?status=active') ?>" 
           class="badge rounded-pill text-decoration-none px-3 py-2 <?= ($statusFilter === 'active') ? 'bg-info text-dark' : 'bg-light text-dark border' ?>">
            <i class="bi bi-gear-wide-connected me-1"></i>Sedang Diproses (<?= $stats['active'] ?>)
        </a>

        <a href="<?= url('customer/orders?status=completed') ?>" 
           class="badge rounded-pill text-decoration-none px-3 py-2 <?= ($statusFilter === 'completed') ? 'bg-success text-white' : 'bg-light text-dark border' ?>">
            <i class="bi bi-check-circle-fill me-1"></i>Selesai (<?= $stats['completed'] ?>)
        </a>

        <a href="<?= url('customer/orders?status=cancelled') ?>" 
           class="badge rounded-pill text-decoration-none px-3 py-2 <?= ($statusFilter === 'cancelled') ? 'bg-danger text-white' : 'bg-light text-dark border' ?>">
            <i class="bi bi-x-circle-fill me-1"></i>Dibatalkan (<?= $stats['cancelled'] ?>)
        </a>
    </div>
</div>

<!-- ============================================================
     TABEL & DAFTAR PESANAN
     ============================================================ -->
<div class="tj-card-table shadow-sm">
    <div class="tj-card-header d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0">
            Daftar Pesanan 
            <span class="badge bg-secondary-subtle text-secondary ms-2 small"><?= count($orders) ?> Pesanan Ditemukan</span>
        </h6>
        <span class="small text-muted">Diurutkan dari pesanan terbaru</span>
    </div>

    <?php if (empty($orders)): ?>
        <div class="text-center py-5">
            <div class="rounded-circle d-inline-flex align-items-center justify-content-center bg-light text-muted mb-3" style="width: 72px; height: 72px;">
                <i class="bi bi-inbox fs-2"></i>
            </div>
            <h5 class="fw-bold mb-1">Belum Ada Pesanan Pada Kategori Ini</h5>
            <p class="text-muted small mb-4">Anda belum memiliki riwayat pesanan dengan filter status yang dipilih.</p>
            <div class="d-flex justify-content-center gap-2">
                <a href="<?= url('customer/orders') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    Lihat Semua Pesanan
                </a>
                <a href="<?= url('customer/orders/create') ?>" class="btn btn-primary-tj btn-sm rounded-pill px-4">
                    Buat Pesanan Baru
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-muted text-uppercase" style="font-size: .72rem; letter-spacing: .05em;">
                        <th class="ps-4">No. Order</th>
                        <th>Layanan Servis</th>
                        <th>Teknisi Menangani</th>
                        <th>Jadwal Kunjungan</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <?php
                            $service   = $order->service();
                            $tech      = $order->technician();
                            $badgeInfo = $order->getStatusBadge();
                            $review    = $order->review();
                        ?>
                        <tr>
                            <!-- No. Order -->
                            <td class="ps-4">
                                <span class="fw-bold small font-monospace text-primary">#TJ-<?= str_pad($order->id, 5, '0', STR_PAD_LEFT) ?></span>
                                <small class="text-muted d-block" style="font-size: .72rem;"><?= date('d/m/Y', strtotime($order->created_at)) ?></small>
                            </td>

                            <!-- Layanan Servis -->
                            <td>
                                <a href="<?= url('customer/orders/' . $order->id) ?>" class="text-dark fw-semibold small text-decoration-none hover-primary d-block">
                                    <?= e($service ? $service->name : 'Layanan Servis') ?>
                                </a>
                                <small class="text-muted" style="font-size: .75rem;">
                                    <i class="bi bi-geo-alt me-1"></i><?= e(strlen($order->location) > 35 ? substr($order->location, 0, 35) . '…' : $order->location) ?>
                                </small>
                            </td>

                            <!-- Teknisi Menangani -->
                            <td>
                                <?php if ($tech): ?>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold"
                                             style="width: 32px; height: 32px; font-size: .8rem;">
                                            <?= strtoupper(substr($tech->name, 0, 1)) ?>
                                        </div>
                                        <div>
                                            <span class="small fw-semibold d-block"><?= e($tech->name) ?></span>
                                            <small class="text-muted" style="font-size: .72rem;"><?= e($tech->phone ?? 'Aktif') ?></small>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary small">
                                        <i class="bi bi-clock me-1"></i>Menunggu Penugasan
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Jadwal -->
                            <td>
                                <span class="small fw-semibold text-dark d-block"><?= date('d M Y', strtotime($order->schedule)) ?></span>
                                <small class="text-muted" style="font-size: .75rem;"><?= date('H:i', strtotime($order->schedule)) ?> WIB</small>
                            </td>

                            <!-- Status Badge -->
                            <td>
                                <span class="badge <?= $badgeInfo['class'] ?> rounded-pill px-3 py-1 small">
                                    <i class="bi <?= $badgeInfo['icon'] ?> me-1"></i><?= $badgeInfo['label'] ?>
                                </span>
                                <?php if ($order->isCompleted()): ?>
                                    <?php if ($review): ?>
                                        <div class="mt-1 small text-warning" style="font-size: .72rem;">
                                            <i class="bi bi-star-fill"></i> Sudah Direview (★ <?= $review->rating ?>)
                                        </div>
                                    <?php else: ?>
                                        <div class="mt-1">
                                            <a href="<?= url('customer/orders/' . $order->id . '#reviewSection') ?>" class="badge bg-primary text-white text-decoration-none small" style="font-size: .68rem;">
                                                <i class="bi bi-star me-1"></i>Beri Ulasan
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>

                            <!-- Aksi -->
                            <td class="text-end pe-4">
                                <div class="d-flex align-items-center justify-content-end gap-1">
                                    <a href="<?= url('customer/orders/' . $order->id) ?>" class="btn btn-outline-primary btn-sm py-1 px-3">
                                        Detail
                                    </a>
                                    
                                    <?php if ($order->canCancel()): ?>
                                        <button type="button" 
                                                class="btn btn-outline-danger btn-sm py-1 px-2" 
                                                title="Batalkan Pesanan"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalCancel<?= $order->id ?>">
                                            <i class="bi bi-x-lg"></i>
                                        </button>

                                        <!-- Modal Konfirmasi Batal -->
                                        <div class="modal fade text-start" id="modalCancel<?= $order->id ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content rounded-4 border-0 shadow">
                                                    <div class="modal-header border-0 pb-0">
                                                        <h5 class="modal-title fw-bold text-danger">
                                                            <i class="bi bi-exclamation-triangle-fill me-2"></i>Batalkan Pesanan?
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body py-3">
                                                        <p class="mb-2">Apakah Anda yakin ingin membatalkan pesanan <strong>#TJ-<?= str_pad($order->id, 5, '0', STR_PAD_LEFT) ?></strong> (<?= e($service ? $service->name : 'Layanan Servis') ?>)?</p>
                                                        <small class="text-muted d-block">Tindakan ini tidak dapat dibatalkan. Pesanan yang dibatalkan tidak akan diteruskan ke teknisi.</small>
                                                    </div>
                                                    <div class="modal-footer border-0 pt-0">
                                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tidak, Tetap Lanjutkan</button>
                                                        <form action="<?= url('customer/orders/' . $order->id . '/cancel') ?>" method="POST">
                                                            <button type="submit" class="btn btn-danger">
                                                                Ya, Batalkan Pesanan
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
