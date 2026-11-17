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
                    <i class="bi bi-shield-check me-1"></i> Verifikasi Keamanan 2-Faktor
                </div>
                <h3 class="fw-bold mb-3">Verifikasi Nomor WhatsApp</h3>
                <p class="text-light opacity-75 small">
                    Kami menjaga keamanan akun Anda dengan verifikasi One-Time Password (OTP) 6 digit yang dikirimkan langsung ke nomor WhatsApp resmi Anda.
                </p>
            </div>

            <div>
                <div class="p-3 rounded-3 bg-white bg-opacity-10 mb-3 border border-white border-opacity-10">
                    <div class="d-flex align-items-center gap-2 text-info small fw-bold mb-1">
                        <i class="bi bi-info-circle-fill"></i> Tips Keamanan
                    </div>
                    <small class="text-light opacity-75" style="font-size:.8rem;">
                        Kode verifikasi berlaku selama 5 menit. Jangan pernah berikan kode ini kepada siapa pun termasuk staf Terima Jasa.
                    </small>
                </div>
                <div class="d-flex align-items-center justify-content-between text-light opacity-50 small">
                    <span>&copy; <?= date('Y') ?> Terima Jasa</span>
                    <span>Privasi Terjaga</span>
                </div>
            </div>
        </div>

        <!-- Right Form Column -->
        <div class="col-lg-7 p-4 p-md-5">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <a href="<?= url('login') ?>" class="text-decoration-none small text-muted d-flex align-items-center gap-1">
                    <i class="bi bi-arrow-left"></i> Ganti Nomor WhatsApp
                </a>
                <span class="badge bg-light text-dark border">
                    <i class="bi bi-clock-history text-primary me-1"></i> OTP 6 Digit
                </span>
            </div>

            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center bg-success-subtle text-success rounded-circle mb-3" style="width: 64px; height: 64px;">
                    <i class="bi bi-whatsapp fs-2"></i>
                </div>
                <h3 class="fw-bold mb-1 text-dark">Verifikasi Nomor WhatsApp</h3>
                <p class="text-muted small mb-2">
                    Kami telah mengirimkan kode verifikasi ke nomor WhatsApp Anda:
                </p>
                <div class="d-inline-flex align-items-center gap-2 bg-light px-3 py-1 rounded-pill border">
                    <span class="fw-bold text-dark small" id="displayPhone">
                        <?= e($maskedPhone ?? $phone ?? '+62 8xx-xxxx-xxxx') ?>
                    </span>
                    <a href="<?= url('login') ?>" class="small text-primary text-decoration-none" title="Ubah nomor telepon">
                        <i class="bi bi-pencil-square"></i> Ubah
                    </a>
                </div>
            </div>

            <!-- Dynamic Alert Box -->
            <div id="otpAlert" class="alert d-none border-0 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center gap-2">
                    <i id="otpAlertIcon" class="bi me-1"></i>
                    <span id="otpAlertText"></span>
                </div>
            </div>

            <!-- Developer Simulation Helper (Only in dev when session simulated) -->
            <?php if (!empty($_SESSION['_dev_last_otp']['code']) && (config('app.debug') || config('app.env') === 'development')): ?>
                <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm small py-2 mb-3" role="alert">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <i class="bi bi-tools me-1 text-warning"></i>
                            <strong>Mode Dev / Simulasi Gateway:</strong> Kode OTP adalah 
                            <span class="badge bg-dark fs-6 font-monospace px-2 py-1"><?= e($_SESSION['_dev_last_otp']['code']) ?></span>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-dark py-0" id="btnAutoFillDev" data-otp="<?= e($_SESSION['_dev_last_otp']['code']) ?>">
                            Isi Otomatis
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- OTP Form -->
            <form id="otpVerifyForm" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" id="targetPhone" value="<?= e($phone ?? '') ?>">
                <input type="hidden" id="fullOtpInput" name="otp" value="">

                <!-- 6 Digit Input Boxes -->
                <div class="mb-4 text-center">
                    <label class="form-label small fw-semibold text-muted mb-2">Masukkan 6 Digit Kode OTP</label>
                    <div class="d-flex justify-content-center gap-2" id="otpBoxContainer">
                        <?php for ($i = 0; $i < 6; $i++): ?>
                            <input type="text"
                                   inputmode="numeric"
                                   pattern="[0-9]*"
                                   maxlength="1"
                                   class="form-control text-center fs-4 fw-bold otp-digit"
                                   style="width: 48px; height: 56px; border-radius: 10px;"
                                   data-index="<?= $i ?>"
                                   autocomplete="off"
                                   <?= $i === 0 ? 'autofocus' : '' ?>>
                        <?php endfor; ?>
                    </div>
                    <div class="invalid-feedback d-block mt-2" id="otpValidationError" style="display:none !important;"></div>
                </div>

                <!-- Expiry Countdown Display -->
                <div class="d-flex align-items-center justify-content-between bg-light p-3 rounded-3 border mb-4">
                    <div class="d-flex align-items-center gap-2 small">
                        <i class="bi bi-stopwatch text-danger fs-5" id="timerIcon"></i>
                        <div>
                            <span class="text-muted d-block" style="font-size: .75rem;">Masa Berlaku Kode:</span>
                            <span class="fw-bold text-dark font-monospace" id="expiryTimer">05:00</span>
                        </div>
                    </div>
                    <div class="text-end small">
                        <span class="text-muted d-block" style="font-size: .75rem;">Maksimal Percobaan:</span>
                        <span class="badge bg-secondary-subtle text-secondary" id="attemptBadge">Maks. 5 Kali</span>
                    </div>
                </div>

                <!-- Verify Button -->
                <button type="submit" class="btn btn-primary-tj w-100 py-2 mb-3 fw-semibold shadow-sm" id="btnVerifyOtp">
                    <span class="spinner-border spinner-border-sm me-2 d-none" id="verifySpinner" role="status"></span>
                    <i class="bi bi-shield-check me-2" id="verifyIcon"></i>
                    <span id="verifyText">Verifikasi & Masuk</span>
                </button>

                <!-- Resend OTP Action -->
                <div class="d-flex align-items-center justify-content-between pt-2">
                    <span class="small text-muted">Tidak menerima kode?</span>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnResendOtp" disabled>
                        <span class="spinner-border spinner-border-sm me-1 d-none" id="resendSpinner"></span>
                        <i class="bi bi-arrow-repeat me-1" id="resendIcon"></i>
                        <span id="resendText">Kirim Ulang OTP</span>
                        <span class="badge bg-secondary ms-1 font-monospace" id="cooldownBadge">60s</span>
                    </button>
                </div>
            </form>

            <div class="text-center small text-muted pt-4 border-top mt-4">
                Salah memasukkan nomor? <a href="<?= url('login') ?>" class="text-primary fw-semibold text-decoration-none">Ganti nomor WhatsApp</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const phone = document.getElementById('targetPhone').value;
    const digitInputs = document.querySelectorAll('.otp-digit');
    const fullOtpInput = document.getElementById('fullOtpInput');
    const form = document.getElementById('otpVerifyForm');
    const btnVerify = document.getElementById('btnVerifyOtp');
    const verifySpinner = document.getElementById('verifySpinner');
    const verifyIcon = document.getElementById('verifyIcon');
    const verifyText = document.getElementById('verifyText');
    const btnResend = document.getElementById('btnResendOtp');
    const resendSpinner = document.getElementById('resendSpinner');
    const resendIcon = document.getElementById('resendIcon');
    const resendText = document.getElementById('resendText');
    const cooldownBadge = document.getElementById('cooldownBadge');
    const expiryTimer = document.getElementById('expiryTimer');
    const timerIcon = document.getElementById('timerIcon');
    const otpAlert = document.getElementById('otpAlert');
    const otpAlertText = document.getElementById('otpAlertText');
    const otpAlertIcon = document.getElementById('otpAlertIcon');
    const validationError = document.getElementById('otpValidationError');
    const attemptBadge = document.getElementById('attemptBadge');

    let remainingSeconds = parseInt(<?= json_encode($remainingSeconds ?? 300) ?>);
    let resendCooldown = parseInt(<?= json_encode($resendCooldown ?? 60) ?>);
    let isExpired = false;

    // Focus first input automatically
    digitInputs[0]?.focus();

    // Auto-fill dev helper button
    document.getElementById('btnAutoFillDev')?.addEventListener('click', function () {
        const code = this.getAttribute('data-otp') || '';
        if (code.length === 6) {
            code.split('').forEach((digit, i) => {
                if (digitInputs[i]) digitInputs[i].value = digit;
            });
            syncFullOtp();
            digitInputs[5]?.focus();
        }
    });

    // Handle 6-digit inputs with auto-advance, backspace, and paste
    digitInputs.forEach((input, index) => {
        input.addEventListener('input', function (e) {
            const val = this.value.replace(/[^0-9]/g, '');
            this.value = val ? val[val.length - 1] : '';

            syncFullOtp();
            clearValidation();

            if (this.value && index < digitInputs.length - 1) {
                digitInputs[index + 1].focus();
            }

            // Auto submit when 6 digits are filled
            if (fullOtpInput.value.length === 6 && !isExpired) {
                btnVerify.focus();
            }
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace') {
                if (!this.value && index > 0) {
                    digitInputs[index - 1].value = '';
                    digitInputs[index - 1].focus();
                    syncFullOtp();
                } else {
                    this.value = '';
                    syncFullOtp();
                }
            } else if (e.key === 'ArrowLeft' && index > 0) {
                digitInputs[index - 1].focus();
            } else if (e.key === 'ArrowRight' && index < digitInputs.length - 1) {
                digitInputs[index + 1].focus();
            }
        });

        // Support paste whole 6-digit code
        input.addEventListener('paste', function (e) {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text').trim();
            const clean = pasted.replace(/[^0-9]/g, '');
            if (clean.length > 0) {
                clean.split('').slice(0, 6).forEach((char, i) => {
                    if (digitInputs[i]) digitInputs[i].value = char;
                });
                syncFullOtp();
                const nextIdx = Math.min(clean.length, 5);
                digitInputs[nextIdx].focus();
            }
        });
    });

    function syncFullOtp() {
        let code = '';
        digitInputs.forEach(input => {
            code += input.value;
        });
        fullOtpInput.value = code;
    }

    function showAlert(type, message, htmlContent = null) {
        otpAlert.className = `alert alert-${type} alert-dismissible fade show border-0 shadow-sm mb-4`;
        if (htmlContent) {
            otpAlertText.innerHTML = htmlContent;
        } else {
            otpAlertText.textContent = message;
        }
        otpAlertIcon.className = `bi bi-${type === 'success' ? 'check-circle-fill text-success' : 'exclamation-triangle-fill text-danger'} me-2`;
        otpAlert.classList.remove('d-none');
    }

    function clearValidation() {
        digitInputs.forEach(i => i.classList.remove('is-invalid'));
        validationError.style.setProperty('display', 'none', 'important');
        validationError.textContent = '';
        otpAlert.classList.add('d-none');
    }

    function showValidation(msg) {
        digitInputs.forEach(i => i.classList.add('is-invalid'));
        validationError.textContent = msg;
        validationError.style.removeProperty('display');
    }

    // Expiry Countdown (5 minutes default)
    function formatTime(secs) {
        const m = Math.floor(secs / 60);
        const s = secs % 60;
        return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    }

    const expiryInterval = setInterval(() => {
        if (remainingSeconds > 0) {
            remainingSeconds--;
            expiryTimer.textContent = formatTime(remainingSeconds);
            if (remainingSeconds <= 60) {
                expiryTimer.className = 'fw-bold text-danger font-monospace';
                timerIcon.className = 'bi bi-alarm text-danger fs-5 animate-pulse';
            }
        } else {
            clearInterval(expiryInterval);
            isExpired = true;
            expiryTimer.textContent = '00:00 (Kedaluwarsa)';
            showAlert('danger', 'Kode OTP telah kedaluwarsa. Silakan klik "Kirim Ulang OTP".');
            btnVerify.disabled = true;
        }
    }, 1000);
    expiryTimer.textContent = formatTime(remainingSeconds);

    // Resend Cooldown Countdown (60 seconds)
    function updateCooldownUi() {
        if (resendCooldown > 0) {
            btnResend.disabled = true;
            cooldownBadge.textContent = `${resendCooldown}s`;
            cooldownBadge.classList.remove('d-none');
        } else {
            btnResend.disabled = false;
            cooldownBadge.classList.add('d-none');
        }
    }

    const cooldownInterval = setInterval(() => {
        if (resendCooldown > 0) {
            resendCooldown--;
            updateCooldownUi();
        }
    }, 1000);
    updateCooldownUi();

    // Verify OTP Submission
    form?.addEventListener('submit', async function (e) {
        e.preventDefault();
        clearValidation();

        syncFullOtp();
        const otpCode = fullOtpInput.value.trim();

        if (otpCode.length !== 6) {
            showValidation('Silakan masukkan lengkap 6 digit kode OTP.');
            return;
        }

        if (isExpired) {
            showValidation('Kode OTP telah kedaluwarsa. Silakan minta kode baru.');
            return;
        }

        btnVerify.disabled = true;
        verifySpinner.classList.remove('d-none');
        verifyIcon.classList.add('d-none');
        verifyText.textContent = 'Memverifikasi...';

        try {
            const response = await fetch('<?= url('api/auth/whatsapp/verify-otp') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    phone: phone,
                    otp: otpCode
                })
            });

            const result = await response.json();

            if (result.success) {
                showAlert('success', result.message || 'Verifikasi berhasil!');
                btnVerify.classList.replace('btn-primary-tj', 'btn-success');
                verifyText.textContent = 'Berhasil! Mengalihkan...';

                // Redirect to role dashboard or registration
                setTimeout(() => {
                    window.location.href = result.redirect_url || '<?= url('dashboard') ?>';
                }, 800);
            } else {
                showAlert('danger', result.message || 'Kode OTP tidak valid atau telah kedaluwarsa.');
                showValidation(result.message);

                if (result.remaining_attempts !== undefined) {
                    attemptBadge.textContent = `Sisa ${result.remaining_attempts} Kali`;
                    attemptBadge.className = result.remaining_attempts <= 2 ? 'badge bg-danger' : 'badge bg-warning text-dark';
                }

                if (result.status === 'max_attempts_exceeded' || result.status === 'expired') {
                    btnVerify.disabled = true;
                } else {
                    btnVerify.disabled = false;
                }

                verifySpinner.classList.add('d-none');
                verifyIcon.classList.remove('d-none');
                verifyText.textContent = 'Verifikasi & Masuk';

                // Clear input boxes for retry
                digitInputs.forEach(i => i.value = '');
                syncFullOtp();
                digitInputs[0]?.focus();
            }
        } catch (err) {
            showAlert('danger', 'Terjadi kesalahan komunikasi dengan server.');
            btnVerify.disabled = false;
            verifySpinner.classList.add('d-none');
            verifyIcon.classList.remove('d-none');
            verifyText.textContent = 'Verifikasi & Masuk';
        }
    });

    // Resend OTP Action
    btnResend?.addEventListener('click', async function () {
        if (resendCooldown > 0) return;

        clearValidation();
        btnResend.disabled = true;
        resendSpinner.classList.remove('d-none');
        resendIcon.classList.add('d-none');
        resendText.textContent = 'Mengirim...';

        try {
            const response = await fetch('<?= url('api/auth/whatsapp/resend-otp') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    phone: phone
                })
            });

            const result = await response.json();

            if (result.success) {
                showAlert('success', result.message || 'Kode OTP baru telah dikirimkan ke WhatsApp Anda.');
                
                // Reset timers
                remainingSeconds = result.expires_in || 300;
                resendCooldown = result.resend_cooldown || 60;
                isExpired = false;
                btnVerify.disabled = false;
                attemptBadge.textContent = 'Maks. 5 Kali';
                attemptBadge.className = 'badge bg-secondary-subtle text-secondary';

                // Clear input boxes
                digitInputs.forEach(i => i.value = '');
                syncFullOtp();
                digitInputs[0]?.focus();
            } else {
                if (result.message && (result.message.includes('terputus') || result.message.includes('disconnected') || result.error_code === 'GATEWAY_ERROR')) {
                    const extraHtml = `<div>${result.message} <br><a href="<?= url('admin/whatsapp') ?>" target="_blank" class="btn btn-sm btn-outline-danger mt-2 fw-semibold"><i class="bi bi-qr-code me-1"></i> Hubungkan WhatsApp Gateway</a></div>`;
                    showAlert('danger', '', extraHtml);
                } else {
                    showAlert('danger', result.message || 'Gagal mengirim ulang OTP.');
                }
                if (result.cooldown) {
                    resendCooldown = result.cooldown;
                }
            }
        } catch (err) {
            showAlert('danger', 'Gagal menghubungi server untuk mengirim ulang OTP.');
        } finally {
            resendSpinner.classList.add('d-none');
            resendIcon.classList.remove('d-none');
            resendText.textContent = 'Kirim Ulang OTP';
            updateCooldownUi();
        }
    });
});
</script>
