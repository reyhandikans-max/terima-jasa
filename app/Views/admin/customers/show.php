<!-- ============================================================
     ADMIN CUSTOMER MANAGEMENT: DETAIL & RIWAYAT
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

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?= url('admin/dashboard') ?>" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= url('admin/customers') ?>" class="text-decoration-none">Customer</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($customer->name) ?></li>
            </ol>
        </nav>
        <h4 class="fw-bold mb-0">Detail Pelanggan</h4>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url("admin/customers/{$customer->id}/edit") ?>" class="btn btn-outline-primary d-inline-flex align-items-center gap-2">
            <i class="bi bi-pencil"></i> Edit Profil
        </a>
        <form method="POST" action="<?= url("admin/customers/{$customer->id}/toggle-status") ?>" class="d-inline">
            <button type="submit" class="btn <?= ($customer->status === 'active') ? 'btn-outline-secondary' : 'btn-outline-success' ?> d-inline-flex align-items-center gap-2">
                <i class="bi <?= ($customer->status === 'active') ? 'bi-toggle-off' : 'bi-toggle-on' ?>"></i>
                <?= ($customer->status === 'active') ? 'Nonaktifkan' : 'Aktifkan' ?>
            </button>
        </form>
        <a href="<?= url('admin/customers') ?>" class="btn btn-light border">
            &larr; Kembali
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Profil Card -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4 text-center">
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center fw-bold text-white mb-3 shadow"
                     style="width:84px;height:84px;background:linear-gradient(135deg,#2563eb,#38bdf8);font-size:2rem;">
                    <?= strtoupper(substr($customer->name, 0, 1)) ?>
                </div>
                <h5 class="fw-bold mb-1"><?= e($customer->name) ?></h5>
                <p class="text-muted small mb-2"><?= e($customer->email) ?></p>

                <?php
                    $cStatus = $customer->status ?? 'active';
                    $statusBadge = match($cStatus) {
                        'active'    => ['bg-success-subtle text-success border-success-subtle', 'Aktif'],
                        'inactive'  => ['bg-secondary-subtle text-secondary border-secondary-subtle', 'Nonaktif'],
                        default     => ['bg-danger-subtle text-danger border-danger-subtle', 'Suspended']
                    };
                ?>
                <span class="badge <?= $statusBadge[0] ?> border rounded-pill px-3 py-2">
                    <i class="bi bi-circle-fill me-1" style="font-size: .45rem;"></i> Status: <?= $statusBadge[1] ?>
                </span>

                <hr class="my-4">

                <div class="text-start">
                    <div class="mb-3">
                        <small class="text-muted d-block">Nomor Handphone</small>
                        <span class="fw-semibold font-monospace"><?= e($customer->phone ?: 'Belum dicantumkan') ?></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">ID Pengguna</small>
                        <span class="fw-semibold font-monospace">#CUST-<?= str_pad($customer->id, 5, '0', STR_PAD_LEFT) ?></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Tanggal Registrasi</small>
                        <span class="fw-semibold">
                            <?= !empty($customer->created_at) ? date('d F Y, H:i', strtotime($customer->created_at)) : '-' ?>
                        </span>
                    </div>
                </div>

                <div class="pt-3 border-top text-start">
                    <form method="POST" action="<?= url("admin/customers/{$customer->id}/delete") ?>" onsubmit="return confirm('PERINGATAN: Apakah Anda yakin ingin menghapus akun customer ini secara permanen?');">
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100 rounded-pill">
                            <i class="bi bi-trash me-1"></i> Hapus Akun Customer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats & Order History -->
    <div class="col-lg-8">
        <!-- Stats Mini Cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <span class="text-muted small">Total Pemesanan</span>
                    <h3 class="fw-bold mb-0 text-dark"><?= $totalOrders ?></h3>
                    <small class="text-muted">Order dibuat</small>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <span class="text-muted small">Order Selesai</span>
                    <h3 class="fw-bold mb-0 text-success"><?= $completedCount ?></h3>
                    <small class="text-success"><?= $activeCount ?> sedang berjalan</small>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <span class="text-muted small">Total Pengeluaran</span>
                    <h4 class="fw-bold mb-0 text-primary">Rp <?= number_format($totalSpent, 0, ',', '.') ?></h4>
                    <small class="text-muted">Estimasi biaya</small>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0">Riwayat Pemesanan Servis</h6>
                <span class="badge bg-light text-dark border"><?= count($orders) ?> Catatan</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small text-muted text-uppercase" style="font-size:.72rem;">
                            <th class="ps-4">No. Order</th>
                            <th>Layanan Jasa</th>
                            <th>Teknisi</th>
                            <th>Jadwal</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted small">
                                    Customer ini belum pernah membuat pesanan servis.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <?php
                                    $srv = $order->service();
                                    $tech = $order->technician();
                                    $badge = $order->getStatusBadge();
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <span class="fw-bold font-monospace text-danger">#<?= str_pad($order->id, 5, '0', STR_PAD_LEFT) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold small"><?= e($srv ? $srv->name : 'Layanan') ?></div>
                                        <small class="text-muted">Rp <?= number_format($srv->estimated_price ?? 0, 0, ',', '.') ?></small>
                                    </td>
                                    <td>
                                        <span class="small"><?= e($tech ? $tech->name : 'Belum Ditugaskan') ?></span>
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
    </div>
</div>
