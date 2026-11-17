<!-- ============================================================
     ADMIN CUSTOMER MANAGEMENT: EDIT CUSTOMER
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

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?= url('admin/dashboard') ?>" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= url('admin/customers') ?>" class="text-decoration-none">Customer</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit <?= e($customer->name) ?></li>
            </ol>
        </nav>
        <h4 class="fw-bold mb-0">Edit Data Customer</h4>
        <p class="text-muted small mb-0">Perbarui informasi profil dan status akun customer.</p>
    </div>
    <div>
        <a href="<?= url("admin/customers/{$customer->id}") ?>" class="btn btn-light border">
            &larr; Batal & Kembali
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-md-5">
                <form method="POST" action="<?= url("admin/customers/{$customer->id}/edit") ?>">
                    <div class="row g-3">
                        <!-- Nama Lengkap -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required
                                   value="<?= e($customer->name) ?>" placeholder="Masukkan nama lengkap">
                        </div>

                        <!-- Email -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Alamat Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required
                                   value="<?= e($customer->email) ?>" placeholder="contoh@domain.com">
                        </div>

                        <!-- Nomor Handphone -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nomor Handphone / WhatsApp</label>
                            <input type="text" name="phone" class="form-control"
                                   value="<?= e($customer->phone) ?>" placeholder="0812xxxxxxxx">
                        </div>

                        <!-- Status Akun -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Status Akun</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= ($customer->status === 'active') ? 'selected' : '' ?>>Aktif (Bisa login & pesan jasa)</option>
                                <option value="inactive" <?= ($customer->status === 'inactive') ? 'selected' : '' ?>>Nonaktif (Tidak bisa login sementara)</option>
                                <option value="suspended" <?= ($customer->status === 'suspended') ? 'selected' : '' ?>>Suspended (Diblokir oleh sistem)</option>
                            </select>
                            <small class="text-muted">Status akun memengaruhi akses login customer ke sistem.</small>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= url("admin/customers/{$customer->id}") ?>" class="btn btn-light border px-4">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-save me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
