<!-- ============================================================
     DAFTAR JASA & KATALOG LAYANAN — TERIMA JASA
     ============================================================ -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?= url('customer/dashboard') ?>">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Katalog Layanan Jasa</li>
            </ol>
        </nav>
        <h4 class="fw-bold mb-1">Katalog & Pencarian Jasa Servis</h4>
        <p class="text-muted small mb-0">Temukan solusi servis terpercaya oleh teknisi profesional berpengalaman.</p>
    </div>
    <a href="<?= url('customer/orders/create') ?>" class="btn btn-primary-tj btn-sm d-inline-flex align-items-center gap-2 shadow-sm">
        <i class="bi bi-plus-circle-fill"></i> Buat Pesanan Baru
    </a>
</div>

<!-- ============================================================
     SEARCH & FILTER BAR
     ============================================================ -->
<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <form action="<?= url('customer/services') ?>" method="GET" class="row g-3 align-items-center">
        <!-- Search input -->
        <div class="col-lg-7 col-md-6">
            <label for="searchInput" class="form-label small fw-semibold text-muted">Cari Nama Layanan atau Keluhan</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" 
                       class="form-control border-start-0 ps-0" 
                       id="searchInput" 
                       name="search" 
                       value="<?= e($search) ?>" 
                       placeholder="Contoh: AC bocor, Freon, Kulkas, Ganti LCD laptop, Korslet...">
                <?php if (!empty($search)): ?>
                    <a href="<?= url('customer/services' . (!empty($selectedCatId) ? '?category_id=' . $selectedCatId : '')) ?>" 
                       class="btn btn-outline-secondary" title="Hapus pencarian">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filter category dropdown -->
        <div class="col-lg-3 col-md-4">
            <label for="categoryFilter" class="form-label small fw-semibold text-muted">Filter Berdasarkan Kategori</label>
            <select name="category_id" id="categoryFilter" class="form-select" onchange="this.form.submit()">
                <option value="">Semua Kategori (<?= count($categories) ?>)</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat->id ?>" <?= ((string)$selectedCatId === (string)$cat->id) ? 'selected' : '' ?>>
                        <?= e($cat->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Submit button -->
        <div class="col-lg-2 col-md-2 d-flex align-items-end mt-4">
            <button type="submit" class="btn btn-primary-tj w-100 py-2">
                <i class="bi bi-funnel me-1"></i> Terapkan
            </button>
        </div>
    </form>

    <!-- Category Pills Quick Filter -->
    <div class="d-flex align-items-center gap-2 overflow-auto pt-3 border-top mt-3" style="white-space: nowrap;">
        <span class="small fw-semibold text-muted me-1"><i class="bi bi-tags me-1"></i>Kategori:</span>
        <a href="<?= url('customer/services' . (!empty($search) ? '?search=' . urlencode($search) : '')) ?>" 
           class="badge rounded-pill text-decoration-none px-3 py-2 <?= empty($selectedCatId) ? 'bg-primary text-white' : 'bg-light text-dark border' ?>">
            Semua
        </a>
        <?php foreach ($categories as $cat): ?>
            <a href="<?= url('customer/services?category_id=' . $cat->id . (!empty($search) ? '&search=' . urlencode($search) : '')) ?>" 
               class="badge rounded-pill text-decoration-none px-3 py-2 <?= ((string)$selectedCatId === (string)$cat->id) ? 'bg-primary text-white' : 'bg-light text-dark border' ?>">
                <i class="bi <?= e($cat->icon ?? 'bi-tools') ?> me-1"></i><?= e($cat->name) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- ============================================================
     DAFTAR LAYANAN (SERVICES GRID)
     ============================================================ -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <h6 class="fw-bold mb-0">
        Daftar Layanan Jasa
        <span class="badge bg-secondary-subtle text-secondary ms-2 small"><?= count($services) ?> Layanan Ditemukan</span>
    </h6>
    <?php if (!empty($selectedCategory)): ?>
        <span class="small text-muted">
            Menampilkan kategori: <strong><?= e($selectedCategory->name) ?></strong>
        </span>
    <?php endif; ?>
</div>

<?php if (empty($services)): ?>
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center my-4">
        <div class="rounded-circle d-inline-flex align-items-center justify-content-center bg-light text-muted mb-3 mx-auto"
             style="width: 72px; height: 72px;">
            <i class="bi bi-search fs-2"></i>
        </div>
        <h5 class="fw-bold mb-2">Layanan Tidak Ditemukan</h5>
        <p class="text-muted small mb-4" style="max-width: 480px; margin: 0 auto;">
            Tidak ditemukan jasa servis dengan kata kunci "<strong><?= e($search) ?></strong>"<?= !empty($selectedCategory) ? ' pada kategori <strong>'.e($selectedCategory->name).'</strong>' : '' ?>. Coba gunakan kata kunci lain atau reset filter Anda.
        </p>
        <div>
            <a href="<?= url('customer/services') ?>" class="btn btn-outline-primary btn-sm rounded-pill px-4">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Pencarian
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3 mb-4">
        <?php foreach ($services as $service): ?>
            <?php
                $cat = $service->category();
                $techs = $service->availableTechnicians();
            ?>
            <div class="col-lg-4 col-md-6">
                <div class="card border-0 shadow-sm rounded-4 h-100 d-flex flex-column hover-card-tj" style="transition: transform .2s, box-shadow .2s;">
                    <div class="card-body p-4 d-flex flex-column">
                        <!-- Category Badge & Estimated Price -->
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 small fw-semibold">
                                <i class="bi <?= e($cat->icon ?? 'bi-tools') ?> me-1"></i><?= e($cat ? $cat->name : 'Umum') ?>
                            </span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1 small">
                                <i class="bi bi-shield-check me-1"></i>Bergaransi
                            </span>
                        </div>

                        <!-- Service Title -->
                        <h5 class="fw-bold mb-2">
                            <a href="<?= url('customer/services/' . $service->id) ?>" class="text-dark text-decoration-none hover-primary">
                                <?= e($service->name) ?>
                            </a>
                        </h5>

                        <!-- Description -->
                        <p class="text-muted small mb-4 flex-grow-1" style="line-height: 1.5;">
                            <?= e(strlen($service->description) > 110 ? substr($service->description, 0, 110) . '…' : $service->description) ?>
                        </p>

                        <!-- Price & Available Technicians Info -->
                        <div class="pt-3 border-top mt-auto">
                            <div class="d-flex align-items-baseline justify-content-between mb-3">
                                <div>
                                    <small class="text-muted d-block" style="font-size: .75rem;">Estimasi Biaya Mulai</small>
                                    <span class="fw-bold fs-5 text-primary">Rp <?= number_format($service->estimated_price, 0, ',', '.') ?></span>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted d-block" style="font-size: .75rem;">Teknisi Siap</small>
                                    <span class="badge bg-light text-dark border small">
                                        <i class="bi bi-person-check text-success me-1"></i><?= count($techs) ?> Mitra Siaga
                                    </span>
                                </div>
                            </div>

                            <!-- Buttons: Detail & Pesan Langsung -->
                            <div class="d-flex gap-2">
                                <a href="<?= url('customer/services/' . $service->id) ?>" class="btn btn-outline-secondary btn-sm flex-fill py-2">
                                    Lihat Detail
                                </a>
                                <a href="<?= url('customer/orders/create?service_id=' . $service->id) ?>" class="btn btn-primary-tj btn-sm flex-fill py-2">
                                    <i class="bi bi-lightning-fill me-1"></i> Pesan Jasa
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
