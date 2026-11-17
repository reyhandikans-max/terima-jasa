<!-- ============================================================
     ADMIN SERVICE MANAGEMENT: LIST, FILTER & ACTIONS
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
                <li class="breadcrumb-item active" aria-current="page">Katalog Layanan</li>
            </ol>
        </nav>
        <h4 class="fw-bold mb-0">Manajemen Layanan Jasa</h4>
        <p class="text-muted small mb-0">Total <?= $totalCount ?> ragam servis terdaftar dalam sistem perbaikan Terima Jasa.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('admin/categories') ?>" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
            <i class="bi bi-tags"></i> Kelola Kategori
        </a>
        <a href="<?= url('admin/services/create') ?>" class="btn btn-primary d-inline-flex align-items-center gap-2">
            <i class="bi bi-plus-circle"></i> Tambah Layanan
        </a>
    </div>
</div>

<!-- Search & Filter Card -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= url('admin/services') ?>" class="row g-2 align-items-center">
            <div class="col-md-7 col-lg-8">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control bg-light border-start-0" 
                           placeholder="Cari nama layanan atau kata kunci perbaikan..." 
                           value="<?= e($search) ?>">
                </div>
            </div>
            <div class="col-md-3 col-lg-2">
                <select name="category_id" class="form-select bg-light">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat->id ?>" <?= ((string)$selectedCatId === (string)$cat->id) ? 'selected' : '' ?>>
                            <?= e($cat->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100 fw-semibold">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <?php if (!empty($search) || !empty($selectedCatId)): ?>
                    <a href="<?= url('admin/services') ?>" class="btn btn-outline-secondary" title="Reset filter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Service Table Card -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                    <th class="ps-4">Nama Layanan</th>
                    <th>Kategori</th>
                    <th>Deskripsi</th>
                    <th>Estimasi Biaya</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($services)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-grid fs-1 text-secondary d-block mb-2"></i>
                            <div class="fw-semibold">Tidak ada layanan jasa yang sesuai kriteria.</div>
                            <small>Klik "Tambah Layanan" untuk menambahkan layanan baru.</small>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($services as $srv): ?>
                        <?php
                            $cat = $srv->category();
                            $isActive = ($srv->status ?? 'active') === 'active';
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark"><?= e($srv->name) ?></div>
                                <small class="text-muted font-monospace">#SRV-<?= str_pad($srv->id, 4, '0', STR_PAD_LEFT) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-tag text-primary me-1"></i><?= e($cat ? $cat->name : 'Uncategorized') ?>
                                </span>
                            </td>
                            <td>
                                <span class="small text-muted text-truncate d-inline-block" style="max-width: 280px;">
                                    <?= e($srv->description ?: '-') ?>
                                </span>
                            </td>
                            <td>
                                <span class="fw-bold text-primary font-monospace">
                                    Rp <?= number_format($srv->estimated_price, 0, ',', '.') ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $isActive ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?> rounded-pill px-2 py-1">
                                    <?= $isActive ? 'Aktif' : 'Nonaktif' ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-inline-flex gap-1">
                                    <a href="<?= url("admin/services/{$srv->id}/edit") ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3" title="Edit">
                                        <i class="bi bi-pencil me-1"></i> Edit
                                    </a>
                                    <form method="POST" action="<?= url("admin/services/{$srv->id}/delete") ?>" 
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus layanan \'<?= e(addslashes($srv->name)) ?>\'?');" class="d-inline">
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
