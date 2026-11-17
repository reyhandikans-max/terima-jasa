<!-- ============================================================
     PROFIL ADMINISTRATOR — EDIT IDENTITAS SISTEM
     ============================================================ -->
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= url('admin/dashboard') ?>" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Profil Administrator</li>
        </ol>
    </nav>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="bi bi-person-gear text-danger me-2"></i>Profil & Identitas Administrator
            </h4>
            <p class="text-muted small mb-0">Kelola informasi nama akun, email login, nomor WhatsApp resmi, dan kredensial keamanan Anda.</p>
        </div>
        <div>
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill font-monospace" style="font-size: .75rem;">
                <i class="bi bi-shield-fill-check me-1"></i> ACCESS LEVEL: ROOT ADMIN
            </span>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Kolom Kiri: Kartu Identitas Ringkas & Info Keamanan -->
    <div class="col-lg-4">
        <!-- Kartu Identitas Admin -->
        <div class="card border-0 shadow-sm rounded-4 text-center p-4 mb-4">
            <div class="position-relative d-inline-block mx-auto mb-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white shadow-sm"
                     style="width: 88px; height: 88px; background: linear-gradient(135deg, #ef4444, #f97316); font-size: 2.2rem; border: 4px solid #fff;">
                    <?= strtoupper(substr($admin->name ?? 'A', 0, 1)) ?>
                </div>
                <span class="position-absolute bottom-0 end-0 p-2 bg-success border border-white rounded-circle" title="Akun Aktif">
                    <span class="visually-hidden">Status Aktif</span>
                </span>
            </div>

            <h5 class="fw-bold mb-1 text-truncate" title="<?= e($admin->name) ?>"><?= e($admin->name) ?></h5>
            <p class="text-muted small mb-2 text-truncate" title="<?= e($admin->email) ?>">
                <i class="bi bi-envelope me-1"></i><?= e($admin->email) ?>
            </p>

            <div class="d-flex justify-content-center gap-2 mb-3">
                <span class="badge bg-danger rounded-pill px-3 py-1">Administrator</span>
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1">
                    <i class="bi bi-check-circle-fill me-1"></i><?= ucfirst($admin->status ?? 'Active') ?>
                </span>
            </div>

            <hr class="my-3 opacity-10">

            <div class="text-start small">
                <div class="d-flex justify-content-between py-1 border-bottom border-light">
                    <span class="text-muted"><i class="bi bi-hash me-1"></i>User ID:</span>
                    <span class="fw-semibold font-monospace">#<?= (int)$admin->id ?></span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom border-light">
                    <span class="text-muted"><i class="bi bi-whatsapp me-1"></i>No. WhatsApp:</span>
                    <span class="fw-semibold font-monospace"><?= !empty($admin->phone) ? e($admin->phone) : '<span class="text-muted fw-normal">Belum diatur</span>' ?></span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom border-light">
                    <span class="text-muted"><i class="bi bi-calendar-event me-1"></i>Bergabung:</span>
                    <span class="fw-semibold"><?= !empty($admin->created_at) ? date('d M Y', strtotime($admin->created_at)) : '-' ?></span>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted"><i class="bi bi-clock-history me-1"></i>Pembaruan Terakhir:</span>
                    <span class="fw-semibold text-truncate" style="max-width: 140px;" title="<?= !empty($admin->updated_at) ? date('d M Y, H:i', strtotime($admin->updated_at)) : 'Belum pernah' ?>">
                        <?= !empty($admin->updated_at) ? date('d M Y', strtotime($admin->updated_at)) : 'Baru' ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Kartu Pintasan Pengaturan Sistem -->
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-light">
            <h6 class="fw-bold mb-2 small text-uppercase text-muted" style="letter-spacing: .05em;">
                <i class="bi bi-sliders me-1"></i> Pintasan Konfigurasi
            </h6>
            <div class="d-grid gap-2">
                <a href="<?= url('admin/ai-settings') ?>" class="btn btn-white bg-white border text-start small py-2 d-flex align-items-center justify-content-between rounded-3 shadow-none">
                    <span><i class="bi bi-cpu text-warning me-2"></i>Konfigurasi AI Provider</span>
                    <i class="bi bi-chevron-right text-muted small"></i>
                </a>
                <a href="<?= url('admin/whatsapp') ?>" class="btn btn-white bg-white border text-start small py-2 d-flex align-items-center justify-content-between rounded-3 shadow-none">
                    <span><i class="bi bi-whatsapp text-success me-2"></i>WhatsApp Gateway OTP</span>
                    <i class="bi bi-chevron-right text-muted small"></i>
                </a>
                <a href="<?= url('admin/reports') ?>" class="btn btn-white bg-white border text-start small py-2 d-flex align-items-center justify-content-between rounded-3 shadow-none">
                    <span><i class="bi bi-graph-up-arrow text-danger me-2"></i>Laporan & Log Audit</span>
                    <i class="bi bi-chevron-right text-muted small"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Formulir Pembaruan Profil & Kata Sandi -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
            <form action="<?= url('admin/profile') ?>" method="POST" id="adminProfileForm" novalidate>
                <?= csrf_field() ?>

                <!-- BAGIAN 1: IDENTITAS UTAMA -->
                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                    <div class="p-2 bg-danger-subtle text-danger rounded-3">
                        <i class="bi bi-person-lines-fill fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">Identitas Utama Administrator</h6>
                        <small class="text-muted">Informasi nama dan alamat email yang digunakan untuk login ke panel sistem.</small>
                    </div>
                </div>

                <!-- Input Nama Lengkap -->
                <div class="mb-3">
                    <label for="nameInput" class="form-label small fw-bold">
                        Nama Lengkap / Identitas Administrator <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-person-badge"></i></span>
                        <input type="text" 
                               class="form-control <?= has_error('name') ? 'is-invalid' : '' ?>" 
                               id="nameInput" 
                               name="name" 
                               value="<?= e(old('name', $admin->name)) ?>" 
                               placeholder="Contoh: Super Admin Terima Jasa"
                               required 
                               autocomplete="name">
                        <?php if (has_error('name')): ?>
                            <div class="invalid-feedback"><?= e(error('name')) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-text small">Nama ini akan ditampilkan pada navbar, laporan sistem, dan log operasional platform.</div>
                </div>

                <div class="row g-3 mb-4">
                    <!-- Input Email -->
                    <div class="col-md-6">
                        <label for="emailInput" class="form-label small fw-bold">
                            Alamat Email Administrator <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope-at"></i></span>
                            <input type="email" 
                                   class="form-control <?= has_error('email') ? 'is-invalid' : '' ?>" 
                                   id="emailInput" 
                                   name="email" 
                                   value="<?= e(old('email', $admin->email)) ?>" 
                                   placeholder="admin@terimajasa.id"
                                   required 
                                   autocomplete="email">
                            <?php if (has_error('email')): ?>
                                <div class="invalid-feedback"><?= e(error('email')) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-text small">Digunakan sebagai kredensial login utama Anda.</div>
                    </div>

                    <!-- Input Telepon / WhatsApp -->
                    <div class="col-md-6">
                        <label for="phoneInput" class="form-label small fw-bold">
                            Nomor WhatsApp / Kontak HP
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-success"><i class="bi bi-whatsapp"></i></span>
                            <input type="tel" 
                                   class="form-control <?= has_error('phone') ? 'is-invalid' : '' ?>" 
                                   id="phoneInput" 
                                   name="phone" 
                                   value="<?= e(old('phone', $admin->phone)) ?>" 
                                   placeholder="Contoh: 08123456789 atau +628123456789"
                                   autocomplete="tel">
                            <?php if (has_error('phone')): ?>
                                <div class="invalid-feedback"><?= e(error('phone')) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-text small">Untuk menerima notifikasi sistem & verifikasi OTP darurat.</div>
                    </div>
                </div>

                <!-- BAGIAN 2: KEAMANAN & KATA SANDI (OPSIONAL) -->
                <div class="d-flex align-items-center gap-2 mb-3 mt-4 pt-3 pb-2 border-bottom">
                    <div class="p-2 bg-secondary-subtle text-secondary rounded-3">
                        <i class="bi bi-shield-lock fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">Ubah Kata Sandi (Opsional)</h6>
                        <small class="text-muted">Biarkan semua kolom di bawah kosong jika Anda tidak ingin mengubah kata sandi akun.</small>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <!-- Password Lama -->
                    <div class="col-12">
                        <label for="currentPasswordInput" class="form-label small fw-bold">
                            Kata Sandi Saat Ini (Lama)
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-key"></i></span>
                            <input type="password" 
                                   class="form-control <?= has_error('current_password') ? 'is-invalid' : '' ?>" 
                                   id="currentPasswordInput" 
                                   name="current_password" 
                                   placeholder="Wajib diisi hanya jika ingin mengganti kata sandi"
                                   autocomplete="current-password">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('currentPasswordInput', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                            <?php if (has_error('current_password')): ?>
                                <div class="invalid-feedback"><?= e(error('current_password')) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Password Baru -->
                    <div class="col-md-6">
                        <label for="newPasswordInput" class="form-label small fw-bold">
                            Kata Sandi Baru
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-lock"></i></span>
                            <input type="password" 
                                   class="form-control <?= has_error('new_password') ? 'is-invalid' : '' ?>" 
                                   id="newPasswordInput" 
                                   name="new_password" 
                                   placeholder="Minimal 6 karakter"
                                   autocomplete="new-password">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('newPasswordInput', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                            <?php if (has_error('new_password')): ?>
                                <div class="invalid-feedback"><?= e(error('new_password')) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Konfirmasi Password Baru -->
                    <div class="col-md-6">
                        <label for="newPasswordConfirmInput" class="form-label small fw-bold">
                            Konfirmasi Kata Sandi Baru
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" 
                                   class="form-control <?= has_error('new_password_confirmation') ? 'is-invalid' : '' ?>" 
                                   id="newPasswordConfirmInput" 
                                   name="new_password_confirmation" 
                                   placeholder="Ulangi kata sandi baru"
                                   autocomplete="new-password">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('newPasswordConfirmInput', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                            <?php if (has_error('new_password_confirmation')): ?>
                                <div class="invalid-feedback"><?= e(error('new_password_confirmation')) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- TOMBOL SIMPAN -->
                <div class="d-flex flex-wrap align-items-center justify-content-between pt-3 border-top gap-2">
                    <a href="<?= url('admin/dashboard') ?>" class="btn btn-light border px-4 py-2">
                        <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
                    </a>
                    <button type="submit" class="btn btn-danger px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm">
                        <i class="bi bi-check2-circle fs-5"></i> Simpan Perubahan Identitas
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) {
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        }
    } else {
        input.type = 'password';
        if (icon) {
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }
}
</script>

