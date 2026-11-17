<div class="tj-auth-card mx-auto">
    <div class="row g-0">
        <!-- Left Brand / Info Column -->
        <div class="col-lg-5 tj-auth-sidebar d-none d-lg-flex flex-column justify-content-between p-4 p-xl-5 text-white">
            <div>
                <a href="<?= url() ?>" class="d-flex align-items-center gap-2 text-white text-decoration-none fw-bold fs-4 mb-4">
                    <span class="tj-brand-badge"><i class="bi bi-wrench-adjustable-circle"></i></span>
                    <span>Terima<span class="text-info">Jasa</span></span>
                </a>
                <div class="badge bg-success bg-opacity-25 text-success-emphasis border border-success border-opacity-25 px-3 py-2 rounded-pill mb-3">
                    <i class="bi bi-whatsapp me-1"></i> WhatsApp Login Active
                </div>
                <h3 class="fw-bold mb-3">Login Cepat & Aman Tanpa Kata Sandi</h3>
                <p class="text-light opacity-75 small">
                    Nikmati kemudahan masuk ke platform Terima Jasa menggunakan nomor WhatsApp. Kode OTP langsung dikirimkan ke perangkat Anda.
                </p>
            </div>

            <div>
                <div class="p-3 rounded-3 bg-white bg-opacity-10 mb-3 border border-white border-opacity-10">
                    <div class="d-flex align-items-center gap-2 text-info small fw-bold mb-1">
                        <i class="bi bi-shield-check"></i> Keamanan Multi-Role
                    </div>
                    <small class="text-light opacity-75" style="font-size:.8rem;">
                        Sistem memverifikasi identitas pengguna berdasar data resmi untuk Customer, Teknisi, dan Admin.
                    </small>
                </div>
                <div class="d-flex align-items-center justify-content-between text-light opacity-50 small">
                    <span>&copy; <?= date('Y') ?> Terima Jasa</span>
                    <span>Versi 1.0.0</span>
                </div>
            </div>
        </div>

        <!-- Right Form Column -->
        <div class="col-lg-7 p-4 p-md-5">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <a href="<?= url() ?>" class="text-decoration-none small text-muted d-flex align-items-center gap-1">
                    <i class="bi bi-arrow-left"></i> Kembali ke Beranda
                </a>
                <span class="badge bg-light text-dark border">
                    <i class="bi bi-shield-lock-fill text-primary me-1"></i> Terenkripsi
                </span>
            </div>

            <div class="mb-4">
                <h3 class="fw-bold mb-1 text-dark">Login ke Terima Jasa</h3>
                <p class="text-muted small mb-0" id="loginSubtitle">Masuk dengan nomor WhatsApp untuk melanjutkan.</p>
            </div>

            <!-- Flash Messages -->
            <?php if (!empty($_SESSION['flash_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?= e($_SESSION['flash_error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['flash_info'])): ?>
                <div class="alert alert-info alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <?= e($_SESSION['flash_info']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['flash_info']); ?>
            <?php endif; ?>

            <!-- Dynamic Alert Box for JS/Ajax Messages -->
            <div id="authAlert" class="alert d-none border-0 shadow-sm" role="alert">
                <div class="d-flex align-items-center gap-2">
                    <i id="authAlertIcon" class="bi me-1"></i>
                    <span id="authAlertText"></span>
                </div>
            </div>

            <!-- Login Method Selector Tabs -->
            <ul class="nav nav-pills nav-fill mb-4 p-1 bg-light rounded-3 border" id="loginTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-semibold small py-2 d-flex align-items-center justify-content-center gap-2" 
                            id="tab-whatsapp-btn" 
                            data-bs-toggle="pill" 
                            data-bs-target="#tab-whatsapp" 
                            type="button" 
                            role="tab">
                        <i class="bi bi-whatsapp text-success"></i> WhatsApp OTP
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold small py-2 d-flex align-items-center justify-content-center gap-2" 
                            id="tab-password-btn" 
                            data-bs-toggle="pill" 
                            data-bs-target="#tab-password" 
                            type="button" 
                            role="tab">
                        <i class="bi bi-key-fill text-primary"></i> Email & Kata Sandi
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="loginTabsContent">
                <!-- ==================================================== -->
                <!-- TAB 1: WHATSAPP OTP LOGIN (UTAMA)                    -->
                <!-- ==================================================== -->
                <div class="tab-pane fade show active" id="tab-whatsapp" role="tabpanel">
                    <!-- Quick Test / Demo Accounts for Tester Convenience -->
                    <div class="mb-4 p-3 bg-light rounded-3 border">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="small fw-bold text-dark">
                                <i class="bi bi-phone text-success me-1"></i> Demo Akun WhatsApp Terdaftar:
                            </span>
                            <span class="badge bg-success-subtle text-success small">Klik untuk Mengisi</span>
                        </div>
                        <div class="row g-2">
                            <div class="col-4">
                                <button type="button" class="btn btn-outline-primary btn-sm w-100 py-1 demo-wa-btn" 
                                        data-phone="+6281234567890" 
                                        data-role="Customer" 
                                        title="Budi Santoso (Customer)">
                                    <span class="d-block fw-bold small">Customer</span>
                                    <small class="text-muted d-block" style="font-size:.7rem;">081234567890</small>
                                </button>
                            </div>
                            <div class="col-4">
                                <button type="button" class="btn btn-outline-success btn-sm w-100 py-1 demo-wa-btn" 
                                        data-phone="+6282198765432" 
                                        data-role="Teknisi" 
                                        title="Hendra Wijaya (Teknisi)">
                                    <span class="d-block fw-bold small">Teknisi</span>
                                    <small class="text-muted d-block" style="font-size:.7rem;">082198765432</small>
                                </button>
                            </div>
                            <div class="col-4">
                                <button type="button" class="btn btn-outline-danger btn-sm w-100 py-1 demo-wa-btn" 
                                        data-phone="+6281100001111" 
                                        data-role="Admin" 
                                        title="Super Admin Terima Jasa">
                                    <span class="d-block fw-bold small">Admin</span>
                                    <small class="text-muted d-block" style="font-size:.7rem;">081100001111</small>
                                </button>
                            </div>
                        </div>
                    </div>

                    <form id="whatsappLoginForm" novalidate>
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="whatsappPhoneInput" class="form-label small fw-semibold">
                                Nomor WhatsApp <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="bi bi-whatsapp text-success fs-5"></i>
                                </span>
                                <input type="tel"
                                       class="form-control border-start-0 ps-1"
                                       id="whatsappPhoneInput"
                                       name="phone"
                                       placeholder="+62 812-3456-7890"
                                       autocomplete="tel"
                                       required>
                                <button class="btn btn-outline-secondary" type="button" id="clearPhoneBtn" title="Hapus input" style="display:none;">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                            <div class="form-text small text-muted">
                                Masukkan nomor WhatsApp aktif (contoh: <code>+62 812-3456-7890</code> atau <code>081234567890</code>).
                            </div>
                            <div class="invalid-feedback d-block" id="phoneValidationError" style="display:none !important;"></div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="agreeTerms" checked>
                                <label class="form-check-label small text-muted" for="agreeTerms">
                                    Saya menyetujui <a href="#" class="text-decoration-none">Syarat & Ketentuan</a> Terima Jasa
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success w-100 py-2 mb-3 fw-semibold shadow-sm" id="btnSendOtp">
                            <span class="spinner-border spinner-border-sm me-2 d-none" id="sendOtpSpinner" role="status"></span>
                            <i class="bi bi-send-fill me-2" id="sendOtpIcon"></i>
                            <span id="sendOtpText">Kirim Kode OTP</span>
                        </button>

                        <div class="text-center mb-3">
                            <a href="javascript:void(0)" class="small text-decoration-none text-muted" id="switchToPasswordLink">
                                <i class="bi bi-shield-lock me-1"></i> Login dengan Password
                            </a>
                        </div>
                    </form>
                </div>

                <!-- ==================================================== -->
                <!-- TAB 2: EMAIL & PASSWORD LOGIN (LEGACY / FALLBACK)     -->
                <!-- ==================================================== -->
                <div class="tab-pane fade" id="tab-password" role="tabpanel">
                    <!-- 1-Click Quick Login (Demo) -->
                    <div class="mb-4 p-3 bg-light rounded-3 border">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="small fw-bold text-dark">
                                <i class="bi bi-lightning-charge-fill text-warning me-1"></i> Quick 1-Click Password (Demo):
                            </span>
                        </div>
                        <div class="row g-2">
                            <div class="col-4">
                                <form action="<?= url('login') ?>" method="POST">
                                    <input type="hidden" name="demo_role" value="customer">
                                    <button type="submit" class="btn btn-outline-primary btn-sm w-100 py-1">
                                        <span class="small fw-semibold">Customer</span>
                                    </button>
                                </form>
                            </div>
                            <div class="col-4">
                                <form action="<?= url('login') ?>" method="POST">
                                    <input type="hidden" name="demo_role" value="teknisi">
                                    <button type="submit" class="btn btn-outline-success btn-sm w-100 py-1">
                                        <span class="small fw-semibold">Teknisi</span>
                                    </button>
                                </form>
                            </div>
                            <div class="col-4">
                                <form action="<?= url('login') ?>" method="POST">
                                    <input type="hidden" name="demo_role" value="admin">
                                    <button type="submit" class="btn btn-outline-danger btn-sm w-100 py-1">
                                        <span class="small fw-semibold">Admin</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Standard Email/Password Form -->
                    <form action="<?= url('login') ?>" method="POST" id="passwordLoginForm">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="emailInput" class="form-label small fw-semibold">Email Pengguna</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                <input type="email"
                                       class="form-control <?= has_error('email') ? 'is-invalid' : '' ?>"
                                       id="emailInput"
                                       name="email"
                                       value="<?= e(old('email')) ?>"
                                       placeholder="email@contoh.com"
                                       required>
                                <?php if (has_error('email')): ?>
                                    <div class="invalid-feedback"><?= e(error('email')) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <label for="passwordInput" class="form-label small fw-semibold">Kata Sandi</label>
                                <a href="#" class="small text-decoration-none">Lupa Sandi?</a>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                                <input type="password"
                                       class="form-control <?= has_error('password') ? 'is-invalid' : '' ?>"
                                       id="passwordInput"
                                       name="password"
                                       placeholder="Masukkan kata sandi"
                                       required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword" tabindex="-1">
                                    <i class="bi bi-eye" id="eyeIcon"></i>
                                </button>
                                <?php if (has_error('password')): ?>
                                    <div class="invalid-feedback"><?= e(error('password')) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="rememberCheck" name="remember" value="1">
                            <label class="form-check-label small text-muted" for="rememberCheck">Ingat saya pada perangkat ini</label>
                        </div>

                        <button type="submit" class="btn btn-primary-tj w-100 py-2 mb-3 fw-semibold">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Masuk dengan Password
                        </button>
                    </form>
                </div>
            </div>

            <!-- Footer Registration Link -->
            <div class="text-center small text-muted pt-3 border-top mt-4">
                Belum memiliki akun? <a href="<?= url('register') ?>" class="text-primary fw-semibold text-decoration-none">Daftar sekarang</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const phoneInput = document.getElementById('whatsappPhoneInput');
    const clearBtn = document.getElementById('clearPhoneBtn');
    const waForm = document.getElementById('whatsappLoginForm');
    const btnSendOtp = document.getElementById('btnSendOtp');
    const sendOtpSpinner = document.getElementById('sendOtpSpinner');
    const sendOtpIcon = document.getElementById('sendOtpIcon');
    const sendOtpText = document.getElementById('sendOtpText');
    const authAlert = document.getElementById('authAlert');
    const authAlertText = document.getElementById('authAlertText');
    const authAlertIcon = document.getElementById('authAlertIcon');
    const validationError = document.getElementById('phoneValidationError');

    // Switch to password tab via link
    document.getElementById('switchToPasswordLink')?.addEventListener('click', function () {
        const tabTrigger = new bootstrap.Tab(document.getElementById('tab-password-btn'));
        tabTrigger.show();
    });

    // Toggle password visibility
    document.getElementById('togglePassword')?.addEventListener('click', function () {
        const input = document.getElementById('passwordInput');
        const icon  = document.getElementById('eyeIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'bi bi-eye';
        }
    });

    // 1-Click Fill Demo Account
    document.querySelectorAll('.demo-wa-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            phoneInput.value = this.getAttribute('data-phone');
            phoneInput.dispatchEvent(new Event('input'));
            clearValidation();
        });
    });

    // Format input phone
    phoneInput?.addEventListener('input', function () {
        clearValidation();
        if (this.value.trim().length > 0) {
            clearBtn.style.display = 'block';
        } else {
            clearBtn.style.display = 'none';
        }
    });

    clearBtn?.addEventListener('click', function () {
        phoneInput.value = '';
        clearBtn.style.display = 'none';
        phoneInput.focus();
        clearValidation();
    });

    function showAlert(type, message, htmlContent = null) {
        authAlert.className = `alert alert-${type} alert-dismissible fade show border-0 shadow-sm`;
        if (htmlContent) {
            authAlertText.innerHTML = htmlContent;
        } else {
            authAlertText.textContent = message;
        }
        authAlertIcon.className = `bi bi-${type === 'success' ? 'check-circle-fill text-success' : 'exclamation-triangle-fill text-danger'} me-2`;
        authAlert.classList.remove('d-none');
    }

    function clearValidation() {
        phoneInput.classList.remove('is-invalid');
        validationError.style.setProperty('display', 'none', 'important');
        validationError.textContent = '';
        authAlert.classList.add('d-none');
    }

    function showValidation(msg) {
        phoneInput.classList.add('is-invalid');
        validationError.textContent = msg;
        validationError.style.removeProperty('display');
    }

    // Submit WhatsApp Login Form (Request OTP)
    waForm?.addEventListener('submit', async function (e) {
        e.preventDefault();
        clearValidation();

        const phoneVal = phoneInput.value.trim();

        // Client-side quick validation (Server-side validation is always the authoritative check)
        if (!phoneVal) {
            showValidation('Silakan masukkan nomor WhatsApp Anda.');
            phoneInput.focus();
            return;
        }

        // Basic pattern check: digits, plus, hyphens, min 9 chars
        const cleanDigits = phoneVal.replace(/[^\d]/g, '');
        if (cleanDigits.length < 9) {
            showValidation('Nomor telepon terlalu pendek. Masukkan minimal 9-13 digit.');
            phoneInput.focus();
            return;
        }

        // Loading state
        btnSendOtp.disabled = true;
        sendOtpSpinner.classList.remove('d-none');
        sendOtpIcon.classList.add('d-none');
        sendOtpText.textContent = 'Mengirim Kode OTP...';

        try {
            const response = await fetch('<?= url('api/auth/whatsapp/request-otp') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    phone: phoneVal
                })
            });

            const result = await response.json();

            if (result.success) {
                showAlert('success', result.message || 'Kode OTP berhasil dikirim!');
                btnSendOtp.classList.replace('btn-success', 'btn-secondary');
                sendOtpText.textContent = 'Mengarahkan ke Verifikasi...';

                // Redirect to OTP verification page
                setTimeout(() => {
                    window.location.href = result.redirect_url || ('<?= url('login/whatsapp/verify?phone=') ?>' + encodeURIComponent(result.phone || phoneVal));
                }, 800);
            } else {
                // Tampilkan pesan kesalahan
                if (result.message && (result.message.includes('terputus') || result.message.includes('disconnected') || result.error_code === 'GATEWAY_ERROR')) {
                    const extraHtml = `<div>${result.message} <br><a href="<?= url('admin/whatsapp') ?>" target="_blank" class="btn btn-sm btn-outline-danger mt-2 fw-semibold"><i class="bi bi-qr-code me-1"></i> Hubungkan WhatsApp Gateway (Scan QR / Pairing)</a></div>`;
                    showAlert('danger', '', extraHtml);
                } else {
                    showAlert('danger', result.message || 'Gagal mengirim kode OTP.');
                }
                if (result.type === 'validation' || response.status === 422) {
                    showValidation(result.message);
                }
                btnSendOtp.disabled = false;
                sendOtpSpinner.classList.add('d-none');
                sendOtpIcon.classList.remove('d-none');
                sendOtpText.textContent = 'Kirim Kode OTP';
            }
        } catch (err) {
            showAlert('danger', 'Terjadi kesalahan jaringan atau server tidak merespons.');
            btnSendOtp.disabled = false;
            sendOtpSpinner.classList.add('d-none');
            sendOtpIcon.classList.remove('d-none');
            sendOtpText.textContent = 'Kirim Kode OTP';
        }
    });
});
</script>
