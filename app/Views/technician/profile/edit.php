<!-- ============================================================
     PROFIL & STATUS KETERSEDIAAN TEKNISI — TERIMA JASA
     ============================================================ -->
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= url('technician/dashboard') ?>">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Profil & Ketersediaan</li>
        </ol>
    </nav>
    <h4 class="fw-bold mb-1">Pengaturan Profil Teknisi</h4>
    <p class="text-muted small mb-0">Kelola informasi profesional, wilayah kerja, dan status ketersediaan Anda untuk menerima order servis.</p>
</div>

<div class="row g-4">
    <!-- Kolom Kiri: Form Edit Profil -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
            <form action="<?= url('technician/profile') ?>" method="POST" id="techProfileForm">

                <!-- 1. DATA PRIBADI & AKUN -->
                <h6 class="fw-bold text-success mb-3 pb-2 border-bottom">
                    <i class="bi bi-person-circle me-2"></i>Informasi Akun & Kontak
                </h6>

                <!-- Nama Lengkap -->
                <div class="mb-3">
                    <label for="nameInput" class="form-label small fw-bold">Nama Lengkap <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                        <input type="text" 
                               class="form-control <?= has_error('name') ? 'is-invalid' : '' ?>" 
                               id="nameInput" 
                               name="name" 
                               value="<?= e(old('name', $currentUser['name'])) ?>" 
                               required>
                        <?php if (has_error('name')): ?>
                            <div class="invalid-feedback"><?= e(error('name')) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Email (Readonly) & No. Telepon -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Alamat Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control bg-light" value="<?= e($currentUser['email']) ?>" readonly disabled>
                        </div>
                        <small class="text-muted" style="font-size: .72rem;">Email digunakan sebagai identitas login unik.</small>
                    </div>

                    <div class="col-md-6">
                        <label for="phoneInput" class="form-label small fw-bold">Nomor WhatsApp / HP <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-whatsapp text-success"></i></span>
                            <input type="tel" 
                                   class="form-control <?= has_error('phone') ? 'is-invalid' : '' ?>" 
                                   id="phoneInput" 
                                   name="phone" 
                                   value="<?= e(old('phone', $currentUser['phone'] ?? ($profile ? $profile->phone : ''))) ?>" 
                                   required>
                            <?php if (has_error('phone')): ?>
                                <div class="invalid-feedback"><?= e(error('phone')) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- 2. DATA KEAHLIAN & PROFESIONAL -->
                <h6 class="fw-bold text-success mb-3 pb-2 border-bottom">
                    <i class="bi bi-tools me-2"></i>Kompetensi & Wilayah Pengerjaan
                </h6>

                <!-- Bidang Keahlian -->
                <div class="mb-3">
                    <label for="expertiseInput" class="form-label small fw-bold">Bidang Keahlian Utama <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-award text-primary"></i></span>
                        <input type="text" 
                               class="form-control <?= has_error('expertise') ? 'is-invalid' : '' ?>" 
                               id="expertiseInput" 
                               name="expertise" 
                               value="<?= e(old('expertise', $profile ? $profile->expertise : '')) ?>" 
                               placeholder="Contoh: Servis AC, Kulkas, Chiller & Pompa Air"
                               required>
                        <?php if (has_error('expertise')): ?>
                            <div class="invalid-feedback"><?= e(error('expertise')) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pengalaman & Wilayah Lokasi -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="expInput" class="form-label small fw-bold">Pengalaman Kerja <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-briefcase"></i></span>
                            <input type="text" 
                                   class="form-control <?= has_error('experience') ? 'is-invalid' : '' ?>" 
                                   id="expInput" 
                                   name="experience" 
                                   value="<?= e(old('experience', $profile ? $profile->experience : '')) ?>" 
                                   placeholder="Contoh: 6 Tahun Pengalaman"
                                   required>
                            <?php if (has_error('experience')): ?>
                                <div class="invalid-feedback"><?= e(error('experience')) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="locInput" class="form-label small fw-bold">Wilayah / Kota Cakupan <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-compass text-danger"></i></span>
                            <input type="text" 
                                   class="form-control <?= has_error('location') ? 'is-invalid' : '' ?>" 
                                   id="locInput" 
                                   name="location" 
                                   value="<?= e(old('location', $profile ? $profile->location : 'Jakarta Selatan')) ?>" 
                                   placeholder="Contoh: Jakarta Selatan, Jabodetabek"
                                   required>
                            <?php if (has_error('location')): ?>
                                <div class="invalid-feedback"><?= e(error('location')) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Alamat Domisili -->
                <div class="mb-4">
                    <label for="addrInput" class="form-label small fw-bold">Alamat Lengkap Workshop / Domisili <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light align-items-start pt-2"><i class="bi bi-geo-alt"></i></span>
                        <textarea class="form-control <?= has_error('address') ? 'is-invalid' : '' ?>" 
                                  id="addrInput" 
                                  name="address" 
                                  rows="3" 
                                  placeholder="Jl. Tebet Raya No. 45, Tebet, Jakarta Selatan"
                                  required><?= e(old('address', $profile ? $profile->address : '')) ?></textarea>
                        <?php if (has_error('address')): ?>
                            <div class="invalid-feedback"><?= e(error('address')) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 3. STATUS AVAILABILITY (KETERSEDIAAN) -->
                <h6 class="fw-bold text-success mb-3 pb-2 border-bottom">
                    <i class="bi bi-broadcast me-2"></i>Status Ketersediaan Terima Order
                </h6>

                <?php 
                    $currAvail = old('availability', $profile ? $profile->availability : 'available');
                ?>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="p-3 rounded-4 border w-100 d-flex align-items-start gap-3 <?= ($currAvail === 'available') ? 'border-success bg-success-subtle' : 'bg-light' ?>" style="cursor: pointer;">
                            <input type="radio" name="availability" value="available" class="mt-1" <?= ($currAvail === 'available') ? 'checked' : '' ?>>
                            <div>
                                <strong class="d-block text-success"><i class="bi bi-check-circle-fill me-1"></i>Available (Tersedia)</strong>
                                <small class="text-muted" style="font-size: .78rem;">
                                    Profil aktif dan siap menerima pesanan servis baru dari pelanggan di sekitar area Anda.
                                </small>
                            </div>
                        </label>
                    </div>

                    <div class="col-md-6">
                        <label class="p-3 rounded-4 border w-100 d-flex align-items-start gap-3 <?= ($currAvail === 'unavailable') ? 'border-secondary bg-secondary-subtle' : 'bg-light' ?>" style="cursor: pointer;">
                            <input type="radio" name="availability" value="unavailable" class="mt-1" <?= ($currAvail === 'unavailable') ? 'checked' : '' ?>>
                            <div>
                                <strong class="d-block text-secondary"><i class="bi bi-moon-fill me-1"></i>Unavailable (Tidak Tersedia)</strong>
                                <small class="text-muted" style="font-size: .78rem;">
                                    Sedang istirahat atau jadwal penuh. Sistem tidak akan mengirimkan notifikasi permintaan baru.
                                </small>
                            </div>
                        </label>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Submit Button -->
                <div class="d-flex align-items-center justify-content-between">
                    <a href="<?= url('technician/dashboard') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
                    </a>
                    <button type="submit" class="btn btn-success px-4 py-2 shadow-sm">
                        <i class="bi bi-floppy-fill me-2"></i> Simpan Perubahan Profil
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Kolom Kanan: Status & Tips Mitra -->
    <div class="col-lg-4">
        <!-- Status Verifikasi Mitra -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-shield-check text-success me-2"></i>Status Kemitraan</h6>
            
            <div class="d-flex align-items-center gap-3 p-3 rounded-3 bg-light border mb-3">
                <i class="bi bi-patch-check-fill fs-1 text-success"></i>
                <div>
                    <h6 class="fw-bold mb-0">Mitra Terverifikasi</h6>
                    <small class="text-muted">Kualifikasi teknisi telah divalidasi oleh Terima Jasa.</small>
                </div>
            </div>

            <div class="d-flex flex-column gap-2 small text-muted">
                <div class="d-flex align-items-center justify-content-between">
                    <span>ID Mitra:</span>
                    <strong class="text-dark font-monospace">TJ-TECH-<?= str_pad($currentUser['id'], 4, '0', STR_PAD_LEFT) ?></strong>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    <span>Role Akun:</span>
                    <span class="badge bg-success-subtle text-success">Teknisi Servis</span>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    <span>Garansi Komitmen:</span>
                    <span class="text-dark fw-semibold">Standar SNI & SOP</span>
                </div>
            </div>
        </div>

        <!-- Tips Meningkatkan Order -->
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-lightbulb-fill text-warning me-2"></i>Tips Mitra Sukses</h6>
            <ul class="list-unstyled d-flex flex-column gap-3 small text-muted mb-0">
                <li class="d-flex align-items-start gap-2">
                    <i class="bi bi-check-circle-fill text-success fs-6 mt-1"></i>
                    <span><strong>Respon Cepat:</strong> Menanggapi permintaan servis dalam 10 menit meningkatkan rasio konversi hingga 85%.</span>
                </li>
                <li class="d-flex align-items-start gap-2">
                    <i class="bi bi-check-circle-fill text-success fs-6 mt-1"></i>
                    <span><strong>Kerapihan & Etika:</strong> Berikan nota rincian yang transparan kepada customer untuk mengumpulkan ulasan bintang 5.</span>
                </li>
                <li class="d-flex align-items-start gap-2">
                    <i class="bi bi-check-circle-fill text-success fs-6 mt-1"></i>
                    <span><strong>Update Status:</strong> Pastikan selalu mengaktifkan status "Available" saat siap menerima panggilan servis.</span>
                </li>
            </ul>
        </div>
    </div>
</div>
