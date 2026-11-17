<!-- ============================================================
     ADMIN AI COPILOT & INTELLIGENCE — Terima Jasa
     Route: /admin/ai-assistant
     ============================================================ -->

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm overflow-hidden" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);">
            <div class="card-body p-4 p-lg-5 text-white position-relative">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white bg-opacity-20 text-white mb-3" style="font-size: .85rem;">
                            <span class="spinner-grow spinner-grow-sm text-danger" role="status"></span>
                            <span>Admin AI Platform Intelligence Active</span>
                        </div>
                        <h2 class="fw-bold mb-2">Admin AI Copilot & Operasional Platform</h2>
                        <p class="text-white-50 mb-0 fs-6" style="max-width: 650px;">
                            Pusat kecerdasan buatan untuk mengawasi kesehatan platform Terima Jasa, analisis retensi pesanan, SOP verifikasi berkas mitra teknisi, penyelesaian sengketa/komplain, dan strategi penyesuaian tarif layanan.
                        </p>
                    </div>
                    <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                        <div class="d-inline-flex flex-column align-items-lg-end bg-black bg-opacity-25 p-3 rounded-4 border border-white border-opacity-10">
                            <span class="text-white-50 small mb-1">Supervisi Platform Terima Jasa</span>
                            <span class="badge bg-danger px-3 py-2 fs-6 rounded-pill">
                                <i class="bi bi-shield-lock-fill me-1"></i> Administrator Akses Penuh
                            </span>
                            <small class="text-white-50 mt-2"><?= date('d F Y, H:i') ?> WIB</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- LIVE PLATFORM HEALTH STATS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small d-block">Total Pengguna</span>
                    <h4 class="fw-bold mb-0 text-dark"><?= (int)($metrics['total_users'] ?? 0) ?></h4>
                    <small class="text-primary"><?= (int)($metrics['customers'] ?? 0) ?> Cust · <?= (int)($metrics['technicians'] ?? 0) ?> Tek</small>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center text-primary bg-primary-subtle" style="width: 44px; height: 44px;">
                    <i class="bi bi-people-fill fs-5"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small d-block">Total Pesanan</span>
                    <h4 class="fw-bold mb-0 text-dark"><?= (int)($metrics['total_orders'] ?? 0) ?></h4>
                    <small class="text-success"><?= (int)($metrics['completed_orders'] ?? 0) ?> Selesai</small>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center text-success bg-success-subtle" style="width: 44px; height: 44px;">
                    <i class="bi bi-receipt fs-5"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small d-block">Pesanan Berjalan</span>
                    <h4 class="fw-bold mb-0 text-warning"><?= (int)($metrics['active_orders'] ?? 0) ?></h4>
                    <small class="text-muted"><?= (int)($metrics['pending_orders'] ?? 0) ?> Menunggu Konfirmasi</small>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center text-warning bg-warning-subtle" style="width: 44px; height: 44px;">
                    <i class="bi bi-clock-history fs-5"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small d-block">Antrean Verifikasi</span>
                    <h4 class="fw-bold mb-0 text-danger"><?= (int)($metrics['unverified_techs'] ?? 0) ?></h4>
                    <small class="text-danger">Mitra Perlu Validasi</small>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center text-danger bg-danger-subtle" style="width: 44px; height: 44px;">
                    <i class="bi bi-patch-exclamation-fill fs-5"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- LEFT COLUMN: Admin Interactive Chat Hub -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden d-flex flex-column" style="min-height: 600px;">
            <!-- Header Chat -->
            <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 44px; height: 44px; background: linear-gradient(135deg, #4f46e5, #9333ea);">
                        <i class="bi bi-cpu-fill fs-5"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold">Live Admin Strategic Copilot</h6>
                        <small class="text-muted"><span class="text-danger">●</span> Mode: Administrator Strategic Operations (Groq Cloud)</small>
                    </div>
                </div>
                <div>
                    <form action="<?= url('admin/ai-assistant/clear') ?>" method="POST" onsubmit="return confirm('Bersihkan riwayat konsultasi admin sesi ini?');">
                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                            <i class="bi bi-trash3 me-1"></i> Bersihkan Chat
                        </button>
                    </form>
                </div>
            </div>

            <!-- Quick Suggestion Chips -->
            <div class="p-3 bg-light border-bottom overflow-auto text-nowrap" id="adminPromptChips">
                <span class="text-muted small fw-semibold me-2"><i class="bi bi-stars text-warning"></i> Quick Analysis:</span>
                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill me-1 tj-admin-chip" data-prompt="Berapa total teknisi yang terdaftar di platform saat ini beserta rincian statusnya?">
                    📊 Total Teknisi & Status
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill me-1 tj-admin-chip" data-prompt="Bagaimana SOP standar memverifikasi keaslian sertifikat keahlian dan riwayat KTP teknisi baru agar terhindar dari pemalsuan?">
                    🛡️ SOP Verifikasi Berkas
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill me-1 tj-admin-chip" data-prompt="Ada komplain customer: teknisi terlambat 3 jam dan unit AC bocor kembali dalam 3 hari. Bagaimana langkah mediasi dan kebijakan garansi 14 hari Terima Jasa?">
                    ⚖️ Mediasi Komplain Order
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill me-1 tj-admin-chip" data-prompt="Berapa rekomendasi tarif pasar wajar untuk kategori servis AC dan kelistrikan rumah di wilayah Jabodetabek saat ini?">
                    🏷️ Benchmarking Tarif Pasar
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill me-1 tj-admin-chip" data-prompt="Berdasarkan data operasional platform kami saat ini, berikan strategi untuk meningkatkan tingkat retensi repeat-order pelanggan Terima Jasa.">
                    📈 Strategi Retensi Order
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill me-1 tj-admin-chip" data-prompt="Buatkan draf surat peringatan resmi (SP-1) via email untuk mitra teknisi yang menolak order tanpa alasan valid sebanyak 3 kali berturut-turut.">
                    📋 Draf SP Pelanggaran Mitra
                </button>
            </div>

            <!-- Messages Stream Area -->
            <div class="card-body p-4 overflow-y-auto flex-grow-1" id="adminChatMessages" style="max-height: 480px; background: #f8fafc;">
                <!-- Initial Welcome Message -->
                <div class="d-flex gap-3 mb-4 tj-msg-bot">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width: 38px; height: 38px; background: #4f46e5;">
                        <i class="bi bi-robot"></i>
                    </div>
                    <div class="bg-white p-3 rounded-4 border shadow-sm text-dark" style="max-width: 85%;">
                        <div class="fw-bold text-danger mb-1 small">Admin AI Strategic Copilot</div>
                        <p class="mb-2">
                            Halo Administrator <strong><?= e($currentUser['name'] ?? 'Super Admin') ?></strong>! ⚡<br>
                            Saya siap membantu Anda dalam supervisi harian: analisis metrik kepuasan pengguna, verifikasi berkas teknisi, resolusi sengketa pesanan, hingga rekomendasi penyesuaian tarif layanan platform Terima Jasa.
                        </p>
                        <small class="text-muted">Klik salah satu prompt analisis cepat di atas atau ajukan pertanyaan operasional Anda.</small>
                    </div>
                </div>

                <!-- Existing Session History -->
                <?php if (!empty($chatHistory)): ?>
                    <?php foreach ($chatHistory as $item): ?>
                        <!-- User Bubble -->
                        <div class="d-flex justify-content-end mb-3">
                            <div class="bg-primary text-white p-3 rounded-4 shadow-sm" style="max-width: 82%;">
                                <div class="small text-white-50 mb-1 text-end">Anda (Administrator)</div>
                                <div><?= nl2br(e($item['question'])) ?></div>
                            </div>
                        </div>

                        <!-- Bot Bubble -->
                        <div class="d-flex gap-3 mb-4">
                            <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width: 38px; height: 38px; background: #4f46e5;">
                                <i class="bi bi-robot"></i>
                            </div>
                            <div class="bg-white p-3 rounded-4 border shadow-sm text-dark position-relative" style="max-width: 85%;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold text-primary small">Admin AI Copilot</span>
                                    <button class="btn btn-sm btn-link text-muted p-0 tj-copy-btn" title="Salin Jawaban" onclick="copyAdminText(this)">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                                <div class="tj-chat-rendered"><?= nl2br(e($item['reply'])) ?></div>
                                <div class="text-muted mt-2" style="font-size: .72rem;"><?= e($item['timestamp'] ?? '') ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Typing indicator -->
                <div id="adminTypingIndicator" class="d-none gap-3 mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width: 38px; height: 38px; background: #4f46e5;">
                        <i class="bi bi-robot"></i>
                    </div>
                    <div class="bg-white p-3 rounded-4 border shadow-sm d-inline-flex align-items-center gap-2">
                        <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                        <span class="text-muted small">AI sedang mengolah data dan strategi operasional...</span>
                    </div>
                </div>
            </div>

            <!-- Footer Chat Input -->
            <div class="card-footer bg-white border-top p-3">
                <form id="adminChatForm">
                    <div class="input-group">
                        <textarea id="adminQuestionInput" class="form-control rounded-start-4 border" rows="1" placeholder="Konsultasikan evaluasi sistem, verifikasi teknisi, mediasi sengketa, atau kebijakan harga..." style="resize:none;"></textarea>
                        <button type="submit" id="adminSendBtn" class="btn btn-danger px-4 rounded-end-4 fw-semibold">
                            <i class="bi bi-send-fill me-1"></i> Kirim
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN: Strategic Admin Toolkits -->
    <div class="col-lg-4">
        <div class="d-flex flex-column gap-4">
            
            <!-- Strategic Toolkit 1: Panduan Verifikasi Teknisi -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom p-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-patch-check-fill text-success me-2"></i>Checklist Verifikasi Teknisi</h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex flex-column gap-2 small text-secondary">
                        <div class="p-2 border rounded-3 bg-light">
                            <strong>1. Cek NIK & KTP:</strong> Validasi 16 digit NIK, nama lengkap, dan kecocokan foto selfie pemegang identitas.
                        </div>
                        <div class="p-2 border rounded-3 bg-light">
                            <strong>2. Sertifikasi / Portofolio:</strong> Cek keabsahan sertifikat BNSP/APITU atau pengalaman kerja bengkel minimal 2 tahun.
                        </div>
                        <div class="p-2 border rounded-3 bg-light">
                            <strong>3. Verifikasi Nomor HP & Rekening:</strong> Pastikan rekening pencairan atas nama yang sama dengan KTP.
                        </div>
                    </div>
                    <a href="<?= url('admin/technicians') ?>" class="btn btn-sm btn-outline-primary w-100 rounded-pill mt-3">
                        Buka Antrean Teknisi (<?= (int)($metrics['unverified_techs'] ?? 0) ?>)
                    </a>
                </div>
            </div>

            <!-- Strategic Toolkit 2: Ringkasan Kebijakan Garansi Platform -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom p-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-shield-shaded text-danger me-2"></i>SOP Garansi 14 Hari</h6>
                </div>
                <div class="card-body p-3 small text-secondary">
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                        <li>• <strong>Cakupan Garansi:</strong> Komponen dan pengerjaan yang diservis dalam waktu 14 hari kalender sejak status 'Completed'.</li>
                        <li>• <strong>Penanganan Komplain:</strong> Teknisi penanggung jawab wajib melakukan revisi tanpa biaya jasa tambahan.</li>
                        <li>• <strong>Dana Perlindungan:</strong> Jika teknisi wanprestasi, sistem mencabut status 'Verified' dan menggantikan biaya perbaikan dari cadangan garansi.</li>
                    </ul>
                </div>
            </div>

            <!-- Strategic Toolkit 3: Navigasi Cepat Manajemen -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom p-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-grid text-dark me-2"></i>Aksi Cepat Admin</h6>
                </div>
                <div class="card-body p-3 d-flex flex-column gap-2">
                    <a href="<?= url('admin/orders') ?>" class="btn btn-sm btn-light border text-start d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-receipt me-2 text-primary"></i>Kelola Seluruh Order</span>
                        <span class="badge bg-secondary"><?= (int)($metrics['total_orders'] ?? 0) ?></span>
                    </a>
                    <a href="<?= url('admin/categories') ?>" class="btn btn-sm btn-light border text-start d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-tags me-2 text-success"></i>Katalog Kategori Jasa</span>
                        <span class="badge bg-secondary"><?= (int)($metrics['categories_count'] ?? 0) ?></span>
                    </a>
                    <a href="<?= url('admin/reports') ?>" class="btn btn-sm btn-light border text-start d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-graph-up me-2 text-danger"></i>Laporan Keuangan & Statistik</span>
                        <i class="bi bi-chevron-right small text-muted"></i>
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const chatMessages = document.getElementById('adminChatMessages');
    const questionInput = document.getElementById('adminQuestionInput');
    const chatForm = document.getElementById('adminChatForm');
    const sendBtn = document.getElementById('adminSendBtn');
    const typingIndicator = document.getElementById('adminTypingIndicator');

    // Auto scroll chat to bottom
    chatMessages.scrollTop = chatMessages.scrollHeight;

    // Handle Quick Prompt Chips
    document.querySelectorAll('.tj-admin-chip').forEach(btn => {
        btn.addEventListener('click', function () {
            const prompt = this.getAttribute('data-prompt');
            if (prompt) {
                questionInput.value = prompt;
                submitAdminChat(prompt);
            }
        });
    });

    // Form Submit
    chatForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const text = questionInput.value.trim();
        if (!text) return;
        submitAdminChat(text);
    });

    async function submitAdminChat(question) {
        const userHtml = `
            <div class="d-flex justify-content-end mb-3">
                <div class="bg-primary text-white p-3 rounded-4 shadow-sm" style="max-width: 82%;">
                    <div class="small text-white-50 mb-1 text-end">Anda (Administrator)</div>
                    <div>${escapeHtml(question).replace(/\n/g, '<br>')}</div>
                </div>
            </div>`;
        chatMessages.insertAdjacentHTML('beforeend', userHtml);
        questionInput.value = '';
        chatMessages.scrollTop = chatMessages.scrollHeight;

        // Show typing indicator
        typingIndicator.classList.remove('d-none');
        typingIndicator.classList.add('d-flex');
        chatMessages.appendChild(typingIndicator);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        sendBtn.disabled = true;

        try {
            const res = await fetch('<?= url('admin/ai-assistant/chat') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ question: question })
            });

            const data = await res.json();
            typingIndicator.classList.add('d-none');
            typingIndicator.classList.remove('d-flex');

            if (data.success) {
                const botHtml = `
                    <div class="d-flex gap-3 mb-4">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width: 38px; height: 38px; background: #4f46e5;">
                            <i class="bi bi-robot"></i>
                        </div>
                        <div class="bg-white p-3 rounded-4 border shadow-sm text-dark position-relative" style="max-width: 85%;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-primary small">Admin AI Copilot</span>
                                <button class="btn btn-sm btn-link text-muted p-0 tj-copy-btn" title="Salin Jawaban" onclick="copyAdminText(this)">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                            <div class="tj-chat-rendered">${renderMarkdown(data.reply)}</div>
                            <div class="text-muted mt-2" style="font-size: .72rem;">${data.timestamp}</div>
                        </div>
                    </div>`;
                chatMessages.insertAdjacentHTML('beforeend', botHtml);
            } else {
                const errHtml = `<div class="alert alert-danger mb-3">${data.error || 'Terjadi kesalahan sistem.'}</div>`;
                chatMessages.insertAdjacentHTML('beforeend', errHtml);
            }
        } catch (err) {
            typingIndicator.classList.add('d-none');
            const errHtml = `<div class="alert alert-danger mb-3">Koneksi ke asisten AI terputus. Silakan coba kembali.</div>`;
            chatMessages.insertAdjacentHTML('beforeend', errHtml);
        } finally {
            sendBtn.disabled = false;
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function renderMarkdown(text) {
        if (!text) return '';

        // Extract code blocks
        const codeBlocks = [];
        let src = text.replace(/```([\s\S]*?)```/g, (match, code) => {
            codeBlocks.push(code);
            return `__CODE_BLOCK_${codeBlocks.length - 1}__`;
        });

        // Extract markdown tables
        const tables = [];
        src = src.replace(/((?:\|[^\n]+\|\r?\n)+)/g, (match, tableText) => {
            const rows = tableText.trim().split(/\r?\n/).map(r => r.trim()).filter(Boolean);
            if (rows.length < 2) return match;
            let tableHtml = '<div class="table-responsive my-2"><table class="table table-sm table-bordered bg-white small mb-0">';
            let isHead = true;
            rows.forEach((row) => {
                if (row.match(/^\|[\s\-:]+\|\s*$/)) {
                    isHead = false;
                    return;
                }
                const cells = row.split('|').slice(1, -1).map(c => c.trim());
                if (isHead) {
                    tableHtml += '<thead class="table-light"><tr>' + cells.map(c => `<th>${escapeHtml(c)}</th>`).join('') + '</tr></thead><tbody>';
                    isHead = false;
                } else {
                    tableHtml += '<tr>' + cells.map(c => `<td>${escapeHtml(c)}</td>`).join('') + '</tr>';
                }
            });
            tableHtml += '</tbody></table></div>';
            tables.push(tableHtml);
            return `__TABLE_BLOCK_${tables.length - 1}__`;
        });

        let html = escapeHtml(src);

        // Headers
        html = html.replace(/^### (.*$)/gim, '<h6 class="fw-bold mt-2 mb-1 text-dark">$1</h6>');
        html = html.replace(/^## (.*$)/gim, '<h5 class="fw-bold mt-2 mb-1 text-dark">$1</h5>');
        html = html.replace(/^# (.*$)/gim, '<h5 class="fw-bold mt-2 mb-1 text-dark">$1</h5>');

        // Bold & Italic
        html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');

        // Inline code
        html = html.replace(/`([^`]+)`/g, '<code class="bg-light text-danger px-1 py-0.5 rounded small">$1</code>');

        // Horizontal line
        html = html.replace(/^---$/gm, '<hr class="my-2 border-secondary-subtle">');

        // Lists
        html = html.replace(/^[•\-\*]\s+(.*)$/gm, '<li class="ms-3">$1</li>');

        // Newlines
        html = html.replace(/\n/g, '<br>');

        // Restore tables
        tables.forEach((tbl, i) => {
            html = html.replace(`__TABLE_BLOCK_${i}__`, tbl);
        });

        // Restore code blocks
        codeBlocks.forEach((code, i) => {
            html = html.replace(`__CODE_BLOCK_${i}__`, `<pre class="bg-dark text-white p-2 rounded small overflow-x-auto my-2"><code>${escapeHtml(code.trim())}</code></pre>`);
        });

        return html;
    }

    window.copyAdminText = function (btn) {
        const container = btn.closest('.bg-white');
        const textContent = container.querySelector('.tj-chat-rendered').innerText;
        navigator.clipboard.writeText(textContent).then(() => {
            const original = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check-lg text-success"></i>';
            setTimeout(() => { btn.innerHTML = original; }, 2000);
        });
    };
});
</script>
