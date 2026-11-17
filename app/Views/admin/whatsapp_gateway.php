<!-- ============================================================
     WHATSAPP GATEWAY & OTP VIEW — Terima Jasa
     ============================================================ -->

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold mb-0">Manajemen WhatsApp Gateway</h4>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 font-monospace" style="font-size:.7rem;">FONNTE INTEGRATION</span>
        </div>
        <p class="text-muted small mb-0">
            Hubungkan WhatsApp nomor <strong>085213595836</strong> agar kode OTP dari web dapat langsung terkirim ke HP penerima.
        </p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" id="btnRefreshStatus">
            <i class="bi bi-arrow-clockwise" id="iconRefreshStatus"></i> Refresh Status
        </button>
        <a href="<?= url('login/whatsapp') ?>" target="_blank" class="btn btn-outline-success d-inline-flex align-items-center gap-2">
            <i class="bi bi-box-arrow-up-right"></i> Buka Halaman Login OTP
        </a>
    </div>
</div>

<!-- Dynamic Alert -->
<div id="gatewayAlert" class="alert d-none border-0 shadow-sm mb-4" role="alert">
    <div class="d-flex align-items-center gap-2">
        <i id="gatewayAlertIcon" class="bi me-2 fs-5"></i>
        <span id="gatewayAlertText"></span>
    </div>
</div>

<!-- Indicator Cards -->
<div class="row g-3 mb-4">
    <!-- Status Koneksi -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 <?= ($deviceInfo['device_status'] ?? '') === 'connect' ? 'border-start border-4 border-success' : 'border-start border-4 border-danger' ?>" id="statusCardWrap">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Status Koneksi</span>
                    <div class="p-2 rounded-circle bg-light" id="statusIconWrap">
                        <i class="bi bi-wifi fs-5 <?= ($deviceInfo['device_status'] ?? '') === 'connect' ? 'text-success' : 'text-danger' ?>"></i>
                    </div>
                </div>
                <h4 class="fw-bold mb-1" id="deviceStatusText">
                    <?php if (($deviceInfo['device_status'] ?? '') === 'connect'): ?>
                        <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> Terhubung</span>
                    <?php else: ?>
                        <span class="text-danger"><i class="bi bi-exclamation-circle-fill me-1"></i> Terputus</span>
                    <?php endif; ?>
                </h4>
                <small class="text-muted" id="deviceStatusSub">
                    <?= ($deviceInfo['device_status'] ?? '') === 'connect' ? 'Siap mengirim OTP ke nomor tujuan' : 'Perlu di-scan/ditautkan dengan HP' ?>
                </small>
            </div>
        </div>
    </div>

    <!-- Nomor Perangkat Pengirim -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Nomor Sender WA</span>
                    <div class="p-2 rounded-circle bg-light text-primary">
                        <i class="bi bi-whatsapp fs-5"></i>
                    </div>
                </div>
                <h4 class="fw-bold mb-1 font-monospace" id="deviceNumberText">
                    <?= e($deviceInfo['device'] ?? '085213595836') ?>
                </h4>
                <small class="text-muted">Nama: <strong><?= e($deviceInfo['name'] ?? 'rey') ?></strong></small>
            </div>
        </div>
    </div>

    <!-- Sisa Kuota Pesan -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Sisa Kuota Fonnte</span>
                    <div class="p-2 rounded-circle bg-light text-success">
                        <i class="bi bi-chat-left-dots-fill fs-5"></i>
                    </div>
                </div>
                <h4 class="fw-bold mb-1 text-success" id="deviceQuotaText">
                    <?= number_format((int)($deviceInfo['quota'] ?? 0)) ?> <span class="fs-6 fw-normal text-muted">pesan</span>
                </h4>
                <small class="text-muted">Paket: <?= e($deviceInfo['package'] ?? 'Free') ?></small>
            </div>
        </div>
    </div>

    <!-- Masa Aktif / Expired -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Masa Aktif Akun</span>
                    <div class="p-2 rounded-circle bg-light text-warning">
                        <i class="bi bi-calendar-check-fill fs-5"></i>
                    </div>
                </div>
                <h5 class="fw-bold mb-1" id="deviceExpiredText">
                    <?= e($deviceInfo['expired'] ?? '23 November 2026') ?>
                </h5>
                <small class="text-muted">Pesan terkirim: <?= number_format((int)($deviceInfo['messages'] ?? 0)) ?></small>
            </div>
        </div>
    </div>
</div>

<!-- Warning Banner if Disconnected -->
<div class="alert alert-warning border-0 shadow-sm mb-4 d-flex align-items-start gap-3 p-3 <?= ($deviceInfo['device_status'] ?? '') === 'connect' ? 'd-none' : '' ?>" id="disconnectedAlertBanner">
    <i class="bi bi-exclamation-triangle-fill fs-3 text-warning flex-shrink-0"></i>
    <div>
        <h6 class="fw-bold mb-1 text-dark">Perangkat WhatsApp Belum Terhubung (Disconnected)</h6>
        <p class="mb-0 small text-dark opacity-75">
            Server Fonnte memerlukan perangkat WhatsApp Anda ditautkan terlebih dahulu agar website bisa mengirimkan kode OTP ke nomor tujuan. 
            Silakan <strong>Scan QR Code</strong> di bawah atau gunakan <strong>Pairing Code</strong> pada aplikasi WhatsApp di HP Anda.
        </p>
    </div>
</div>

<!-- Success Banner if Connected -->
<div class="alert alert-success border-0 shadow-sm mb-4 d-flex align-items-center gap-3 p-3 <?= ($deviceInfo['device_status'] ?? '') === 'connect' ? '' : 'd-none' ?>" id="connectedAlertBanner">
    <i class="bi bi-check-circle-fill fs-3 text-success flex-shrink-0"></i>
    <div>
        <h6 class="fw-bold mb-1 text-dark">WhatsApp Gateway Terhubung & Aktif!</h6>
        <p class="mb-0 small text-dark opacity-75">
            Perangkat WhatsApp <strong><?= e($deviceInfo['device'] ?? '085213595836') ?></strong> telah siap. Kode OTP dari website akan langsung dikirim dan diterima oleh nomor tujuan.
        </p>
    </div>
</div>

<div class="row g-4">
    <!-- Kolom Kiri: Tautkan WhatsApp (QR Code & Pairing Code) -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-qr-code-scan text-success"></i> Tautkan WhatsApp Sekarang
                </h5>
                <span class="badge bg-light text-secondary border">Fonnte API Gateway</span>
            </div>
            <div class="card-body p-4">
                <ul class="nav nav-pills mb-4" id="pills-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold" id="tab-qr-btn" data-bs-toggle="pill" data-bs-target="#tab-qr" type="button" role="tab">
                            <i class="bi bi-qr-code me-1"></i> 1. Scan QR Code (Kamera)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold" id="tab-pairing-btn" data-bs-toggle="pill" data-bs-target="#tab-pairing" type="button" role="tab">
                            <i class="bi bi-key-fill me-1"></i> 2. Pairing Code (8 Karakter)
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="pills-tabContent">
                    <!-- Tab QR Code -->
                    <div class="tab-pane fade show active" id="tab-qr" role="tabpanel">
                        <div class="text-center p-4 bg-light rounded-3 border mb-3">
                            <div id="qrLoadingSpinner" class="py-4">
                                <div class="spinner-border text-success" style="width: 3rem; height: 3rem;" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="text-dark fw-semibold mt-3 mb-1">Mengambil QR Code WhatsApp terbaru...</p>
                                <small class="text-muted">Menghubungkan ke API Fonnte Gateway</small>
                            </div>

                            <div id="qrCodeContainer" class="py-2 d-none">
                                <div class="d-inline-block p-3 bg-white rounded-3 shadow-sm border mb-3">
                                    <img id="qrImage" src="" alt="WhatsApp QR Code" class="img-fluid rounded" style="width: 250px; height: 250px; object-fit: contain;">
                                </div>
                                <div>
                                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill font-monospace small">
                                        <i class="bi bi-stopwatch me-1"></i> Scan QR dalam WhatsApp HP Anda sekarang
                                    </span>
                                </div>
                            </div>

                            <div id="qrConnectedPlaceholder" class="py-5 d-none">
                                <div class="d-inline-flex p-3 rounded-circle bg-success-subtle text-success mb-3">
                                    <i class="bi bi-check2-circle display-4"></i>
                                </div>
                                <h5 class="fw-bold text-success">Perangkat Sudah Terhubung!</h5>
                                <p class="text-muted small mb-0">Tidak perlu scan QR lagi. Gateway sudah siap mengirim OTP.</p>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="bi bi-arrow-repeat me-1"></i> Sistem otomatis mendeteksi koneksi setiap 3 detik.
                            </small>
                            <button class="btn btn-sm btn-outline-success" id="btnRefreshQr">
                                <i class="bi bi-arrow-clockwise me-1"></i> Perbarui QR Baru
                            </button>
                        </div>
                    </div>

                    <!-- Tab Pairing Code -->
                    <div class="tab-pane fade" id="tab-pairing" role="tabpanel">
                        <div class="p-3 bg-light rounded-3 border mb-3">
                            <p class="text-muted small mb-3">
                                Jika scan kamera sulit atau berkedip, gunakan <strong>Pairing Code 8 karakter</strong> langsung dari WhatsApp:
                            </p>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Nomor WhatsApp Anda:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="pairingPhoneInput" value="085213595836" placeholder="085213595836">
                                    <button class="btn btn-success fw-semibold" id="btnGetPairingCode">
                                        <i class="bi bi-shield-lock me-1"></i> Buat Pairing Code
                                    </button>
                                </div>
                            </div>

                            <div id="pairingCodeResult" class="p-4 bg-white rounded-3 border text-center my-3 d-none">
                                <small class="text-muted d-block mb-1 fw-semibold text-uppercase" style="letter-spacing: .05em;">Kode Pairing WhatsApp Anda:</small>
                                <div class="display-5 fw-bold font-monospace text-primary tracking-wider my-2" id="pairingCodeDisplay">--------</div>
                                <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill small">
                                    <i class="bi bi-clock-history me-1"></i> Buka WhatsApp &gt; Perangkat Tertaut &gt; Tautkan dengan nomor telepon
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cara Tautkan Panduan -->
                <div class="mt-4 pt-3 border-top">
                    <h6 class="fw-bold small mb-2 text-dark"><i class="bi bi-phone me-1"></i> Panduan Menautkan di Aplikasi WhatsApp HP:</h6>
                    <ol class="small text-muted ps-3 mb-0" style="line-height: 1.8;">
                        <li>Buka aplikasi WhatsApp di HP Anda (Nomor: <strong>085213595836</strong>).</li>
                        <li>Tekan ikon <strong>Titik Tiga</strong> di pojok kanan atas (Android) atau menu <strong>Pengaturan</strong> (iPhone).</li>
                        <li>Pilih <strong>Perangkat Tertaut (Linked Devices)</strong> &rarr; lalu tekan <strong>Tautkan Perangkat</strong>.</li>
                        <li>Arahkan kamera ke <strong>QR Code</strong> di atas, <em>ATAU</em> klik "Tautkan dengan nomor telepon saja" dan masukkan Pairing Code.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Uji Coba Pengiriman Pesan & Informasi API -->
    <div class="col-lg-5">
        <!-- Test Kirim Pesan -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-send-check text-primary"></i> Uji Coba Kirim WhatsApp
                </h5>
                <span class="badge bg-primary-subtle text-primary" id="testBadgeStatus">
                    <?= ($deviceInfo['device_status'] ?? '') === 'connect' ? 'Siap Uji Coba' : 'Menunggu Koneksi' ?>
                </span>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small mb-3">
                    Kirim pesan tes langsung dari website ini ke nomor WhatsApp Anda untuk memastikan pesan benar-benar masuk.
                </p>
                <form id="testSendForm">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nomor WhatsApp Tujuan:</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-telephone"></i></span>
                            <input type="text" class="form-control font-monospace" id="testTargetPhone" value="085213595836" placeholder="08xxxxxxxxxx" required>
                        </div>
                        <div class="form-text small">Nomor yang akan menerima pesan tes (bisa nomor Anda sendiri).</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Isi Pesan Tes:</label>
                        <textarea class="form-control small font-monospace" id="testMessageText" rows="3" required>*Terima Jasa* - Uji Coba WhatsApp Gateway

Halo! Pesan ini dikirim langsung dari web Terima Jasa untuk memastikan nomor WhatsApp Anda berhasil menerima notifikasi dan kode OTP.</textarea>
                    </div>

                    <div id="testSendWarning" class="alert alert-warning py-2 small mb-3 <?= ($deviceInfo['device_status'] ?? '') === 'connect' ? 'd-none' : '' ?>">
                        <i class="bi bi-info-circle me-1"></i> Scan QR Code di sebelah kiri terlebih dahulu sebelum mengirim pesan tes.
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold" id="btnSubmitTestSend" <?= ($deviceInfo['device_status'] ?? '') === 'connect' ? '' : 'disabled' ?>>
                        <span class="spinner-border spinner-border-sm me-2 d-none" id="testSendSpinner"></span>
                        <i class="bi bi-send me-2" id="testSendIcon"></i>
                        <span id="testSendBtnText">Kirim Pesan Tes Sekarang</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Info Konfigurasi .env -->
        <div class="card border-0 shadow-sm bg-light">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-2 text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-gear-wide-connected text-secondary"></i> Konfigurasi Sistem (.env)
                </h6>
                <div class="table-responsive">
                    <table class="table table-sm table-borderless small mb-0">
                        <tr>
                            <td class="text-muted">Provider:</td>
                            <td class="fw-bold font-monospace"><?= e(config('whatsapp.provider', 'fonnte')) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">API Endpoint:</td>
                            <td class="fw-bold font-monospace text-truncate" style="max-width: 180px;"><?= e(config('whatsapp.api_url', 'https://api.fonnte.com/send')) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Masa Berlaku OTP:</td>
                            <td class="fw-bold font-monospace"><?= config('whatsapp.otp_expiry', 300) ?> detik (5 menit)</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Cooldown Kirim Ulang:</td>
                            <td class="fw-bold font-monospace"><?= config('whatsapp.resend_cooldown', 60) ?> detik</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const btnRefreshStatus = document.getElementById('btnRefreshStatus');
    const iconRefreshStatus = document.getElementById('iconRefreshStatus');
    const btnRefreshQr = document.getElementById('btnRefreshQr');
    const qrLoadingSpinner = document.getElementById('qrLoadingSpinner');
    const qrCodeContainer = document.getElementById('qrCodeContainer');
    const qrConnectedPlaceholder = document.getElementById('qrConnectedPlaceholder');
    const qrImage = document.getElementById('qrImage');
    const btnGetPairingCode = document.getElementById('btnGetPairingCode');
    const pairingPhoneInput = document.getElementById('pairingPhoneInput');
    const pairingCodeResult = document.getElementById('pairingCodeResult');
    const pairingCodeDisplay = document.getElementById('pairingCodeDisplay');
    const testSendForm = document.getElementById('testSendForm');
    const btnSubmitTestSend = document.getElementById('btnSubmitTestSend');
    const testSendSpinner = document.getElementById('testSendSpinner');
    const testSendIcon = document.getElementById('testSendIcon');
    const testSendBtnText = document.getElementById('testSendBtnText');
    const testSendWarning = document.getElementById('testSendWarning');
    const testBadgeStatus = document.getElementById('testBadgeStatus');
    const gatewayAlert = document.getElementById('gatewayAlert');
    const gatewayAlertText = document.getElementById('gatewayAlertText');
    const gatewayAlertIcon = document.getElementById('gatewayAlertIcon');
    const disconnectedAlertBanner = document.getElementById('disconnectedAlertBanner');
    const connectedAlertBanner = document.getElementById('connectedAlertBanner');
    const statusCardWrap = document.getElementById('statusCardWrap');

    let isDeviceConnected = <?= json_encode(($deviceInfo['device_status'] ?? '') === 'connect') ?>;

    function showAlert(type, message) {
        gatewayAlert.className = `alert alert-${type} alert-dismissible fade show border-0 shadow-sm mb-4`;
        gatewayAlertText.textContent = message;
        gatewayAlertIcon.className = `bi bi-${type === 'success' ? 'check-circle-fill text-success' : 'exclamation-triangle-fill text-danger'} me-2 fs-5`;
        gatewayAlert.classList.remove('d-none');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Refresh Device Status
    async function refreshStatus(silent = false) {
        if (!silent) {
            iconRefreshStatus.classList.add('spin-icon');
            btnRefreshStatus.disabled = true;
        }

        try {
            const res = await fetch('<?= url('api/admin/whatsapp/status') ?>');
            const result = await res.json();
            const data = result.data || {};

            const isConnect = (data.device_status === 'connect');
            isDeviceConnected = isConnect;

            const statusText = document.getElementById('deviceStatusText');
            const statusSub = document.getElementById('deviceStatusSub');
            const statusWrap = document.getElementById('statusIconWrap');

            if (isConnect) {
                statusText.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> Terhubung</span>';
                statusSub.textContent = 'Siap mengirim OTP ke nomor tujuan';
                statusWrap.innerHTML = '<i class="bi bi-wifi fs-5 text-success"></i>';
                statusCardWrap.className = 'card border-0 shadow-sm h-100 border-start border-4 border-success';
                disconnectedAlertBanner.classList.add('d-none');
                connectedAlertBanner.classList.remove('d-none');
                btnSubmitTestSend.disabled = false;
                testSendWarning.classList.add('d-none');
                testBadgeStatus.className = 'badge bg-success-subtle text-success';
                testBadgeStatus.textContent = 'Siap Uji Coba';

                qrLoadingSpinner.classList.add('d-none');
                qrCodeContainer.classList.add('d-none');
                qrConnectedPlaceholder.classList.remove('d-none');
            } else {
                statusText.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-circle-fill me-1"></i> Terputus</span>';
                statusSub.textContent = 'Perlu di-scan/ditautkan dengan HP';
                statusWrap.innerHTML = '<i class="bi bi-wifi fs-5 text-danger"></i>';
                statusCardWrap.className = 'card border-0 shadow-sm h-100 border-start border-4 border-danger';
                disconnectedAlertBanner.classList.remove('d-none');
                connectedAlertBanner.classList.add('d-none');
                btnSubmitTestSend.disabled = true;
                testSendWarning.classList.remove('d-none');
                testBadgeStatus.className = 'badge bg-warning-subtle text-warning';
                testBadgeStatus.textContent = 'Menunggu Koneksi';

                qrConnectedPlaceholder.classList.add('d-none');
            }

            if (data.device) {
                document.getElementById('deviceNumberText').textContent = data.device;
            }
            if (data.quota !== undefined) {
                document.getElementById('deviceQuotaText').innerHTML = `${parseInt(data.quota).toLocaleString()} <span class="fs-6 fw-normal text-muted">pesan</span>`;
            }
            if (data.expired) {
                document.getElementById('deviceExpiredText').textContent = data.expired;
            }
        } catch (e) {
            console.error(e);
        } finally {
            if (!silent) {
                iconRefreshStatus.classList.remove('spin-icon');
                btnRefreshStatus.disabled = false;
            }
        }
    }

    btnRefreshStatus.addEventListener('click', () => refreshStatus(false));

    // Load QR Code Automatically
    async function loadQrCode() {
        if (isDeviceConnected) {
            qrLoadingSpinner.classList.add('d-none');
            qrCodeContainer.classList.add('d-none');
            qrConnectedPlaceholder.classList.remove('d-none');
            return;
        }

        qrCodeContainer.classList.add('d-none');
        qrLoadingSpinner.classList.remove('d-none');
        qrConnectedPlaceholder.classList.add('d-none');

        try {
            const res = await fetch('<?= url('api/admin/whatsapp/qr') ?>', {
                method: 'POST',
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();

            if (data.connected) {
                showAlert('success', 'Perangkat WhatsApp Anda sudah dalam status terhubung (Connected)!');
                refreshStatus(true);
                return;
            }

            if (data.url && data.url.length > 50) {
                qrImage.src = 'data:image/png;base64,' + data.url;
                qrLoadingSpinner.classList.add('d-none');
                qrCodeContainer.classList.remove('d-none');
            } else {
                qrLoadingSpinner.classList.add('d-none');
                showAlert('warning', data.message || 'Gagal memuat QR Code. Silakan klik tombol "Perbarui QR Baru".');
            }
        } catch (e) {
            qrLoadingSpinner.classList.add('d-none');
            showAlert('danger', 'Terjadi kesalahan saat memuat QR Code dari server.');
        }
    }

    btnRefreshQr.addEventListener('click', loadQrCode);

    // Auto-load QR code immediately when page opens
    loadQrCode();

    // Auto-poll status every 4 seconds until connected
    const pollInterval = setInterval(() => {
        refreshStatus(true);
        if (isDeviceConnected) {
            clearInterval(pollInterval);
        }
    }, 4000);

    // Get Pairing Code
    btnGetPairingCode.addEventListener('click', async function () {
        const phone = pairingPhoneInput.value.trim();
        if (!phone) {
            alert('Silakan masukkan nomor WhatsApp Anda.');
            return;
        }

        btnGetPairingCode.disabled = true;
        btnGetPairingCode.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengambil...';

        try {
            const res = await fetch('<?= url('api/admin/whatsapp/pairing-code') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ phone: phone })
            });
            const data = await res.json();

            if (data.code) {
                pairingCodeDisplay.textContent = data.code;
                pairingCodeResult.classList.remove('d-none');
                showAlert('success', 'Pairing Code berhasil didapatkan! Masukkan 8 karakter ini di WhatsApp HP Anda.');
            } else if (data.connected) {
                showAlert('success', 'Perangkat WhatsApp Anda sudah terhubung!');
                refreshStatus(true);
            } else {
                showAlert('danger', data.message || 'Gagal mendapatkan Pairing Code. Silakan gunakan metode Scan QR Code.');
            }
        } catch (e) {
            showAlert('danger', 'Terjadi kesalahan komunikasi dengan server.');
        } finally {
            btnGetPairingCode.disabled = false;
            btnGetPairingCode.innerHTML = '<i class="bi bi-shield-lock me-1"></i> Buat Pairing Code';
        }
    });

    // Test Send
    testSendForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        if (!isDeviceConnected) {
            showAlert('warning', 'Perangkat WhatsApp masih terputus! Harap Scan QR Code di sebelah kiri terlebih dahulu sebelum mengirim pesan tes.');
            return;
        }

        const phone = document.getElementById('testTargetPhone').value.trim();
        const msg = document.getElementById('testMessageText').value.trim();

        btnSubmitTestSend.disabled = true;
        testSendSpinner.classList.remove('d-none');
        testSendIcon.classList.add('d-none');
        testSendBtnText.textContent = 'Mengirim Pesan...';

        try {
            const res = await fetch('<?= url('api/admin/whatsapp/test-send') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ phone: phone, message: msg })
            });
            const data = await res.json();

            if (data.success) {
                showAlert('success', data.message || 'Pesan WhatsApp berhasil terkirim! Silakan cek WhatsApp di HP tujuan.');
                refreshStatus(true);
            } else {
                showAlert('danger', data.message || 'Gagal mengirim pesan WhatsApp. Pastikan perangkat sudah terhubung.');
            }
        } catch (err) {
            showAlert('danger', 'Gagal menghubungi server untuk mengirim pesan.');
        } finally {
            btnSubmitTestSend.disabled = false;
            testSendSpinner.classList.add('d-none');
            testSendIcon.classList.remove('d-none');
            testSendBtnText.textContent = 'Kirim Pesan Tes Sekarang';
        }
    });
});
</script>

<style>
.spin-icon {
    display: inline-block;
    animation: spin 1s linear infinite;
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.tracking-wider {
    letter-spacing: .25em;
}
</style>
