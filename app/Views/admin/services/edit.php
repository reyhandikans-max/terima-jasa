<!-- ============================================================
     ADMIN SERVICE MANAGEMENT: EDIT LAYANAN JASA
     ============================================================ -->

<!-- Flash messages -->
<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-exclamation-octagon-fill text-danger fs-5"></i>
        <div><?= e($_SESSION['flash_error']) ?></div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?= url('admin/dashboard') ?>" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= url('admin/services') ?>" class="text-decoration-none">Katalog Layanan</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit <?= e($service->name) ?></li>
            </ol>
        </nav>
        <h4 class="fw-bold mb-0">Edit Layanan Jasa</h4>
        <p class="text-muted small mb-0">Perbarui informasi, harga estimasi, atau kategori layanan.</p>
    </div>
    <div>
        <a href="<?= url('admin/services') ?>" class="btn btn-light border">
            &larr; Batal & Kembali
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-md-5">
                <form method="POST" action="<?= url("admin/services/{$service->id}") ?>">
                    <div class="row g-3">
                        <!-- Kategori -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kategori Layanan <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat->id ?>" <?= ((int)$service->category_id === (int)$cat->id) ? 'selected' : '' ?>>
                                        <?= e($cat->name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Status -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status Layanan</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= ($service->status === 'active') ? 'selected' : '' ?>>Aktif (Dapat dipesan pelanggan)</option>
                                <option value="inactive" <?= ($service->status === 'inactive') ? 'selected' : '' ?>>Nonaktif (Sembunyikan)</option>
                            </select>
                        </div>

                        <!-- Nama Jasa -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Nama Layanan Jasa <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required 
                                   value="<?= e($service->name) ?>" minlength="3">
                        </div>

                        <!-- Estimasi Harga -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Estimasi Harga (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold">Rp</span>
                                <input type="number" name="estimated_price" class="form-control" required 
                                       value="<?= (int)$service->estimated_price ?>" min="0" step="1000">
                            </div>
                        </div>

                        <!-- Deskripsi -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Deskripsi Layanan & Cakupan Pekerjaan</label>
                            <textarea name="description" class="form-control" rows="4"><?= e($service->description) ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-4 mt-3 border-top">
                        <a href="<?= url('admin/services') ?>" class="btn btn-light border px-4">Batal</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-save me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
