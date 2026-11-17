<!-- ============================================================
     AI ASSISTANT CHAT INTERFACE — Terima Jasa
     Route: /customer/ai-assistant
     ============================================================ -->

<!-- Custom Chat Styling -->
<style>
.tj-chat-container {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 210px);
    min-height: 540px;
    background: #ffffff;
    border-radius: 1.25rem;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05);
}

.tj-chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
    background: #f8fafc;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.tj-bubble-user {
    max-width: 82%;
    margin-left: auto;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #ffffff;
    padding: 1rem 1.25rem;
    border-radius: 1.25rem 1.25rem 0.25rem 1.25rem;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.18);
    font-size: 0.95rem;
    line-height: 1.5;
}

.tj-bubble-ai {
    max-width: 86%;
    margin-right: auto;
    background: #ffffff;
    color: #1e293b;
    border: 1px solid #e2e8f0;
    border-radius: 1.25rem 1.25rem 1.25rem 0.25rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    padding: 1.25rem;
    font-size: 0.95rem;
    line-height: 1.55;
}

.tj-typing-indicator {
    display: none;
    align-items: center;
    gap: 6px;
    padding: 0.75rem 1.25rem;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 1.25rem;
    width: fit-content;
    box-shadow: 0 2px 6px rgba(0,0,0,0.03);
}

.tj-dot {
    width: 8px;
    height: 8px;
    background: #3b82f6;
    border-radius: 50%;
    animation: tjWave 1.3s linear infinite;
}
.tj-dot:nth-child(2) { animation-delay: -1.1s; }
.tj-dot:nth-child(3) { animation-delay: -0.9s; }

@keyframes tjWave {
    0%, 60%, 100% { transform: initial; }
    30% { transform: translateY(-7px); }
}

.tj-prompt-chip {
    background: #f1f5f9;
    color: #334155;
    border: 1px solid #cbd5e1;
    border-radius: 2rem;
    padding: 0.4rem 0.85rem;
    font-size: 0.82rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
    text-decoration: none;
    display: inline-block;
}
.tj-prompt-chip:hover {
    background: #e2e8f0;
    color: #0f172a;
    border-color: #94a3b8;
    transform: translateY(-1px);
}
</style>

<!-- Flash Messages -->
<?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3 mb-3 d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-check-circle-fill text-success fs-5"></i>
        <div><?= e($_SESSION['flash_success']) ?></div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h4 class="fw-bold mb-0">Konsultasi & Diagnosa AI</h4>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                <i class="bi bi-stars me-1"></i> Smart Assistant
            </span>
        </div>
        <p class="text-muted small mb-0">Ceritakan kendala perangkat Anda, AI akan menganalisis penyebab dan merekomendasikan layanan teknisi yang tepat.</p>
    </div>
    <div class="d-flex gap-2">
        <form method="POST" action="<?= url('customer/ai-assistant/clear') ?>" onsubmit="return confirm('Bersihkan riwayat percakapan sesi ini?');">
            <button type="submit" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-trash me-1"></i> Bersihkan Chat
            </button>
        </form>
        <a href="<?= url('customer/services') ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
            <i class="bi bi-search me-1"></i> Cari Jasa Manual
        </a>
    </div>
</div>

<!-- Chat Container -->
<div class="tj-chat-container">

    <!-- Header Chat Bar -->
    <div class="p-3 bg-white border-bottom d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle d-flex align-items-center justify-content-center text-white"
                 style="width: 38px; height: 38px; background: linear-gradient(135deg, #06b6d4, #3b82f6);">
                <i class="bi bi-robot fs-5"></i>
            </div>
            <div>
                <div class="fw-bold text-dark small">TerimaJasa AI Diagnostic Engine</div>
                <div class="d-flex align-items-center gap-1 small text-success" style="font-size: .75rem;">
                    <span class="rounded-circle bg-success d-inline-block" style="width: 7px; height: 7px;"></span>
                    <span>Online & Siap Membantu</span>
                </div>
            </div>
        </div>
        <span class="badge bg-light text-muted border small d-none d-sm-inline-block font-monospace">
            v1.0 AI Assistant
        </span>
    </div>

    <!-- Message Stream -->
    <div class="tj-chat-messages" id="chatMessages">

        <!-- Welcome Bubble -->
        <div class="d-flex gap-2 align-items-start">
            <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0"
                 style="width: 34px; height: 34px; background: linear-gradient(135deg, #06b6d4, #3b82f6); font-size: .85rem;">
                <i class="bi bi-robot"></i>
            </div>
            <div class="tj-bubble-ai">
                <div class="fw-semibold text-primary small mb-1">
                    <i class="bi bi-stars me-1"></i> Halo, <?= e($currentUser['name'] ?? 'Pelanggan') ?>!
                </div>
                <p class="mb-2">
                    Saya asisten AI Terima Jasa. Apakah ada AC, kelistrikan, elektronik, komputer, atau pompa air di rumah Anda yang sedang bermasalah?
                </p>
                <div class="p-2 bg-light rounded-3 small text-muted border mb-0">
                    <i class="bi bi-info-circle me-1 text-primary"></i> 
                    Contoh: <em>"Saya punya AC yang tidak dingin dan mengeluarkan air."</em> atau <em>"MCB listrik di rumah sering anjlok tiba-tiba."</em>
                </div>
            </div>
        </div>

        <!-- Render Previous Chat History from Database if any -->
        <?php if (!empty($chatHistory)): ?>
            <?php foreach ($chatHistory as $item): ?>
                <!-- Customer Message -->
                <div class="d-flex gap-2 justify-content-end align-items-start">
                    <div class="tj-bubble-user">
                        <?= nl2br(e($item->question)) ?>
                    </div>
                </div>

                <!-- AI Response -->
                <div class="d-flex gap-2 align-items-start">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0"
                         style="width: 34px; height: 34px; background: linear-gradient(135deg, #06b6d4, #3b82f6); font-size: .85rem;">
                        <i class="bi bi-robot"></i>
                    </div>
                    <div class="tj-bubble-ai">
                        <div class="small text-muted mb-2 font-monospace">
                            <i class="bi bi-clock me-1"></i><?= date('d M Y, H:i', strtotime($item->created_at)) ?> WIB
                        </div>
                        <div class="text-dark mb-3">
                            <?= nl2br(e($item->answer)) ?>
                        </div>
                        <a href="<?= url('customer/services') ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold">
                            <i class="bi bi-search me-1"></i> Cari Jasa yang Direkomendasikan
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Typing Indicator Element -->
        <div id="typingIndicatorRow" class="d-flex gap-2 align-items-center" style="display: none !important;">
            <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0"
                 style="width: 34px; height: 34px; background: linear-gradient(135deg, #06b6d4, #3b82f6); font-size: .85rem;">
                <i class="bi bi-robot"></i>
            </div>
            <div class="tj-typing-indicator" id="typingIndicator">
                <span class="tj-dot"></span>
                <span class="tj-dot"></span>
                <span class="tj-dot"></span>
                <small class="text-muted ms-2" style="font-size: .78rem;">AI sedang menganalisis kerusakan...</small>
            </div>
        </div>

    </div>

    <!-- Quick Question Chips -->
    <div class="px-3 py-2 bg-white border-top border-bottom overflow-x-auto d-flex gap-2 align-items-center">
        <span class="text-muted small flex-shrink-0" style="font-size: .78rem;">Saran Cepat:</span>
        <button type="button" class="tj-prompt-chip bg-primary-subtle text-primary border-primary-subtle fw-bold" onclick="applyPrompt('Laptop saya tiba-tiba mati dan tidak bisa menyala.')">
            💻 Laptop tiba-tiba mati &amp; tidak bisa menyala
        </button>
        <button type="button" class="tj-prompt-chip" onclick="applyPrompt('Saya punya AC yang tidak dingin dan mengeluarkan air.')">
            ❄️ AC bocor air &amp; tidak dingin
        </button>
        <button type="button" class="tj-prompt-chip" onclick="applyPrompt('MCB listrik di rumah sering anjlok saat menyalakan beban berat.')">
            ⚡ MCB listrik sering anjlok
        </button>
        <button type="button" class="tj-prompt-chip" onclick="applyPrompt('Pipa pembuangan air wastafel mampet dan merembes ke lantai.')">
            🚰 Pipa air wastafel mampet
        </button>
    </div>

    <!-- Input Box -->
    <div class="p-3 bg-white">
        <form id="chatForm" onsubmit="handleSend(event)" class="d-flex gap-2 align-items-end">
            <div class="flex-grow-1 position-relative">
                <textarea id="questionInput" 
                          class="form-control bg-light border-1 rounded-4 px-3 py-2" 
                          rows="2" 
                          style="resize: none;"
                          placeholder="Jelaskan kendala perangkat Anda di sini (misal: Laptop mati total tidak mau menyala, AC bocor air, MCB listrik anjlok)..."
                          required></textarea>
            </div>
            <button type="submit" id="sendBtn" class="btn btn-primary rounded-4 px-4 py-2 d-flex align-items-center gap-2 flex-shrink-0" style="height: 58px;">
                <span class="fw-semibold d-none d-sm-inline">Kirim</span>
                <i class="bi bi-send-fill fs-5"></i>
            </button>
        </form>
    </div>

</div>

<!-- ============================================================
     JAVASCRIPT CHAT CONTROLLER
     ============================================================ -->
<script>
function applyPrompt(text) {
    const input = document.getElementById('questionInput');
    input.value = text;
    document.getElementById('chatForm').dispatchEvent(new Event('submit'));
}

function scrollToBottom() {
    const container = document.getElementById('chatMessages');
    container.scrollTop = container.scrollHeight;
}

// Enter untuk kirim (Shift+Enter untuk baris baru)
document.getElementById('questionInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        document.getElementById('chatForm').dispatchEvent(new Event('submit'));
    }
});

async function handleSend(e) {
    e.preventDefault();
    const input = document.getElementById('questionInput');
    const question = input.value.trim();
    if (!question) return;

    const chatMessages = document.getElementById('chatMessages');
    const sendBtn = document.getElementById('sendBtn');
    const typingRow = document.getElementById('typingIndicatorRow');

    // 1. Tampilkan bubble pesan customer
    const userMsgWrapper = document.createElement('div');
    userMsgWrapper.className = 'd-flex gap-2 justify-content-end align-items-start';
    userMsgWrapper.innerHTML = `
        <div class="tj-bubble-user">
            ${escapeHtml(question)}
        </div>
    `;
    // Sisipkan sebelum baris typing indicator
    chatMessages.insertBefore(userMsgWrapper, typingRow);

    // Reset input dan disable tombol
    input.value = '';
    sendBtn.disabled = true;
    typingRow.style.setProperty('display', 'flex', 'important');
    scrollToBottom();

    try {
        // 2. Request ke Backend
        const response = await fetch('<?= url('customer/ai-assistant') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ question: question })
        });

        const data = await response.json();

        // 3. Sembunyikan typing indicator
        typingRow.style.setProperty('display', 'none', 'important');
        sendBtn.disabled = false;

        // 4. Render Respon AI Terstruktur
        renderAiResponse(data);

    } catch (err) {
        typingRow.style.setProperty('display', 'none', 'important');
        sendBtn.disabled = false;

        // Fallback UI error jika koneksi jaringan putus total
        renderFallbackError(question);
    }

    scrollToBottom();
}

function renderAiResponse(data) {
    const chatMessages = document.getElementById('chatMessages');
    const typingRow = document.getElementById('typingIndicatorRow');

    const urgencyColor = {
        'Darurat': 'danger',
        'Tinggi': 'danger',
        'Sedang': 'warning',
        'Rendah': 'success'
    }[data.urgency] || 'info';

    // 1. KARTU REKOMENDASI JASA (Database Matched Services)
    let serviceCardsHtml = '';
    if (data.matched_services && data.matched_services.length > 0) {
        serviceCardsHtml = `
            <div class="mt-3 pt-3 border-top">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-tools text-primary"></i> Rekomendasi Jasa
                    </h6>
                    <span class="badge bg-light text-muted border small font-monospace" style="font-size: .7rem;">
                        ${data.matched_services.length} Layanan Ditemukan
                    </span>
                </div>
                <div class="d-flex flex-column gap-2">
                    ${data.matched_services.slice(0, 3).map(s => `
                        <div class="p-3 rounded-3 border bg-light d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 shadow-xs">
                            <div class="flex-grow-1">
                                <div class="fw-bold text-dark fs-6 mb-1">${escapeHtml(s.name)}</div>
                                <div class="text-secondary small mb-2" style="font-size: .83rem;">
                                    ${escapeHtml(s.description || 'Layanan servis profesional dengan jaminan kualitas dan garansi pengerjaan.')}
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted small" style="font-size: .75rem;">Estimasi Harga:</span>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace fw-bold fs-6">
                                        ${s.formatted_price}
                                    </span>
                                </div>
                            </div>
                            <div class="flex-shrink-0">
                                <a href="${s.order_url}" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm d-flex align-items-center gap-1 text-nowrap">
                                    <i class="bi bi-cart-plus me-1"></i> Pesan Jasa Ini
                                </a>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    // 2. KARTU REKOMENDASI TEKNISI (Database Verified & Ranked Technicians)
    let technicianCardsHtml = '';
    if (data.recommended_technicians && data.recommended_technicians.length > 0) {
        technicianCardsHtml = `
            <div class="mt-4 pt-3 border-top">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-person-badge text-success"></i> Rekomendasi Teknisi Terverifikasi
                    </h6>
                    <span class="badge bg-success-subtle text-success border border-success-subtle small font-monospace" style="font-size: .7rem;">
                        Urutan Prioritas: Keahlian &bull; Verified &bull; Available &bull; Rating &bull; Lokasi
                    </span>
                </div>
                <div class="d-flex flex-column gap-2">
                    ${data.recommended_technicians.slice(0, 3).map((t, idx) => {
                        const isAvail = (t.availability === 'available');
                        const availBadge = isAvail ? 'bg-success text-white' : 'bg-warning text-dark';
                        const availIcon = isAvail ? 'bi-check-circle-fill' : 'bi-clock-fill';

                        return `
                            <div class="p-3 rounded-3 border bg-white d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 shadow-xs">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="position-relative flex-shrink-0">
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-5" style="width: 48px; height: 48px;">
                                            ${escapeHtml(t.name.charAt(0).toUpperCase())}
                                        </div>
                                        <span class="position-absolute bottom-0 end-0 p-1 bg-success border border-white rounded-circle"></span>
                                    </div>
                                    <div>
                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                            <span class="fw-bold text-dark">${escapeHtml(t.name)}</span>
                                            ${t.is_verified ? `
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill py-0 px-2 small" style="font-size: .72rem;">
                                                    <i class="bi bi-patch-check-fill me-1"></i> Verified
                                                </span>
                                            ` : ''}
                                            <span class="badge ${availBadge} rounded-pill py-0 px-2 small" style="font-size: .72rem;">
                                                <i class="bi ${availIcon} me-1"></i> ${escapeHtml(t.availability_label)}
                                            </span>
                                            ${idx === 0 ? `
                                                <span class="badge bg-primary text-white rounded-pill py-0 px-2 small" style="font-size: .72rem;">
                                                    <i class="bi bi-award-fill me-1"></i> Rekomendasi Utama #1
                                                </span>
                                            ` : ''}
                                        </div>
                                        <div class="text-secondary small fw-medium mb-1">
                                            <i class="bi bi-wrench-adjustable me-1 text-primary"></i> Keahlian: <strong>${escapeHtml(t.expertise)}</strong>
                                        </div>
                                        <div class="d-flex flex-wrap align-items-center gap-3 small text-muted" style="font-size: .78rem;">
                                            <span><i class="bi bi-star-fill text-warning me-1"></i>${escapeHtml(t.rating_label)}</span>
                                            <span>&bull;</span>
                                            <span><i class="bi bi-briefcase text-secondary me-1"></i>${escapeHtml(t.experience)}</span>
                                            <span>&bull;</span>
                                            <span><i class="bi bi-geo-alt-fill text-danger me-1"></i>${escapeHtml(t.location)}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-shrink-0 align-self-start align-self-md-center">
                                    <a href="${t.order_url}" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-2 fw-semibold text-nowrap d-flex align-items-center gap-1 shadow-sm">
                                        <i class="bi bi-calendar-check me-1"></i> Pesan Jasa Ini
                                    </a>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>
        `;
    }

    const aiMsgWrapper = document.createElement('div');
    aiMsgWrapper.className = 'd-flex gap-2 align-items-start';

    // Template Respon AI Lengkap sesuai Spesifikasi Prompt 8
    aiMsgWrapper.innerHTML = `
        <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0"
             style="width: 34px; height: 34px; background: linear-gradient(135deg, #06b6d4, #3b82f6); font-size: .85rem;">
            <i class="bi bi-robot"></i>
        </div>
        <div class="tj-bubble-ai">
            <!-- Header Badges -->
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2 pb-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">
                        <i class="bi bi-tag-fill me-1"></i> ${escapeHtml(data.recommended_category || 'Jasa Servis')}
                    </span>
                    <span class="badge bg-${urgencyColor}-subtle text-${urgencyColor} border border-${urgencyColor}-subtle rounded-pill">
                        Urgensi: ${escapeHtml(data.urgency || 'Sedang')}
                    </span>
                </div>
                <small class="text-muted" style="font-size: .72rem;">${data.timestamp || 'Baru saja'}</small>
            </div>

            <!-- 1. Diagnosis Summary -->
            <div class="mb-2">
                <div class="fw-bold text-dark fs-6 mb-1">
                    <i class="bi bi-search text-info me-1"></i> ${escapeHtml(data.diagnosis_summary)}
                </div>
            </div>

            <!-- 2. Explanation -->
            <div class="text-secondary small mb-3">
                ${escapeHtml(data.explanation)}
            </div>

            <!-- 3. Primary Recommended Service Highlight -->
            ${data.recommended_service ? `
                <div class="p-2 px-3 mb-2 rounded-3 bg-primary-subtle text-primary border border-primary-subtle small fw-semibold">
                    <i class="bi bi-check-circle-fill me-1"></i> Rekomendasi Jasa Utama: <strong>${escapeHtml(data.recommended_service)}</strong>
                </div>
            ` : ''}

            <!-- 4. Output: Rekomendasi Jasa -->
            ${serviceCardsHtml}

            <!-- 5. Output: Rekomendasi Teknisi -->
            ${technicianCardsHtml}

            <!-- 6. Tombol Aksi Tambahan -->
            <div class="mt-3 pt-2 d-flex flex-wrap gap-2">
                <a href="${data.search_url || '<?= url('customer/services') ?>'}" class="btn btn-outline-primary btn-sm rounded-pill px-4 fw-semibold shadow-sm">
                    <i class="bi bi-search me-1"></i> Telusuri Katalog Lengkap
                </a>
                <a href="<?= url('customer/services') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    Lihat Semua Kategori
                </a>
            </div>

            <!-- 7. Disclaimer -->
            <div class="mt-3 pt-2 border-top text-muted" style="font-size: .72rem;">
                <i class="bi bi-shield-exclamation me-1 text-warning"></i>
                <em>${escapeHtml(data.disclaimer)}</em>
            </div>
        </div>
    `;

    chatMessages.insertBefore(aiMsgWrapper, typingRow);
}

function renderFallbackError(question) {
    const chatMessages = document.getElementById('chatMessages');
    const typingRow = document.getElementById('typingIndicatorRow');

    const errorWrapper = document.createElement('div');
    errorWrapper.className = 'd-flex gap-2 align-items-start';
    errorWrapper.innerHTML = `
        <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0"
             style="width: 34px; height: 34px; background: linear-gradient(135deg, #f59e0b, #ef4444); font-size: .85rem;">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <div class="tj-bubble-ai">
            <h6 class="fw-bold text-danger mb-1"><i class="bi bi-wifi-off me-1"></i> Terjadi Kendala Koneksi AI</h6>
            <p class="small text-muted mb-3">
                Mohon maaf, sambungan ke server AI sedang tidak stabil. Namun jangan khawatir, Anda tetap dapat menelusuri katalog jasa kami secara manual untuk menemukan teknisi yang sesuai.
            </p>
            <div class="d-flex gap-2">
                <a href="<?= url('customer/services') ?>" class="btn btn-primary btn-sm rounded-pill px-3 fw-semibold">
                    <i class="bi bi-search me-1"></i> Cari Jasa Secara Manual
                </a>
                <a href="<?= url('customer/orders/create') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    Buat Pesanan Langsung
                </a>
            </div>
        </div>
    `;

    chatMessages.insertBefore(errorWrapper, typingRow);
}

function escapeHtml(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>
