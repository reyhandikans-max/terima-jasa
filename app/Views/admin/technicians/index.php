<!-- ============================================================
     ADMIN TECHNICIAN MANAGEMENT: LIST, SEARCH & FILTER
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
                <li class="breadcrumb-item active" aria-current="page">Manajemen Teknisi</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h4 class="fw-bold mb-0">Daftar Mitra Teknisi</h4>
            <?php if ($pendingCount > 0): ?>
                <span class="badge bg-warning text-dark rounded-pill px-2 py-1">
                    <?= $pendingCount ?> Menunggu Verifikasi
                </span>
            <?php endif; ?>
        </div>
        <p class="text-muted small mb-0">Kelola validasi, status verifikasi, dan operasional seluruh mitra teknisi.</p>
    </div>
</div>

<!-- Search & Filter Bar -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= url('admin/technicians') ?>" class="row g-2 align-items-center">
            <div class="col-md-7 col-lg-8">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control bg-light border-start-0" 
                           placeholder="Cari nama teknisi, keahlian (AC, Listrik), lokasi, no telp..." 
                           value="<?= e($search) ?>">
                </div>
            </div>
            <div class="col-md-3 col-lg-2">
                <select name="verification" class="form-select bg-light">
                    <option value="">Semua Status Verifikasi</option>
                    <option value="pending" <?= ($verifFilter === 'pending') ? 'selected' : '' ?>>Pending (Menunggu)</option>
                    <option value="verified" <?= ($verifFilter === 'verified') ? 'selected' : '' ?>>Verified (Terverifikasi)</option>
                    <option value="rejected" <?= ($verifFilter === 'rejected') ? 'selected' : '' ?>>Rejected (Ditolak)</option>
                </select>
            </div>
            <div class="col-md-2 col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-success w-100 fw-semibold">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <?php if (!empty($search) || !empty($verifFilter)): ?>
                    <a href="<?= url('admin/technicians') ?>" class="btn btn-outline-secondary" title="Reset filter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Quick Status Filter Pills -->
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="<?= url('admin/technicians') ?>" class="btn btn-sm <?= empty($verifFilter) ? 'btn-dark' : 'btn-light border' ?> rounded-pill px-3">
        Semua (<?= $totalCount ?>)
    </a>
    <a href="<?= url('admin/technicians?verification=pending') ?>" class="btn btn-sm <?= ($verifFilter === 'pending') ? 'btn-warning text-dark' : 'btn-light border' ?> rounded-pill px-3">
        <i class="bi bi-hourglass-split me-1"></i> Menunggu Verifikasi
        <?php if ($pendingCount > 0): ?>
            <span class="badge bg-danger ms-1"><?= $pendingCount ?></span>
        <?php endif; ?>
    </a>
    <a href="<?= url('admin/technicians?verification=verified') ?>" class="btn btn-sm <?= ($verifFilter === 'verified') ? 'btn-success' : 'btn-light border' ?> rounded-pill px-3">
        <i class="bi bi-patch-check-fill me-1"></i> Terverifikasi
    </a>
    <a href="<?= url('admin/technicians?verification=rejected') ?>" class="btn btn-sm <?= ($verifFilter === 'rejected') ? 'btn-danger' : 'btn-light border' ?> rounded-pill px-3">
        <i class="bi bi-x-circle-fill me-1"></i> Ditolak
    </a>
</div>

<!-- Table Card -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                    <th class="ps-4">Mitra Teknisi</th>
                    <th>Bidang Keahlian</th>
                    <th>Pengalaman & Lokasi</th>
                    <th>Verifikasi</th>
                    <th>Ketersediaan</th>
                    <th>Akun</th>
                    <th class="text-end pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($techItems)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-tools fs-1 text-secondary d-block mb-2"></i>
                            <div class="fw-semibold">Tidak ada teknisi yang sesuai kriteria.</div>
                            <small>Silakan coba kata kunci atau filter lain.</small>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($techItems as $item): ?>
                        <?php
                            $techUser = $item['user'];
                            $profile  = $item['profile'];
                            $vStatus  = $item['status'];
                            $initial  = strtoupper(substr($techUser->name ?? 'T', 0, 1));

                            $vBadge = match($vStatus) {
                                'verified' => ['bg-success-subtle text-success border border-success-subtle', 'bi-patch-check-fill', 'Verified'],
                                'rejected' => ['bg-danger-subtle text-danger border border-danger-subtle', 'bi-x-circle-fill', 'Rejected'],
                                default    => ['bg-warning-subtle text-warning-emphasis border border-warning-subtle', 'bi-hourglass-split', 'Pending']
                            };

                            $avail = $profile ? ($profile->availability ?? 'offline') : 'offline';
                            $availBadge = match($avail) {
                                'available' => ['bg-success text-white', 'Siap Tugas'],
                                'busy'      => ['bg-warning text-dark', 'Sedang Bertugas'],
                                default     => ['bg-secondary text-white', 'Offline']
                            };

                            $uStatus = $techUser->status ?? 'active';
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white shadow-sm"
                                         style="width:40px;height:40px;background:linear-gradient(135deg,#059669,#10b981);flex-shrink:0;">
                                        <?= $initial ?>
                                    </div>
                                    <div>
                                        <a href="<?= url("admin/technicians/{$techUser->id}") ?>" class="fw-bold text-dark text-decoration-none">
                                            <?= e($techUser->name) ?>
                                        </a>
                                        <div class="text-muted small"><?= e($techUser->phone ?: $techUser->email) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="fw-semibold small d-block"><?= e($profile ? $profile->expertise : 'Belum diatur') ?></span>
                            </td>
                            <td>
                                <div class="small fw-semibold"><?= e($profile ? ($profile->experience ?? '-') : '-') ?></div>
                                <small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= e($profile ? ($profile->location ?? '-') : '-') ?></small>
                            </td>
                            <td>
                                <span class="badge <?= $vBadge[0] ?> rounded-pill px-2 py-1">
                                    <i class="bi <?= $vBadge[1] ?> me-1"></i><?= $vBadge[2] ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $availBadge[0] ?> rounded-pill" style="font-size: .72rem;">
                                    <?= $availBadge[1] ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= ($uStatus === 'active') ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?> rounded-pill">
                                    <?= ucfirst($uStatus) ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border rounded-pill px-3 dropdown-toggle" type="button" data-bs-dismiss="dropdown" data-bs-toggle="dropdown">
                                        Opsi
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                        <li>
                                            <a class="dropdown-item" href="<?= url("admin/technicians/{$techUser->id}") ?>">
                                                <i class="bi bi-eye text-primary me-2"></i> Detail Lengkap
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <?php if ($vStatus !== 'verified'): ?>
                                            <li>
                                                <form method="POST" action="<?= url("admin/technicians/{$techUser->id}/verify") ?>">
                                                    <button type="submit" class="dropdown-item text-success">
                                                        <i class="bi bi-check-circle-fill me-2"></i> Setujui (Verify)
                                                    </button>
                                                </form>
                                            </li>
                                        <?php endif; ?>
                                        <?php if ($vStatus !== 'rejected'): ?>
                                            <li>
                                                <form method="POST" action="<?= url("admin/technicians/{$techUser->id}/reject") ?>" onsubmit="return confirm('Tolak verifikasi teknisi ini?');">
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="bi bi-x-circle-fill me-2"></i> Tolak (Reject)
                                                    </button>
                                                </form>
                                            </li>
                                        <?php endif; ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" action="<?= url("admin/technicians/{$techUser->id}/toggle-status") ?>">
                                                <button type="submit" class="dropdown-item <?= ($uStatus === 'active') ? 'text-secondary' : 'text-success' ?>">
                                                    <i class="bi <?= ($uStatus === 'active') ? 'bi-toggle-off' : 'bi-toggle-on' ?> me-2"></i>
                                                    <?= ($uStatus === 'active') ? 'Nonaktifkan Akun' : 'Aktifkan Akun' ?>
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
