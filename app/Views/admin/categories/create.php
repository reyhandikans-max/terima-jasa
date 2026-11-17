<!-- ============================================================
     ADMIN CATEGORY MANAGEMENT: TAMBAH KATEGORI BARU
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
                <li class="breadcrumb-item"><a href="<?= url('admin/categories') ?>" class="text-decoration-none">Kategori Jasa</a></li>
                <li class="breadcrumb-item active" aria-current="page">Tambah Baru</li>
            </ol>
        </nav>
        <h4 class="fw-bold mb-0">Tambah Kategori Jasa Baru</h4>
        <p class="text-muted small mb-0">Buat klasifikasi kategori servis baru untuk memudahkan pencarian pelanggan.</p>
    </div>
    <div>
        <a href="<?= url('admin/categories') ?>" class="btn btn-light border">
            &larr; Batal & Kembali
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-md-5">
                <form method="POST" action="<?= url('admin/categories') ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="Contoh: Servis AC & Pendingin" minlength="3">
                        <small class="text-muted">Nama kategori harus jelas dan merepresentasikan jenis perbaikan.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deskripsi Singkat</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Jelaskan jenis-jenis layanan yang dicakup kategori ini..."></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Status Kategori</label>
                        <select name="status" class="form-select">
                            <option value="active" selected>Aktif (Tampil di katalog customer)</option>
                            <option value="inactive">Nonaktif (Disembunyikan)</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= url('admin/categories') ?>" class="btn btn-light border px-4">Batal</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-check2-circle me-1"></i> Simpan Kategori
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
