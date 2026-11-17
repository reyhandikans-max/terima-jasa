<!-- ============================================================
     ADMIN CATEGORY MANAGEMENT: LIST & ACTIONS
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
                <li class="breadcrumb-item active" aria-current="page">Kategori Jasa</li>
            </ol>
        </nav>
        <h4 class="fw-bold mb-0">Manajemen Kategori Jasa</h4>
        <p class="text-muted small mb-0">Kelola kelompok kategori yang mengelompokkan ragam katalog servis.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('admin/services') ?>" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
            <i class="bi bi-grid"></i> Lihat Layanan
        </a>
        <a href="<?= url('admin/categories/create') ?>" class="btn btn-primary d-inline-flex align-items-center gap-2">
            <i class="bi bi-plus-circle"></i> Tambah Kategori
        </a>
    </div>
</div>

<!-- Category Table Card -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                    <th class="ps-4">Nama Kategori</th>
                    <th>Deskripsi</th>
                    <th>Jumlah Layanan</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="bi bi-tags fs-1 text-secondary d-block mb-2"></i>
                            <div class="fw-semibold">Belum ada kategori jasa.</div>
                            <small>Klik tombol "Tambah Kategori" untuk membuat kategori pertama.</small>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <?php
                            $cat = $item['category'];
                            $cnt = $item['serviceCount'];
                            $isActive = ($cat->status ?? 'active') === 'active';
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-3 d-flex align-items-center justify-content-center text-primary bg-primary-subtle"
                                         style="width:40px;height:40px;font-size:1.2rem;flex-shrink:0;">
                                        <i class="bi <?= e($cat->icon ?? 'bi-tag-fill') ?>"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= e($cat->name) ?></div>
                                        <small class="text-muted font-monospace">ID: #CAT-<?= str_pad($cat->id, 3, '0', STR_PAD_LEFT) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="small text-muted text-truncate d-inline-block" style="max-width: 320px;">
                                    <?= e($cat->description ?: 'Tidak ada deskripsi.') ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?= url("admin/services?category_id={$cat->id}") ?>" class="badge bg-light text-dark border text-decoration-none px-3 py-2">
                                    <i class="bi bi-grid-fill text-primary me-1"></i> <?= $cnt ?> Layanan
                                </a>
                            </td>
                            <td>
                                <span class="badge <?= $isActive ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?> rounded-pill px-2 py-1">
                                    <i class="bi bi-circle-fill me-1" style="font-size:.45rem;"></i><?= $isActive ? 'Aktif' : 'Nonaktif' ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-inline-flex gap-1">
                                    <a href="<?= url("admin/categories/{$cat->id}/edit") ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3" title="Edit Kategori">
                                        <i class="bi bi-pencil me-1"></i> Edit
                                    </a>
                                    <form method="POST" action="<?= url("admin/categories/{$cat->id}/delete") ?>" 
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori \'<?= e(addslashes($cat->name)) ?>\'?');" class="d-inline">
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2" title="Hapus Kategori">
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
