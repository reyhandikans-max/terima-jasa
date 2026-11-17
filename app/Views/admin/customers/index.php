<!-- ============================================================
     ADMIN CUSTOMER MANAGEMENT: LIST & SEARCH
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
                <li class="breadcrumb-item active" aria-current="page">Manajemen Customer</li>
            </ol>
        </nav>
        <h4 class="fw-bold mb-0">Daftar Customer (Pelanggan)</h4>
        <p class="text-muted small mb-0">Total <?= $totalCount ?> pengguna dengan role Customer terdaftar di sistem.</p>
    </div>
</div>

<!-- Search & Filter Card -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= url('admin/customers') ?>" class="row g-2 align-items-center">
            <div class="col-md-7 col-lg-8">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control bg-light border-start-0" 
                           placeholder="Cari berdasarkan nama, email, atau no handphone..." 
                           value="<?= e($search) ?>">
                </div>
            </div>
            <div class="col-md-3 col-lg-2">
                <select name="status" class="form-select bg-light">
                    <option value="">Semua Status</option>
                    <option value="active" <?= ($statusFilter === 'active') ? 'selected' : '' ?>>Aktif</option>
                    <option value="inactive" <?= ($statusFilter === 'inactive') ? 'selected' : '' ?>>Nonaktif</option>
                    <option value="suspended" <?= ($statusFilter === 'suspended') ? 'selected' : '' ?>>Suspended</option>
                </select>
            </div>
            <div class="col-md-2 col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100 fw-semibold">
                    <i class="bi bi-filter me-1"></i> Filter
                </button>
                <?php if (!empty($search) || !empty($statusFilter)): ?>
                    <a href="<?= url('admin/customers') ?>" class="btn btn-outline-secondary" title="Reset filter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Customer Table Card -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                    <th class="ps-4">Pelanggan</th>
                    <th>Email</th>
                    <th>No. Telepon</th>
                    <th>Status Akun</th>
                    <th>Tanggal Daftar</th>
                    <th class="text-end pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-person-x fs-1 text-secondary d-block mb-2"></i>
                            <div class="fw-semibold">Tidak ada customer yang sesuai kriteria pencarian.</div>
                            <small>Coba gunakan kata kunci lain atau reset filter.</small>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($customers as $c): ?>
                        <?php
                            $cStatus = $c->status ?? 'active';
                            $statusBadge = match($cStatus) {
                                'active'    => ['bg-success-subtle text-success', 'Aktif'],
                                'inactive'  => ['bg-secondary-subtle text-secondary', 'Nonaktif'],
                                default     => ['bg-danger-subtle text-danger', 'Suspended']
                            };
                            $initial = strtoupper(substr($c->name ?? 'C', 0, 1));
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white shadow-sm"
                                         style="width:40px;height:40px;background:linear-gradient(135deg,#2563eb,#38bdf8);flex-shrink:0;">
                                        <?= $initial ?>
                                    </div>
                                    <div>
                                        <a href="<?= url("admin/customers/{$c->id}") ?>" class="fw-bold text-dark text-decoration-none">
                                            <?= e($c->name) ?>
                                        </a>
                                        <div class="text-muted small">ID: #<?= str_pad($c->id, 4, '0', STR_PAD_LEFT) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="small"><?= e($c->email) ?></span>
                            </td>
                            <td>
                                <span class="small font-monospace"><?= e($c->phone ?? '-') ?></span>
                            </td>
                            <td>
                                <span class="badge <?= $statusBadge[0] ?> rounded-pill px-2 py-1">
                                    <i class="bi bi-circle-fill me-1" style="font-size: .45rem;"></i><?= $statusBadge[1] ?>
                                </span>
                            </td>
                            <td>
                                <span class="small text-muted">
                                    <?= !empty($c->created_at) ? date('d M Y', strtotime($c->created_at)) : '-' ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border rounded-pill px-3 dropdown-toggle" type="button" data-bs-dismiss="dropdown" data-bs-toggle="dropdown">
                                        Pilihan
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                        <li>
                                            <a class="dropdown-item" href="<?= url("admin/customers/{$c->id}") ?>">
                                                <i class="bi bi-eye text-primary me-2"></i> Lihat Detail
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="<?= url("admin/customers/{$c->id}/edit") ?>">
                                                <i class="bi bi-pencil text-warning me-2"></i> Edit Data
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" action="<?= url("admin/customers/{$c->id}/toggle-status") ?>">
                                                <button type="submit" class="dropdown-item <?= ($cStatus === 'active') ? 'text-secondary' : 'text-success' ?>">
                                                    <i class="bi <?= ($cStatus === 'active') ? 'bi-toggle-off' : 'bi-toggle-on' ?> me-2"></i>
                                                    <?= ($cStatus === 'active') ? 'Deactivate (Nonaktifkan)' : 'Activate (Aktifkan)' ?>
                                                </button>
                                            </form>
                                        </li>
                                        <li>
                                            <form method="POST" action="<?= url("admin/customers/{$c->id}/delete") ?>" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data customer ini? Data yang terhapus tidak dapat dikembalikan.');">
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="bi bi-trash me-2"></i> Hapus Customer
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
