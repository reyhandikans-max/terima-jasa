<div class="tj-auth-card mx-auto">
    <div class="row g-0">
        <!-- Left Column -->
        <div class="col-lg-5 tj-auth-sidebar d-none d-lg-flex">
            <div>
                <a href="<?= url() ?>" class="d-flex align-items-center gap-2 text-white text-decoration-none fw-bold fs-4 mb-4">
                    <span class="tj-brand-badge"><i class="bi bi-wrench-adjustable-circle"></i></span>
                    <span>Terima<span class="text-info">Jasa</span></span>
                </a>
                <h3 class="fw-bold mb-3">Mulai Bersama Terima Jasa</h3>
                <p class="text-light opacity-75 small">
                    Bergabunglah dengan ekosistem solusi servis terbaik. Pilih peran Anda: cari teknisi handal dengan mudah, atau tawarkan keahlian Anda untuk penghasilan maksimal.
                </p>
            </div>
            <div>
                <ul class="list-unstyled small text-light opacity-90 d-flex flex-column gap-2 mb-4">
                    <li><i class="bi bi-check2-circle text-info me-2"></i> Gratis pendaftaran akun</li>
                    <li><i class="bi bi-check2-circle text-info me-2"></i> Akses konsultasi AI</li>
                    <li><i class="bi bi-check2-circle text-info me-2"></i> Perlindungan garansi pengerjaan</li>
                </ul>
                <small class="text-light opacity-50">&copy; <?= date('Y') ?> Terima Jasa Inc.</small>
            </div>
        </div>

        <!-- Right Form Column -->
        <div class="col-lg-7 p-4 p-md-5" style="overflow-y:auto; max-height:100vh;">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <a href="<?= url() ?>" class="text-decoration-none small text-muted d-flex align-items-center gap-1">
                    <i class="bi bi-arrow-left"></i> Kembali ke Beranda
                </a>
                <a href="<?= url('login') ?>" class="text-decoration-none small text-primary fw-semibold">
                    Sudah punya akun? Masuk
                </a>
            </div>

            <h3 class="fw-bold mb-1">Buat Akun Baru</h3>
            <p class="text-muted small mb-3">Pilih jenis akun yang sesuai:</p>

            <!-- Flash Messages -->
            <?php if (!empty($_SESSION['flash_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?= e($_SESSION['flash_error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>

            <form action="<?= url('register') ?>" method="POST" id="registerForm">
                <!-- Role Selection Cards -->
                <?php $selRole = $selectedRole ?? 'customer'; ?>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="tj-role-badge-btn w-100 <?= $selRole === 'customer' ? 'active' : '' ?>" id="lbl-customer" style="cursor:pointer;">
                            <input type="radio" name="role" value="customer" class="d-none"
                                   <?= $selRole === 'customer' ? 'checked' : '' ?>
                                   onchange="toggleTechFields(false)">
                            <div class="text-center">
                                <i class="bi bi-person-fill fs-5 text-primary"></i>
                                <div class="fw-bold small mt-1">Customer</div>
                                <div class="text-muted" style="font-size:.72rem;">Butuh Jasa Servis</div>
                            </div>
                        </label>
                    </div>
                    <div class="col-6">
                        <label class="tj-role-badge-btn w-100 <?= $selRole === 'teknisi' ? 'active' : '' ?>" id="lbl-teknisi" style="cursor:pointer;">
                            <input type="radio" name="role" value="teknisi" class="d-none"
                                   <?= $selRole === 'teknisi' ? 'checked' : '' ?>
                                   onchange="toggleTechFields(true)">
                            <div class="text-center">
                                <i class="bi bi-tools fs-5 text-success"></i>
                                <div class="fw-bold small mt-1">Mitra Teknisi</div>
                                <div class="text-muted" style="font-size:.72rem;">Tawarkan Keahlian</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Name -->
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                        <input type="text"
                               class="form-control <?= has_error('name') ? 'is-invalid' : '' ?>"
                               name="name"
                               value="<?= e(old('name')) ?>"
                               placeholder="Contoh: Andi Pratama"
                               required>
                        <?php if (has_error('name')): ?>
                            <div class="invalid-feedback"><?= e(error('name')) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Email + Phone Row -->
                <div class="row g-2 mb-2">
                    <div class="col-md-6 col-12">
                        <label class="form-label small fw-semibold">Alamat Email <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                            <input type="email"
                                   class="form-control <?= has_error('email') ? 'is-invalid' : '' ?>"
                                   name="email"
                                   value="<?= e(old('email')) ?>"
                                   placeholder="nama@email.com"
                                   required>
                            <?php if (has_error('email')): ?>
                                <div class="invalid-feedback"><?= e(error('email')) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label small fw-semibold">No. WhatsApp <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-whatsapp"></i></span>
                            <input type="tel"
                                   class="form-control <?= has_error('phone') ? 'is-invalid' : '' ?>"
                                   name="phone"
                                   value="<?= e(old('phone')) ?>"
                                   placeholder="08123456789"
                                   required>
                            <?php if (has_error('phone')): ?>
                                <div class="invalid-feedback"><?= e(error('phone')) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Password Row -->
                <div class="row g-2 mb-2">
                    <div class="col-md-6 col-12">
                        <label class="form-label small fw-semibold">Kata Sandi <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                            <input type="password"
                                   class="form-control <?= has_error('password') ? 'is-invalid' : '' ?>"
                                   name="password"
                                   id="reg_password"
                                   placeholder="Min. 6 karakter"
                                   required>
                            <?php if (has_error('password')): ?>
                                <div class="invalid-feedback"><?= e(error('password')) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label small fw-semibold">Konfirmasi Sandi <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-lock-fill"></i></span>
                            <input type="password"
                                   class="form-control <?= has_error('password_confirmation') ? 'is-invalid' : '' ?>"
                                   name="password_confirmation"
                                   id="reg_password_confirmation"
                                   placeholder="Ulangi sandi"
                                   required>
                            <?php if (has_error('password_confirmation')): ?>
                                <div class="invalid-feedback"><?= e(error('password_confirmation')) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Teknisi Fields (hidden by default if customer) -->
                <div id="techFields" class="<?= $selRole === 'teknisi' ? '' : 'd-none' ?>">
                    <hr class="my-2">
                    <p class="small fw-bold text-success mb-2"><i class="bi bi-tools me-1"></i> Data Mitra Teknisi</p>

                    <div class="row g-2 mb-2">
                        <div class="col-md-6 col-12">
                            <label class="form-label small fw-semibold">Bidang Keahlian <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-star"></i></span>
                                <input type="text"
                                       class="form-control <?= has_error('expertise') ? 'is-invalid' : '' ?>"
                                       name="expertise"
                                       value="<?= e(old('expertise')) ?>"
                                       placeholder="Contoh: Servis AC & Pendingin"
                                       id="expertiseField">
                                <?php if (has_error('expertise')): ?>
                                    <div class="invalid-feedback"><?= e(error('expertise')) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6 col-12">
                            <label class="form-label small fw-semibold">Pengalaman Kerja <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-briefcase"></i></span>
                                <input type="text"
                                       class="form-control <?= has_error('experience') ? 'is-invalid' : '' ?>"
                                       name="experience"
                                       value="<?= e(old('experience')) ?>"
                                       placeholder="Contoh: 5 Tahun"
                                       id="experienceField">
                                <?php if (has_error('experience')): ?>
                                    <div class="invalid-feedback"><?= e(error('experience')) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Alamat Lengkap <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-geo-alt"></i></span>
                            <textarea class="form-control <?= has_error('address') ? 'is-invalid' : '' ?>"
                                      name="address"
                                      rows="2"
                                      placeholder="Jl. Contoh No. 1, Jakarta Selatan"
                                      id="addressField"><?= e(old('address')) ?></textarea>
                            <?php if (has_error('address')): ?>
                                <div class="invalid-feedback"><?= e(error('address')) ?></div>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted" style="font-size:.72rem;">Dokumen verifikasi KTP akan diminta pada tahap kurasi berikutnya.</small>
                    </div>
                </div>

                <!-- Terms -->
                <div class="mb-3 form-check mt-3">
                    <input type="checkbox" class="form-check-input" id="termsCheck" required>
                    <label class="form-check-label small text-muted" for="termsCheck">
                        Saya menyetujui <a href="#" class="text-decoration-none">Syarat & Ketentuan</a> dan Kebijakan Privasi.
                    </label>
                </div>

                <button type="submit" class="btn btn-primary-tj w-100 py-2" id="btnRegister">
                    <i class="bi bi-person-plus-fill me-2"></i> Daftar Sekarang
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function toggleTechFields(show) {
    document.getElementById('techFields').classList.toggle('d-none', !show);
    document.getElementById('lbl-customer').classList.toggle('active', !show);
    document.getElementById('lbl-teknisi').classList.toggle('active', show);

    // Require/unrequire technician fields
    ['expertiseField', 'experienceField', 'addressField'].forEach(function(id) {
        const el = document.getElementById(id);
        if (el) el.required = show;
    });
}

// Set initial required state
document.addEventListener('DOMContentLoaded', function() {
    const isTech = document.querySelector('input[name="role"][value="teknisi"]')?.checked;
    toggleTechFields(isTech || false);
});
</script>
