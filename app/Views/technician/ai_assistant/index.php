<!-- ============================================================
     AI COPILOT MITRA TEKNISI — Terima Jasa
     Route: /technician/ai-assistant
     ============================================================ -->

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm overflow-hidden" style="background: linear-gradient(135deg, #064e3b 0%, #065f46 50%, #047857 100%);">
            <div class="card-body p-4 p-lg-5 text-white position-relative">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white bg-opacity-20 text-white mb-3" style="font-size: .85rem;">
                            <span class="spinner-grow spinner-grow-sm text-warning" role="status"></span>
                            <span>AI Copilot Ahli Servis & K3 Aktif</span>
                        </div>
                        <h2 class="fw-bold mb-2">Asisten AI Khusus Mitra Teknisi</h2>
                        <p class="text-white-50 mb-0 fs-6" style="max-width: 650px;">
                            Dapatkan panduan diagnosa kode error instan, estimasi sparepart & biaya wajar, prosedur keselamatan kerja (K3), serta template pesan sopan ke customer untuk memudahkan pekerjaan Anda di lapangan.
                        </p>
                    </div>
                    <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                        <div class="d-inline-flex flex-column align-items-lg-end bg-black bg-opacity-25 p-3 rounded-4 border border-white border-opacity-10">
                            <span class="text-white-50 small mb-1">Status Ketersediaan Anda</span>
                            <span class="badge bg-success px-3 py-2 fs-6 rounded-pill">
                                <i class="bi bi-circle-fill me-1" style="font-size:.6rem;"></i> <?= ucfirst($profile->availability ?? 'Available') ?>
                            </span>
                            <small class="text-white-50 mt-2"><?= e($profile->expertise ?? 'Teknisi Servis Berpengalaman') ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- LEFT COLUMN: Interactive Chat Hub -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden d-flex flex-column" style="min-height: 600px;">
            <!-- Header Chat -->
            <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 44px; height: 44px; background: linear-gradient(135deg, #059669, #10b981);">
                        <i class="bi bi-tools fs-5"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold">Live AI Troubleshooting Hub</h6>
                        <small class="text-muted"><span class="text-success">●</span> Model: <?= e(env('AI_MODEL', 'llama-3.3-70b-versatile')) ?> (Didukung Groq AI)</small>
                    </div>
                </div>
                <div>
                    <form action="<?= url('technician/ai-assistant/clear') ?>" method="POST" onsubmit="return confirm('Bersihkan riwayat chat sesi ini?');">
                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                            <i class="bi bi-trash3 me-1"></i> Bersihkan Chat
                        </button>
                    </form>
                </div>
            </div>

            <!-- Quick Suggestion Chips -->
            <div class="p-3 bg-light border-bottom overflow-auto text-nowrap" id="techPromptChips">
                <span class="text-muted small fw-semibold me-2"><i class="bi bi-lightning-charge text-warning"></i> Prompt Cepat:</span>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill me-1 tj-tech-chip" data-prompt="AC Daikin inverter muncul error E3 dan outdoor mati setelah 5 menit. Tolong berikan langkah troubleshooting teknis dan komponen yang harus diukur.">
                    ❄️ Daikin Error E3
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill me-1 tj-tech-chip" data-prompt="MCB 10A di rumah pelanggan trip terus saat AC dan pompa air menyala bersamaan. Bagaimana cara analisis beban dan uji grounding?">
                    ⚡ MCB Sering Trip
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill me-1 tj-tech-chip" data-prompt="Buatkan template pesan WA sopan ke customer mengabarkan bahwa kompresor kulkas macet dan perlu diganti relay PTC serta kapasitor starting.">
                    💬 Template Chat Sparepart
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill me-1 tj-tech-chip" data-prompt="Kulkas 2 pintu bagian freezer beku tebal namun kulkas bawah tidak dingin sama sekali. Apa saja komponen defros yang perlu dicek resistansinya?">
                    🧊 Defrost Kulkas Rusak
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill me-1 tj-tech-chip" data-prompt="Jelaskan SOP Keselamatan Kerja (K3) saat membongkar unit outdoor AC di ketinggian lantai 2 dan pengisian freon R32.">
                    🛡️ SOP K3 Ketinggian & Freon
                </button>
            </div>

            <!-- Messages Stream Area -->
            <div class="card-body p-4 overflow-y-auto flex-grow-1" id="techChatMessages" style="max-height: 480px; background: #f8fafc;">
                <!-- Initial Welcome Message -->
                <div class="d-flex gap-3 mb-4 tj-msg-bot">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width: 38px; height: 38px; background: #059669;">
                        <i class="bi bi-robot"></i>
                    </div>
                    <div class="bg-white p-3 rounded-4 border shadow-sm text-dark" style="max-width: 85%;">
                        <div class="fw-bold text-success mb-1 small">AI Copilot Mitra Teknisi</div>
                        <p class="mb-2">
                            Halo Rekan Teknisi <strong><?= e($currentUser['name'] ?? 'Mitra') ?></strong>! 🔧<br>
                            Saya siap menjadi rekan analisa teknis Anda di lapangan. Silakan tanyakan kode error peralatan, skema kelistrikan, estimasi sparepart, atau minta dibuatkan template pesan formal ke pelanggan.
                        </p>
                        <small class="text-muted">Pilih prompt cepat di atas atau ketikkan pertanyaan teknis Anda di bawah.</small>
                    </div>
                </div>

                <!-- Existing Session History -->
                <?php if (!empty($chatHistory)): ?>
                    <?php foreach ($chatHistory as $item): ?>
                        <!-- User Bubble -->
                        <div class="d-flex justify-content-end mb-3">
                            <div class="bg-success text-white p-3 rounded-4 shadow-sm" style="max-width: 82%;">
                                <div class="small text-white-50 mb-1 text-end">Anda (Teknisi)</div>
                                <div><?= nl2br(e($item['question'])) ?></div>
                            </div>
                        </div>

                        <!-- Bot Bubble -->
                        <div class="d-flex gap-3 mb-4">
                            <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width: 38px; height: 38px; background: #059669;">
                                <i class="bi bi-robot"></i>
                            </div>
                            <div class="bg-white p-3 rounded-4 border shadow-sm text-dark position-relative" style="max-width: 85%;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold text-success small">AI Copilot</span>
                                    <button class="btn btn-sm btn-link text-muted p-0 tj-copy-btn" title="Salin Jawaban" onclick="copyText(this)">
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
                <div id="techTypingIndicator" class="d-none gap-3 mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width: 38px; height: 38px; background: #059669;">
                        <i class="bi bi-robot"></i>
                    </div>
                    <div class="bg-white p-3 rounded-4 border shadow-sm d-inline-flex align-items-center gap-2">
                        <span class="spinner-border spinner-border-sm text-success" role="status"></span>
                        <span class="text-muted small">AI sedang menganalisis solusi teknis...</span>
                    </div>
                </div>
            </div>

            <!-- Footer Chat Input -->
            <div class="card-footer bg-white border-top p-3">
                <form id="techChatForm">
                    <div class="input-group">
                        <textarea id="techQuestionInput" class="form-control rounded-start-4 border" rows="1" placeholder="Ketik kode error atau kendala teknis (contoh: AC Daikin error U4, kulkas kompresor berdengung)..." style="resize:none;"></textarea>
                        <button type="submit" id="techSendBtn" class="btn btn-success px-4 rounded-end-4 fw-semibold">
                            <i class="bi bi-send-fill me-1"></i> Kirim
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN: Quick Toolkits & References -->
    <div class="col-lg-4">
        <div class="d-flex flex-column gap-4">
            
            <!-- Quick Toolkit 1: Kamus Kode Error Terpopuler -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom p-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-journal-code text-success me-2"></i>Kamus Cepat Kode Error AC</h6>
                </div>
                <div class="card-body p-3">
                    <div class="accordion accordion-flush" id="errorCodeAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fw-semibold text-dark py-2" type="button" data-bs-toggle="collapse" data-bs-target="#errDaikin">
                                    Daikin Inverter (E3, U4, L5)
                                </button>
                            </h2>
                            <div id="errDaikin" class="accordion-collapse collapse" data-bs-parent="#errorCodeAccordion">
                                <div class="accordion-body small text-muted">
                                    <strong>E3:</strong> High pressure switch trip (kondensor kotor / fan outdoor mati).<br>
                                    <strong>U4:</strong> Kabel komunikasi interkoneksi no. 1-2-3 longgar/putus.<br>
                                    <strong>L5:</strong> Kompresor overcurrent / modul inverter PCB rusak.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fw-semibold text-dark py-2" type="button" data-bs-toggle="collapse" data-bs-target="#errPanasonic">
                                    Panasonic (H11, H14, F91)
                                </button>
                            </h2>
                            <div id="errPanasonic" class="accordion-collapse collapse" data-bs-parent="#errorCodeAccordion">
                                <div class="accordion-body small text-muted">
                                    <strong>H11:</strong> Gagal sinkronisasi sinyal indoor-outdoor.<br>
                                    <strong>H14:</strong> Sensor suhu intake indoor putus/short.<br>
                                    <strong>F91:</strong> Kebocoran refrigeran / tekanan siklus freon abnormal.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fw-semibold text-dark py-2" type="button" data-bs-toggle="collapse" data-bs-target="#errSharp">
                                    Sharp & LG (E1, E2, CH05)
                                </button>
                            </h2>
                            <div id="errSharp" class="accordion-collapse collapse" data-bs-parent="#errorCodeAccordion">
                                <div class="accordion-body small text-muted">
                                    <strong>Sharp E1:</strong> Tekanan kompresor abnormal.<br>
                                    <strong>LG CH05:</strong> Komunikasi serial PCB indoor & outdoor terganggu.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Toolkit 2: Standar Keselamatan Kerja (K3) -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom p-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-shield-check text-primary me-2"></i>Panduan Wajib K3 Teknisi</h6>
                </div>
                <div class="card-body p-3">
                    <ul class="list-unstyled mb-0 small text-secondary d-flex flex-column gap-2">
                        <li class="d-flex align-items-start gap-2">
                            <i class="bi bi-check-circle-fill text-success mt-1"></i>
                            <span><strong>Uji Arus:</strong> Selalu cek tegangan dengan tespen & multitester sebelum memegang jalur kabel terbuka.</span>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <i class="bi bi-check-circle-fill text-success mt-1"></i>
                            <span><strong>Discharge Elco:</strong> Discas kapasitor utama power supply pada modul PCB AC inverter untuk mencegah sengatan sisa muatan DC.</span>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <i class="bi bi-check-circle-fill text-success mt-1"></i>
                            <span><strong>Alat Pelindung:</strong> Gunakan sepatu beralas karet tebal dan sarung tangan tahan listrik saat memeriksa panel listrik.</span>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <i class="bi bi-check-circle-fill text-success mt-1"></i>
                            <span><strong>Vakum Standar:</strong> Wajib gunakan pompa vakum minimal 15-20 menit hingga -30 inHg sebelum membuka kran freon baru.</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Quick Toolkit 3: Pekerjaan Aktif Anda -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom p-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-inbox text-info me-2"></i>Pekerjaan Aktif Anda (<?= count($activeOrders) ?>)</h6>
                </div>
                <div class="card-body p-3">
                    <?php if (empty($activeOrders)): ?>
                        <div class="text-center py-3 text-muted small">
                            <i class="bi bi-check2-circle text-success fs-3 d-block mb-1"></i>
                            Tidak ada pekerjaan aktif yang sedang berjalan.
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($activeOrders as $ao): ?>
                                <div class="p-2 border rounded-3 bg-light d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold small">#ORD-<?= e($ao->id) ?> · <?= e($ao->service_name ?? 'Layanan Servis') ?></div>
                                        <small class="text-muted"><i class="bi bi-geo-alt"></i> <?= e($ao->address ?? '-') ?></small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-success tj-tech-chip" data-prompt="Saya sedang mengerjakan pesanan #ORD-<?= e($ao->id) ?> (<?= e($ao->service_name ?? 'Servis') ?>). Tolong berikan checklist pengecekan dan template laporan selesai ke customer.">
                                        Analisa AI
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const chatMessages = document.getElementById('techChatMessages');
    const questionInput = document.getElementById('techQuestionInput');
    const chatForm = document.getElementById('techChatForm');
    const sendBtn = document.getElementById('techSendBtn');
    const typingIndicator = document.getElementById('techTypingIndicator');

    // Auto scroll chat to bottom
    chatMessages.scrollTop = chatMessages.scrollHeight;

    // Handle Quick Prompt Chips
    document.querySelectorAll('.tj-tech-chip').forEach(btn => {
        btn.addEventListener('click', function () {
            const prompt = this.getAttribute('data-prompt');
            if (prompt) {
                questionInput.value = prompt;
                submitChat(prompt);
            }
        });
    });

    // Form Submit
    chatForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const text = questionInput.value.trim();
        if (!text) return;
        submitChat(text);
    });

    async function submitChat(question) {
        // Append user bubble to UI
        const userHtml = `
            <div class="d-flex justify-content-end mb-3">
                <div class="bg-success text-white p-3 rounded-4 shadow-sm" style="max-width: 82%;">
                    <div class="small text-white-50 mb-1 text-end">Anda (Teknisi)</div>
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
            const res = await fetch('<?= url('technician/ai-assistant/chat') ?>', {
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
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width: 38px; height: 38px; background: #059669;">
                            <i class="bi bi-robot"></i>
                        </div>
                        <div class="bg-white p-3 rounded-4 border shadow-sm text-dark position-relative" style="max-width: 85%;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-success small">AI Copilot</span>
                                <button class="btn btn-sm btn-link text-muted p-0 tj-copy-btn" title="Salin Jawaban" onclick="copyText(this)">
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

    window.copyText = function (btn) {
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
