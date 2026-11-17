<!-- ============================================================
     ADMIN: KONFIGURASI AI TERIMA JASA (ENTERPRISE EDITION)
     ============================================================ -->

<!-- Flash messages -->
<?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-4 mb-4 d-flex align-items-center gap-3 shadow-sm border-0" role="alert">
        <div class="rounded-circle bg-success text-white p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width:38px;height:38px;">
            <i class="bi bi-check-lg fs-5"></i>
        </div>
        <div>
            <div class="fw-bold">Berhasil</div>
            <div class="small opacity-90"><?= e($_SESSION['flash_success']) ?></div>
        </div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-4 mb-4 d-flex align-items-center gap-3 shadow-sm border-0" role="alert">
        <div class="rounded-circle bg-danger text-white p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width:38px;height:38px;">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        </div>
        <div>
            <div class="fw-bold">Peringatan / Gagal</div>
            <div class="small opacity-90"><?= e($_SESSION['flash_error']) ?></div>
        </div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<!-- Page Header (Requirement 3) -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?= url('admin/dashboard') ?>" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Konfigurasi AI</li>
            </ol>
        </nav>
        <h4 class="fw-bold mb-1 d-flex align-items-center gap-2 text-dark">
            <i class="bi bi-cpu text-primary"></i> Konfigurasi AI
        </h4>
        <p class="text-muted small mb-0">Kelola provider, model, dan parameter kecerdasan buatan yang digunakan oleh Terima Jasa.</p>
    </div>

    <!-- Quick Control Bar -->
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <!-- Global Toggle AI Service -->
        <form action="<?= url('admin/ai-settings/toggle-service') ?>" method="POST" class="d-inline">
            <?php if ($serviceActive): ?>
                <button type="submit" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-1 rounded-3 px-3 py-2" title="Nonaktifkan seluruh panggilan AI ke provider">
                    <i class="bi bi-power"></i> Nonaktifkan AI
                </button>
            <?php else: ?>
                <button type="submit" class="btn btn-success btn-sm d-inline-flex align-items-center gap-1 rounded-3 px-3 py-2" title="Aktifkan kembali layanan AI">
                    <i class="bi bi-power"></i> Aktifkan Layanan AI
                </button>
            <?php endif; ?>
        </form>

        <button type="button" id="btnTopTestConnection" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1 rounded-3 px-3 py-2 shadow-sm">
            <i class="bi bi-broadcast"></i> Uji Koneksi AI
        </button>
    </div>
</div>

<!-- 4 KARTU INFORMASI UTAMA (Requirement 3) -->
<div class="row g-3 mb-4">
    <!-- 1. Provider Aktif -->
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-4 p-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                    <i class="bi bi-hdd-network-fill fs-4"></i>
                </div>
                <div class="overflow-hidden">
                    <span class="text-muted small d-block">Provider Aktif</span>
                    <h6 class="fw-bold mb-0 text-truncate text-uppercase text-dark">
                        <?= e($activeConfig->provider ?? 'Belum Ada') ?>
                    </h6>
                    <span class="text-muted" style="font-size:.72rem;">
                        <?= e($activeConfig->name ?? 'Default System') ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Model Aktif -->
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-4 p-3 bg-info-subtle text-info d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                    <i class="bi bi-robot fs-4"></i>
                </div>
                <div class="overflow-hidden">
                    <span class="text-muted small d-block">Model Aktif</span>
                    <h6 class="fw-bold mb-0 text-truncate text-dark" title="<?= e($activeConfig->default_model ?? '-') ?>">
                        <?= e($activeConfig->default_model ?? 'Belum Dipilih') ?>
                    </h6>
                    <span class="text-muted" style="font-size:.72rem;">
                        Temp: <?= e($activeConfig->temperature ?? 0.3) ?> | Max: <?= e($activeConfig->max_output_tokens ?? 800) ?> tk
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Status Koneksi (Requirement 3: Akurat, tidak bohong) -->
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-4 p-3 <?= ($connectionStatus === 'Terhubung') ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' ?> d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                    <i class="bi <?= ($connectionStatus === 'Terhubung') ? 'bi-shield-check' : 'bi-activity' ?> fs-4"></i>
                </div>
                <div>
                    <span class="text-muted small d-block">Status Koneksi</span>
                    <span class="badge <?= e($connectionStatusBadge) ?> rounded-pill px-2 py-1" style="font-size:.75rem;">
                        <?= e($connectionStatus) ?>
                    </span>
                    <div class="text-muted mt-1" style="font-size:.7rem;">
                        <?= $latestLog ? 'Diuji ' . date('H:i d/m', strtotime($latestLog['tested_at'])) . ' (' . e($latestLog['latency_ms']) . 'ms)' : 'Belum pernah diuji' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Status Layanan AI -->
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-4 p-3 <?= $serviceActive ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                    <i class="bi <?= $serviceActive ? 'bi-toggle-on' : 'bi-toggle-off' ?> fs-4"></i>
                </div>
                <div>
                    <span class="text-muted small d-block">Status Layanan AI</span>
                    <span class="badge <?= $serviceActive ? 'bg-success' : 'bg-danger' ?> rounded-pill px-2 py-1" style="font-size:.75rem;">
                        <?= $serviceActive ? 'Aktif' : 'Tidak Aktif' ?>
                    </span>
                    <div class="text-muted mt-1" style="font-size:.7rem;">
                        <?= $serviceActive ? 'Siap melayani request' : 'Dialihkan ke fallback lokal' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alert Notifikasi Hasil Uji Koneksi Live -->
<div id="liveAlertBox" class="d-none mb-4"></div>

<div class="row g-4 mb-5">
    <!-- ========================================================
         BAGIAN FORM KONFIGURASI PROVIDER (Requirement 4)
         ======================================================== -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
            <div class="card-header bg-transparent border-bottom px-4 py-3 d-flex align-items-center justify-content-between">
                <div class="fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-sliders2-vertical text-primary"></i> Form Pengaturan Provider & Model AI
                </div>
                <span class="badge bg-light text-muted border">ID Konfig: #<?= e($activeConfig->id ?? 1) ?></span>
            </div>

            <form action="<?= url('admin/ai-settings') ?>" method="POST" id="mainAiForm" class="p-4">
                <input type="hidden" name="config_id" value="<?= e($activeConfig->id ?? 0) ?>">

                <!-- A. Pilihan Provider AI -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="providerSelect" class="form-label fw-semibold small text-muted text-uppercase mb-0" style="letter-spacing: .05em;">
                            A. Provider AI
                        </label>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" id="providerSupportBadge">Adapter Aktif</span>
                    </div>
                    <select name="provider" id="providerSelect" class="form-select rounded-3 py-2">
                        <?php foreach ($supportedProviders as $pKey => $pData): ?>
                            <option value="<?= e($pKey) ?>" <?= (($activeConfig->provider ?? 'groq') === $pKey) ? 'selected' : '' ?>>
                                <?= e($pData['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text small text-muted mt-1" id="providerNoteText">
                        <?= e($supportedProviders[$activeConfig->provider ?? 'groq']['note'] ?? '') ?>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <!-- B. Nama Konfigurasi -->
                    <div class="col-md-6">
                        <label for="nameInput" class="form-label fw-semibold small text-muted text-uppercase mb-1" style="letter-spacing: .05em;">
                            B. Nama Konfigurasi
                        </label>
                        <input type="text" 
                               name="name" 
                               id="nameInput" 
                               class="form-control rounded-3" 
                               value="<?= e($activeConfig->name ?? 'AI Utama Terima Jasa') ?>" 
                               placeholder="Contoh: AI Utama Terima Jasa" 
                               required>
                    </div>

                    <!-- C. Base URL / API Endpoint (SSRF Protected) -->
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="baseUrlInput" class="form-label fw-semibold small text-muted text-uppercase mb-0" style="letter-spacing: .05em;">
                                C. Base URL / Endpoint
                            </label>
                            <span class="text-muted" style="font-size:.7rem;"><i class="bi bi-shield-check text-success"></i> SSRF Filter</span>
                        </div>
                        <input type="url" 
                               name="base_url" 
                               id="baseUrlInput" 
                               class="form-control rounded-3" 
                               value="<?= e($activeConfig->base_url ?? 'https://api.groq.com/openai/v1') ?>" 
                               placeholder="https://api.provider.example/v1">
                    </div>
                </div>

                <!-- D. API Key (Keamanan Terenkripsi, Masked, Show/Hide) -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="apiKeyInput" class="form-label fw-semibold small text-muted text-uppercase mb-0" style="letter-spacing: .05em;">
                            D. API Key / Authorization Token
                        </label>
                        <a href="<?= e($supportedProviders[$activeConfig->provider ?? 'groq']['docs_url'] ?? '#') ?>" 
                           target="_blank" 
                           id="providerDocsLink" 
                           class="text-decoration-none small text-primary d-inline-flex align-items-center gap-1">
                            <span>Buka Dokumentasi Kunci</span> <i class="bi bi-box-arrow-up-right" style="font-size:.7rem;"></i>
                        </a>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-key-fill"></i></span>
                        <input type="password" 
                               name="api_key" 
                               id="apiKeyInput" 
                               class="form-control border-start-0 border-end-0 bg-light" 
                               placeholder="<?= $hasApiKey ? 'API key telah tersimpan secara aman (Kosongkan jika tidak ingin mengubah)' : 'Masukkan API Key baru' ?>"
                               value=""
                               autocomplete="new-password">
                        <button type="button" class="btn btn-light border border-start-0 text-muted" id="btnToggleKeyVisibility" title="Tampilkan / Sembunyikan API Key">
                            <i class="bi bi-eye" id="iconEye"></i>
                        </button>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-1">
                        <span class="text-muted small" style="font-size:.75rem;">
                            Status Kredensial: 
                            <strong id="credentialStatusLabel" class="<?= $hasApiKey ? 'text-success' : 'text-danger' ?>">
                                <?= $hasApiKey ? "Tersimpan [{$maskedApiKey}]" : 'Belum Dikonfigurasi' ?>
                            </strong>
                        </span>
                        <span class="text-muted small" style="font-size:.72rem;">Enkripsi Server: AES-256-CBC</span>
                    </div>
                    <div id="apiKeyNoticeBox" class="mt-2 d-none"></div>
                </div>

                <!-- E. Model AI & Tombol "Ambil Daftar Model" (Requirement 4E & 6) -->
                <div class="mb-4 p-3 bg-light rounded-4 border">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-2">
                        <label class="form-label fw-semibold small text-muted text-uppercase mb-0" style="letter-spacing: .05em;">
                            E. Pilihan Model AI
                        </label>
                        <button type="button" id="btnFetchModels" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 rounded-3">
                            <i class="bi bi-arrow-repeat" id="fetchSpinnerIcon"></i> Ambil Daftar Model dari Provider
                        </button>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-7">
                            <label for="modelSelect" class="form-label small text-muted mb-1">Pilih dari Daftar Model:</label>
                            <select name="model" id="modelSelect" class="form-select rounded-3">
                                <option value="<?= e($activeConfig->default_model ?? 'llama-3.3-70b-versatile') ?>" selected>
                                    <?= e($activeConfig->default_model ?? 'llama-3.3-70b-versatile') ?> (Model Aktif)
                                </option>
                                <option value="llama-3.3-70b-versatile">llama-3.3-70b-versatile (Llama 3.3 70B - Rekomendasi)</option>
                                <option value="openai/gpt-oss-120b">openai/gpt-oss-120b (DeepSeek/GPT-OSS 120B)</option>
                                <option value="llama-3.1-8b-instant">llama-3.1-8b-instant (Llama 3.1 8B - Super Cepat)</option>
                                <option value="mixtral-8x7b-32768">mixtral-8x7b-32768 (Mixtral MoE 32k)</option>
                                <option value="custom-model">-- Masukkan Model Manual / Kustom --</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label for="customModelInput" class="form-label small text-muted mb-1">Atau Ketik Model Manual:</label>
                            <input type="text" 
                                   name="custom_model" 
                                   id="customModelInput" 
                                   class="form-control rounded-3" 
                                   placeholder="Contoh: gpt-4o atau qwen-2.5" 
                                   value="">
                        </div>
                    </div>
                    <div id="fetchModelNotice" class="small text-muted mt-2" style="font-size:.75rem;">
                        Klik <strong>"Ambil Daftar Model"</strong> untuk memuat model aktual yang aktif dari endpoint resmi penyedia.
                    </div>
                </div>

                <!-- F. Parameter Model (Requirement 4F) -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label fw-semibold small text-muted text-uppercase mb-0" style="letter-spacing: .05em;">
                            F. Hyperparameter & Kontrol Respon
                        </label>
                        <!-- Tombol Reset Parameter (Requirement 6) -->
                        <button type="button" id="btnTriggerResetParams" class="btn btn-link text-decoration-none btn-sm text-secondary p-0">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset Parameter ke Default
                        </button>
                    </div>

                    <div class="row g-3">
                        <!-- Temperature -->
                        <div class="col-md-6">
                            <div class="p-3 bg-white border rounded-3 h-100">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="tempInput" class="form-label small fw-semibold mb-0">Temperature (0.0 - 1.0)</label>
                                    <span class="badge bg-primary rounded-pill px-2" id="tempBadge"><?= e($activeConfig->temperature ?? 0.3) ?></span>
                                </div>
                                <input type="range" 
                                       name="temperature" 
                                       id="tempInput" 
                                       class="form-range" 
                                       min="0.0" 
                                       max="1.0" 
                                       step="0.05" 
                                       value="<?= e($activeConfig->temperature ?? 0.3) ?>">
                                <div class="d-flex justify-content-between text-muted" style="font-size:.68rem;">
                                    <span>Presisi (0.0)</span>
                                    <span>Standar (0.3)</span>
                                    <span>Kreatif (1.0)</span>
                                </div>
                            </div>
                        </div>

                        <!-- Maximum Output Tokens -->
                        <div class="col-md-6">
                            <div class="p-3 bg-white border rounded-3 h-100">
                                <label for="tokensInput" class="form-label small fw-semibold mb-1">Maximum Output Tokens</label>
                                <div class="input-group">
                                    <input type="number" 
                                           name="max_output_tokens" 
                                           id="tokensInput" 
                                           class="form-control rounded-start-3" 
                                           min="100" 
                                           max="4096" 
                                           step="50" 
                                           value="<?= e($activeConfig->max_output_tokens ?? 800) ?>">
                                    <span class="input-group-text bg-light text-muted small">Tokens</span>
                                </div>
                                <div class="text-muted mt-1" style="font-size:.68rem;">Rentang: 100 - 4096 tokens (Default: 800)</div>
                            </div>
                        </div>

                        <!-- Top P -->
                        <div class="col-md-4">
                            <div class="p-3 bg-white border rounded-3 h-100">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="topPInput" class="form-label small fw-semibold mb-0">Top P</label>
                                    <span class="badge bg-secondary rounded-pill px-2" id="topPBadge"><?= e($activeConfig->top_p ?? 1.0) ?></span>
                                </div>
                                <input type="range" 
                                       name="top_p" 
                                       id="topPInput" 
                                       class="form-range" 
                                       min="0.1" 
                                       max="1.0" 
                                       step="0.05" 
                                       value="<?= e($activeConfig->top_p ?? 1.0) ?>">
                                <div class="text-muted" style="font-size:.68rem;">Nucleus sampling (Default: 1.0)</div>
                            </div>
                        </div>

                        <!-- Timeout Request -->
                        <div class="col-md-4">
                            <div class="p-3 bg-white border rounded-3 h-100">
                                <label for="timeoutInput" class="form-label small fw-semibold mb-1">Timeout Request</label>
                                <div class="input-group">
                                    <input type="number" 
                                           name="timeout_seconds" 
                                           id="timeoutInput" 
                                           class="form-control rounded-start-3" 
                                           min="3" 
                                           max="60" 
                                           value="<?= e($activeConfig->timeout_seconds ?? 15) ?>">
                                    <span class="input-group-text bg-light text-muted small">Detik</span>
                                </div>
                                <div class="text-muted mt-1" style="font-size:.68rem;">Batas waktu tunggu koneksi</div>
                            </div>
                        </div>

                        <!-- Retry Count -->
                        <div class="col-md-4">
                            <div class="p-3 bg-white border rounded-3 h-100">
                                <label for="retryInput" class="form-label small fw-semibold mb-1">Retry Count</label>
                                <div class="input-group">
                                    <input type="number" 
                                           name="retry_count" 
                                           id="retryInput" 
                                           class="form-control rounded-start-3" 
                                           min="0" 
                                           max="5" 
                                           value="<?= e($activeConfig->retry_count ?? 2) ?>">
                                    <span class="input-group-text bg-light text-muted small">Kali</span>
                                </div>
                                <div class="text-muted mt-1" style="font-size:.68rem;">Toleransi coba ulang jika gagal</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Opsi Aktifkan Konfigurasi -->
                <div class="mb-4 p-3 bg-light rounded-3 d-flex align-items-center justify-content-between border">
                    <div>
                        <div class="fw-bold small text-dark">Tetapkan sebagai Provider Aktif Utama</div>
                        <div class="text-muted" style="font-size:.75rem;">Konfigurasi ini akan langsung dipakai oleh seluruh fitur AI Terima Jasa.</div>
                    </div>
                    <div class="form-check form-switch fs-4 mb-0">
                        <input class="form-check-input cursor-pointer" type="checkbox" name="is_active" value="1" <?= $serviceActive ? 'checked' : '' ?>>
                    </div>
                </div>

                <!-- TOMBOL PENGELOLAAN (Requirement 6) -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-3 border-top">
                    <button type="button" id="btnTestForm" class="btn btn-outline-primary d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3">
                        <i class="bi bi-broadcast"></i> Uji Koneksi
                    </button>

                    <div class="d-flex gap-2">
                        <a href="<?= url('admin/dashboard') ?>" class="btn btn-light px-3 py-2 rounded-3 text-muted">Batal</a>
                        <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 fw-semibold shadow-sm d-inline-flex align-items-center gap-2">
                            <i class="bi bi-check2-circle fs-5"></i> Simpan Konfigurasi
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <!-- ========================================================
         SIDEBAR: STATUS AUDIT LOG & RIWAYAT (Requirement 12)
         ======================================================== -->
    <div class="col-lg-4">
        
        <!-- Riwayat Pengujian Koneksi Terakhir -->
        <div class="card border-0 shadow-sm rounded-4 bg-white p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark d-flex align-items-center gap-2 mb-0">
                    <i class="bi bi-clock-history text-primary"></i> Riwayat Pengujian
                </h6>
                <span class="badge bg-light text-muted border">Audit Trail</span>
            </div>

            <?php if (empty($recentLogs)): ?>
                <div class="text-center py-4 text-muted small">
                    <i class="bi bi-clipboard2-check fs-2 text-secondary mb-2 d-block"></i>
                    Belum ada riwayat pengujian koneksi. Klik tombol <strong>Uji Koneksi</strong> untuk mulai memvalidasi.
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush small">
                    <?php foreach ($recentLogs as $log): ?>
                        <div class="list-group-item px-0 py-2 border-bottom">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-dark text-uppercase" style="font-size:.78rem;">
                                    <?= e($log['provider_name']) ?> <span class="text-muted fw-normal">(<?= e($log['model_tested']) ?>)</span>
                                </span>
                                <?php if ($log['status'] === 'success'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.65rem;">
                                        <i class="bi bi-check-circle-fill me-1"></i> Sukses (<?= e($log['latency_ms']) ?>ms)
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:.65rem;">
                                        <i class="bi bi-x-circle-fill me-1"></i> Gagal
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex justify-content-between text-muted" style="font-size:.7rem;">
                                <span>Oleh: <?= e($log['tester_name'] ?? 'Administrator') ?></span>
                                <span><?= date('d M Y, H:i', strtotime($log['tested_at'])) ?></span>
                            </div>
                            <?php if ($log['status'] === 'failed' && !empty($log['error_message'])): ?>
                                <div class="text-danger mt-1 small" style="font-size:.68rem;">
                                    <?= e($log['error_message']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Panduan Keamanan & SSRF Guard -->
        <div class="card border-0 bg-primary-subtle text-primary-emphasis rounded-4 p-4">
            <h6 class="fw-bold mb-2 d-flex align-items-center gap-2">
                <i class="bi bi-shield-lock-fill text-primary"></i> Keamanan API Terjamin
            </h6>
            <ul class="small ps-3 mb-0" style="font-size:.78rem; line-height: 1.6;">
                <li>Kunci API dienkripsi di level database dengan <strong>AES-256-CBC</strong>.</li>
                <li>API Key tidak pernah dikembalikan ke browser setelah disimpan.</li>
                <li>Dilengkapi <strong>SSRF Protection</strong> yang memblokir akses ke host lokal & metadata cloud.</li>
                <li>Audit trail mencatat setiap pengujian dan perubahan status.</li>
            </ul>
        </div>

    </div>
</div>

<!-- ========================================================
     BAGIAN 5: PENGATURAN MODEL PER FITUR (Requirement 5)
     ======================================================== -->
<div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-5">
    <div class="card-header bg-transparent border-bottom px-4 py-3 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
        <div>
            <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-grid-1x2-fill text-primary"></i> Pengaturan Model per Fitur AI
            </h5>
            <p class="text-muted small mb-0">Tentukan model, provider, dan status aktif/nonaktif untuk masing-masing kapabilitas AI Terima Jasa.</p>
        </div>
        <span class="badge bg-info-subtle text-info border border-info-subtle">
            <?= count($featureConfigs) ?> Fitur Terdaftar
        </span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.05em;">
                    <th class="ps-4">Nama Fitur AI</th>
                    <th>Kunci Sistem</th>
                    <th>Model AI</th>
                    <th>Status Fitur</th>
                    <th class="text-end pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($featureConfigs)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            Belum ada konfigurasi fitur. Refresh halaman untuk auto-seed konfigurasi.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($featureConfigs as $feat): ?>
                        <tr>
                            <!-- Label Fitur -->
                            <td class="ps-4">
                                <div class="fw-semibold text-dark small"><?= e($feat->feature_label) ?></div>
                                <div class="text-muted" style="font-size:.72rem;">Terintegrasi dengan core service Terima Jasa</div>
                            </td>

                            <!-- Feature Key -->
                            <td>
                                <code><?= e($feat->feature_key) ?></code>
                            </td>

                            <!-- Form Inline untuk Perbarui Model & Toggle -->
                            <form action="<?= url('admin/ai-settings/features') ?>" method="POST" id="formFeat_<?= e($feat->id) ?>">
                                <input type="hidden" name="feature_key" value="<?= e($feat->feature_key) ?>">

                                <!-- Model yang Digunakan -->
                                <td>
                                    <input type="text" 
                                           name="model_name" 
                                           class="form-control form-control-sm rounded-3" 
                                           placeholder="Gunakan Model Default (<?= e($activeConfig->default_model ?? 'Aktif') ?>)"
                                           value="<?= e($feat->model_name ?? '') ?>" 
                                           style="min-width: 220px;">
                                </td>

                                <!-- Status Toggle -->
                                <td>
                                    <div class="form-check form-switch fs-5 mb-0">
                                        <input class="form-check-input cursor-pointer" 
                                               type="checkbox" 
                                               name="is_enabled" 
                                               value="1" 
                                               <?= ((int)$feat->is_enabled === 1) ? 'checked' : '' ?>
                                               title="Aktifkan atau nonaktifkan fitur ini">
                                        <span class="badge <?= ((int)$feat->is_enabled === 1) ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?>" style="font-size:.68rem;">
                                            <?= ((int)$feat->is_enabled === 1) ? 'Aktif' : 'Nonaktif' ?>
                                        </span>
                                    </div>
                                </td>

                                <!-- Aksi Simpan -->
                                <td class="text-end pe-4">
                                    <button type="submit" class="btn btn-sm btn-outline-primary rounded-3 px-3">
                                        <i class="bi bi-save2"></i> Simpan
                                    </button>
                                </td>
                            </form>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Uji Koneksi Nyata (Requirement 6) -->
<div class="modal fade" id="testResultModal" tabindex="-1" aria-labelledby="testResultModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom px-4 py-3">
                <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="testResultModalLabel">
                    <i class="bi bi-broadcast text-primary"></i> Pengujian Respon Model AI
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="testModalContent">
                <!-- Injected via JavaScript -->
            </div>
            <div class="modal-footer border-top px-4 py-2">
                <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Form untuk Reset Parameter (Requirement 6) -->
<form id="resetParamForm" action="<?= url('admin/ai-settings/reset-defaults') ?>" method="POST" class="d-none">
    <input type="hidden" name="config_id" value="<?= e($activeConfig->id ?? 0) ?>">
</form>

<!-- CSS Kustom -->
<style>
.cursor-pointer { cursor: pointer; }
.transition { transition: all 0.2s ease-in-out; }
</style>

<!-- JAVASCRIPT LOGIC LENGKAP -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const supportedProviders = <?= json_encode($supportedProviders) ?>;

    const providerSelect = document.getElementById('providerSelect');
    const baseUrlInput = document.getElementById('baseUrlInput');
    const providerDocsLink = document.getElementById('providerDocsLink');
    const providerNoteText = document.getElementById('providerNoteText');
    const providerSupportBadge = document.getElementById('providerSupportBadge');

    const apiKeyInput = document.getElementById('apiKeyInput');
    const btnToggleKeyVisibility = document.getElementById('btnToggleKeyVisibility');
    const iconEye = document.getElementById('iconEye');

    const modelSelect = document.getElementById('modelSelect');
    const customModelInput = document.getElementById('customModelInput');
    const btnFetchModels = document.getElementById('btnFetchModels');
    const fetchSpinnerIcon = document.getElementById('fetchSpinnerIcon');
    const fetchModelNotice = document.getElementById('fetchModelNotice');

    const tempInput = document.getElementById('tempInput');
    const tempBadge = document.getElementById('tempBadge');
    const topPInput = document.getElementById('topPInput');
    const topPBadge = document.getElementById('topPBadge');

    const btnTopTestConnection = document.getElementById('btnTopTestConnection');
    const btnTestForm = document.getElementById('btnTestForm');
    const liveAlertBox = document.getElementById('liveAlertBox');
    const testResultModal = new bootstrap.Modal(document.getElementById('testResultModal'));
    const testModalContent = document.getElementById('testModalContent');

    const btnTriggerResetParams = document.getElementById('btnTriggerResetParams');
    const resetParamForm = document.getElementById('resetParamForm');

    // 1. Toggle Show / Hide Password Key
    btnToggleKeyVisibility.addEventListener('click', function() {
        if (apiKeyInput.type === 'password') {
            apiKeyInput.type = 'text';
            iconEye.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            apiKeyInput.type = 'password';
            iconEye.classList.replace('bi-eye-slash', 'bi-eye');
        }
    });

    // 2. Real-time Slider Badges
    tempInput.addEventListener('input', function() {
        tempBadge.textContent = parseFloat(this.value).toFixed(2);
    });
    topPInput.addEventListener('input', function() {
        topPBadge.textContent = parseFloat(this.value).toFixed(2);
    });

    const activeSavedProvider = '<?= e($activeConfig->provider ?? 'groq') ?>';
    const activeSavedMaskedKey = '<?= e($maskedApiKey) ?>';
    const apiKeyNoticeBox = document.getElementById('apiKeyNoticeBox');
    const credentialStatusLabel = document.getElementById('credentialStatusLabel');

    // 3. Provider Switching Logic — Dynamic Models & Credentials Guidance
    function updateProviderFormState(pKey) {
        const pData = supportedProviders[pKey];
        if (!pData) return;

        // Update Base URL & Docs Link
        baseUrlInput.value = pData.default_url || '';
        providerDocsLink.href = pData.docs_url || '#';
        providerNoteText.textContent = pData.note || '';

        // Update Provider Support Badge
        if (pData.is_supported) {
            providerSupportBadge.className = 'badge bg-primary-subtle text-primary border border-primary-subtle';
            providerSupportBadge.textContent = 'Adapter Aktif';
        } else {
            providerSupportBadge.className = 'badge bg-warning-subtle text-warning border border-warning-subtle';
            providerSupportBadge.textContent = 'Perlu Dikonfigurasi';
        }

        // Update Model Options Dinamis sesuai Provider
        modelSelect.innerHTML = '';
        if (Array.isArray(pData.models) && pData.models.length > 0) {
            pData.models.forEach((m, idx) => {
                const opt = document.createElement('option');
                opt.value = m.id;
                opt.textContent = `${m.name}`;
                if (idx === 0) opt.selected = true;
                modelSelect.appendChild(opt);
            });
        }

        const customOpt = document.createElement('option');
        customOpt.value = 'custom-model';
        customOpt.textContent = '-- Masukkan Model Manual / Kustom --';
        modelSelect.appendChild(customOpt);

        customModelInput.value = '';

        // Penanganan Kredensial API Key saat berganti provider
        if (pKey === activeSavedProvider) {
            apiKeyNoticeBox.classList.add('d-none');
            apiKeyNoticeBox.innerHTML = '';
            apiKeyInput.placeholder = activeSavedMaskedKey ? 'API key telah tersimpan secara aman (Kosongkan jika tidak ingin mengubah)' : 'Masukkan API Key baru';
            credentialStatusLabel.className = activeSavedMaskedKey ? 'text-success' : 'text-danger';
            credentialStatusLabel.textContent = activeSavedMaskedKey ? `Tersimpan [${activeSavedMaskedKey}]` : 'Belum Dikonfigurasi';
        } else {
            apiKeyNoticeBox.classList.remove('d-none');
            apiKeyNoticeBox.innerHTML = `
                <div class="alert alert-warning py-2 px-3 small rounded-3 border-0 d-flex align-items-center gap-2 mb-0 shadow-sm" style="font-size:.78rem;">
                    <i class="bi bi-info-circle-fill text-warning fs-5 flex-shrink-0"></i>
                    <div>
                        Anda beralih ke <strong>${escapeHtml(pData.name)}</strong>. Kunci tersimpan di sistem saat ini adalah untuk <strong>${activeSavedProvider.toUpperCase()}</strong>.
                        Silakan masukkan API Key resmi untuk <strong>${escapeHtml(pData.name)}</strong> di atas sebelum menguji atau menyimpan.
                    </div>
                </div>
            `;
            apiKeyInput.placeholder = pData.key_placeholder || `Masukkan API Key untuk ${pData.name}`;
            credentialStatusLabel.className = 'text-warning';
            credentialStatusLabel.textContent = `Perlu Kunci ${pData.name.split(' ')[0]}`;
        }
    }

    providerSelect.addEventListener('change', function() {
        updateProviderFormState(this.value);
    });

    // 5. Ambil Daftar Model Asli dari Provider (Requirement 6)
    btnFetchModels.addEventListener('click', async function() {
        const provider = providerSelect.value;
        const apiKey = apiKeyInput.value.trim();
        const baseUrl = baseUrlInput.value.trim();

        fetchSpinnerIcon.classList.add('bi-spin');
        btnFetchModels.disabled = true;
        fetchModelNotice.innerHTML = `<span class="text-primary"><div class="spinner-border spinner-border-sm me-1" role="status"></div> Menghubungi ${provider.toUpperCase()} untuk mengambil daftar model resmi...</span>`;

        try {
            const resp = await fetch('<?= url('admin/ai-settings/fetch-models') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    provider: provider,
                    api_key: apiKey,
                    base_url: baseUrl
                })
            });

            const data = await resp.json();

            if (data.success && Array.isArray(data.models)) {
                // Bersihkan dropdown dan isi dengan model aktual
                modelSelect.innerHTML = '';
                data.models.forEach(m => {
                    const opt = document.createElement('option');
                    opt.value = m.id;
                    opt.textContent = `${m.name} (${m.owned_by || 'Provider'})`;
                    modelSelect.appendChild(opt);
                });

                // Tambahkan opsi kustom di akhir
                const customOpt = document.createElement('option');
                customOpt.value = 'custom-model';
                customOpt.textContent = '-- Masukkan Model Manual / Kustom --';
                modelSelect.appendChild(customOpt);

                fetchModelNotice.innerHTML = `<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> ${escapeHtml(data.message)}</span>`;
            } else {
                fetchModelNotice.innerHTML = `<span class="text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> ${escapeHtml(data.message || 'Gagal memuat model.')}</span>`;
            }
        } catch (err) {
            fetchModelNotice.innerHTML = `<span class="text-danger"><i class="bi bi-wifi-off me-1"></i> Terjadi kesalahan jaringan saat memuat model: ${escapeHtml(err.message)}</span>`;
        } finally {
            fetchSpinnerIcon.classList.remove('bi-spin');
            btnFetchModels.disabled = false;
        }
    });

    // 6. Uji Koneksi Nyata (Requirement 6)
    async function executeLiveTest() {
        const provider = providerSelect.value;
        const apiKey = apiKeyInput.value.trim();
        const baseUrl = baseUrlInput.value.trim();
        let model = modelSelect.value;
        if (model === 'custom-model' && customModelInput.value.trim()) {
            model = customModelInput.value.trim();
        }
        const temp = parseFloat(tempInput.value);

        testModalContent.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h6 class="fw-bold text-dark mb-1">Menghubungi Server ${escapeHtml(provider.toUpperCase())}...</h6>
                <p class="text-muted small mb-0">Menguji model <strong>${escapeHtml(model || 'default')}</strong>...</p>
            </div>
        `;
        testResultModal.show();

        try {
            const resp = await fetch('<?= url('admin/ai-settings/test') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    provider: provider,
                    api_key: apiKey,
                    base_url: baseUrl,
                    model: model,
                    temperature: temp
                })
            });

            const data = await resp.json();

            if (data.success) {
                testModalContent.innerHTML = `
                    <div class="text-center mb-3">
                        <div class="rounded-circle bg-success-subtle text-success p-3 d-inline-flex align-items-center justify-content-center mb-2" style="width:64px;height:64px;">
                            <i class="bi bi-check-circle-fill fs-2"></i>
                        </div>
                        <h5 class="fw-bold text-success mb-1">Koneksi Berhasil Terhubung!</h5>
                        <p class="text-muted small mb-0">${escapeHtml(data.message)}</p>
                    </div>

                    <div class="card bg-light border-0 rounded-3 p-3 mb-3">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Provider: <strong class="text-dark">${escapeHtml(data.provider)}</strong></span>
                            <span>Latency: <strong class="text-success">${escapeHtml(data.latency_ms)} ms</strong></span>
                        </div>
                        <div class="small text-muted">Model Terkonfirmasi: <strong class="text-dark">${escapeHtml(data.model)}</strong></div>
                    </div>

                    <div>
                        <label class="small fw-semibold text-muted text-uppercase mb-1" style="font-size:.7rem;">Respon Output Pengujian:</label>
                        <div class="p-3 bg-white border rounded-3 small text-dark fst-italic">
                            "${escapeHtml(data.reply)}"
                        </div>
                    </div>
                `;

                liveAlertBox.className = 'alert alert-success alert-dismissible fade show rounded-4 d-flex align-items-center gap-3 shadow-sm border-0 mb-4';
                liveAlertBox.innerHTML = `
                    <i class="bi bi-check-circle-fill fs-4 text-success flex-shrink-0"></i>
                    <div>
                        <strong>Pengujian Berhasil:</strong> Model <code>${escapeHtml(data.model)}</code> siap digunakan dengan response time ${escapeHtml(data.latency_ms)} ms.
                    </div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                `;
            } else {
                testModalContent.innerHTML = `
                    <div class="text-center mb-3">
                        <div class="rounded-circle bg-danger-subtle text-danger p-3 d-inline-flex align-items-center justify-content-center mb-2" style="width:64px;height:64px;">
                            <i class="bi bi-x-circle-fill fs-2"></i>
                        </div>
                        <h5 class="fw-bold text-danger mb-1">Pengujian Gagal</h5>
                        <p class="text-muted small mb-0">Provider menolak permintaan atau model tidak dapat dihubungi.</p>
                    </div>

                    <div class="alert alert-danger rounded-3 p-3 small mb-0">
                        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Rincian Kesalahan:</div>
                        <div>${escapeHtml(data.error || 'Terjadi kesalahan tidak terduga.')}</div>
                        ${data.code ? `<div class="mt-1 text-muted" style="font-size:.7rem;">Error Code: ${escapeHtml(data.code)}</div>` : ''}
                    </div>
                `;

                liveAlertBox.className = 'alert alert-danger alert-dismissible fade show rounded-4 d-flex align-items-center gap-3 shadow-sm border-0 mb-4';
                liveAlertBox.innerHTML = `
                    <i class="bi bi-exclamation-triangle-fill fs-4 text-danger flex-shrink-0"></i>
                    <div>
                        <strong>Pengujian Gagal:</strong> ${escapeHtml(data.error || 'Gagal menghubungi server AI.')}
                    </div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                `;
            }
        } catch (err) {
            testModalContent.innerHTML = `
                <div class="text-center py-4">
                    <div class="rounded-circle bg-danger-subtle text-danger p-3 d-inline-flex align-items-center justify-content-center mb-2" style="width:64px;height:64px;">
                        <i class="bi bi-wifi-off fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-danger mb-1">Kesalahan Koneksi Jaringan</h5>
                    <p class="text-muted small mb-0">Gagal mengirimkan permintaan pengujian (${escapeHtml(err.message)}).</p>
                </div>
            `;
        }
    }

    btnTopTestConnection.addEventListener('click', executeLiveTest);
    btnTestForm.addEventListener('click', executeLiveTest);

    // 7. Reset Parameters Confirmation
    btnTriggerResetParams.addEventListener('click', function(e) {
        e.preventDefault();
        if (confirm('Kembalikan Temperature, Max Tokens, Top P, dan Timeout ke nilai default standar? (Kunci API Anda akan tetap aman tersimpan)')) {
            resetParamForm.submit();
        }
    });

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});
</script>
