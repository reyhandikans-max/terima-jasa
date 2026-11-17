/**
 * Terima Jasa — Multi-Role AI Chatbot Widget
 * Mendukung Peran: Customer, Mitra Teknisi, Administrator, & Guest
 */
(function () {
    'use strict';

    // ── Role & User Context ────────────────────────────────────────
    const activeRole = (window.CURRENT_USER_ROLE || 'guest').toLowerCase();
    const activeUserName = window.CURRENT_USER_NAME || '';
    const activeUserId = parseInt(window.CURRENT_USER_ID || '0', 10);

    // ── Role Configuration Map ────────────────────────────────────
    const ROLE_CONFIGS = {
        teknisi: {
            title: 'AI Copilot Teknisi',
            roleLabel: 'Mitra Teknisi',
            headerClass: 'role-teknisi',
            avatarIcon: 'bi-tools',
            expandUrl: '/technician/ai-assistant',
            expandTitle: 'Buka Dashboard AI Teknisi',
            placeholder: 'Tanyakan kode error AC, kelistrikan, K3, atau template chat...',
            greeting: `Halo Rekan Teknisi ${activeUserName ? '<strong>' + activeUserName + '</strong>' : ''}! 🔧\n\nSaya **AI Copilot Mitra Terima Jasa**. Siap membantu Anda di lapangan:\n• ❄️ **Kode Error & Skema:** AC Inverter, Kulkas, Mesin Cuci\n• ⚡ **Kelistrikan:** MCB trip, grounding, short circuit\n• 🧰 **Estimasi:** Kebutuhan sparepart & biaya pengerjaan wajar\n• 🛡️ **SOP K3:** Prosedur keselamatan kerja kelistrikan & refrigeran\n• 💬 **Template Chat:** Pesan formal & sopan ke customer\n\nAda kendala unit yang ingin Anda konsultasikan?`,
            suggestions: [
                '❄️ AC Daikin error E3/U4',
                '⚡ SOP perbaikan MCB trip terus',
                '💬 Template chat konfirmasi kedatangan',
                '🧊 Kompresor kulkas dengung tapi mati',
                '📝 Template rincian sparepart ke customer',
                '🛡️ Checklist K3 pembongkaran outdoor'
            ]
        },
        admin: {
            title: 'Admin AI Copilot',
            roleLabel: 'Administrator',
            headerClass: 'role-admin',
            avatarIcon: 'bi-cpu-fill',
            expandUrl: '/admin/ai-assistant',
            expandTitle: 'Buka Admin Intelligence Hub',
            placeholder: 'Analisis metrik platform, SOP verifikasi teknisi, mediasi sengketa...',
            greeting: `Halo Administrator ${activeUserName ? '<strong>' + activeUserName + '</strong>' : ''}! ⚡\n\nSaya **Admin AI Copilot Terima Jasa**. Siap membantu supervisi operasional platform:\n• 📊 **Monitoring Platform:** Evaluasi order & performa teknisi\n• 🛡️ **Verifikasi Mitra:** SOP checklist KTP & sertifikasi\n• ⚖️ **Mediasi Sengketa:** Penyelesaian komplain & garansi 14 hari\n• 🏷️ **Strategi Tarif:** Benchmarking harga jasa pasar\n\nApa data atau keputusan operasional yang ingin dianalisis?`,
            suggestions: [
                '📊 Berapa total teknisi yang terdaftar di sistem?',
                '📈 Ringkasan total order & status pesanan',
                '🛡️ Checklist verifikasi berkas teknisi baru',
                '⚖️ SOP mediasi komplain keterlambatan order',
                '🏷️ Benchmarking tarif servis AC Jabodetabek',
                '📋 Draf surat peringatan SP-1 mitra'
            ]
        },
        customer: {
            title: 'Konsultan AI Terima Jasa',
            roleLabel: 'Customer',
            headerClass: 'role-customer',
            avatarIcon: 'bi-robot',
            expandUrl: '/customer/ai-assistant',
            expandTitle: 'Buka Halaman Diagnosa Lengkap',
            placeholder: 'Ceritakan kerusakan perangkat rumah atau tanyakan biaya servis...',
            greeting: `Halo Kak ${activeUserName ? '<strong>' + activeUserName + '</strong>' : ''}! 👋\n\nSaya **Asisten AI Terima Jasa**. Ada kendala perangkat di rumah?\n• 🔍 **Diagnosa Kerusakan:** AC, kulkas, mesin cuci, kelistrikan, & laptop\n• 💰 **Estimasi Biaya:** Kisaran tarif servis wajar & transparan\n• 👨‍🔧 **Cari Teknisi:** Rekomendasi teknisi terdekat & bergaransi\n• 🛡️ **Garansi Resmi:** Perlindungan servis 14 hari kalender\n\nCeritakan apa keluhan perangkat Anda!`,
            suggestions: [
                '🌬️ AC bocor & tidak dingin',
                '⚡ Listrik jepret terus / MCB trip',
                '💻 Laptop lemot & bluescreen',
                '🚰 Pipa air bocor & pompa mati',
                '🧊 Kulkas atas beku bawah tidak dingin',
                '💰 Estimasi biaya servis cuci AC'
            ]
        },
        guest: {
            title: 'AI Assistant Terima Jasa',
            roleLabel: 'Pengunjung',
            headerClass: '',
            avatarIcon: 'bi-robot',
            expandUrl: '/services',
            expandTitle: 'Jelajahi Katalog Jasa',
            placeholder: 'Tanyakan layanan teknisi, cara booking, atau estimasi tarif...',
            greeting: `Halo! 👋 Selamat datang di **Terima Jasa**.\n\nPlatform terpercaya untuk memesan teknisi profesional dan bergaransi resmi:\n• ❄️ Servis AC & Peralatan Pendingin\n• ⚡ Kelistrikan Rumah & Bangunan\n• 🚰 Plumbing, Pipa & Pompa Air\n• 💻 Servis Laptop & Komputer\n\nAda yang bisa kami bantu seputar pemesanan jasa?`,
            suggestions: [
                '🌬️ Berapa biaya cuci AC di Terima Jasa?',
                '⚡ Bagaimana cara memesan teknisi listrik?',
                '🛡️ Apakah ada garansi untuk servis?',
                '👨‍🔧 Bagaimana cara daftar mitra teknisi?',
                '📍 Wilayah mana saja yang dijangkau?'
            ]
        }
    };

    const currentConfig = ROLE_CONFIGS[activeRole] || ROLE_CONFIGS.guest;

    // ── State ──────────────────────────────────────────────────────
    const STATE = {
        open: false,
        loading: false,
        messages: [],          // { role: 'user'|'assistant', content: '' }
        hasGreeted: false,
    };

    // ── DOM Refs ───────────────────────────────────────────────────
    let toggleBtn, chatWindow, messagesBody, inputEl, sendBtn, badge, suggestionsEl, clearBtn;

    // ── Init ───────────────────────────────────────────────────────
    function init() {
        injectHTML();
        cacheRefs();
        bindEvents();
        showBadge();
    }

    function injectHTML() {
        // Hapus instance lama jika ada
        const oldToggle = document.getElementById('tj-chatbot-toggle');
        if (oldToggle) oldToggle.remove();
        const oldWin = document.getElementById('tj-chatbot-window');
        if (oldWin) oldWin.remove();

        const expandLink = getBaseUrl() + currentConfig.expandUrl;

        const chatHTML = `
        <!-- Floating Toggle Button -->
        <button id="tj-chatbot-toggle" aria-label="Buka Chatbot AI" title="${currentConfig.title}">
            <span id="tj-chatbot-badge" style="display:none">1</span>
            <i class="bi ${currentConfig.avatarIcon} tj-cb-icon-open"></i>
            <i class="bi bi-x-lg tj-cb-icon-close"></i>
        </button>

        <!-- Chat Window -->
        <div id="tj-chatbot-window" role="dialog" aria-label="${currentConfig.title}">
            <!-- Header -->
            <div class="tj-cb-header ${currentConfig.headerClass}">
                <div class="tj-cb-avatar">
                    <i class="bi ${currentConfig.avatarIcon}"></i>
                </div>
                <div class="tj-cb-header-info">
                    <h6>${currentConfig.title}</h6>
                    <div>
                        <span class="tj-cb-role-tag">${currentConfig.roleLabel}</span>
                        <small style="margin-left:4px;color:rgba(255,255,255,0.85);font-size:0.7rem;"><span style="color:#86efac;">●</span> Online</small>
                    </div>
                </div>
                <div class="tj-cb-actions">
                    <button class="tj-cb-action-btn" id="tj-chatbot-history" title="Riwayat Percakapan" aria-label="Riwayat Percakapan">
                        <i class="bi bi-clock-history"></i>
                    </button>
                    <button class="tj-cb-action-btn" id="tj-chatbot-clear" title="Bersihkan riwayat chat" aria-label="Bersihkan chat">
                        <i class="bi bi-trash3"></i>
                    </button>
                    <a class="tj-cb-action-btn" href="${expandLink}" title="${currentConfig.expandTitle}" aria-label="Buka Halaman Penuh">
                        <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                    <button class="tj-cb-action-btn" id="tj-chatbot-close" title="Tutup Chat" aria-label="Tutup chatbot">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Messages Body -->
            <div class="tj-cb-body" id="tj-chatbot-messages"></div>

            <!-- Quick Suggestions -->
            <div class="tj-cb-suggestions" id="tj-chatbot-suggestions"></div>

            <!-- Footer Input -->
            <div class="tj-cb-footer">
                <div class="tj-cb-input-row">
                    <textarea
                        id="tj-chatbot-input"
                        placeholder="${currentConfig.placeholder}"
                        rows="1"
                        maxlength="1000"
                        aria-label="Pesan chatbot"
                    ></textarea>
                    <button id="tj-chatbot-send" aria-label="Kirim pesan" title="Kirim">
                        <i class="bi bi-send-fill"></i>
                    </button>
                </div>
                <p class="tj-cb-branding">⚡ AI Assistant Terima Jasa · Terintegrasi Database Realtime</p>
            </div>
        </div>`;

        const wrapper = document.createElement('div');
        wrapper.id = 'tj-chatbot-root';
        wrapper.innerHTML = chatHTML;
        document.body.appendChild(wrapper);
    }

    function cacheRefs() {
        toggleBtn     = document.getElementById('tj-chatbot-toggle');
        chatWindow    = document.getElementById('tj-chatbot-window');
        messagesBody  = document.getElementById('tj-chatbot-messages');
        inputEl       = document.getElementById('tj-chatbot-input');
        sendBtn       = document.getElementById('tj-chatbot-send');
        badge         = document.getElementById('tj-chatbot-badge');
        suggestionsEl = document.getElementById('tj-chatbot-suggestions');
        clearBtn      = document.getElementById('tj-chatbot-clear');
    }

    function bindEvents() {
        toggleBtn.addEventListener('click', toggleChat);
        document.getElementById('tj-chatbot-close').addEventListener('click', closeChat);
        clearBtn.addEventListener('click', resetChat);
        
        const historyBtn = document.getElementById('tj-chatbot-history');
        if (historyBtn) {
            historyBtn.addEventListener('click', showConversationHistory);
        }

        sendBtn.addEventListener('click', handleSend);
        inputEl.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                handleSend();
            }
        });

        // Auto-resize textarea
        inputEl.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 90) + 'px';
        });
    }

    // ── Toggle / Open / Close ──────────────────────────────────────
    function toggleChat() {
        STATE.open ? closeChat() : openChat();
    }

    function openChat() {
        STATE.open = true;
        chatWindow.classList.add('open');
        toggleBtn.classList.add('active');
        hideBadge();
        if (!STATE.hasGreeted) greet();
        setTimeout(() => inputEl.focus(), 300);
    }

    function closeChat() {
        STATE.open = false;
        chatWindow.classList.remove('open');
        toggleBtn.classList.remove('active');
    }

    // ── Reset Chat ────────────────────────────────────────────────
    function resetChat() {
        messagesBody.innerHTML = '';
        STATE.messages = [];
        STATE.hasGreeted = false;
        greet();
    }

    // ── Badge ──────────────────────────────────────────────────────
    function showBadge() {
        setTimeout(() => { 
            if (badge) badge.style.display = 'flex'; 
        }, 2500);
    }
    function hideBadge() { 
        if (badge) badge.style.display = 'none'; 
    }

    // ── Greeting ──────────────────────────────────────────────────
    function greet() {
        STATE.hasGreeted = true;
        appendMessage('bot', currentConfig.greeting);
        renderSuggestions();
    }

    // ── Suggestions ────────────────────────────────────────────────
    function renderSuggestions() {
        suggestionsEl.innerHTML = '';
        currentConfig.suggestions.forEach(s => {
            const btn = document.createElement('button');
            btn.className = 'tj-suggest-btn';
            btn.textContent = s;
            btn.addEventListener('click', () => {
                inputEl.value = s;
                suggestionsEl.innerHTML = '';
                handleSend();
            });
            suggestionsEl.appendChild(btn);
        });
    }

    // ── Conversation History ──────────────────────────────────────
    async function showConversationHistory() {
        if (STATE.loading) return;
        const typingId = showTyping();

        try {
            const baseUrl = getBaseUrl();
            const res = await fetch(baseUrl + '/api/ai/conversations');
            removeTyping(typingId);

            const json = await res.json();
            const list = json.data || [];

            if (!list.length) {
                appendMessage('bot', 'Belum ada riwayat percakapan tersimpan untuk akun Anda. Silakan mulai bertanya!');
                return;
            }

            let historyHtml = '<strong>📋 Riwayat Percakapan Anda:</strong><br><small style="color:#64748b;">Klik pada judul untuk melihat kembali jawaban:</small><br><br>';
            list.slice(0, 6).forEach((item) => {
                historyHtml += `<div class="tj-history-item" data-id="${item.id}" style="padding:6px 10px;margin-bottom:6px;background:rgba(59,130,246,0.08);border-left:3px solid #3b82f6;border-radius:4px;cursor:pointer;transition:background 0.2s;">
                    <div style="font-weight:600;font-size:0.82rem;color:#1e293b;">💬 ${escapeHtml(item.title)}</div>
                    <div style="font-size:0.72rem;color:#64748b;margin-top:2px;">${escapeHtml(item.preview)}</div>
                    <small style="font-size:0.65rem;color:#94a3b8;">${escapeHtml(item.created_at || '')}</small>
                </div>`;
            });

            appendMessage('bot', historyHtml);

            // Bind click to load detail
            setTimeout(() => {
                const items = messagesBody.querySelectorAll('.tj-history-item');
                items.forEach(el => {
                    el.addEventListener('click', () => {
                        const id = el.getAttribute('data-id');
                        if (id) loadConversationDetail(id);
                    });
                });
            }, 100);

        } catch (err) {
            removeTyping(typingId);
            appendMessage('bot', '⚠️ Gagal memuat riwayat percakapan.');
        }
    }

    async function loadConversationDetail(id) {
        if (STATE.loading) return;
        const typingId = showTyping();

        try {
            const baseUrl = getBaseUrl();
            const res = await fetch(baseUrl + '/api/ai/conversations/' + id + '/messages');
            removeTyping(typingId);

            const json = await res.json();
            if (json.status === 'success' && json.data && json.data.messages) {
                messagesBody.innerHTML = '';
                json.data.messages.forEach(msg => {
                    appendMessage(msg.role === 'user' ? 'user' : 'bot', msg.content);
                });
            } else {
                appendMessage('bot', '⚠️ ' + (json.message || 'Percakapan tidak ditemukan.'));
            }
        } catch (err) {
            removeTyping(typingId);
            appendMessage('bot', '⚠️ Gagal memuat isi percakapan.');
        }
    }

    // ── Send Handler ───────────────────────────────────────────────
    function handleSend() {
        const text = inputEl.value.trim();
        if (!text || STATE.loading) return;

        suggestionsEl.innerHTML = '';
        appendMessage('user', text);
        STATE.messages.push({ role: 'user', content: text });

        inputEl.value = '';
        inputEl.style.height = 'auto';

        sendToAPI();
    }

    // ── API Call ───────────────────────────────────────────────────
    async function sendToAPI() {
        STATE.loading = true;
        sendBtn.disabled = true;

        const typingId = showTyping();

        // Kirim riwayat pesan terakhir
        const payload = { 
            messages: STATE.messages.slice(-8),
            role: activeRole,
            user_name: activeUserName,
            user_id: activeUserId
        };

        try {
            const baseUrl = getBaseUrl();
            // Coba endpoint standar /api/ai/chat terlebih dahulu, fallback ke /api/chatbot
            let endpoint = baseUrl + '/api/ai/chat';
            let res = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });

            if (!res.ok && res.status === 404) {
                res = await fetch(baseUrl + '/api/chatbot', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });
            }

            removeTyping(typingId);

            const text = await res.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (_) {
                console.error('[Chatbot Non-JSON]', text.substring(0, 300));
                appendMessage('bot', '⚠️ Terjadi respon tidak valid dari server.');
                return;
            }

            if (data.reply) {
                STATE.messages.push({ role: 'assistant', content: data.reply });
                appendMessage('bot', data.reply);
            } else {
                appendMessage('bot', '⚠️ Maaf, ' + (data.error || 'terjadi kendala pada asisten AI.'));
            }
        } catch (err) {
            removeTyping(typingId);
            appendMessage('bot', '⚠️ Koneksi ke server AI terganggu. Silakan coba sesaat lagi.');
            console.error('[Chatbot Error]', err);
        } finally {
            STATE.loading = false;
            sendBtn.disabled = false;
            inputEl.focus();
        }
    }

    // ── Typing Indicator ───────────────────────────────────────────
    function showTyping() {
        const id = 'tj-typing-' + Date.now();
        const el = document.createElement('div');
        el.className = 'tj-msg bot';
        el.id = id;
        el.innerHTML = `
            <div class="tj-msg-avatar"><i class="bi ${currentConfig.avatarIcon}"></i></div>
            <div class="tj-typing"><span></span><span></span><span></span></div>`;
        messagesBody.appendChild(el);
        scrollToBottom();
        return id;
    }

    function removeTyping(id) {
        const el = document.getElementById(id);
        if (el) el.remove();
    }

    // ── Append Message ─────────────────────────────────────────────
    function appendMessage(role, content) {
        const isBot = role === 'bot' || role === 'assistant';
        const wrapper = document.createElement('div');
        wrapper.className = 'tj-msg ' + (isBot ? 'bot' : 'user');

        const avatarIcon = isBot ? currentConfig.avatarIcon : 'bi-person-fill';
        const formatted  = formatContent(content);

        wrapper.innerHTML = `
            <div class="tj-msg-avatar"><i class="bi ${avatarIcon}"></i></div>
            <div class="tj-msg-bubble">${formatted}</div>`;

        messagesBody.appendChild(wrapper);
        scrollToBottom();
    }

    // ── Format markdown-lite ───────────────────────────────────────
    function formatContent(text) {
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
            let tableHtml = '<div style="overflow-x:auto;margin:8px 0;"><table style="width:100%;border-collapse:collapse;font-size:0.75rem;border:1px solid #e2e8f0;background:#fff;">';
            let isHead = true;
            rows.forEach((row) => {
                if (row.match(/^\|[\s\-:]+\|\s*$/)) {
                    isHead = false;
                    return;
                }
                const cells = row.split('|').slice(1, -1).map(c => c.trim());
                if (isHead) {
                    tableHtml += '<thead style="background:#f1f5f9;"><tr>' + cells.map(c => `<th style="padding:4px 6px;border:1px solid #cbd5e1;text-align:left;">${escapeHtml(c)}</th>`).join('') + '</tr></thead><tbody>';
                    isHead = false;
                } else {
                    tableHtml += '<tr>' + cells.map(c => `<td style="padding:4px 6px;border:1px solid #e2e8f0;">${escapeHtml(c)}</td>`).join('') + '</tr>';
                }
            });
            tableHtml += '</tbody></table></div>';
            tables.push(tableHtml);
            return `__TABLE_BLOCK_${tables.length - 1}__`;
        });

        let safe = escapeHtml(src);

        // Blockquotes > text
        safe = safe.replace(/^&gt;\s?(.*$)/gim, '<blockquote style="border-left:3px solid #3b82f6;margin:6px 0;padding:4px 8px;background:rgba(0,0,0,0.03);font-style:italic;">$1</blockquote>');
        // Headers
        safe = safe.replace(/^### (.*$)/gim, '<h6 style="font-weight:700;margin:6px 0 2px 0;font-size:0.85rem;">$1</h6>');
        safe = safe.replace(/^## (.*$)/gim, '<h5 style="font-weight:700;margin:8px 0 3px 0;font-size:0.9rem;">$1</h5>');
        safe = safe.replace(/^# (.*$)/gim, '<h5 style="font-weight:700;margin:8px 0 3px 0;font-size:0.95rem;">$1</h5>');
        // Bold **text**
        safe = safe.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        // Italic *text*
        safe = safe.replace(/\*([^\*]+)\*/g, '<em>$1</em>');
        // Inline code `code`
        safe = safe.replace(/`([^`]+)`/g, '<code style="background:rgba(0,0,0,0.06);padding:2px 4px;border-radius:3px;font-size:0.8em;color:#e11d48;">$1</code>');
        // Bullet • or -
        safe = safe.replace(/^[•\-\*]\s+(.+)/gm, '<span style="display:block;padding-left:10px;margin-bottom:2px">• $1</span>');
        // Horizontal rule
        safe = safe.replace(/^---$/gm, '<hr style="margin:6px 0;border:0;border-top:1px solid rgba(0,0,0,0.1);">');
        // Newlines
        safe = safe.replace(/\n/g, '<br>');

        // Restore tables
        tables.forEach((tbl, i) => {
            safe = safe.replace(`__TABLE_BLOCK_${i}__`, tbl);
        });

        // Restore code blocks
        codeBlocks.forEach((code, i) => {
            safe = safe.replace(`__CODE_BLOCK_${i}__`, `<pre style="background:#1e293b;color:#f8fafc;padding:8px;border-radius:6px;font-size:0.75rem;overflow-x:auto;margin:6px 0;"><code>${escapeHtml(code.trim())}</code></pre>`);
        });

        return safe;
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    // ── Scroll ─────────────────────────────────────────────────────
    function scrollToBottom() {
        requestAnimationFrame(() => {
            messagesBody.scrollTop = messagesBody.scrollHeight;
        });
    }

    // ── Base URL helper ────────────────────────────────────────────
    function getBaseUrl() {
        if (window.APP_BASE_URL) {
            return window.APP_BASE_URL;
        }
        const { protocol, hostname, port } = window.location;
        let base = protocol + '//' + hostname;
        if (port && port !== '80' && port !== '443') base += ':' + port;
        const pathParts = window.location.pathname.split('/');
        if (pathParts[1] && pathParts[1] !== '') base += '/' + pathParts[1];
        return base;
    }

    // Expose helpers globally
    window.openChatbotWidget = openChat;
    window.closeChatbotWidget = closeChat;
    window.toggleChatbotWidget = toggleChat;

    // ── Boot ───────────────────────────────────────────────────────
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
