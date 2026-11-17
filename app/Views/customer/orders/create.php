<!-- ============================================================
     FORM MEMBUAT PESANAN BARU — TERIMA JASA
     ============================================================ -->
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= url('customer/dashboard') ?>">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= url('customer/orders') ?>">Pesanan Saya</a></li>
            <li class="breadcrumb-item active" aria-current="page">Buat Pesanan Baru</li>
        </ol>
    </nav>
    <h4 class="fw-bold mb-1">Form Pemesanan Jasa Servis</h4>
    <p class="text-muted small mb-0">Isi formulir di bawah ini dengan lengkap agar teknisi kami dapat segera menjadwalkan kunjungan.</p>
</div>

<div class="row g-4">
    <!-- Form Utama -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
            <form action="<?= url('customer/orders') ?>" method="POST" enctype="multipart/form-data" id="orderCreateForm">

                <!-- 1. PILIH JASA SERVIS -->
                <div class="mb-4">
                    <label for="serviceSelect" class="form-label small fw-bold">
                        Pilih Layanan Jasa <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-tools text-primary"></i></span>
                        <select class="form-select <?= has_error('service_id') ? 'is-invalid' : '' ?>" 
                                id="serviceSelect" 
                                name="service_id" 
                                required
                                onchange="updateServicePreview(this)">
                            <option value="">-- Pilih salah satu layanan jasa --</option>
                            <?php foreach ($services as $srv): ?>
                                <?php $cat = $srv->category(); ?>
                                <option value="<?= $srv->id ?>" 
                                        data-price="<?= $srv->estimated_price ?>"
                                        data-desc="<?= htmlspecialchars($srv->description, ENT_QUOTES) ?>"
                                        data-category="<?= htmlspecialchars($cat ? $cat->name : 'Umum', ENT_QUOTES) ?>"
                                        <?= ($selectedServiceId === (int)$srv->id || (int)old('service_id') === (int)$srv->id) ? 'selected' : '' ?>>
                                    [<?= e($cat ? $cat->name : 'Umum') ?>] <?= e($srv->name) ?> — Rp <?= number_format($srv->estimated_price, 0, ',', '.') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (has_error('service_id')): ?>
                            <div class="invalid-feedback"><?= e(error('service_id')) ?></div>
                        <?php endif; ?>
                    </div>
                    <small class="text-muted" style="font-size: .78rem;">
                        Belum tahu jenis jasa yang tepat? <a href="<?= url('customer/services') ?>" class="text-primary text-decoration-none">Cari di Katalog Jasa</a> atau tanyakan ke <a href="<?= url('customer/ai-assistant') ?>" class="text-primary text-decoration-none">AI Assistant</a>.
                    </small>
                </div>

                <!-- 1.1 PILIHAN TEKNISI (DIREKOMENDASIKAN AI / TERVERIFIKASI) -->
                <div class="mb-4">
                    <label for="technicianSelect" class="form-label small fw-bold d-flex align-items-center justify-content-between">
                        <span>Pilihan Mitra Teknisi <span class="badge bg-secondary-subtle text-secondary small">Opsional</span></span>
                        <?php if (!empty($selectedTech)): ?>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                <i class="bi bi-stars me-1"></i> Rekomendasi AI Terpilih
                            </span>
                        <?php endif; ?>
                    </label>

                    <?php if (!empty($selectedTech)): ?>
                        <!-- Card Teknisi Rekomendasi AI -->
                        <div class="p-3 mb-3 rounded-4 border border-primary-subtle bg-primary-subtle bg-opacity-10 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-5 flex-shrink-0" style="width: 48px; height: 48px;">
                                    <?= strtoupper(substr($selectedTech['name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark d-flex align-items-center gap-2">
                                        <span><?= e($selectedTech['name']) ?></span>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill py-0 px-2" style="font-size: .72rem;">
                                            <i class="bi bi-patch-check-fill me-1"></i> Verified
                                        </span>
                                    </div>
                                    <div class="small text-muted mb-1"><?= e($selectedTech['expertise']) ?></div>
                                    <div class="d-flex flex-wrap gap-2 small text-secondary" style="font-size: .78rem;">
                                        <span><i class="bi bi-star-fill text-warning me-1"></i><?= e($selectedTech['rating_label']) ?></span>
                                        <span>&bull;</span>
                                        <span><i class="bi bi-briefcase me-1 text-primary"></i><?= e($selectedTech['experience']) ?></span>
                                        <span>&bull;</span>
                                        <span><i class="bi bi-geo-alt me-1 text-danger"></i><?= e($selectedTech['location']) ?></span>
                                    </div>
                                </div>
                            </div>
                            <span class="badge bg-success text-white px-3 py-2 rounded-pill small flex-shrink-0">
                                <i class="bi bi-check-circle-fill me-1"></i> Siap Bertugas
                            </span>
                        </div>
                    <?php endif; ?>

                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-person-badge text-primary"></i></span>
                        <select class="form-select <?= has_error('technician_id') ? 'is-invalid' : '' ?>" 
                                id="technicianSelect" 
                                name="technician_id">
                            <option value="">-- Tugaskan Otomatis ke Teknisi Terbaik yang Tersedia --</option>
                            <?php foreach ($verifiedTechs as $vt): ?>
                                <option value="<?= $vt['user_id'] ?>" 
                                        <?= ($selectedTechId === (int)$vt['user_id'] || (int)old('technician_id') === (int)$vt['user_id']) ? 'selected' : '' ?>>
                                    <?= e($vt['name']) ?> [<?= e($vt['location']) ?>] — <?= e($vt['rating_label']) ?> (<?= e($vt['expertise']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (has_error('technician_id')): ?>
                            <div class="invalid-feedback"><?= e(error('technician_id')) ?></div>
                        <?php endif; ?>
                    </div>
                    <small class="text-muted" style="font-size: .78rem;">
                        Semua teknisi di sistem telah melalui proses verifikasi KTP, sertifikasi keahlian, dan pengecekan latar belakang.
                    </small>
                </div>

                <!-- 2. DESKRIPSI MASALAH / KELUHAN -->
                <div class="mb-4">
                    <label for="descInput" class="form-label small fw-bold">
                        Deskripsi Masalah & Gejala Kerusakan <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light align-items-start pt-2"><i class="bi bi-chat-left-text text-primary"></i></span>
                        <textarea class="form-control <?= has_error('description') ? 'is-invalid' : '' ?>" 
                                  id="descInput" 
                                  name="description" 
                                  rows="4" 
                                  placeholder="Jelaskan kendala selengkap mungkin. Contoh: AC kamar mengeluarkan bunyi dengung keras dan hembusan angin terasa kurang dingin sejak 3 hari lalu..."
                                  required><?= e(old('description')) ?></textarea>
                        <?php if (has_error('description')): ?>
                            <div class="invalid-feedback"><?= e(error('description')) ?></div>
                        <?php endif; ?>
                    </div>
                    <small class="text-muted" style="font-size: .78rem;">Minimal 5 karakter.</small>
                </div>

                <!-- 3. LOKASI PENGERJAAN -->
                <div class="mb-4">
                    <label for="locationInput" class="form-label small fw-bold">
                        Alamat Lengkap / Lokasi Pengerjaan <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-geo-alt-fill text-danger"></i></span>
                        <input type="text" 
                               class="form-control <?= has_error('location') ? 'is-invalid' : '' ?>" 
                               id="locationInput" 
                               name="location" 
                               value="<?= e(old('location', 'Jl. Kemang Timur No. 10, Jakarta Selatan')) ?>" 
                               placeholder="Contoh: Jl. Merpati No. 12 RT 04/02, Tebet, Jakarta Selatan (Patokan dekat minimarket)"
                               required>
                        <?php if (has_error('location')): ?>
                            <div class="invalid-feedback"><?= e(error('location')) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 4. JADWAL PENGERJAAN -->
                <div class="mb-4">
                    <label for="scheduleInput" class="form-label small fw-bold">
                        Jadwal Waktu Kunjungan Servis <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-calendar-event text-primary"></i></span>
                        <input type="datetime-local" 
                               class="form-control <?= has_error('schedule') ? 'is-invalid' : '' ?>" 
                               id="scheduleInput" 
                               name="schedule" 
                               value="<?= e(old('schedule', date('Y-m-d\TH:i', strtotime('+1 day 09:00')))) ?>" 
                               required>
                        <?php if (has_error('schedule')): ?>
                            <div class="invalid-feedback"><?= e(error('schedule')) ?></div>
                        <?php endif; ?>
                    </div>
                    <small class="text-muted" style="font-size: .78rem;">Pilih tanggal dan jam estimasi kedatangan teknisi.</small>
                </div>

                <!-- 5. UPLOAD FOTO MASALAH -->
                <div class="mb-4">
                    <label for="imageInput" class="form-label small fw-bold">
                        Upload Foto Masalah / Unit Alat <span class="badge bg-secondary-subtle text-secondary small">Opsional</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-image text-primary"></i></span>
                        <input type="file" 
                               class="form-control <?= has_error('problem_image') ? 'is-invalid' : '' ?>" 
                               id="imageInput" 
                               name="problem_image" 
                               accept="image/png, image/jpeg, image/jpg, image/webp"
                               onchange="previewImage(this)">
                        <?php if (has_error('problem_image')): ?>
                            <div class="invalid-feedback"><?= e(error('problem_image')) ?></div>
                        <?php endif; ?>
                    </div>
                    <small class="text-muted" style="font-size: .78rem;">Format: JPG, PNG, atau WEBP. Maksimal 5 MB.</small>
                    
                    <!-- Image Preview container -->
                    <div id="imagePreviewContainer" class="mt-3 d-none">
                        <div class="position-relative d-inline-block">
                            <img id="previewTarget" src="#" alt="Preview Foto Kerusakan" class="rounded-3 border shadow-sm" style="max-height: 180px; max-width: 100%; object-fit: cover;">
                            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 rounded-circle p-1" onclick="clearImagePreview()" title="Hapus foto">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Submit Button -->
                <div class="d-flex align-items-center justify-content-between">
                    <a href="<?= url('customer/orders') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                    <button type="submit" class="btn btn-primary-tj px-4 py-2" id="btnSubmitOrder">
                        <i class="bi bi-check-circle-fill me-2"></i> Kirim Pesanan Servis
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Kolom Kanan: Ringkasan Pesanan & Petunjuk -->
    <div class="col-lg-4">
        <!-- Live Preview Card -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 sticky-top" style="top: 100px;">
            <h6 class="fw-bold mb-3"><i class="bi bi-receipt text-primary me-2"></i>Ringkasan Estimasi</h6>

            <div class="p-3 rounded-3 bg-light border mb-3">
                <small class="text-muted d-block" style="font-size: .75rem;">Status Awal Pesanan:</small>
                <span class="badge bg-warning text-dark rounded-pill px-3 py-1">
                    <i class="bi bi-hourglass-split me-1"></i>Menunggu Konfirmasi (Pending)
                </span>
            </div>

            <div class="mb-3">
                <small class="text-muted d-block" style="font-size: .75rem;">Pemesan:</small>
                <strong class="small text-dark"><?= e($currentUser['name']) ?></strong>
                <span class="text-muted d-block" style="font-size: .78rem;"><?= e($currentUser['email']) ?></span>
            </div>

            <div class="mb-3">
                <small class="text-muted d-block" style="font-size: .75rem;">Kategori Jasa:</small>
                <span class="badge bg-light text-dark border small" id="previewCatBadge">
                    <?= e($selectedService ? ($selectedService->category() ? $selectedService->category()->name : 'Umum') : '-') ?>
                </span>
            </div>

            <div class="mb-4 pb-3 border-bottom">
                <small class="text-muted d-block" style="font-size: .75rem;">Estimasi Biaya Mulai:</small>
                <h4 class="fw-bold text-primary mb-0" id="previewPrice">
                    <?= $selectedService ? 'Rp ' . number_format($selectedService->estimated_price, 0, ',', '.') : 'Rp 0' ?>
                </h4>
            </div>

            <div class="d-flex flex-column gap-2 small text-muted">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-shield-check text-success fs-5"></i>
                    <span>Garansi servis 30 hari otomatis aktif setelah pekerjaan selesai.</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-person-check text-primary fs-5"></i>
                    <span>Ditugaskan ke mitra teknisi terverifikasi & terlatih.</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-x-circle text-danger fs-5"></i>
                    <span>Pesanan dapat dibatalkan secara mandiri selama status masih <strong>Pending</strong>.</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateServicePreview(selectEl) {
    const opt = selectEl.options[selectEl.selectedIndex];
    const priceEl = document.getElementById('previewPrice');
    const catBadge = document.getElementById('previewCatBadge');

    if (opt && opt.value) {
        const price = opt.getAttribute('data-price');
        const cat = opt.getAttribute('data-category');
        if (price) {
            priceEl.innerText = 'Rp ' + parseInt(price).toLocaleString('id-ID');
        }
        if (cat) {
            catBadge.innerText = cat;
        }
    } else {
        priceEl.innerText = 'Rp 0';
        catBadge.innerText = '-';
    }
}

function previewImage(input) {
    const container = document.getElementById('imagePreviewContainer');
    const target = document.getElementById('previewTarget');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            target.src = e.target.result;
            container.classList.remove('d-none');
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        container.classList.add('d-none');
    }
}

function clearImagePreview() {
    const input = document.getElementById('imageInput');
    const container = document.getElementById('imagePreviewContainer');
    const target = document.getElementById('previewTarget');
    if (input) input.value = '';
    target.src = '#';
    container.classList.add('d-none');
}
</script>
