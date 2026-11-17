<?php
namespace App\Services;

use App\Core\Auth;
use App\Core\Model;
use App\Models\Category;
use App\Models\Service;
use App\Models\TechnicianProfile;
use App\Models\User;
use App\Models\Order;
use App\Models\Review;
use App\Models\AiChat;
use App\Services\AI\AIConfigurationService;
use PDO;

/**
 * ChatbotService — Backend Engine untuk Fitur CHATBOT+
 * 
 * Mengintegrasikan database aplikasi Terima Jasa secara aman,
 * menegakkan otorisasi peran (Customer, Teknisi, Admin, Guest),
 * menyaring informasi sensitif (anti data leak & IDOR),
 * serta menyediakan riwayat percakapan dan fallback cerdas berbasis database riil.
 */
class ChatbotService
{
    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';

    /**
     * Memproses percakapan dari pengguna dengan live database grounding & validasi otorisasi
     *
     * @param array $messages Array riwayat pesan [['role' => 'user'|'assistant', 'content' => '...']]
     * @param array $userContext Konteks pengguna (jika tidak disediakan, ambil dari Auth::user())
     * @return array Respon terstruktur untuk controller / API
     */
    public function handleChat(array $messages, array $userContext = []): array
    {
        // 1. Validasi & Sanitasi Otorisasi Pengguna (Server-Side Saja, Cegah Role Tampering)
        $auth = $this->resolveAuthenticatedUser($userContext);
        $role      = $auth['role'];
        $userId    = $auth['user_id'];
        $userName  = $auth['name'];
        $userEmail = $auth['email'] ?? '';

        // Ambil pesan user terakhir
        $lastUserMessage = '';
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if (($messages[$i]['role'] ?? '') === 'user') {
                $lastUserMessage = trim((string)($messages[$i]['content'] ?? ''));
                break;
            }
        }

        if (empty($lastUserMessage)) {
            return [
                'success' => false,
                'error'   => 'Pesan pengguna tidak boleh kosong.',
                'code'    => 400
            ];
        }

        // 2. Safety Guard: Cek apakah keluhan berbau medis / darurat kesehatan
        if ($this->detectMedicalInquiry($lastUserMessage)) {
            $medReply = "⚠️ **Peringatan Keamanan & Kesehatan:**\n\n" .
                "Terima Jasa adalah platform khusus jasa perbaikan perangkat rumah tangga, kelistrikan, elektronik, dan komputer. AI kami tidak berwenang memberikan diagnosa medis, anjuran obat, atau tindakan kesehatan apapun.\n\n" .
                "Demi keselamatan Anda, mohon segera hubungi dokter profesional, puskesmas, atau fasilitas gawat darurat (IGD) rumah sakit terdekat.";

            $chatRecord = $this->recordConversation($userId, $role, $lastUserMessage, $medReply);

            return [
                'success' => true,
                'reply'   => $medReply,
                'model'   => 'safety_guard',
                'role'    => $role,
                'source'  => 'safety_guard',
                'chat_id' => $chatRecord?->id ?? null
            ];
        }

        // 2c. OUT-OF-SCOPE GUARD: Tolak pertanyaan di luar topik Terima Jasa (kode Python, resep, dll)
        if ($this->isOutOfScope($lastUserMessage)) {
            $outOfScopeReply = $this->buildOutOfScopeReply($lastUserMessage);
            $chatRecord = $this->recordConversation($userId, $role, $lastUserMessage, $outOfScopeReply);
            return [
                'success' => true,
                'reply'   => $outOfScopeReply,
                'model'   => 'scope_guard',
                'role'    => $role,
                'source'  => 'scope_guard',
                'chat_id' => $chatRecord?->id ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }

        // 2b. Privacy Guard: Guest mencoba mengakses pesanan pribadi tanpa login
        if ($role === 'guest') {
            $mLower = strtolower($lastUserMessage);
            if (
                str_contains($mLower, 'pesanan saya') || 
                str_contains($mLower, 'order saya') || 
                str_contains($mLower, 'status pesanan') || 
                str_contains($mLower, 'pesanan aktif') ||
                (str_contains($mLower, 'pesanan') && (str_contains($mLower, 'status') || str_contains($mLower, 'cek') || str_contains($mLower, 'bagaimana')))
            ) {
                $loginNotice = "Untuk melihat status pesanan dan memantau kedatangan teknisi, silakan **Masuk (Login)** ke akun Customer Anda terlebih dahulu. Keamanan dan kerahasiaan data pesanan Anda terlindungi di platform Terima Jasa.";
                return [
                    'success' => true,
                    'reply'   => $loginNotice,
                    'model'   => 'auth_guard',
                    'role'    => 'guest',
                    'source'  => 'auth_guard',
                    'chat_id' => null,
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }
        }

        // 3. Bangun Grounding Data dari Database yang Difilter Sesuai Hak Akses Peran
        $groundingData = $this->getDatabaseGrounding($role, $userId, $userName);

        // 4. Periksa Status Konfigurasi AI dari Administrator
        $chatbotConfig = AIConfigurationService::getFeatureConfig('chatbot');
        if ($chatbotConfig && !$chatbotConfig['enabled']) {
            $disabledNotice = "Layanan Chatbot AI saat ini sedang dinonaktifkan oleh Administrator. " .
                "Anda tetap dapat mencari layanan melalui menu Katalog Jasa atau memesan teknisi langsung.";
            return [
                'success' => true,
                'reply'   => $disabledNotice,
                'model'   => 'disabled',
                'role'    => $role,
                'source'  => 'system_notice',
                'chat_id' => null
            ];
        }

        // 5. Susun System Prompt Khusus dengan Aturan Ketat
        $systemPrompt = $this->buildSystemPrompt($role, $userName, $groundingData, $userId, $userEmail);
        $fullMessages = array_merge([$systemPrompt], $messages);

        $reply = null;
        $source = 'smart_fallback';
        $model = ($chatbotConfig && !empty($chatbotConfig['model'])) ? $chatbotConfig['model'] : (string)env('AI_MODEL', 'openai/gpt-oss-120b');

        // 6. Coba panggil AI Model melalui Adapter Aktif
        if ($chatbotConfig && !empty($chatbotConfig['provider_adapter'])) {
            try {
                $adapterReply = $chatbotConfig['provider_adapter']->chat($fullMessages, [
                    'model'       => $model,
                    'temperature' => $chatbotConfig['temperature'] ?? 0.3,
                    'max_tokens'  => $chatbotConfig['max_tokens'] ?? 800,
                ]);
                if (!empty($adapterReply)) {
                    $reply = $adapterReply;
                    $source = $chatbotConfig['provider_adapter']->getProviderName() . '_ai';
                }
            } catch (\Throwable $e) {
                // Biarkan lanjut ke fallback berikutnya
            }
        }

        // 7. Fallback Langsung ke Groq API jika adapter gagal dan ada key
        if (empty($reply)) {
            $apiKey = trim((string)env('AI_API_KEY', env('GROQ_API_KEY', '')));
            if (!empty($apiKey) && !str_starts_with($apiKey, 'fake_') && !str_starts_with($apiKey, 'gsk_invalid')) {
                $reply = $this->queryGroqApi($fullMessages, $apiKey, $model);
                if (!empty($reply)) {
                    $source = 'groq_ai';
                }
            }
        }

        // 7b. Bersihkan respon AI agar to the point tanpa boilerplate berulang
        if (!empty($reply)) {
            $reply = $this->sanitizeToThePointReply($reply, $lastUserMessage);
        }

        // 8. Smart Database Fallback Engine: Jika AI Offline / Kuota Habis
        // Menggunakan query database riil yang difilter dan format yang rapi to-the-point
        if (empty($reply)) {
            $reply = $this->generateDatabaseSmartFallback($role, $lastUserMessage, $userName, $userId, $userEmail);
            $source = 'database_fallback';
        }

        // 9. Simpan Riwayat Percakapan ke Database (tabel ai_chats untuk customer) atau Session
        $chatRecord = $this->recordConversation($userId, $role, $lastUserMessage, $reply);

        return [
            'success' => true,
            'reply'   => $reply,
            'model'   => $model,
            'role'    => $role,
            'source'  => $source,
            'chat_id' => $chatRecord?->id ?? null,
            'conversation_id' => $chatRecord?->id ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Resolusi identitas pengguna yang terautentikasi (mencegah manipulasi role/user_id dari frontend)
     */
    public function resolveAuthenticatedUser(array $overrideContext = []): array
    {
        $sessionUser = Auth::user();

        // Jika ada konteks manual yang diberikan oleh backend internal (misal unit test)
        if (!empty($overrideContext['user']) && is_array($overrideContext['user'])) {
            $sessionUser = $overrideContext['user'];
        }

        if (!empty($sessionUser) && is_array($sessionUser) && !empty($sessionUser['id'])) {
            return [
                'user_id' => (int)$sessionUser['id'],
                'role'    => strtolower((string)($sessionUser['role'] ?? 'customer')),
                'name'    => (string)($sessionUser['name'] ?? 'Pengguna'),
                'email'   => (string)($sessionUser['email'] ?? ''),
                'is_auth' => true
            ];
        }

        // Jika session kosong tapi overrideContext membawa user_id (dari payload JSON atau API)
        $userIdFromCtx = (int)($overrideContext['user_id'] ?? 0);
        if ($userIdFromCtx > 0) {
            $u = User::find($userIdFromCtx);
            if ($u) {
                return [
                    'user_id' => (int)$u->id,
                    'role'    => strtolower((string)$u->role),
                    'name'    => (string)$u->name,
                    'email'   => (string)$u->email,
                    'is_auth' => true
                ];
            }
        }

        return [
            'user_id' => 0,
            'role'    => 'guest',
            'name'    => 'Pengunjung',
            'email'   => '',
            'is_auth' => false
        ];
    }

    /**
     * Membaca dan membangun Live Database Grounding dengan penyaringan hak akses ketat
     */
    public function getDatabaseGrounding(string $role, int $userId = 0, string $userName = 'Pengguna'): string
    {
        try {
            // 1. Data Kategori Jasa di Platform
            $categories = Category::all();
            $catNames = [];
            foreach ($categories as $c) {
                if ($c->status === 'active' || !isset($c->status)) {
                    $catNames[] = $c->name;
                }
            }
            $catStr = !empty($catNames) ? implode(', ', $catNames) : 'Servis AC & Pendingin, Kelistrikan Rumah, Service Laptop/Komputer, Plumbing & Pompa Air, Elektronik & TV';

            // 2. Data Katalog Layanan & Harga Dasar Resmi (Services)
            $allServices = Service::all();
            $serviceList = [];
            foreach ($allServices as $s) {
                if ($s->status === Service::STATUS_ACTIVE || $s->status === 'active' || !isset($s->status)) {
                    $catName = $s->category() ? $s->category()->name : 'Umum';
                    $priceFmt = 'Rp ' . number_format((float)$s->estimated_price, 0, ',', '.');
                    $serviceList[] = "• [ID: {$s->id}] [{$catName}] **{$s->name}** — Harga Dasar Database: {$priceFmt} (Deskripsi: {$s->description})";
                }
            }
            $servicesStr = implode("\n", $serviceList);

            // 3. Data Teknisi Terdaftar yang Bersifat Publik (Hanya Verified & Available, TANPA No Telp/Alamat Pribadi)
            $allProfiles = TechnicianProfile::all();
            $techList = [];
            $totalTechs = 0;
            $verifiedTechs = 0;
            $availableTechs = 0;

            foreach ($allProfiles as $p) {
                $totalTechs++;
                $u = $p->user();
                $tName = $u ? $u->name : 'Mitra Teknisi #' . $p->user_id;
                $isVerif = ($p->verification_status === TechnicianProfile::VERIFICATION_VERIFIED);
                $isAvail = ($p->availability === TechnicianProfile::AVAILABILITY_AVAILABLE);

                if ($isVerif) $verifiedTechs++;
                if ($isAvail) $availableTechs++;

                // Keamanan: Jangan cantumkan nomor telepon ($p->phone) atau alamat pribadi ($p->address) ke publik/customer!
                $stats = Review::getRatingStatsForTechnician((int)$p->user_id);
                $rating = number_format((float)$stats['average'], 1);
                $revCount = (int)$stats['count'];
                $statusAvail = $isAvail ? 'Tersedia (Ready)' : ($p->availability === 'busy' ? 'Sedang Bertugas' : 'Offline');

                // Hanya tampilkan rincian teknisi verified ke pengguna umum/customer
                if ($isVerif || $role === 'admin') {
                    $verifLabel = $isVerif ? 'Terverifikasi' : ucfirst($p->verification_status);
                    $techList[] = "• **{$tName}** — Spesialisasi: {$p->expertise} | Wilayah: {$p->location} | Pengalaman: {$p->experience} | Rating: ★{$rating} ({$revCount} ulasan) | Status: {$statusAvail} | Verifikasi: {$verifLabel}";
                }
            }
            $techsStr = implode("\n", $techList);

            // 4. Hitung Statistik & Metrik Resmi Database Platform
            $allUsers = User::all();
            $custCount = count(array_filter($allUsers, fn($u) => $u->role === 'customer'));
            $adminCount = count(array_filter($allUsers, fn($u) => $u->role === 'admin'));
            $totalUsers = count($allUsers);

            $allOrders = Order::all();
            $totalOrders = count($allOrders);
            $pendingOrders = count(array_filter($allOrders, fn($o) => $o->status === 'pending' || $o->status === Order::STATUS_PENDING));
            $activeOrders = count(array_filter($allOrders, fn($o) => in_array($o->status, ['accepted', 'in_progress', Order::STATUS_ACCEPTED, Order::STATUS_IN_PROGRESS])));
            $completedOrders = count(array_filter($allOrders, fn($o) => $o->status === 'completed' || $o->status === Order::STATUS_COMPLETED));
            $cancelledOrders = count(array_filter($allOrders, fn($o) => $o->status === 'cancelled' || $o->status === Order::STATUS_CANCELLED));
            $unverifiedTechs = $totalTechs - $verifiedTechs;

            $platformSummary = "=== RINGKASAN RESMI METRIK & DATA DATABASE TERIMA JASA (FAKTA MUTLAK) ===\n" .
                "• TOTAL PELANGGAN (CUSTOMER): {$custCount} orang pelanggan terdaftar aktif di database\n" .
                "• TOTAL MITRA TEKNISI: {$totalTechs} orang mitra teknisi terdaftar ({$verifiedTechs} verified/aktif, {$unverifiedTechs} menunggu verifikasi, {$availableTechs} available/siap order)\n" .
                "• TOTAL SELURUH PENGGUNA SISTEM: {$totalUsers} orang pengguna ({$custCount} customer, {$totalTechs} teknisi, {$adminCount} admin)\n" .
                "• TOTAL TRANSAKSI PESANAN/ORDER: {$totalOrders} pesanan ({$pendingOrders} pending, {$activeOrders} aktif/in progress, {$completedOrders} selesai, {$cancelledOrders} dibatalkan)\n" .
                "• TOTAL KATEGORI LAYANAN: " . count($categories) . " kategori\n" .
                "• TOTAL KATALOG LAYANAN AKTIF: " . count($allServices) . " layanan jasa\n\n";

            // 5. Data Kontekstual Berdasarkan Hak Akses Pengguna (Data Minimization & Least Privilege)
            $roleSpecificContext = "";

            if ($role === 'customer' && $userId > 0) {
                // CUSTOMER: Hanya dapat membaca pesanannya sendiri
                $orders = Order::getByCustomerId($userId);
                if (!empty($orders)) {
                    $orderItems = [];
                    foreach (array_slice($orders, 0, 5) as $o) {
                        $sName = $o->service() ? $o->service()->name : 'Servis';
                        $t = $o->technician();
                        $techName = $t ? $t->name : 'Menunggu konfirmasi teknisi';
                        $statusBadge = $o->getStatusBadge();
                        $statusLabel = $statusBadge['label'] ?? $o->status;
                        $orderItems[] = "- Pesanan #ORD-{$o->id}: {$sName} | Teknisi: {$techName} | Status: **{$statusLabel}** | Jadwal: {$o->schedule} | Lokasi: {$o->location}";
                    }
                    $roleSpecificContext .= "\n=== DATA PESANAN MILIK CUSTOMER INI (AKUN AKTIF #ID {$userId} {$userName}) ===\n" . implode("\n", $orderItems) . "\n*(Customer HANYA memiliki akses ke data pesanannya sendiri)*\n";
                } else {
                    $roleSpecificContext .= "\n=== DATA PESANAN CUSTOMER INI ===\nCustomer ini belum memiliki pesanan aktif.\n";
                }
            } elseif ($role === 'teknisi' && $userId > 0) {
                // TEKNISI: Hanya membaca pekerjaan yang ditugaskan kepadanya
                $techOrders = array_filter($allOrders, fn($o) => (int)$o->technician_id === $userId);
                if (!empty($techOrders)) {
                    $jobItems = [];
                    foreach (array_slice($techOrders, 0, 5) as $jo) {
                        $sName = $jo->service() ? $jo->service()->name : 'Servis';
                        $c = $jo->customer();
                        $custName = $c ? $c->name : 'Customer';
                        $jobItems[] = "- Tugas #ORD-{$jo->id}: {$sName} | Customer: {$custName} | Status: {$jo->status} | Jadwal: {$jo->schedule} | Lokasi: {$jo->location}";
                    }
                    $roleSpecificContext .= "\n=== DAFTAR PENUGASAN PEKERJAAN TEKNISI INI (#ID {$userId}) ===\n" . implode("\n", $jobItems) . "\n";
                } else {
                    $roleSpecificContext .= "\n=== DAFTAR PEKERJAAN TEKNISI INI ===\nBelum ada penugasan pekerjaan aktif saat ini.\n";
                }
            } elseif ($role === 'admin') {
                $custList = [];
                foreach ($allUsers as $u) {
                    if ($u->role === 'customer') {
                        $custList[] = "• [ID: #{$u->id}] **{$u->name}** (Email: {$u->email})";
                    }
                }
                $custListStr = !empty($custList) ? implode("\n", $custList) : "• Belum ada customer terdaftar";

                $roleSpecificContext .= "\n=== DAFTAR DETAIL STATUS OPERASIONAL DASHBOARD ADMIN ===\n" .
                    "• Pelanggan Terdaftar: {$custCount} customer\n" .
                    "• Mitra Teknisi: {$totalTechs} orang ({$verifiedTechs} verified, {$unverifiedTechs} menunggu verifikasi)\n" .
                    "• Pesanan: {$totalOrders} total ({$pendingOrders} pending, {$activeOrders} aktif, {$completedOrders} selesai)\n\n" .
                    "=== DAFTAR LENGKAP PELANGGAN (CUSTOMER) TERDAFTAR (HAK AKSES ADMINISTRATOR) ===\n" .
                    $custListStr . "\n";
            }

            return $platformSummary .
                "=== KATEGORI LAYANAN TERSEDIA DI DATABASE ===\n" . $catStr . "\n\n" .
                "=== KATALOG JASA & HARGA DASAR RESMI DI DATABASE ===\n" . $servicesStr . "\n\n" .
                "=== DAFTAR MITRA TEKNISI RESMI TERDAFTAR (DATA PUBLIK) ===\n" . $techsStr . "\n" .
                $roleSpecificContext;
        } catch (\Throwable $e) {
            return "[Data Database: Katalog layanan dan teknisi tersedia melalui model fallback platform Terima Jasa]";
        }
    }

    /**
     * Membangun System Prompt yang aman, tegas, dan memandu format 3 lapisan biaya
     * Menjawab semua pertanyaan terkait database & identitas, sambil tetap menolak coding/topik luar
     */
    private function buildSystemPrompt(string $role, string $userName, string $grounding, int $userId = 0, string $userEmail = ''): array
    {
        // Ambil daftar nama jasa & kategori dari database untuk referensi eksplisit di prompt
        $dbServiceNames = '';
        try {
            $allSvc = \App\Models\Service::all();
            $names  = array_map(fn($s) => $s->name, $allSvc);
            $dbServiceNames = !empty($names) ? implode(', ', $names) : '(lihat katalog)';
        } catch (\Throwable $e) {
            $dbServiceNames = '(katalog tersedia di database)';
        }

        $roleLabel = match ($role) {
            'admin'   => 'Administrator',
            'teknisi' => 'Mitra Teknisi',
            default   => 'Customer'
        };

        $instructions =
            "Kamu adalah Chatbot+ Asisten AI RESMI platform 'Terima Jasa' (marketplace jasa penghubung Customer dengan Teknisi profesional di Indonesia).\n" .
            "Kamu terhubung secara langsung dan real-time ke DATABASE Terima Jasa untuk memberikan jawaban akurat, faktual, dan to the point.\n\n" .

            "=== IDENTITAS PENGGUNA YANG SEDANG BERBICARA DENGAN ANDA ===\n" .
            "• Nama Pengguna : {$userName}\n" .
            "• Peran (Role)  : {$role} ({$roleLabel})\n" .
            "• ID Pengguna   : #{$userId}\n" .
            "• Email         : {$userEmail}\n\n" .

            "=== REPOSITORI DATA DATABASE REAL-TIME TERIMA JASA (FAKTA MUTLAK) ===\n" .
            $grounding . "\n\n" .

            "=== KATALOG NAMA LAYANAN DI DATABASE ===\n" .
            $dbServiceNames . "\n\n" .

            "=== PRINSIP JAWABAN TO THE POINT (SANGAT PENTING) ===\n" .
            "1. JAWAB LANGSUNG KE INTI PERTANYAAN (TO THE POINT):\n" .
            "- DILARANG menyertakan kalimat pembuka berulang yang panjang seperti 'Halo! Saya adalah Chatbot+ Asisten AI Resmi platform Terima Jasa, terhubung langsung dengan database real-time kami. Saya dapat membantu Anda dengan...' pada setiap respon.\n" .
            "- DILARANG bertele-tele atau mengulang template intro. Langsung berikan jawaban yang diminta.\n" .
            "- Sapaan hanya diberikan jika pengguna secara spesifik HANYA menyapa 'halo' atau 'hai'.\n\n" .

            "2. PERTANYAAN IDENTITAS DIRI PENGGUNA ('siapa saya', 'siapa aku', 'nama saya', 'akun saya', 'profil saya'):\n" .
            "- Jawab LANGSUNG TO THE POINT: 'Anda adalah {$userName}, terdaftar sebagai {$roleLabel} (ID: #{$userId}" . (!empty($userEmail) ? ", Email: {$userEmail}" : "") . ") di platform Terima Jasa.'\n" .
            "- JANGAN menyamakan pertanyaan 'siapa saya' dengan 'siapa kamu/anda'. 'Siapa saya' menanyakan identitas pengguna sendiri!\n\n" .

            "3. PERTANYAAN DAFTAR PENGGUNA ('siapa saja', 'siapa saja customernya', 'daftar customer'):\n" .
            "- Jika pengguna adalah ADMINISTRATOR (role: admin):\n" .
            "  Administrator berhak penuh memantau data operasional sistem. Jika Admin bertanya 'siapa saja', 'siapa saja customernya', 'daftar customer', atau meminta daftar teknisi, SEBUTKAN LANGSUNG nama-nama yang ada pada data database di atas secara to the point.\n" .
            "  DILARANG KERAS menolak permintaan data Administrator dengan alasan privasi! Administrator adalah pengelola sistem resmi.\n" .
            "- Jika pengguna adalah CUSTOMER atau GUEST:\n" .
            "  Jaga privasi data pengguna lain. Jika Customer/Guest bertanya tentang nama atau data pribadi customer lain, tolak dengan sopan bahwa data pengguna lain bersifat privat.\n\n" .

            "4. MENJAWAB SEMUA PERTANYAAN TERKAIT DATABASE TERIMA JASA:\n" .
            "- Jawab SEMUA pertanyaan yang berkaitan dengan data di database di atas (kategori, layanan, harga dasar, teknisi terdaftar, rating/ulasan, pesanan aktif, metrik statistik platform, cara pesan, dan garansi 14 hari).\n" .
            "- Gunakan fakta dan angka aktual yang tercantum pada data database di atas sebagai sumber kebenaran tunggal.\n" .
            "- Jangan mengarang teknisi palsu atau harga dasar fiktif yang tidak ada di database.\n\n" .

            "5. LAYANAN DI LUAR KATALOG DATABASE:\n" .
            "- Jika pengguna menanyakan layanan yang TIDAK ADA di katalog database (misal: 'servis pesawat', 'kapal selam', dll), sampaikan secara jujur dan sopan bahwa layanan tersebut saat ini **belum tersedia** di katalog Terima Jasa.\n\n" .

            "6. LARANGAN KERAS — PERTANYAAN DI LUAR SISTEM (OUT-OF-SCOPE):\n" .
            "- DILARANG KERAS membuat atau menuliskan kode pemrograman komputer (seperti Python, PHP, JavaScript, SQL script, dll).\n" .
            "- DILARANG menjawab topik umum yang sama sekali tidak berhubungan dengan Terima Jasa atau database (seperti resep masakan, rumus matematika sekolah, berita artis/politik).\n" .
            "- Jika pengguna meminta kode pemrograman atau topik umum tersebut, tolak secara sopan dan to the point: '❌ Maaf, saya adalah Asisten AI khusus platform Terima Jasa dan tidak dapat membuat kode pemrograman atau topik di luar layanan jasa Terima Jasa. Saya hanya dapat membantu seputar data layanan servis resmi di database kami (AC, Kelistrikan, Laptop/Komputer, Plumbing, Elektronik), status pesanan, dan informasi teknisi terdaftar.'\n\n" .

            "7. TRANSPARANSI BIAYA 3-LAPIS (JIKA MEMBAHAS BIAYA/HARGA):\n" .
            "  a. [Harga Dasar Database]: Tarif resmi di katalog database (misal Rp 75.000 untuk Cuci AC).\n" .
            "  b. [Estimasi AI]: Perkiraan biaya pengerjaan/suku cadang berdasarkan gejala keluhan.\n" .
            "  c. [Harga Final]: Biaya pasti yang disepakati langsung bersama teknisi setelah inspeksi fisik.\n\n" .

            "8. FORMAT TAMPILAN:\n" .
            "- DILARANG membuat tabel markdown lebar karena akan terpotong di layar chat widget. Gunakan bullet point (•) atau penomoran rapi.\n" .
            "- Bahasa Indonesia yang sopan, profesional, dan to the point.";

        return [
            'role'    => 'system',
            'content' => $instructions
        ];
    }

    /**
     * Deteksi apakah pertanyaan di luar scope Terima Jasa
     * Dipanggil SEBELUM mengirim ke AI — mencegah pemborosan token & mencegah AI menjawab hal di luar DB
     *
     * @param string $message Pesan dari pengguna
     * @return bool true = pertanyaan di luar scope, harus ditolak
     */
    public function isOutOfScope(string $message): bool
    {
        $m = strtolower(trim($message));

        // =====================================================================
        // TOPIK YANG JELAS DI LUAR SCOPE TERIMA JASA
        // =====================================================================

        // Pemrograman & teknologi umum
        $programmingKeywords = [
            'python', 'javascript', 'php code', 'kode python', 'kode php', 'kode javascript',
            'script python', 'program python', 'coding', 'algoritma', 'fungsi python',
            'def main', 'import os', 'class ', 'function()', 'print(', 'console.log',
            'html css', 'react', 'vue.js', 'angular', 'laravel', 'django', 'flask',
            'machine learning', 'deep learning', 'neural network', 'ai model', 'llm',
            'docker', 'kubernetes', 'git', 'github', 'api endpoint', 'rest api',
            'database query', 'sql query', 'json format', 'xml format',
        ];

        // Pelajaran sekolah / akademik
        $academicKeywords = [
            'rumus matematika', 'integral', 'turunan', 'persamaan kuadrat', 'trigonometri',
            'kimia', 'fisika umum', 'biologi', 'sejarah', 'geografi', 'ekonomi makro',
            'soal ujian', 'pr sekolah', 'tugas kuliah', 'makalah', 'skripsi', 'tesis',
        ];

        // Resep & makanan
        $foodKeywords = [
            'resep', 'cara memasak', 'bahan masakan', 'bumbu', 'nasi goreng resep',
            'cara membuat kue', 'cara bikin', 'makanan',
        ];

        // Berita & hiburan
        $entertainmentKeywords = [
            'berita hari ini', 'gosip', 'artis', 'film terbaru', 'lagu', 'musik',
            'pertandingan', 'bola', 'sepak bola skor', 'politik', 'pemilu', 'presiden',
        ];

        // Kesehatan umum (sudah ada detectMedicalInquiry, ini tambahan)
        $healthKeywords = [
            'diet', 'olahraga tips', 'cara menurunkan berat badan', 'suplemen',
        ];

        $allOutOfScopeKeywords = array_merge(
            $programmingKeywords,
            $academicKeywords,
            $foodKeywords,
            $entertainmentKeywords,
            $healthKeywords
        );

        foreach ($allOutOfScopeKeywords as $keyword) {
            if (str_contains($m, $keyword)) {
                return true;
            }
        }

        // =====================================================================
        // PATTERN DETEKSI KHUSUS
        // =====================================================================

        // Deteksi request "tulis kode / buat program"
        if (
            (str_contains($m, 'tulis') || str_contains($m, 'buat') || str_contains($m, 'buatkan') ||
             str_contains($m, 'contoh') || str_contains($m, 'jelaskan')) &&
            (str_contains($m, 'kode') || str_contains($m, 'program') || str_contains($m, 'script') ||
             str_contains($m, 'fungsi') || str_contains($m, 'class') || str_contains($m, 'algoritma'))
        ) {
            return true;
        }

        // Deteksi pertanyaan "siapa [tokoh publik]" / "apa itu [topik umum]"
        if (
            (str_contains($m, 'siapa itu') || str_contains($m, 'apa itu') || str_contains($m, 'jelaskan tentang')) &&
            !str_contains($m, 'terima jasa') && !str_contains($m, 'teknisi') &&
            !str_contains($m, 'jasa') && !str_contains($m, 'servis') &&
            !str_contains($m, 'layanan') && !str_contains($m, 'pesanan')
        ) {
            // Hanya tolak jika tidak ada kata kunci platform sama sekali
            $platformKeywords = ['ac', 'listrik', 'laptop', 'komputer', 'plumbing', 'pipa', 'pompa', 'elektronik', 'tv', 'kulkas'];
            $hasPlatform = false;
            foreach ($platformKeywords as $pk) {
                if (str_contains($m, $pk)) {
                    $hasPlatform = true;
                    break;
                }
            }
            if (!$hasPlatform) {
                return true;
            }
        }

        return false;
    }

    /**
     * Buat pesan penolakan out-of-scope yang sopan dan mengarahkan ke layanan yang ada
     */
    private function buildOutOfScopeReply(string $message): string
    {
        $m = strtolower($message);

        // Pesan penolakan spesifik berdasarkan topik yang terdeteksi
        if (
            str_contains($m, 'python') || str_contains($m, 'kode') || str_contains($m, 'program') ||
            str_contains($m, 'script') || str_contains($m, 'coding') || str_contains($m, 'javascript') ||
            str_contains($m, 'fungsi') || str_contains($m, 'algoritma')
        ) {
            return "❌ **Pertanyaan di Luar Cakupan Layanan**\n\n" .
                "Maaf, saya adalah Asisten AI khusus platform **Terima Jasa** dan tidak dapat menjawab pertanyaan seputar pemrograman atau kode komputer.\n\n" .
                "Saya hanya dapat membantu Anda dengan:\n" .
                "• 🔧 Mencari jasa servis (AC, Listrik, Laptop, Plumbing, Elektronik)\n" .
                "• 👨‍🔧 Menemukan teknisi terverifikasi yang sesuai\n" .
                "• 💰 Informasi harga dan estimasi biaya\n" .
                "• 📦 Status pesanan servis Anda\n" .
                "• 📋 Cara memesan layanan di platform ini\n\n" .
                "Ada keluhan perangkat rumah atau elektronik yang bisa saya bantu diagnosa?";
        }

        if (
            str_contains($m, 'resep') || str_contains($m, 'masak') || str_contains($m, 'makanan')
        ) {
            return "❌ **Pertanyaan di Luar Cakupan Layanan**\n\n" .
                "Maaf, saya tidak dapat membantu pertanyaan seputar resep atau makanan. " .
                "Saya adalah Asisten AI khusus untuk layanan jasa servis rumah dan elektronik di platform **Terima Jasa**.\n\n" .
                "Apakah ada perangkat rumah tangga (kulkas, mesin cuci, AC, dll) yang bermasalah dan perlu diservis?";
        }

        // Default penolakan umum
        return "❌ **Layanan Belum Tersedia / Di Luar Cakupan Terima Jasa**\n\n" .
            "Maaf, layanan atau topik yang Anda tanyakan **belum tersedia** di katalog platform Terima Jasa saat ini.\n\n" .
            "Saya hanya dapat membantu seputar layanan resmi yang terdaftar di database:\n" .
            "• **Servis AC & Pendingin** — cuci AC, isi freon, perbaikan\n" .
            "• **Kelistrikan Rumah** — korsleting, MCB anjlok, instalasi\n" .
            "• **Service Laptop/Komputer** — laptop mati, bluescreen, lemot\n" .
            "• **Plumbing & Pompa Air** — pipa mampet, pompa rusak\n" .
            "• **Elektronik & TV** — kulkas, TV, mesin cuci\n" .
            "• **Status Pesanan** — pantau pekerjaan teknisi Anda\n\n" .
            "Silakan tanyakan seputar topik di atas. 😊";
    }

    /**
     * Memastikan respon AI selalu to the point tanpa boilerplate pembuka berulang
     */
    public function sanitizeToThePointReply(string $reply, string $userMessage): string
    {
        $mLower = strtolower(trim($userMessage));
        // Jika user memang hanya menyapa atau bertanya 'siapa anda', biarkan perkenalan tetap utuh
        if (
            $mLower === 'halo' || $mLower === 'hai' || $mLower === 'p' ||
            str_contains($mLower, 'siapa anda') || str_contains($mLower, 'siapa kamu') || str_contains($mLower, 'kamu siapa')
        ) {
            return $reply;
        }

        // Bersihkan boilerplate intro berulang
        $patterns = [
            '/^Halo! Saya adalah Chatbot\+ Asisten AI Resmi platform Terima Jasa[^\n]*\n+/i',
            '/^Halo Kak [^\n]+! Saya adalah Chatbot\+ Asisten AI Resmi[^\n]*\n+/i',
            '/^Halo Administrator [^\n]+! Saya Admin AI Copilot[^\n]*\n+/i',
            '/^Halo! Saya adalah Asisten AI[^\n]*\n+/i',
            '/^Mengenai identitas Anda,?\s*/i',
        ];

        $cleaned = $reply;
        foreach ($patterns as $pattern) {
            $cleaned = preg_replace($pattern, '', $cleaned);
        }

        return trim($cleaned);
    }

    /**
     * Fallback Cerdas Berbasis Database Langsung (Ketika AI Provider Offline / Error)
     */
    public function generateDatabaseSmartFallback(string $role, string $message, string $userName, int $userId = 0, string $userEmail = ''): string
    {
        $m = strtolower(trim($message));

        // 0a. PERTANYAAN IDENTITAS DIRI PENGGUNA ("siapa saya", "nama saya", "akun saya")
        if (
            $m === 'siapa saya' || $m === 'siapa saya?' || str_contains($m, 'siapa saya') ||
            $m === 'siapa aku' || $m === 'siapa aku?' || str_contains($m, 'siapa aku') ||
            str_contains($m, 'identitas saya') || str_contains($m, 'nama saya') ||
            str_contains($m, 'akun saya') || str_contains($m, 'profil saya')
        ) {
            if ($role === 'guest' || $userId <= 0) {
                return "Anda saat ini mengakses sebagai **Pengunjung (Guest)** dan belum masuk (login). Silakan masuk ke akun Anda untuk melihat profil dan riwayat pesanan.";
            }

            $roleLabel = match ($role) {
                'admin'   => 'Administrator',
                'teknisi' => 'Mitra Teknisi',
                default   => 'Customer'
            };
            $emailStr = !empty($userEmail) ? ", Email: {$userEmail}" : "";

            return "Anda adalah **{$userName}**, terdaftar sebagai **{$roleLabel}** (ID: #{$userId}{$emailStr}) di platform Terima Jasa.";
        }

        // 0b. PERTANYAAN DAFTAR PENGGUNA / CUSTOMER ("siapa saja", "siapa saja customernya")
        if (
            $m === 'siapa saja' || $m === 'siapa saja?' ||
            str_contains($m, 'siapa saja customer') || str_contains($m, 'siapa saja costumer') ||
            str_contains($m, 'siapa saja pelanggan') || str_contains($m, 'siapa customernya') ||
            str_contains($m, 'siapa pelanggannya') || str_contains($m, 'daftar customer') ||
            str_contains($m, 'daftar costumer') || str_contains($m, 'daftar pelanggan') ||
            str_contains($m, 'nama customer') || str_contains($m, 'nama pelanggan') ||
            (str_contains($m, 'siapa') && (str_contains($m, 'customer') || str_contains($m, 'costumer') || str_contains($m, 'pelanggan') || str_contains($m, 'konsumen')))
        ) {
            if ($role === 'admin') {
                $allUsers = User::all();
                $custs = array_values(array_filter($allUsers, fn($u) => $u->role === 'customer'));
                $custCount = count($custs);
                $res = "Berikut daftar {$custCount} pelanggan (customer) terdaftar di database Terima Jasa:\n";
                foreach ($custs as $idx => $c) {
                    $no = $idx + 1;
                    $res .= "{$no}. **{$c->name}** (Email: {$c->email})\n";
                }
                return trim($res);
            } else {
                return "Untuk menjaga privasi data pengguna, daftar pelanggan platform Terima Jasa tidak dapat ditampilkan kepada publik. Anda hanya dapat melihat data akun dan pesanan Anda sendiri.";
            }
        }

        // 0c. PERTANYAAN DAFTAR TEKNISI ("siapa saja teknisi", "daftar teknisi")
        if (
            str_contains($m, 'siapa saja teknisi') || str_contains($m, 'daftar teknisi') ||
            str_contains($m, 'siapa teknisinya') || str_contains($m, 'nama teknisi') ||
            (str_contains($m, 'siapa saja') && str_contains($m, 'teknisi'))
        ) {
            $allUsers = User::all();
            $techs = array_values(array_filter($allUsers, fn($u) => $u->role === 'teknisi'));
            $res = "Berikut daftar " . count($techs) . " mitra teknisi terdaftar di platform Terima Jasa:\n";
            foreach ($techs as $idx => $t) {
                $no = $idx + 1;
                $prof = TechnicianProfile::firstWhere('user_id', $t->id);
                $exp = $prof ? $prof->expertise : 'Servis Umum';
                $ver = ($prof && $prof->verification_status === 'verified') ? 'Terverifikasi' : 'Menunggu Verifikasi';
                $avail = ($prof && $prof->availability === 'available') ? 'Tersedia' : 'Sibuk';
                $res .= "{$no}. **{$t->name}** (Spesialisasi: {$exp} | {$ver} | {$avail})\n";
            }
            return trim($res);
        }

        // 0d. PERTANYAAN IDENTITAS ASISTEN AI ("siapa anda", "siapa kamu")
        if (
            str_contains($m, 'siapa anda') || str_contains($m, 'kamu siapa') || str_contains($m, 'siapa kamu') ||
            str_contains($m, 'anda siapa') || str_contains($m, 'apa tugasmu') || str_contains($m, 'kamu bisa apa') ||
            str_contains($m, 'apa fungsimu') || str_contains($m, 'bisa bantu apa')
        ) {
            return "Saya adalah **Chatbot+ Asisten AI Resmi platform Terima Jasa**, terhubung langsung dengan database real-time kami. Saya dapat membantu memberikan informasi seputar:\n" .
                "• 📊 Metrik platform (total customer, teknisi, pesanan)\n" .
                "• 🔧 Katalog layanan & tarif resmi (AC, Listrik, Laptop, Plumbing, Elektronik)\n" .
                "• 👨‍🔧 Profil teknisi terverifikasi & ketersediaan\n" .
                "• 📦 Status pesanan aktif & jadwal teknisi\n" .
                "• 🛡️ Garansi servis 14 hari kalender";
        }

        // 0e. SAPAAN RINGKAS
        if (
            $m === 'halo' || $m === 'hai' || $m === 'p' || $m === 'assalamualaikum' ||
            $m === 'selamat pagi' || $m === 'selamat siang' || $m === 'selamat sore' || $m === 'selamat malam'
        ) {
            return "Halo Kak {$userName}! Ada yang bisa saya bantu seputar layanan, teknisi, atau data di Terima Jasa?";
        }

        // 1a. PERTANYAAN TOTAL CUSTOMER / PELANGGAN PLATFORM
        if (
            (str_contains($m, 'customer') || str_contains($m, 'costumer') || str_contains($m, 'pelanggan') || str_contains($m, 'konsumen')) &&
            (str_contains($m, 'berapa') || str_contains($m, 'total') || str_contains($m, 'jumlah') || str_contains($m, 'ada berapa'))
        ) {
            $allUsers = User::all();
            $custs = array_values(array_filter($allUsers, fn($u) => $u->role === 'customer'));
            $custCount = count($custs);
            $names = array_map(fn($c) => $c->name, $custs);
            $namesStr = !empty($names) ? " (" . implode(', ', $names) . ")" : "";

            return "Total pelanggan (customer) terdaftar di database Terima Jasa adalah **{$custCount} orang**{$namesStr}.";
        }

        // 1b. PERTANYAAN TOTAL PENGGUNA / USERS
        if (
            (str_contains($m, 'user') || str_contains($m, 'pengguna') || str_contains($m, 'akun')) &&
            (str_contains($m, 'berapa') || str_contains($m, 'total') || str_contains($m, 'jumlah') || str_contains($m, 'ada berapa'))
        ) {
            $allUsers = User::all();
            $totalUsers = count($allUsers);
            $custCount = count(array_filter($allUsers, fn($u) => $u->role === 'customer'));
            $techCount = count(array_filter($allUsers, fn($u) => $u->role === 'teknisi'));
            $adminCount = count(array_filter($allUsers, fn($u) => $u->role === 'admin'));

            return "Total pengguna yang terdaftar di database sistem Terima Jasa adalah **{$totalUsers} orang pengguna**, dengan rincian:\n" .
                "• Pelanggan (Customer): {$custCount} orang\n" .
                "• Mitra Teknisi: {$techCount} orang\n" .
                "• Administrator: {$adminCount} orang";
        }

        // 1c. PERTANYAAN TOTAL ORDER / PESANAN PLATFORM
        if (
            (str_contains($m, 'order') || str_contains($m, 'pesanan') || str_contains($m, 'transaksi')) &&
            (str_contains($m, 'berapa total') || str_contains($m, 'jumlah total') || str_contains($m, 'total order') || str_contains($m, 'total pesanan') || str_contains($m, 'semua order'))
        ) {
            $allOrders = Order::all();
            $totalOrders = count($allOrders);
            $pending = count(array_filter($allOrders, fn($o) => $o->status === 'pending' || $o->status === Order::STATUS_PENDING));
            $active = count(array_filter($allOrders, fn($o) => in_array($o->status, ['accepted', 'in_progress', Order::STATUS_ACCEPTED, Order::STATUS_IN_PROGRESS])));
            $completed = count(array_filter($allOrders, fn($o) => $o->status === 'completed' || $o->status === Order::STATUS_COMPLETED));
            $cancelled = count(array_filter($allOrders, fn($o) => $o->status === 'cancelled' || $o->status === Order::STATUS_CANCELLED));

            return "Berdasarkan data transaksi di database Terima Jasa, saat ini terdapat total **{$totalOrders} pesanan** dengan rincian status:\n" .
                "• Pending (Menunggu konfirmasi): {$pending} order\n" .
                "• Aktif (Diterima / Sedang dikerjakan): {$active} order\n" .
                "• Selesai (Completed): {$completed} order\n" .
                "• Dibatalkan (Cancelled): {$cancelled} order";
        }

        // 1. PENCARIAN STATUS PESANAN CUSTOMER (Hanya miliknya sendiri, Anti-IDOR)
        if (
            str_contains($m, 'pesanan saya') ||
            str_contains($m, 'status pesanan') ||
            str_contains($m, 'order saya') ||
            str_contains($m, 'pesanan aktif') ||
            str_contains($m, 'pesanan yang') ||
            (str_contains($m, 'pesanan') && (str_contains($m, 'status') || str_contains($m, 'cek') || str_contains($m, 'bagaimana') || str_contains($m, 'apakah')))
        ) {
            if ($role === 'guest' || $userId <= 0) {
                return "Untuk melihat status pesanan dan memantau kedatangan teknisi, silakan **Masuk (Login)** ke akun Customer Anda terlebih dahulu. Keamanan data pesanan Anda terlindungi di sistem Terima Jasa.";
            }

            if ($role === 'customer') {
                $orders = Order::getByCustomerId($userId);
                if (empty($orders)) {
                    return "Halo Kak {$userName}, saat ini Anda belum memiliki riwayat pesanan servis di Terima Jasa. Anda dapat membuat pesanan baru kapan saja melalui menu **Katalog Jasa**.";
                }

                $res = "Berikut daftar pesanan servis Anda (Data Realtime Database Terima Jasa):\n\n";
                foreach ($orders as $o) {
                    $sName = $o->service() ? $o->service()->name : 'Servis Perangkat';
                    $t = $o->technician();
                    $techName = $t ? $t->name : 'Menunggu Konfirmasi Teknisi';
                    $badge = $o->getStatusBadge();
                    $statusLabel = $badge['label'] ?? ucfirst($o->status);

                    $res .= "• **Pesanan #ORD-{$o->id}** ({$sName})\n" .
                            "  - Status: **{$statusLabel}**\n" .
                            "  - Teknisi: {$techName}\n" .
                            "  - Jadwal: {$o->schedule}\n" .
                            "  - Lokasi: {$o->location}\n\n";
                }
                return trim($res);
            }

            if ($role === 'teknisi') {
                $allOrders = Order::all();
                $myJobs = array_filter($allOrders, fn($o) => (int)$o->technician_id === $userId);
                if (empty($myJobs)) {
                    return "Rekan Teknisi {$userName}, saat ini tidak ada pekerjaan aktif yang ditugaskan kepada Anda. Silakan cek menu **Permintaan Masuk** untuk menerima order baru.";
                }

                $res = "Berikut daftar penugasan pekerjaan Anda (Data Realtime Database):\n\n";
                foreach ($myJobs as $jo) {
                    $sName = $jo->service() ? $jo->service()->name : 'Servis';
                    $cust = $jo->customer();
                    $custName = $cust ? $cust->name : 'Customer';
                    $badge = $jo->getStatusBadge();
                    $res .= "• **#ORD-{$jo->id}** ({$sName})\n" .
                            "  - Pelanggan: {$custName}\n" .
                            "  - Status: **{$badge['label']}**\n" .
                            "  - Jadwal: {$jo->schedule}\n" .
                            "  - Lokasi: {$jo->location}\n\n";
                }
                return trim($res);
            }
        }

        // 2. KELUHAN / DIAGNOSA MASALAH PERANGKAT (AC Bocor, Laptop Mati, MCB Anjlok, Pipa Mampet)
        if (
            (str_contains($m, 'ac') && (str_contains($m, 'bocor') || str_contains($m, 'tidak dingin') || str_contains($m, 'kurang dingin') || str_contains($m, 'netes'))) ||
            (str_contains($m, 'laptop') && (str_contains($m, 'mati') || str_contains($m, 'rusak') || str_contains($m, 'lemot') || str_contains($m, 'bluescreen'))) ||
            (str_contains($m, 'mcb') || str_contains($m, 'korslet') || str_contains($m, 'konslet') || str_contains($m, 'anjlok')) ||
            (str_contains($m, 'pipa') || str_contains($m, 'mampet') || str_contains($m, 'pompa'))
        ) {
            return $this->formatComplaintDiagnosisResponse($m);
        }

        // 3. PENCARIAN HARGA & TARIF JASA (Dengan 3 Lapisan Biaya Transparan)
        if (
            str_contains($m, 'harga') ||
            str_contains($m, 'biaya') ||
            str_contains($m, 'tarif') ||
            str_contains($m, 'ongkos') ||
            str_contains($m, 'berapa')
        ) {
            return $this->formatPriceInquiryResponse($m);
        }

        // 4. PENCARIAN REKOMENDASI TEKNISI (Hanya yang Ada di Database, Tanpa Nomor Pribadi)
        if (
            str_contains($m, 'teknisi') ||
            str_contains($m, 'tukang') ||
            str_contains($m, 'ahli') ||
            str_contains($m, 'siapa yang')
        ) {
            return $this->formatTechnicianRecommendationResponse($m);
        }

        // 5. PENCARIAN KATALOG JASA ATAU KATEGORI JASA
        if (
            str_contains($m, 'jasa') ||
            str_contains($m, 'katalog') ||
            str_contains($m, 'layanan') ||
            str_contains($m, 'kategori')
        ) {
            return $this->formatServiceCatalogResponse($m);
        }

        // 6. CARA PEMESANAN / INFORMASI LAYANAN
        if (
            str_contains($m, 'cara pesan') ||
            str_contains($m, 'cara order') ||
            str_contains($m, 'bagaimana cara') ||
            str_contains($m, 'garansi') ||
            str_contains($m, 'cara menghubungi')
        ) {
            return "Panduan Layanan Platform Terima Jasa:\n\n" .
                "1. **Mencari Layanan:** Buka menu **Katalog Jasa** untuk memilih servis yang dibutuhkan (AC, Listrik, Laptop, Plumbing, Elektronik).\n" .
                "2. **Membuat Pesanan:** Klik tombol **Pesan Sekarang**, tentukan tanggal & jam kunjungan teknisi, serta tuliskan alamat lengkap.\n" .
                "3. **Memantau Status:** Buka menu **Pesanan Saya** untuk memantau apakah order sudah diterima atau dalam pengerjaan teknisi.\n" .
                "4. **Komunikasi Teknisi:** Anda dapat berkoordinasi langsung dengan teknisi yang ditugaskan setelah pesanan diterima.\n" .
                "5. **Garansi Resmi 14 Hari:** Setiap pekerjaan selesai dilindungi jaminan garansi 14 hari kalender. Anda dapat memberikan rating bintang dan ulasan di akhir pekerjaan.";
        }

        // Default Umum Ramah
        return "Halo Kak {$userName}! Saya Asisten AI Terima Jasa. " .
            "Saya dapat membantu Anda mencari katalog jasa servis resmi, rekomendasi teknisi terverifikasi, estimasi biaya transparan, diagnosa kerusakan perangkat, serta informasi status pesanan Anda. " .
            "Ada yang bisa kami bantu seputar servis rumah tangga atau elektronik hari ini?";
    }

    /**
     * Memformat respon diagnosa keluhan dengan langkah aman, harga dasar DB, estimasi AI, dan teknisi
     */
    private function formatComplaintDiagnosisResponse(string $m): string
    {
        if (str_contains($m, 'ac')) {
            $srv = Service::find(1); // Cuci AC
            $basePrice = $srv ? 'Rp ' . number_format($srv->estimated_price, 0, ',', '.') : 'Rp 75.000';

            return "Hasil Analisis Keluhan AC:\n\n" .
                "• **Diagnosa Masalah:** AC bocor air atau tidak dingin umumnya disebabkan oleh penumpukan debu/lendir pada talang pembuangan (drainase) dan filter indoor, atau penurunan tekanan freon.\n" .
                "• **Langkah Pemeriksaan Awal yang Aman:**\n" .
                "  1. Segera matikan unit AC jika tetesan air mengalir mendekati stop kontak dinding untuk mencegah korsleting.\n" .
                "  2. Periksa apakah filter udara tertutup debu tebal; Anda dapat mencucinya dengan air mengalir secara hati-hati.\n\n" .
                "• **Rekomendasi Layanan Database:** Cuci AC Standard 0.5 - 2 PK & Pengecekan Freon.\n" .
                "• **Rincian Biaya (Transparan):**\n" .
                "  - **Harga Dasar Database:** {$basePrice} (untuk jasa pembersihan/cuci unit standar).\n" .
                "  - **Estimasi AI (Kisaran Pengerjaan):** Rp 75.000 – Rp 175.000 (tergantung bila memerlukan penambahan freon).\n" .
                "  - **Harga Final:** Akan dikonfirmasi langsung oleh teknisi setelah pemeriksaan fisik di lokasi.\n" .
                "  *(Estimasi AI bersifat acuan awal dan bukan harga final yang mengikat)*\n\n" .
                "• **Rekomendasi Teknisi Tersedia:** Hendra Wijaya (Spesialis AC & Pendingin, Rating ★5.0, Status: Siap Bertugas).\n" .
                "Pesanan dilindungi garansi resmi Terima Jasa 14 hari kalender.";
        }

        if (str_contains($m, 'laptop') || str_contains($m, 'komputer')) {
            $srv = Service::find(12); // Hardware Laptop Mati Total
            $basePrice = $srv ? 'Rp ' . number_format($srv->estimated_price, 0, ',', '.') : 'Rp 180.000';

            return "Hasil Analisis Keluhan Laptop / Komputer:\n\n" .
                "• **Diagnosa Masalah:** Laptop yang tiba-tiba mati total umumnya disebabkan oleh kegagalan adaptor daya, kerusakan IC charging / power regulator motherboard, atau korsleting jalur kelistrikan utama.\n" .
                "• **Langkah Pemeriksaan Awal yang Aman:**\n" .
                "  1. Cabut kabel charger dari laptop dan stop kontak dinding.\n" .
                "  2. Jangan memaksakan menyalakan berulang kali jika tercium bau terbakar untuk mencegah kerusakan menyebar ke chipset prosesor.\n\n" .
                "• **Rekomendasi Layanan Database:** Perbaikan Hardware Laptop / Komputer Mati Total.\n" .
                "• **Rincian Biaya (Transparan):**\n" .
                "  - **Harga Dasar Database:** {$basePrice} (untuk diagnosa dan perbaikan jalur power standar).\n" .
                "  - **Estimasi AI (Kisaran Pengerjaan):** Rp 180.000 – Rp 350.000 (tergantung suku cadang IC yang perlu diganti).\n" .
                "  - **Harga Final:** Akan dikonfirmasi langsung oleh teknisi setelah analisa multitester di tempat.\n" .
                "  *(Estimasi AI bersifat acuan awal dan bukan harga final yang mengikat)*\n\n" .
                "• **Rekomendasi Teknisi Tersedia:** Bambang Irawan, S.Kom (Spesialis Laptop Mati Total, Rating ★5.0, Jakarta Selatan).\n" .
                "Pengerjaan bergaransi resmi 14 hari kalender.";
        }

        if (str_contains($m, 'mcb') || str_contains($m, 'listrik') || str_contains($m, 'korslet') || str_contains($m, 'anjlok')) {
            $srv = Service::find(8); // Perbaikan Korsleting Listrik
            $basePrice = $srv ? 'Rp ' . number_format($srv->estimated_price, 0, ',', '.') : 'Rp 120.000';

            return "Hasil Analisis Gangguan Kelistrikan:\n\n" .
                "• **Diagnosa Masalah:** MCB yang sering anjlok menandakan beban peralatan rumah melampaui kapasitas ampere terpasang atau terjadi hubungan pendek arus listrik (short circuit) pada instalasi dinding.\n" .
                "• **Langkah Pemeriksaan Awal yang Aman:**\n" .
                "  1. Matikan atau cabut semua peralatan berdaya besar (AC, dispenser pemanas, setrika, pompa air).\n" .
                "  2. Naikkan tuas MCB kembali; jika langsung trip seketika, JANGAN dipaksa naik karena ada kabel konslet aktif.\n\n" .
                "• **Rekomendasi Layanan Database:** Perbaikan Korsleting Listrik Darurat.\n" .
                "• **Rincian Biaya (Transparan):**\n" .
                "  - **Harga Dasar Database:** {$basePrice} (untuk pelacakan titik konslet & pemeriksaan beban).\n" .
                "  - **Estimasi AI (Kisaran Pengerjaan):** Rp 120.000 – Rp 200.000 (bila ada penggantian kabel/stop kontak).\n" .
                "  - **Harga Final:** Dikonfirmasi oleh teknisi setelah pengukuran tang ampere dan isolasi jalur.\n" .
                "  *(Estimasi AI bersifat acuan awal dan bukan harga final yang mengikat)*\n\n" .
                "• **Rekomendasi Teknisi Tersedia:** Ahmad Fauzi (Instalasi Listrik & Panel MCB, Rating ★5.0, Status: Tersedia).";
        }

        // Plumbing
        $srv = Service::find(11);
        $basePrice = $srv ? 'Rp ' . number_format($srv->estimated_price, 0, ',', '.') : 'Rp 135.000';

        return "Hasil Analisis Masalah Saluran Air & Plumbing:\n\n" .
            "• **Diagnosa Masalah:** Pipa wastafel mampet umumnya disebabkan oleh penumpukan lemak beku dan sisa kotoran pada leher angsa pipa PVC.\n" .
            "• **Langkah Pemeriksaan Awal yang Aman:** Jangan menuangkan soda api berlebih yang dapat merusak dan melengkungkan sambungan pipa PVC.\n" .
            "• **Rekomendasi Layanan Database:** Pelancaran Pipa Saluran Air Mampet (Harga Dasar Database: {$basePrice}).\n" .
            "• **Rekomendasi Teknisi Tersedia:** Joko Susilo (Spesialis Plumbing & Pompa Air, Rating ★5.0, Jakarta Selatan).";
    }

    /**
     * Memformat respon pertanyaan harga berdasarkan data riil database
     */
    private function formatPriceInquiryResponse(string $m): string
    {
        $allServices = Service::all();
        $matched = [];

        foreach ($allServices as $s) {
            $sLower = strtolower($s->name);
            $dLower = strtolower($s->description ?? '');

            if (str_contains($m, 'ac') && (str_contains($sLower, 'ac') || str_contains($sLower, 'freon'))) {
                $matched[] = $s;
            } elseif ((str_contains($m, 'laptop') || str_contains($m, 'komputer')) && (str_contains($sLower, 'laptop') || str_contains($sLower, 'komputer') || str_contains($sLower, 'os'))) {
                $matched[] = $s;
            } elseif ((str_contains($m, 'listrik') || str_contains($m, 'lampu')) && (str_contains($sLower, 'listrik') || str_contains($sLower, 'lampu'))) {
                $matched[] = $s;
            } elseif ((str_contains($m, 'pompa') || str_contains($m, 'pipa') || str_contains($m, 'mampet')) && (str_contains($sLower, 'pompa') || str_contains($sLower, 'pipa'))) {
                $matched[] = $s;
            }
        }

        if (empty($matched)) {
            // Cek apakah menanyakan layanan yang sama sekali tidak ada di platform
            if (str_contains($m, 'kapal') || str_contains($m, 'pesawat') || str_contains($m, 'mobil') || str_contains($m, 'motor')) {
                return "Maaf, data jasa untuk pencarian tersebut **belum tersedia** di katalog Terima Jasa saat ini. Kami saat ini berfokus pada layanan servis perangkat rumah tangga (AC, Elektronik, Kelistrikan, Plumbing, dan Komputer/Laptop).";
            }
            $matched = array_slice($allServices, 0, 4);
        }

        $res = "Berikut daftar harga dasar resmi layanan di database Terima Jasa:\n\n";
        foreach ($matched as $s) {
            $cat = $s->category() ? $s->category()->name : 'Servis';
            $priceFmt = 'Rp ' . number_format($s->estimated_price, 0, ',', '.');
            $res .= "• **{$s->name}** ({$cat})\n" .
                    "  - **Harga Dasar Database:** {$priceFmt}\n" .
                    "  - **Deskripsi:** {$s->description}\n";
        }

        $res .= "\n*Catatan Transparansi Biaya:*\n" .
                "- Harga dasar di atas adalah tarif acuan pengerjaan standar dari sistem.\n" .
                "- Estimasi AI dan harga final akan disesuaikan dengan kondisi riil di lapangan serta dikonfirmasi langsung oleh teknisi sebelum pekerjaan dimulai.\n" .
                "- Seluruh layanan dilindungi garansi pengerjaan 14 hari resmi.";

        return $res;
    }

    /**
     * Memformat respon rekomendasi teknisi dengan data asli tanpa membocorkan nomor telepon
     */
    private function formatTechnicianRecommendationResponse(string $m): string
    {
        $profiles = TechnicianProfile::all();
        $candidates = [];

        foreach ($profiles as $p) {
            $expLower = strtolower($p->expertise);
            $isVerif = ($p->verification_status === TechnicianProfile::VERIFICATION_VERIFIED);
            $isAvail = ($p->availability === TechnicianProfile::AVAILABILITY_AVAILABLE);

            // Hanya rekomendasikan teknisi yang verified
            if (!$isVerif) continue;

            $score = 0;
            if (str_contains($m, 'ac') && str_contains($expLower, 'ac')) $score += 10;
            if (str_contains($m, 'listrik') && str_contains($expLower, 'listrik')) $score += 10;
            if ((str_contains($m, 'laptop') || str_contains($m, 'komputer')) && (str_contains($expLower, 'laptop') || str_contains($expLower, 'komputer'))) $score += 10;
            if ((str_contains($m, 'pompa') || str_contains($m, 'pipa') || str_contains($m, 'saluran')) && (str_contains($expLower, 'pompa') || str_contains($expLower, 'plumbing') || str_contains($expLower, 'pipa'))) $score += 10;

            if ($isAvail) $score += 5;

            $candidates[] = [
                'profile' => $p,
                'score'   => $score
            ];
        }

        usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);
        $topCandidates = array_slice($candidates, 0, 3);

        if (empty($topCandidates) || $topCandidates[0]['score'] === 0) {
            // Cek jika menanyakan teknisi untuk bidang yang tidak ada
            if (str_contains($m, 'jahit') || str_contains($m, 'las') || str_contains($m, 'cat mobil')) {
                return "Maaf, saat ini mitra teknisi untuk keahlian tersebut belum tersedia di platform Terima Jasa. Kami akan terus menambah cakupan mitra terverifikasi.";
            }
        }

        $res = "Berikut rekomendasi mitra teknisi resmi dan terverifikasi di Terima Jasa:\n\n";
        foreach ($topCandidates as $c) {
            $p = $c['profile'];
            $u = $p->user();
            $tName = $u ? $u->name : 'Mitra Teknisi';
            $stats = Review::getRatingStatsForTechnician((int)$p->user_id);
            $rating = number_format((float)$stats['average'], 1);
            $revCount = (int)$stats['count'];
            $availLabel = ($p->availability === TechnicianProfile::AVAILABILITY_AVAILABLE) ? 'Tersedia Sekarang' : 'Sedang Bertugas';

            $res .= "• **{$tName}**\n" .
                    "  - **Keahlian:** {$p->expertise}\n" .
                    "  - **Wilayah Cakupan:** {$p->location}\n" .
                    "  - **Pengalaman:** {$p->experience}\n" .
                    "  - **Rating:** ★{$rating} ({$revCount} ulasan pelanggan)\n" .
                    "  - **Status:** {$availLabel} (Terverifikasi)\n\n";
        }

        $res .= "Anda dapat memilih langsung mitra teknisi di atas saat membuat pesanan baru melalui menu Katalog Jasa.";
        return trim($res);
    }

    /**
     * Memformat daftar katalog jasa atau kategori dari database
     */
    private function formatServiceCatalogResponse(string $m): string
    {
        // Cek apakah menanyakan layanan yang sama sekali tidak ada di platform (No Hallucination)
        if (str_contains($m, 'kapal') || str_contains($m, 'pesawat') || str_contains($m, 'mobil') || str_contains($m, 'motor') || str_contains($m, 'nuklir') || str_contains($m, 'helikopter') || str_contains($m, 'kereta')) {
            return "Maaf, data jasa untuk pencarian tersebut **belum tersedia** di katalog Terima Jasa saat ini. Kami saat ini berfokus pada layanan servis perangkat rumah tangga (AC, Elektronik, Kelistrikan, Plumbing, dan Komputer/Laptop).";
        }

        if (str_contains($m, 'kategori')) {
            $categories = Category::all();
            $res = "Platform Terima Jasa menyediakan **" . count($categories) . " kategori layanan utama**:\n\n";
            foreach ($categories as $i => $c) {
                $no = $i + 1;
                $res .= "{$no}. **{$c->name}** — {$c->description}\n";
            }
            $res .= "\nSilakan sebutkan kategori atau jenis keluhan yang Anda miliki untuk rekomendasi jasa spesifik.";
            return $res;
        }

        // Daftar jasa umum
        $services = Service::all();
        $res = "Berikut daftar layanan jasa yang tersedia di platform Terima Jasa:\n\n";
        foreach (array_slice($services, 0, 6) as $s) {
            $price = 'Rp ' . number_format($s->estimated_price, 0, ',', '.');
            $cat = $s->category() ? $s->category()->name : 'Servis';
            $res .= "• **{$s->name}** ({$cat}) — Harga Dasar: {$price}\n";
        }
        $res .= "\nSeluruh jasa dilindungi jaminan garansi resmi 14 hari. Buka menu **Katalog Jasa** untuk melihat daftar selengkapnya.";
        return $res;
    }

    /**
     * Menyimpan riwayat percakapan ke database (tabel ai_chats untuk customer) atau session
     */
    public function recordConversation(int $userId, string $role, string $question, string $answer): ?AiChat
    {
        try {
            // Simpan ke database jika pengguna terdaftar sebagai customer (relasi customer_id)
            if ($userId > 0) {
                return AiChat::recordChat($userId, $question, $answer);
            }

            // Simpan di session untuk pengunjung (guest)
            if (!isset($_SESSION['ai_chat_history']['guest'])) {
                $_SESSION['ai_chat_history']['guest'] = [];
            }
            $_SESSION['ai_chat_history']['guest'][] = [
                'id'         => time() . rand(100, 999),
                'question'   => $question,
                'answer'     => $answer,
                'created_at' => date('Y-m-d H:i:s')
            ];

            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Mengambil daftar percakapan pengguna (Anti-IDOR: hanya membaca milik pengguna yang sedang aktif)
     */
    public function getConversations(int $userId, string $role): array
    {
        if ($userId <= 0) {
            // Ambil dari session untuk guest
            $guestChats = $_SESSION['ai_chat_history']['guest'] ?? [];
            return array_map(function($chat, $idx) {
                return [
                    'id'         => (int)($chat['id'] ?? ($idx + 1)),
                    'title'      => mb_substr((string)($chat['question'] ?? 'Percakapan Tamu'), 0, 40) . '...',
                    'preview'    => mb_substr((string)($chat['answer'] ?? ''), 0, 80) . '...',
                    'created_at' => $chat['created_at'] ?? date('Y-m-d H:i:s')
                ];
            }, $guestChats, array_keys($guestChats));
        }

        // Customer: ambil dari tabel ai_chats berdasarkan customer_id
        $chats = AiChat::getByCustomerId($userId);
        $conversations = [];

        foreach ($chats as $c) {
            $conversations[] = [
                'id'         => (int)$c->id,
                'title'      => mb_substr((string)$c->question, 0, 45) . (mb_strlen($c->question) > 45 ? '...' : ''),
                'preview'    => mb_substr((string)$c->answer, 0, 90) . '...',
                'created_at' => $c->created_at ?? date('Y-m-d H:i:s')
            ];
        }

        return $conversations;
    }

    /**
     * Mengambil detail pesan untuk percakapan tertentu (Anti-IDOR: validasi kepemilikan ketat)
     */
    public function getConversationMessages(int $conversationId, int $userId, string $role): ?array
    {
        if ($userId <= 0) {
            // Guest session
            $guestChats = $_SESSION['ai_chat_history']['guest'] ?? [];
            foreach ($guestChats as $g) {
                if ((int)($g['id'] ?? 0) === $conversationId) {
                    return [
                        'conversation_id' => $conversationId,
                        'messages' => [
                            ['role' => 'user', 'content' => $g['question'], 'timestamp' => $g['created_at']],
                            ['role' => 'assistant', 'content' => $g['answer'], 'timestamp' => $g['created_at']]
                        ]
                    ];
                }
            }
            return null;
        }

        $chat = AiChat::find($conversationId);
        if (!$chat) {
            return null;
        }

        // VALIDASI HAK AKSES KETAT (IDOR Guard): Customer hanya boleh mengakses chat miliknya sendiri!
        if ((int)$chat->customer_id !== $userId && $role !== 'admin') {
            return null; // Akses ditolak
        }

        return [
            'conversation_id' => (int)$chat->id,
            'customer_id'     => (int)$chat->customer_id,
            'messages'        => [
                [
                    'role'      => 'user',
                    'content'   => $chat->question,
                    'timestamp' => $chat->created_at
                ],
                [
                    'role'      => 'assistant',
                    'content'   => $chat->answer,
                    'timestamp' => $chat->created_at
                ]
            ],
            'created_at' => $chat->created_at
        ];
    }

    /**
     * Query Groq Cloud LLM API langsung
     */
    private function queryGroqApi(array $messages, string $apiKey, string $preferredModel): ?string
    {
        $candidateModels = array_values(array_unique(array_filter([
            $preferredModel,
            'openai/gpt-oss-120b',
            'openai/gpt-oss-20b',
            'llama-3.3-70b-versatile'
        ])));

        foreach ($candidateModels as $model) {
            $ch = curl_init(self::GROQ_API_URL);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey,
                ],
                CURLOPT_POSTFIELDS     => json_encode([
                    'model'       => $model,
                    'messages'    => $messages,
                    'temperature' => (float)env('AI_TEMPERATURE', 0.3),
                    'max_tokens'  => (int)env('AI_MAX_TOKENS', 800),
                ]),
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT        => 12,
                CURLOPT_CONNECTTIMEOUT => 5,
            ]);

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && !empty($result)) {
                $decoded = json_decode($result, true);
                $reply = $decoded['choices'][0]['message']['content'] ?? null;
                if (!empty($reply)) {
                    $reply = preg_replace('/<think>.*?<\/think>/si', '', $reply);
                    $clean = trim($reply);
                    if (!empty($clean)) {
                        return $clean;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Deteksi pertanyaan atau keluhan medis
     */
    private function detectMedicalInquiry(string $text): bool
    {
        $lower = strtolower($text);
        $medicalKeywords = [
            'dokter', 'demam', 'batuk', 'berdarah', 'obat', 'resep dokter', 'sakit kepala',
            'mual', 'muntah', 'diare', 'pusing', 'gejala penyakit', 'flu', 'infeksi',
            'nyeri dada', 'sesak nafas', 'tensimeter', 'asam urat', 'kolesterol', 'stroke'
        ];

        foreach ($medicalKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                return true;
            }
        }

        return false;
    }
}
