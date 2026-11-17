<div class="container py-5 text-center my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="display-1 fw-bold text-primary mb-2">404</div>
            <h2 class="fw-bold mb-3">Halaman Tidak Ditemukan</h2>
            <p class="text-muted mb-4">
                Maaf, rute <code><?= e($requestedPath ?? '') ?></code> yang Anda cari tidak tersedia atau sedang dalam tahap pengembangan berikutnya.
            </p>
            <div class="d-flex justify-content-center gap-3">
                <a href="<?= url() ?>" class="btn btn-primary-tj">
                    <i class="bi bi-house-door-fill me-1"></i> Kembali ke Beranda
                </a>
                <a href="<?= url('dashboard') ?>" class="btn btn-outline-tj">
                    <i class="bi bi-grid-fill me-1"></i> Buka Dashboard
                </a>
            </div>
        </div>
    </div>
</div>
