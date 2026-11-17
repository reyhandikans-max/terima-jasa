<!-- ============================================================
     ADMIN ORDER MANAGEMENT: LIST & FILTERS (Status, Tanggal, Jasa, Teknisi)
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
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?= url('admin/dashboard') ?>" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Manajemen Order</li>
            </ol>
        </nav>
        <h4 class="fw-bold mb-0">Seluruh Transaksi & Order Masuk</h4>
        <p class="text-muted small mb-0">Total <?= $totalCount ?> transaksi servis tercatat di seluruh sistem.</p>
    </div>
    <div>
        <a href="<?= url('admin/reports') ?>" class="btn btn-outline-danger d-inline-flex align-items-center gap-2">
            <i class="bi bi-graph-up"></i> Lihat Laporan Order
        </a>
    </div>
</div>

<!-- ============================================================
     FILTER SECTION (Status, Tanggal, Jasa, Teknisi)
     ============================================================ -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <div class="fw-semibold small text-uppercase" style="letter-spacing:.05em;">
            <i class="bi bi-funnel text-danger me-1"></i> Filter Pencarian Order
        </div>
        <?php if (!empty($statusFilter) || !empty($dateFilter) || !empty($serviceId) || !empty($techId) || !empty($search)): ?>
            <a href="<?= url('admin/orders') ?>" class="text-danger text-decoration-none small fw-semibold">
                <i class="bi bi-x-circle me-1"></i> Reset Semua Filter
            </a>
        <?php endif; ?>
    </div>
    <div class="card-body p-3 p-md-4">
        <form method="GET" action="<?= url('admin/orders') ?>" class="row g-3">
            <!-- 1. Search Text -->
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted">Kata Kunci (No. Order / Lokasi / Pelanggan)</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control bg-light border-start-0" 
                           placeholder="Contoh: Kemang / AC / #1..." value="<?= e($search) ?>">
                </div>
            </div>

            <!-- 2. Filter Status -->
            <div class="col-md-2 col-sm-6">
                <label class="form-label small fw-semibold text-muted">Status Order</label>
                <select name="status" class="form-select bg-light">
                    <option value="">Semua Status</option>
                    <option value="pending" <?= ($statusFilter === 'pending') ? 'selected' : '' ?>>Pending (Menunggu)</option>
                    <option value="accepted" <?= ($statusFilter === 'accepted') ? 'selected' : '' ?>>Accepted (Diterima)</option>
                    <option value="in_progress" <?= ($statusFilter === 'in_progress') ? 'selected' : '' ?>>In Progress (Dikerjakan)</option>
                    <option value="completed" <?= ($statusFilter === 'completed') ? 'selected' : '' ?>>Completed (Selesai)</option>
                    <option value="cancelled" <?= ($statusFilter === 'cancelled') ? 'selected' : '' ?>>Cancelled (Batal)</option>
                </select>
            </div>

            <!-- 3. Filter Tanggal -->
            <div class="col-md-2 col-sm-6">
                <label class="form-label small fw-semibold text-muted">Tanggal (Jadwal/Dibuat)</label>
                <input type="date" name="date" class="form-control bg-light" value="<?= e($dateFilter) ?>">
            </div>

            <!-- 4. Filter Jasa -->
            <div class="col-md-2 col-sm-6">
                <label class="form-label small fw-semibold text-muted">Pilihan Jasa</label>
                <select name="service_id" class="form-select bg-light">
                    <option value="">Semua Jasa</option>
                    <?php foreach ($services as $s): ?>
                        <option value="<?= $s->id ?>" <?= ((string)$serviceId === (string)$s->id) ? 'selected' : '' ?>>
                            <?= e($s->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 5. Filter Teknisi -->
            <div class="col-md-2 col-sm-6">
                <label class="form-label small fw-semibold text-muted">Teknisi Bertugas</label>
                <select name="technician_id" class="form-select bg-light">
                    <option value="">Semua Teknisi</option>
                    <option value="unassigned" <?= ($techId === 'unassigned') ? 'selected' : '' ?>>[Belum Ditugaskan]</option>
                    <?php foreach ($technicians as $t): ?>
                        <option value="<?= $t->id ?>" <?= ((string)$techId === (string)$t->id) ? 'selected' : '' ?>>
                            <?= e($t->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 text-end">
                <button type="submit" class="btn btn-danger px-4 fw-semibold">
                    <i class="bi bi-funnel me-1"></i> Terapkan Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     ORDER TABLE LIST
     ============================================================ -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0">Daftar Hasil Order</h6>
        <span class="badge bg-light text-dark border"><?= count($orders) ?> Ditemukan</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                    <th class="ps-4">No. Order</th>
                    <th>Customer</th>
                    <th>Layanan Jasa</th>
                    <th>Teknisi Bertugas</th>
                    <th>Jadwal & Lokasi</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-receipt-cutoff fs-1 text-secondary d-block mb-2"></i>
                            <div class="fw-semibold">Tidak ada pesanan servis yang cocok dengan filter.</div>
                            <small>Coba sesuaikan tanggal, status, atau filter lainnya.</small>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <?php
                            $customer = $order->customer();
                            $tech     = $order->technician();
                            $service  = $order->service();
                            $badge    = $order->getStatusBadge();
                        ?>
                        <tr>
                            <td class="ps-4">
                                <span class="fw-bold font-monospace text-danger">#<?= str_pad($order->id, 5, '0', STR_PAD_LEFT) ?></span>
                                <div class="text-muted" style="font-size:.72rem;">
                                    <?= !empty($order->created_at) ? date('d/m/y H:i', strtotime($order->created_at)) : '-' ?>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold small">
                                    <?php if ($customer): ?>
                                        <a href="<?= url("admin/customers/{$customer->id}") ?>" class="text-dark text-decoration-none">
                                            <?= e($customer->name) ?>
                                        </a>
                                    <?php else: ?>
                                        Customer #<?= $order->customer_id ?>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted"><?= e($customer->phone ?? '-') ?></small>
                            </td>
                            <td>
                                <div class="fw-semibold small"><?= e($service ? $service->name : 'Layanan Jasa') ?></div>
                                <small class="text-primary fw-semibold font-monospace">Rp <?= number_format($service->estimated_price ?? 0, 0, ',', '.') ?></small>
                            </td>
                            <td>
                                <?php if ($tech): ?>
                                    <a href="<?= url("admin/technicians/{$tech->id}") ?>" class="badge bg-light text-dark border text-decoration-none">
                                        <i class="bi bi-person-check text-success me-1"></i><?= e($tech->name) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis">
                                        <i class="bi bi-hourglass-split me-1"></i>Belum Ditugaskan
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small fw-semibold text-dark">
                                    <i class="bi bi-calendar-event text-secondary me-1"></i><?= date('d M Y, H:i', strtotime($order->schedule)) ?>
                                </div>
                                <small class="text-muted text-truncate d-inline-block" style="max-width: 200px;">
                                    <i class="bi bi-geo-alt me-1"></i><?= e($order->location) ?>
                                </small>
                            </td>
                            <td>
                                <span class="badge <?= $badge['class'] ?> rounded-pill px-2 py-1">
                                    <i class="bi <?= $badge['icon'] ?> me-1"></i><?= $badge['label'] ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <a href="<?= url("admin/orders/{$order->id}") ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                    Detail <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
