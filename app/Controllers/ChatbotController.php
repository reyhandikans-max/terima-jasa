<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Auth;
use App\Core\Model;
use PDO;

use App\Services\ChatbotService;

/**
 * ChatbotController — Mengelola AI Chatbot Multi-Pemeran dengan Live Database Grounding
 * Mampu membaca database secara real-time dan menjawab to the point.
 */
class ChatbotController extends Controller
{
    private ChatbotService $chatbotService;
    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';

    public function __construct()
    {
        $this->chatbotService = new ChatbotService();
    }

    private function getApiKey(): string
    {
        return trim((string)env('AI_API_KEY', env('GROQ_API_KEY', '')));
    }

    private function getModel(): string
    {
        return trim((string)env('AI_MODEL', 'openai/gpt-oss-120b'));
    }

    /**
     * POST /api/chatbot & POST /api/ai/chat
     * Body JSON: { "messages": [ { "role": "user"|"assistant", "content": "..." } ], "question": "..." }
     */
    public function chat(Request $request): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::json(['error' => 'Method not allowed'], 405);
            return;
        }

        $body = file_get_contents('php://input');
        $payload = json_decode($body, true);
        if (!is_array($payload)) {
            $payload = [];
        }

        $messages = $payload['messages'] ?? $request->input('messages') ?? $_POST['messages'] ?? [];
        if (is_string($messages)) {
            $decodedMsg = json_decode($messages, true);
            if (is_array($decodedMsg)) $messages = $decodedMsg;
        }

        $question = $payload['question'] ?? $request->input('question') ?? $_POST['question'] ?? '';
        if (empty($messages) && !empty($question)) {
            $messages = [
                ['role' => 'user', 'content' => trim((string)$question)]
            ];
        }

        if (empty($messages)) {
            Response::json(['error' => 'Pesan tidak boleh kosong.'], 400);
            return;
        }

        $userContext = [
            'user_id'   => $payload['user_id'] ?? null,
            'role'      => $payload['role'] ?? null,
            'user_name' => $payload['user_name'] ?? null
        ];

        // Delegasikan ke ChatbotService untuk validasi otorisasi server-side, grounding, AI query, fallback, dan penyimpanan ke ai_chats
        $result = $this->chatbotService->handleChat($messages, $userContext);

        if (!$result['success']) {
            Response::json(['error' => $result['error'] ?? 'Terjadi kendala pada layanan Chatbot.'], $result['code'] ?? 400);
            return;
        }

        Response::json([
            'success'    => true,
            'reply'      => $result['reply'],
            'model'      => $result['model'],
            'role'       => $result['role'],
            'source'     => $result['source'],
            'chat_id'    => $result['chat_id'] ?? null,
            'created_at' => $result['created_at'] ?? date('Y-m-d H:i:s')
        ]);
    }

    /**
     * GET /api/ai/conversations
     * Mengambil daftar riwayat percakapan pengguna aktif (Anti-IDOR)
     */
    public function getConversations(Request $request): void
    {
        $user = Auth::user();
        $userId = (int)($user['id'] ?? 0);
        $role = strtolower((string)($user['role'] ?? 'guest'));

        $conversations = $this->chatbotService->getConversations($userId, $role);

        Response::json([
            'status'  => 'success',
            'data'    => $conversations,
            'user_id' => $userId,
            'role'    => $role
        ]);
    }

    /**
     * GET /api/ai/conversations/{id}/messages
     * Mengambil detail pesan percakapan tertentu (Anti-IDOR: validasi kepemilikan ketat)
     */
    public function getConversationMessages(Request $request, $id): void
    {
        $user = Auth::user();
        $userId = (int)($user['id'] ?? 0);
        $role = strtolower((string)($user['role'] ?? 'guest'));

        $data = $this->chatbotService->getConversationMessages((int)$id, $userId, $role);

        if ($data === null) {
            Response::json([
                'status'  => 'error',
                'message' => 'Percakapan tidak ditemukan atau Anda tidak memiliki hak akses.'
            ], 404);
            return;
        }

        Response::json([
            'status' => 'success',
            'data'   => $data
        ]);
    }

    /**
     * Membaca fakta data riil dari database platform untuk RAG (Retrieval Augmented Generation)
     */
    public function fetchDatabaseGrounding(?int $userId = null, ?string $userRole = null): string
    {
        $db = Model::getDb();
        if (!$db) {
            return "[Database offline/mock]";
        }

        try {
            // 1. Data Teknisi Terdaftar Lengkap (dengan Rating Ulasan)
            $stmtTech = $db->query("
                SELECT u.id, u.name, u.phone, u.status as user_status, 
                       p.expertise, p.experience, p.location, p.availability, p.verification_status,
                       COALESCE(AVG(r.rating), 5.0) as avg_rating,
                       COUNT(r.id) as review_count
                FROM users u 
                LEFT JOIN technician_profiles p ON u.id = p.user_id 
                LEFT JOIN reviews r ON u.id = r.technician_id
                WHERE u.role = 'teknisi'
                GROUP BY u.id, u.name, u.phone, u.status, p.expertise, p.experience, p.location, p.availability, p.verification_status
            ");
            $techs = $stmtTech ? $stmtTech->fetchAll(PDO::FETCH_ASSOC) : [];
            $totalTechs = count($techs);

            $verifiedTechs = 0;
            $pendingTechs = 0;
            $otherTechs = 0;
            $availableTechs = 0;

            $techsListStr = "";
            foreach ($techs as $idx => $t) {
                $num = $idx + 1;
                $exp = $t['expertise'] ?? 'Servis Umum';
                $loc = $t['location'] ?? 'Jakarta';
                $avail = ($t['availability'] ?? 'available') === 'available' ? 'Tersedia' : 'Sibuk/Tidak Tersedia';
                $verif = $t['verification_status'] ?? 'pending';
                $ratingFmt = number_format((float)($t['avg_rating'] ?? 5.0), 1);
                $revCount = (int)($t['review_count'] ?? 0);

                if ($verif === 'verified') {
                    $verifiedTechs++;
                } elseif ($verif === 'pending') {
                    $pendingTechs++;
                } else {
                    $otherTechs++;
                }

                if (($t['availability'] ?? 'available') === 'available') {
                    $availableTechs++;
                }

                $techsListStr .= "{$num}. **{$t['name']}** (Telp/WA: {$t['phone']}, Keahlian: {$exp}, Wilayah: {$loc}, Rating: ★{$ratingFmt} [{$revCount} ulasan], Status Ketersediaan: {$avail}, Verifikasi: {$verif})\n";
            }

            // 2. Data Pelanggan & Admin
            $stmtUsers = $db->query("SELECT role, COUNT(*) as cnt FROM users GROUP BY role");
            $userCounts = $stmtUsers ? $stmtUsers->fetchAll(PDO::FETCH_KEY_PAIR) : [];
            $custCount = (int)($userCounts['customer'] ?? 0);
            $adminCount = (int)($userCounts['admin'] ?? 1);
            $totalUsers = $custCount + $totalTechs + $adminCount;

            // 3. Data Order / Pesanan
            $stmtOrders = $db->query("SELECT status, COUNT(*) as cnt FROM orders GROUP BY status");
            $orderCounts = $stmtOrders ? $stmtOrders->fetchAll(PDO::FETCH_KEY_PAIR) : [];
            $totalOrders = (int)array_sum($orderCounts);
            $pendingOrders = (int)($orderCounts['pending'] ?? 0);
            $activeOrders = (int)(($orderCounts['accepted'] ?? 0) + ($orderCounts['in_progress'] ?? 0));
            $completedOrders = (int)($orderCounts['completed'] ?? 0);
            $cancelledOrders = (int)($orderCounts['cancelled'] ?? 0);

            // Antrean Pesanan Berstatus Pending Terbaru
            $stmtPending = $db->query("
                SELECT o.id, s.name as service_name, c.name as customer_name, o.location, o.schedule 
                FROM orders o 
                JOIN services s ON o.service_id = s.id 
                JOIN users c ON o.customer_id = c.id 
                WHERE o.status = 'pending' 
                ORDER BY o.id DESC LIMIT 4
            ");
            $pendingRows = $stmtPending ? $stmtPending->fetchAll(PDO::FETCH_ASSOC) : [];
            $pendingListStr = "";
            foreach ($pendingRows as $pr) {
                $pendingListStr .= "• [Order #ORD-{$pr['id']}] {$pr['service_name']} oleh customer {$pr['customer_name']} ({$pr['location']}, Jadwal: {$pr['schedule']})\n";
            }

            // 4. Kategori & Layanan Aktif beserta Tarif
            $stmtSrv = $db->query("
                SELECT s.id, c.name as category, s.name, s.estimated_price 
                FROM services s 
                JOIN categories c ON s.category_id = c.id 
                WHERE s.status = 'active'
                ORDER BY s.estimated_price ASC
            ");
            $services = $stmtSrv ? $stmtSrv->fetchAll(PDO::FETCH_ASSOC) : [];
            $servicesListStr = "";
            foreach ($services as $s) {
                $priceFmt = 'Rp ' . number_format($s['estimated_price'], 0, ',', '.');
                $servicesListStr .= "• [{$s['category']}] {$s['name']} — Tarif Resmi: {$priceFmt}\n";
            }

            // 5. Ulasan dan Testimoni Terbaru dari Database
            $stmtReviews = $db->query("
                SELECT r.rating, r.comment, c.name as customer_name, t.name as tech_name, s.name as service_name
                FROM reviews r
                JOIN users c ON r.customer_id = c.id
                JOIN users t ON r.technician_id = t.id
                LEFT JOIN orders o ON r.order_id = o.id
                LEFT JOIN services s ON o.service_id = s.id
                ORDER BY r.id DESC LIMIT 4
            ");
            $reviewsRows = $stmtReviews ? $stmtReviews->fetchAll(PDO::FETCH_ASSOC) : [];
            $reviewsListStr = "";
            foreach ($reviewsRows as $rw) {
                $reviewsListStr .= "• [Rating ★{$rw['rating']}] {$rw['customer_name']} untuk Teknisi {$rw['tech_name']}: \"{$rw['comment']}\"\n";
            }

            // 6. Data Kontekstual Personal User (Jika login)
            $userSpecificContext = "";
            if ($userId && $userRole === 'customer') {
                $stmtUO = $db->prepare("
                    SELECT o.id, s.name as service_name, u.name as tech_name, u.phone as tech_phone, o.status, o.schedule, o.location 
                    FROM orders o 
                    JOIN services s ON o.service_id = s.id 
                    LEFT JOIN users u ON o.technician_id = u.id 
                    WHERE o.customer_id = ? 
                    ORDER BY o.id DESC LIMIT 5
                ");
                $stmtUO->execute([$userId]);
                $userOrders = $stmtUO->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($userOrders)) {
                    $userSpecificContext .= "\nRIWAYAT PESANAN CUSTOMER INI (#ID {$userId} {$userName}):\n";
                    foreach ($userOrders as $uo) {
                        $tech = $uo['tech_name'] ? "{$uo['tech_name']} (Telp: {$uo['tech_phone']})" : 'Menunggu Konfirmasi Teknisi';
                        $userSpecificContext .= "- Order #ORD-{$uo['id']}: {$uo['service_name']} (Teknisi: {$tech}, Status: {$uo['status']}, Jadwal: {$uo['schedule']}, Lokasi: {$uo['location']})\n";
                    }
                }
            } elseif ($userId && $userRole === 'teknisi') {
                $stmtTO = $db->prepare("
                    SELECT o.id, s.name as service_name, c.name as cust_name, c.phone as cust_phone, o.status, o.schedule, o.location 
                    FROM orders o 
                    JOIN services s ON o.service_id = s.id 
                    JOIN users c ON o.customer_id = c.id 
                    WHERE o.technician_id = ? 
                    ORDER BY o.id DESC LIMIT 5
                ");
                $stmtTO->execute([$userId]);
                $techOrders = $stmtTO->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($techOrders)) {
                    $userSpecificContext .= "\nDAFTAR PEKERJAAN TEKNISI INI (#ID {$userId} {$userName}):\n";
                    foreach ($techOrders as $to) {
                        $userSpecificContext .= "- Pekerjaan #ORD-{$to['id']}: {$to['service_name']} (Customer: {$to['cust_name']}, Telp: {$to['cust_phone']}, Status: {$to['status']}, Jadwal: {$to['schedule']}, Lokasi: {$to['location']})\n";
                    }
                }
            }

            $adminCustListStr = "";
            if ($userRole === 'admin') {
                $stmtCustAll = $db->query("SELECT id, name, email FROM users WHERE role = 'customer' ORDER BY id ASC");
                $custAll = $stmtCustAll ? $stmtCustAll->fetchAll(PDO::FETCH_ASSOC) : [];
                $adminCustListStr = "=== DAFTAR PELANGGAN (CUSTOMER) TERDAFTAR (HAK AKSES ADMINISTRATOR) ===\n";
                foreach ($custAll as $ca) {
                    $adminCustListStr .= "• [ID: #{$ca['id']}] {$ca['name']} (Email: {$ca['email']})\n";
                }
                $adminCustListStr .= "\n";
            }

            return "=== RINGKASAN RESMI STATISTIK DASHBOARD ADMINISTRATOR (SINGLE SOURCE OF TRUTH) ===\n" .
                "• TOTAL TEKNISI KESELURUHAN (AGREGAT RESMI): {$totalTechs} orang mitra teknisi terdaftar di sistem\n" .
                "  (Rincian status: {$verifiedTechs} verified/aktif, {$pendingTechs} menunggu verifikasi" . ($otherTechs > 0 ? ", {$otherTechs} unverified" : "") . ", serta {$availableTechs} teknisi berstatus ketersediaan available/siap order)\n" .
                "• TOTAL PELANGGAN (CUSTOMER): {$custCount} orang pelanggan terdaftar\n" .
                "• TOTAL SELURUH PENGGUNA SISTEM: {$totalUsers} orang pengguna\n" .
                "• TOTAL ORDER/PESANAN KESELURUHAN (AGREGAT RESMI): {$totalOrders} pesanan\n" .
                "  (Rincian status: {$pendingOrders} pending/menunggu konfirmasi, {$activeOrders} sedang dikerjakan/in progress, {$completedOrders} selesai/completed, {$cancelledOrders} dibatalkan)\n" .
                "• ANTREAN VERIFIKASI MITRA: {$pendingTechs} teknisi\n\n" .
                (!empty($adminCustListStr) ? $adminCustListStr : "") .
                (!empty($pendingListStr) ? "=== ANTREAN PESANAN PENDING TERBARU ===\n{$pendingListStr}\n" : "") .
                "=== DAFTAR RINCIAN MITRA TEKNISI TERDAFTAR ===\n{$techsListStr}\n" .
                "=== KATALOG JASA & TARIF RESMI DI DATABASE ===\n{$servicesListStr}\n" .
                (!empty($reviewsListStr) ? "=== ULASAN TESTIMONI PELANGGAN TERBARU ===\n{$reviewsListStr}\n" : "") .
                $userSpecificContext;
        } catch (\Throwable $e) {
            return "[Data DB: Terjadi kesalahan saat membaca database]";
        }
    }

    /**
     * Membangun System Prompt terintegrasi data database dan aturan to-the-point
     */
    private function buildSystemPrompt(string $role, string $userName, ?int $userId = null): array
    {
        $dbGrounding = $this->fetchDatabaseGrounding($userId, $role);

        $rules = "\n\nPEDOMAN PENTING GAYA JAWABAN (WAJIB DIPATUHI SECARA KETAT):\n" .
            "1. JAWAB LANGSUNG TO THE POINT & NYAMBUNG: " .
            "Langsung ke inti jawaban tanpa basa-basi pengantar formal atau salam berulang. " .
            "Jangan gunakan awalan seperti 'Berikut adalah...', 'Tentu saya akan...', atau pengantar yang berbelit-belit. " .
            "2. WAJIB BACA & GUNAKAN DATA DATABASE DI ATAS SEBAGAI FAKTA MUTLAK: " .
            "Jika pengguna bertanya jumlah atau daftar data (misalnya: 'ada berapa total teknisi yang ada', 'siapa teknisi listrik', 'berapa biaya cuci ac'): " .
            "GUNAKAN ANGKA DAN NAMA DARI DATA DATABASE DI ATAS! " .
            "DILARANG MENGARANG ANGKA LAIN (misal ribuan atau ratusan teknisi). " .
            "3. HINDARI TABEL MARKDOWN LEBAR: " .
            "Dilarang membuat format tabel markdown yang lebar karena akan terpotong dan rusak di layar chat widget. Selalu gunakan format bullet point (•) atau penomoran rapi. " .
            "4. Ringkas dan padat: Jawab dalam 2 sampai 4 kalimat atau poin-poin singkat yang jelas.";

        $baseContext = "Kamu adalah Asisten AI resmi platform 'Terima Jasa' (platform penghubung customer dan teknisi servis profesional di Indonesia).\n\n" .
            $dbGrounding . "\n" . $rules . "\n\n";

        switch ($role) {
            case 'teknisi':
                $content = $baseContext .
                    "Kamu berbicara dengan Rekan Teknisi {$userName}. Berikan diagnosa teknis, kode error, nilai toleransi komponen, SOP K3, atau template chat yang praktis dan langsung to the point.";
                break;

            case 'admin':
                $content = $baseContext .
                    "\n\n[SYSTEM INSTRUCTION & DATA ACCURACY GUARDRAIL]\n" .
                    "Peran: Anda adalah Asisten AI Copilot untuk Dashboard Administrator 'Terima Jasa'.\n\n" .
                    "Aturan Utama Akurasi Data:\n\n" .
                    "1. Sumber Kebenaran (Single Source of Truth):\n" .
                    "- Selalu gunakan angka agregat/total resmi dari ringkasan statistik Dashboard Administrator (seperti Total Customer, Total Teknisi, Total Order, Order Pending, dll.) sebagai jawaban utama.\n" .
                    "- Jangan pernah menghitung ulang total secara independen hanya berdasarkan potongan data/list yang tampil (misalnya hanya menghitung teknisi yang berstatus verified atau available).\n\n" .
                    "2. Penanganan Pertanyaan Total Data:\n" .
                    "- Jika pengguna menanyakan angka total (contoh: 'berapa total teknisi'), sebutkan angka Total Teknisi keseluruhan yang terdaftar di sistem terlebih dahulu (contoh: 5 atau 7 teknisi sesuai total database).\n" .
                    "- Setelah menyebutkan total keseluruhan, Anda boleh memberikan rincian/breakdown statusnya jika relevan (contoh: 'X mitra teknisi terdaftar, terdiri dari Y verified/aktif, Z menunggu verifikasi, dll.').\n\n" .
                    "3. Konsistensi & Transparansi:\n" .
                    "- Jika terdapat ketidaksesuaian antara jumlah list yang dikembalikan oleh query dengan angka summary dashboard, utamakan angka summary dashboard dan beri keterangan status ringkasnya secara jelas.\n\n" .
                    "Berikan data statistik aktual dari database di atas, SOP verifikasi dokumen teknisi, mediasi sengketa, dan analisis operasional yang tajam, akurat, dan to the point.";
                break;

            case 'customer':
                $content = $baseContext .
                    "Kamu berbicara dengan Pelanggan {$userName}. Bantu diagnosa kendala perangkat, rekomendasikan jenis jasa dan teknisi dari database di atas beserta tarif resmi, dengan bahasa ramah, solutif, dan to the point.";
                break;

            default: // guest / umum
                $content = $baseContext .
                    "Kamu berbicara dengan Pengunjung Umum. Jelaskan layanan teknisi yang tersedia, tarif resmi, dan cara memesan secara ramah dan to the point.";
                break;
        }

        return [
            'role'    => 'system',
            'content' => $content
        ];
    }

    /**
     * Query Groq Cloud LLM API dengan cURL (Multi-Model Support & SSL Fix)
     */
    public function queryGroq(array $messages, string $apiKey, string $preferredModel): ?string
    {
        $candidateModels = array_values(array_unique(array_filter([
            $preferredModel,
            'openai/gpt-oss-120b',
            'openai/gpt-oss-20b',
            'qwen/qwen3.8-27b'
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
                CURLOPT_TIMEOUT        => 18,
                CURLOPT_CONNECTTIMEOUT => 6,
            ]);

            $result   = curl_exec($ch);
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
     * Smart Role Fallback Heuristic Engine — Membaca Real Database dan To The Point
     */
    public function generateRoleFallbackReply(string $role, string $message, string $userName, ?int $userId = null): string
    {
        $m = strtolower(trim($message));
        $db = Model::getDb();

        // ════════════════════════════════════════════════════════════
        // OUT-OF-SCOPE GUARD: Tolak pertanyaan di luar platform Terima Jasa
        // ════════════════════════════════════════════════════════════
        if ($this->chatbotService->isOutOfScope($message)) {
            return "❌ **Pertanyaan di Luar Cakupan Layanan Terima Jasa**\n\n" .
                "Maaf, AI Terima Jasa hanya dapat menjawab pertanyaan seputar layanan jasa yang terdaftar di database platform (Servis AC, Kelistrikan, Laptop/Komputer, Plumbing, dan Elektronik), serta status pesanan dan data operasional sistem.\n\n" .
                "Pertanyaan di luar cakupan database kami (seperti pembuatan kode program, tutorial umum, atau topik di luar platform) tidak dapat diproses.";
        }

        // ════════════════════════════════════════════════════════════
        // DETEKSI PERTANYAAN DATA SPESIFIK DATABASE (SEMUA ROLE)
        // ════════════════════════════════════════════════════════════

        // 0a. Pertanyaan Identitas Pengguna ("siapa saya", "nama saya")
        if (
            $m === 'siapa saya' || $m === 'siapa saya?' || str_contains($m, 'siapa saya') ||
            $m === 'siapa aku' || $m === 'siapa aku?' || str_contains($m, 'siapa aku') ||
            str_contains($m, 'identitas saya') || str_contains($m, 'nama saya')
        ) {
            $roleLabel = match ($role) {
                'admin'   => 'Administrator',
                'teknisi' => 'Mitra Teknisi',
                default   => 'Customer'
            };
            return "Anda adalah **{$userName}**, terdaftar sebagai **{$roleLabel}** (ID: #{$userId}) di platform Terima Jasa.";
        }

        // 0b. Pertanyaan Daftar Customer ("siapa saja", "daftar customer")
        if (
            $m === 'siapa saja' || $m === 'siapa saja?' ||
            str_contains($m, 'siapa saja customer') || str_contains($m, 'siapa saja costumer') ||
            str_contains($m, 'siapa saja pelanggan') || str_contains($m, 'siapa customernya') ||
            str_contains($m, 'daftar customer') || str_contains($m, 'daftar pelanggan')
        ) {
            if ($role === 'admin' && $db) {
                $customers = $db->query("SELECT id, name, email FROM users WHERE role = 'customer' ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
                $res = "Berikut daftar " . count($customers) . " pelanggan (customer) terdaftar di platform Terima Jasa:\n";
                foreach ($customers as $idx => $c) {
                    $no = $idx + 1;
                    $res .= "{$no}. **{$c['name']}** (Email: {$c['email']})\n";
                }
                return trim($res);
            } elseif ($role !== 'admin') {
                return "Untuk menjaga privasi dan keamanan data pengguna, daftar pelanggan platform Terima Jasa tidak dapat ditampilkan kepada akun publik. Anda hanya dapat melihat data akun dan pesanan Anda sendiri.";
            }
        }

        // 1. Pertanyaan Jumlah / Total Teknisi
        if (
            (str_contains($m, 'berapa') && str_contains($m, 'teknisi')) ||
            str_contains($m, 'total teknisi') ||
            str_contains($m, 'jumlah teknisi') ||
            str_contains($m, 'daftar teknisi') ||
            str_contains($m, 'siapa teknisi') ||
            (str_contains($m, 'ada berapa') && str_contains($m, 'teknisi'))
        ) {
            if ($db) {
                $techs = $db->query("
                    SELECT u.id, u.name, p.expertise, p.location, p.availability, p.verification_status 
                    FROM users u 
                    LEFT JOIN technician_profiles p ON u.id = p.user_id 
                    WHERE u.role = 'teknisi'
                    ORDER BY u.id ASC
                ")->fetchAll(PDO::FETCH_ASSOC);

                $total = count($techs);
                $verified = 0;
                $pending = 0;
                $unverified = 0;
                $available = 0;

                $list = "";
                foreach ($techs as $i => $t) {
                    $no = $i + 1;
                    $exp = $t['expertise'] ?? 'Servis Umum';
                    $loc = $t['location'] ?? 'Jakarta';
                    $avail = ($t['availability'] ?? 'available') === 'available' ? 'Tersedia' : 'Sibuk';
                    $verif = $t['verification_status'] ?? 'pending';

                    if ($verif === 'verified') {
                        $verified++;
                    } elseif ($verif === 'pending') {
                        $pending++;
                    } else {
                        $unverified++;
                    }

                    if (($t['availability'] ?? 'available') === 'available') {
                        $available++;
                    }

                    $list .= "{$no}. **{$t['name']}** — {$exp} ({$loc}) [Status: {$verif}, Ketersediaan: {$avail}]\n";
                }

                $breakdownParts = ["{$verified} verified/aktif"];
                if ($pending > 0) $breakdownParts[] = "{$pending} menunggu verifikasi";
                if ($unverified > 0) $breakdownParts[] = "{$unverified} ditolak/nonaktif";
                $breakdownStr = implode(', ', $breakdownParts) . ", serta {$available} teknisi siap/tersedia";

                return "Saat ini terdapat total **{$total} mitra teknisi** yang terdaftar di sistem platform Terima Jasa (terdiri dari {$breakdownStr}):\n\n" .
                    $list . "\n" .
                    "*(Angka agregat di atas merupakan single source of truth dari ringkasan statistik Dashboard Administrator)*";
            }

            return "Saat ini terdapat total **7 mitra teknisi** terdaftar di sistem platform Terima Jasa: Hendra Wijaya (AC), Ahmad Fauzi (Listrik), Dimas Pratama (Mesin Cuci - Pending), Rian Syahputra (Komputer - Rejected), Bambang Irawan (Laptop), Dedi Setiawan (Laptop), dan Joko Susilo (Plumbing).";
        }

        // 2. Pertanyaan Teknisi Menunggu Verifikasi
        if (
            str_contains($m, 'menunggu verifikasi') ||
            str_contains($m, 'antrean verifikasi') ||
            str_contains($m, 'butuh approval') ||
            (str_contains($m, 'verifikasi') && (str_contains($m, 'berapa') || str_contains($m, 'pending') || str_contains($m, 'antrian')))
        ) {
            if ($db) {
                $pendingTechs = $db->query("
                    SELECT u.name, u.phone, p.expertise, p.location 
                    FROM users u 
                    JOIN technician_profiles p ON u.id = p.user_id 
                    WHERE u.role = 'teknisi' AND p.verification_status = 'pending'
                ")->fetchAll(PDO::FETCH_ASSOC);

                $cntPending = count($pendingTechs);
                $detail = "";
                foreach ($pendingTechs as $pt) {
                    $detail .= "• **{$pt['name']}** — {$pt['expertise']} ({$pt['location']}, Telp: {$pt['phone']})\n";
                }

                return "Saat ini terdapat **{$cntPending} mitra teknisi** yang berstatus **Menunggu Verifikasi** (butuh approval segera):\n\n" .
                    (!empty($detail) ? $detail . "\n" : "Tidak ada antrean verifikasi saat ini.\n\n") .
                    "Anda dapat meninjau dan menyetujui berkas melalui menu **Verifikasi & Teknisi** di Dashboard Administrator.";
            }
        }

        // 3. Pertanyaan Total Order / Pesanan Platform
        if (
            (str_contains($m, 'berapa') && (str_contains($m, 'order') || str_contains($m, 'pesanan') || str_contains($m, 'transaksi'))) ||
            str_contains($m, 'total order') ||
            str_contains($m, 'status order') ||
            str_contains($m, 'total pesanan') ||
            str_contains($m, 'jumlah order')
        ) {
            if ($db) {
                $orderCounts = $db->query("SELECT status, COUNT(*) as cnt FROM orders GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
                $total = (int)array_sum($orderCounts);
                $pending = (int)($orderCounts['pending'] ?? 0);
                $active = (int)(($orderCounts['accepted'] ?? 0) + ($orderCounts['in_progress'] ?? 0));
                $completed = (int)($orderCounts['completed'] ?? 0);
                $cancelled = (int)($orderCounts['cancelled'] ?? 0);

                return "Total pesanan di platform Terima Jasa saat ini berjumlah **{$total} order** (seluruh transaksi resmi dashboard):\n\n" .
                    "• **{$pending}** Menunggu Konfirmasi (Order Pending)\n" .
                    "• **{$active}** Diterima / Pengerjaan (Order Aktif)\n" .
                    "• **{$completed}** Sukses Diselesaikan (Order Selesai)" .
                    ($cancelled > 0 ? "\n• **{$cancelled}** Dibatalkan (Cancelled)" : "") . "\n\n" .
                    "*(Data ini selaras 100% dengan metrik pada kartu ringkasan Dashboard Administrator)*";
            }
        }

        // 4. Pertanyaan Total Customer / Pelanggan / Pengguna (Mendukung Typo 'costumer')
        if (
            (
                str_contains($m, 'customer') ||
                str_contains($m, 'costumer') ||
                str_contains($m, 'pelanggan') ||
                str_contains($m, 'konsumen') ||
                str_contains($m, 'user') ||
                str_contains($m, 'pengguna')
            ) && (
                str_contains($m, 'berapa') ||
                str_contains($m, 'total') ||
                str_contains($m, 'jumlah') ||
                str_contains($m, 'daftar') ||
                str_contains($m, 'siapa') ||
                str_contains($m, 'ada berapa') ||
                str_contains($m, 'data')
            )
        ) {
            if ($db) {
                $customers = $db->query("
                    SELECT u.id, u.name, u.email, u.phone, u.status 
                    FROM users u 
                    WHERE u.role = 'customer'
                    ORDER BY u.id ASC
                ")->fetchAll(PDO::FETCH_ASSOC);

                $custCount = count($customers);
                $activeCount = 0;
                $inactiveCount = 0;
                $custList = "";

                foreach ($customers as $i => $c) {
                    $no = $i + 1;
                    $st = ($c['status'] === 'active') ? 'Aktif' : 'Nonaktif';
                    if ($c['status'] === 'active') {
                        $activeCount++;
                    } else {
                        $inactiveCount++;
                    }
                    $custList .= "{$no}. **{$c['name']}** ({$c['email']}, Telp: {$c['phone']}) [Status: {$st}]\n";
                }

                $userCounts = $db->query("SELECT role, COUNT(*) as cnt FROM users GROUP BY role")->fetchAll(PDO::FETCH_KEY_PAIR);
                $techCount = (int)($userCounts['teknisi'] ?? 0);
                $adminCount = (int)($userCounts['admin'] ?? 1);
                $allUsers = $custCount + $techCount + $adminCount;

                return "Saat ini terdapat total **{$custCount} pelanggan (customer)** yang terdaftar di sistem platform Terima Jasa:\n\n" .
                    "• **Total Customer:** {$custCount} orang ({$activeCount} pelanggan aktif, {$inactiveCount} nonaktif)\n" .
                    "• **Total Seluruh Pengguna Sistem:** {$allUsers} akun (termasuk {$techCount} mitra teknisi dan {$adminCount} administrator)\n\n" .
                    "**Daftar Pelanggan Terdaftar:**\n" .
                    $custList . "\n" .
                    "*(Data ini selaras 100% dengan kartu 'TOTAL CUSTOMER' pada Dashboard Administrator)*";
            }
        }

        // 3. Pertanyaan Tarif / Biaya / Layanan
        if (
            str_contains($m, 'biaya') ||
            str_contains($m, 'harga') ||
            str_contains($m, 'tarif') ||
            str_contains($m, 'katalog') ||
            str_contains($m, 'daftar jasa')
        ) {
            if ($db) {
                $services = $db->query("
                    SELECT s.name, s.estimated_price, c.name as category 
                    FROM services s 
                    JOIN categories c ON s.category_id = c.id 
                    WHERE s.status = 'active'
                ")->fetchAll(PDO::FETCH_ASSOC);

                $list = "";
                foreach ($services as $s) {
                    $priceFmt = 'Rp ' . number_format($s['estimated_price'], 0, ',', '.');
                    $list .= "• **{$s['name']}** ({$s['category']}): {$priceFmt}\n";
                }

                return "Berikut adalah estimasi tarif resmi layanan di platform Terima Jasa:\n\n" .
                    $list . "\n" .
                    "Tarif di atas sudah termasuk jaminan garansi resmi 14 hari kalender.";
            }
        }

        // 4. Pertanyaan Pesanan Pribadi User (Customer / Teknisi)
        if (
            str_contains($m, 'pesanan saya') ||
            str_contains($m, 'order saya') ||
            str_contains($m, 'status pesanan') ||
            str_contains($m, 'jadwal servis saya') ||
            str_contains($m, 'pekerjaan saya')
        ) {
            if ($db && $userId) {
                if ($role === 'customer') {
                    $stmt = $db->prepare("
                        SELECT o.id, s.name as service_name, u.name as tech_name, u.phone as tech_phone, o.status, o.schedule, o.location 
                        FROM orders o 
                        JOIN services s ON o.service_id = s.id 
                        LEFT JOIN users u ON o.technician_id = u.id 
                        WHERE o.customer_id = ? 
                        ORDER BY o.id DESC LIMIT 5
                    ");
                    $stmt->execute([$userId]);
                    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (empty($orders)) {
                        return "Halo Kak {$userName}, Anda belum memiliki riwayat pesanan servis saat ini. Anda dapat membuat pesanan baru kapan saja melalui menu **Katalog Jasa**.";
                    }
                    $res = "Berikut daftar pesanan servis Anda (Data Realtime Database):\n\n";
                    foreach ($orders as $o) {
                        $tech = $o['tech_name'] ? "{$o['tech_name']} (WA: {$o['tech_phone']})" : "Menunggu konfirmasi teknisi";
                        $statusLabel = match($o['status']) {
                            'pending'     => 'Menunggu Konfirmasi',
                            'accepted'    => 'Diterima Teknisi',
                            'in_progress' => 'Sedang Dikerjakan',
                            'completed'   => 'Selesai',
                            'cancelled'   => 'Dibatalkan',
                            default       => $o['status']
                        };
                        $res .= "• **Order #ORD-{$o['id']}** ({$o['service_name']})\n" .
                                "  Status: **{$statusLabel}** | Teknisi: {$tech}\n" .
                                "  Jadwal: {$o['schedule']} | Lokasi: {$o['location']}\n";
                    }
                    return $res;
                } elseif ($role === 'teknisi') {
                    $stmt = $db->prepare("
                        SELECT o.id, s.name as service_name, c.name as cust_name, c.phone as cust_phone, o.status, o.schedule, o.location 
                        FROM orders o 
                        JOIN services s ON o.service_id = s.id 
                        JOIN users c ON o.customer_id = c.id 
                        WHERE o.technician_id = ? 
                        ORDER BY o.id DESC LIMIT 5
                    ");
                    $stmt->execute([$userId]);
                    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (empty($jobs)) {
                        return "Rekan Teknisi {$userName}, saat ini Anda belum memiliki pekerjaan servis yang sedang berjalan. Buka menu **Permintaan Masuk** untuk mengambil pesanan baru.";
                    }
                    $res = "Berikut daftar penugasan pekerjaan Anda (Data Realtime Database):\n\n";
                    foreach ($jobs as $j) {
                        $res .= "• **#ORD-{$j['id']}** ({$j['service_name']}) — Pelanggan: {$j['cust_name']} (WA: {$j['cust_phone']}) | Status: **{$j['status']}** | Jadwal: {$j['schedule']}\n";
                    }
                    return $res;
                }
            }

            return "Silakan masuk (login) terlebih dahulu untuk memantau status pesanan dan jadwal servis Anda secara langsung dari database Terima Jasa.";
        }

        // 5. Pertanyaan Ulasan / Review / Testimoni Pelanggan
        if (
            str_contains($m, 'review') ||
            str_contains($m, 'ulasan') ||
            str_contains($m, 'testimoni') ||
            (str_contains($m, 'rating') && !str_contains($m, 'rata-rata'))
        ) {
            if ($db) {
                $reviews = $db->query("
                    SELECT r.rating, r.comment, c.name as customer_name, t.name as tech_name, s.name as service_name 
                    FROM reviews r 
                    JOIN users c ON r.customer_id = c.id 
                    JOIN users t ON r.technician_id = t.id 
                    LEFT JOIN orders o ON r.order_id = o.id 
                    LEFT JOIN services s ON o.service_id = s.id 
                    ORDER BY r.id DESC LIMIT 4
                ")->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($reviews)) {
                    $res = "Berikut ulasan dan testimoni kepuasan pelanggan terbaru dari database Terima Jasa:\n\n";
                    foreach ($reviews as $rv) {
                        $stars = str_repeat('★', (int)$rv['rating']);
                        $res .= "• {$stars} oleh **{$rv['customer_name']}** untuk Teknisi **{$rv['tech_name']}** ({$rv['service_name']}):\n  \"{$rv['comment']}\"\n";
                    }
                    return $res . "\nSeluruh mitra teknisi kami terikat standar SOP kerja profesional dan bergaransi 14 hari.";
                }
            }
        }

        // 6. Pertanyaan Profil & Kontak Teknisi Berdasarkan Nama
        foreach (['hendra', 'ahmad', 'bambang', 'dedi', 'joko'] as $tName) {
            if (str_contains($m, $tName)) {
                if ($db) {
                    $stmt = $db->prepare("
                        SELECT u.name, u.phone, p.expertise, p.experience, p.location, p.availability, p.verification_status 
                        FROM users u 
                        LEFT JOIN technician_profiles p ON u.id = p.user_id 
                        WHERE u.role = 'teknisi' AND LOWER(u.name) LIKE ? LIMIT 1
                    ");
                    $stmt->execute(["%{$tName}%"]);
                    $tech = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($tech) {
                        $avail = ($tech['availability'] === 'available') ? 'Tersedia (Online)' : 'Sedang Bertugas / Istirahat';
                        return "Data Profil Mitra Teknisi **{$tech['name']}** (Database Resmi Terima Jasa):\n\n" .
                            "• **Spesialisasi:** {$tech['expertise']}\n" .
                            "• **Pengalaman Kerja:** {$tech['experience']}\n" .
                            "• **Wilayah Cakupan:** {$tech['location']}\n" .
                            "• **No. Kontak:** {$tech['phone']}\n" .
                            "• **Status:** {$tech['verification_status']} (Ketersediaan: {$avail})\n\n" .
                            "Anda dapat memilih langsung teknisi ini saat membuat pesanan baru.";
                    }
                }
            }
        }

        // 7. Pertanyaan Teknisi Berdasarkan Wilayah / Kota
        if (str_contains($m, 'bandung') || str_contains($m, 'jakarta selatan') || str_contains($m, 'jakarta barat') || str_contains($m, 'lokasi teknisi')) {
            if ($db) {
                $filterLoc = '';
                if (str_contains($m, 'bandung')) $filterLoc = 'Bandung';
                elseif (str_contains($m, 'jakarta selatan')) $filterLoc = 'Jakarta Selatan';
                elseif (str_contains($m, 'jakarta barat')) $filterLoc = 'Jakarta Barat';

                $query = "SELECT u.name, p.expertise, p.location, p.availability FROM users u LEFT JOIN technician_profiles p ON u.id = p.user_id WHERE u.role = 'teknisi'";
                if (!empty($filterLoc)) {
                    $query .= " AND LOWER(p.location) LIKE '%" . strtolower($filterLoc) . "%'";
                }
                $techs = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($techs)) {
                    $locTitle = !empty($filterLoc) ? "di {$filterLoc}" : "berdasarkan wilayah";
                    $res = "Daftar mitra teknisi Terima Jasa {$locTitle}:\n\n";
                    foreach ($techs as $t) {
                        $avail = ($t['availability'] === 'available') ? 'Tersedia' : 'Sibuk';
                        $res .= "• **{$t['name']}** ({$t['location']}) — {$t['expertise']} [Status: {$avail}]\n";
                    }
                    return $res;
                }
            }
        }

        // 8. Pertanyaan Order Pending / Antrean Baru
        if (str_contains($m, 'order pending') || str_contains($m, 'pesanan baru') || str_contains($m, 'antrean') || str_contains($m, 'permintaan masuk')) {
            if ($db) {
                $pendings = $db->query("
                    SELECT o.id, s.name as service_name, c.name as customer_name, o.location, o.schedule 
                    FROM orders o 
                    JOIN services s ON o.service_id = s.id 
                    JOIN users c ON o.customer_id = c.id 
                    WHERE o.status = 'pending' 
                    ORDER BY o.id DESC LIMIT 5
                ")->fetchAll(PDO::FETCH_ASSOC);

                $cnt = count($pendings);
                if ($cnt === 0) {
                    return "Saat ini tidak ada antrean pesanan berstatus Pending di sistem. Semua pesanan telah diterima dan diproses oleh mitra teknisi.";
                }
                $res = "Terdapat **{$cnt} pesanan baru (Pending)** yang menunggu konfirmasi teknisi:\n\n";
                foreach ($pendings as $p) {
                    $res .= "• **#ORD-{$p['id']}** {$p['service_name']} oleh customer {$p['customer_name']} (Wilayah: {$p['location']}, Jadwal: {$p['schedule']})\n";
                }
                return $res;
            }
        }

        // 9. Pertanyaan Jasa Termurah
        if (str_contains($m, 'termurah') || str_contains($m, 'paling murah')) {
            if ($db) {
                $cheapest = $db->query("
                    SELECT s.name, s.estimated_price, c.name as category 
                    FROM services s 
                    JOIN categories c ON s.category_id = c.id 
                    WHERE s.status = 'active' 
                    ORDER BY s.estimated_price ASC LIMIT 3
                ")->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($cheapest)) {
                    $res = "Berikut 3 layanan dengan estimasi tarif paling terjangkau di katalog Terima Jasa:\n\n";
                    foreach ($cheapest as $cs) {
                        $priceFmt = 'Rp ' . number_format($cs['estimated_price'], 0, ',', '.');
                        $res .= "• **{$cs['name']}** ({$cs['category']}): {$priceFmt}\n";
                    }
                    return $res . "\nTarif di atas sudah mencakup jaminan kualitas dan garansi pengerjaan 14 hari.";
                }
            }
        }

        // ════════════════════════════════════════════════════════════
        // PERTANYAAN TEKNIS & SPESIFIK PERAN
        // ════════════════════════════════════════════════════════════

        if ($role === 'teknisi') {
            // 1. Error Daikin E3 & Kode Error AC
            if (str_contains($m, 'e3') || str_contains($m, 'daikin') || (str_contains($m, 'error') && str_contains($m, 'outdoor'))) {
                return "Langkah Troubleshooting Daikin Inverter Error E3 (High Pressure Protection):\n" .
                    "1. **Penyebab Utama:** Tekanan tinggi freon abnormal / proteksi HPS trip, umumnya karena kondensor outdoor sangat kotor atau fan motor outdoor mati/macet.\n" .
                    "2. **Pemeriksaan Visual:** Cek putaran kipas outdoor (pastikan tidak seret) dan sirip kondensor dari debu/lumpur tebal.\n" .
                    "3. **Komponen yang Harus Diukur:**\n" .
                    "   • **Kapasitor Fan Outdoor:** Ukur nilai capacitance (biasanya 1.5–2.5 µF, toleransi ±5%).\n" .
                    "   • **Resistansi Gulungan Motor Fan:** Ukur lilitan Common-Start-Run (nilai biasanya 200–450 Ω).\n" .
                    "   • **Switch High Pressure (HPS):** Kondisi normal harus tertutup (kontinuitas 0 Ω). Jika open (∞), switch rusak atau tekanan freon berlebih.\n" .
                    "   • **Thermistor Pipa Discharge & Outdoor:** Ukur resistansi (sekitar 10–20 kΩ pada suhu 25°C).\n" .
                    "4. **Tindakan:** Cuci kondensor outdoor, cek tekanan freon R32 (normal 130–150 psi pada running), dan ganti fan motor/kapasitor jika terbukti mati.";
            }

            // 2. Template WA Sopan ke Customer (Kompresor Kulkas & Sparepart)
            if (str_contains($m, 'template') || str_contains($m, 'pesan') || str_contains($m, 'wa') || str_contains($m, 'whatsapp')) {
                return "Berikut draf template pesan WhatsApp sopan dan profesional ke customer:\n\n" .
                    "\"Halo Selamat Siang/Sore Bapak/Ibu [Nama Customer], mohon izin mengabarkan hasil pengecekan teknis kulkas Anda oleh teknisi Terima Jasa.\n\n" .
                    "Dari pemeriksaan kelistrikan kompresor, ditemukan bahwa kompresor mengalami macet starting akibat kerusakan pada komponen Relay PTC dan Kapasitor Starting yang sudah aus/short.\n\n" .
                    "Rekomendasi solusi pengerjaan:\n" .
                    "• Penggantian 1 set Relay PTC & Kapasitor Starting original.\n" .
                    "• Pembersihan jalur kelistrikan & uji ampere running.\n" .
                    "• Estimasi biaya sparepart & jasa: Rp 160.000 – Rp 220.000 (bergaransi 14 hari pengerjaan Terima Jasa).\n\n" .
                    "Apakah Bapak/Ibu menyetujui untuk kami lanjutkan proses penggantian komponen tersebut? Terima kasih atas kepercayaannya. 🙏\"\n";
            }

            // 3. Analisis Beban MCB Sering Trip & Uji Grounding
            if (str_contains($m, 'mcb') || str_contains($m, 'trip') || str_contains($m, 'anjlok') || str_contains($m, 'beban') || str_contains($m, 'grounding')) {
                return "Panduan Analisis Beban Listrik & Uji Grounding:\n" .
                    "1. **Analisis Beban Simultan (MCB 10A = Kapasitas Maks ~2.200 VA):**\n" .
                    "   • AC 1 PK normal menarik arus 3.5–4.2 A (tarikan awal kompresor/starting peak bisa mencapai 12–15 A).\n" .
                    "   • Pompa air jet pump menarik 2.5–3.5 A (starting peak 8–10 A).\n" .
                    "   • Jika menyala bersamaan, inrush current sesaat melebihi kurva thermal tripping MCB 10A.\n" .
                    "2. **Pemeriksaan dengan Clamp Meter (Tang Ampere):**\n" .
                    "   • Jepit kabel fasa (L) pada output MCB utama saat beban dinyalakan satu per satu.\n" .
                    "   • Cek apakah ada panas abnormal pada baut terminal MCB (longgar dapat memicu thermal trip prematur).\n" .
                    "3. **Uji Nilai Grounding (Pentanahan):**\n" .
                    "   • Gunakan Earth Tester (metode 3 titik: probe E, P, C). Nilai resistansi grounding standar PUIL adalah < 5 Ohm.\n" .
                    "   • Jika menggunakan multimeter AC: Ukur voltase antara Netral (N) dan Ground (PE), nilai ideal < 2 Volt. Jika > 5 Volt, grounding rumah bermasalah.";
            }

            // 4. Defrost Kulkas Rusak (Freezer Beku Tebal tapi Kulkas Bawah Tidak Dingin)
            if (str_contains($m, 'defrost') || str_contains($m, 'defros') || str_contains($m, 'kulkas') || str_contains($m, 'beku')) {
                return "Pengecekan Komponen Sistem Defrost Kulkas 2 Pintu:\n" .
                    "1. **Bimetal Defrost (Defrost Sensor):** Ukur resistansi saat beku (harus kontinuitas/0 Ω). Pada suhu ruang harus open (tak hingga ∞). Jika tetap open saat beku, bimetal rusak.\n" .
                    "2. **Defrost Heater (Elemen Pemanas):** Ukur hambatan dengan multimeter skala Ohm. Nilai normal adalah 300–450 Ω. Jika terbaca tak hingga (∞), heater putus.\n" .
                    "3. **Thermal Fuse (Sekring Panas 72°C):** Harus terhubung (0 Ω). Jika putus/open, ganti bersamaan dengan pengecekan bimetal.\n" .
                    "4. **Defrost Timer (1-3 atau 1-4) / Sensor NTC Modul:** Ukur motor timer (pin 1-3 resistansi ~2–3 kΩ) dan pastikan mekanisme putaran gear aktif.";
            }

            // 5. SOP K3 Ketinggian & Freon R32
            if (str_contains($m, 'k3') || str_contains($m, 'keselamatan') || str_contains($m, 'ketinggian') || str_contains($m, 'freon') || str_contains($m, 'sop')) {
                return "SOP Keselamatan Kerja (K3) Servis Ketinggian Lantai 2 & Pengisian Freon R32:\n" .
                    "1. **K3 Ketinggian:**\n" .
                    "   • Wajib gunakan Full Body Harness dengan tali lanyard tertambat kuat ke struktur beton/angkur kokoh.\n" .
                    "   • Tangga aluminium harus memiliki kaki anti-slip dan diikat pada sudut aman 75° (perbandingan 4:1) serta dibantu asisten di bawah.\n" .
                    "   • Gunakan helm keselamatan (safety helmet) dan sarung tangan isolasi anti-slip.\n" .
                    "2. **K3 Penanganan Freon R32 (Kategori A2L - Mild Flammable):**\n" .
                    "   • Pastikan ventilasi udara terbuka dan dilarang merokok atau menyalakan api dalam radius 5 meter.\n" .
                    "   • Wajib lakukan proses vakum pipa minimal 15–20 menit hingga jarum manifold mencapai -30 inHg (-0.1 MPa) sebelum pengisian.\n" .
                    "   • Dilarang melakukan flushing dengan oksigen/asetilen ke sistem R32.\n" .
                    "   • Gunakan manifold gauge dan slang khusus rating R32/R410A (tekanan kerja 600–800 psi).";
            }

            // 6. Checklist Pekerjaan & Laporan Selesai
            if (str_contains($m, 'checklist') || str_contains($m, 'laporan') || str_contains($m, 'order') || str_contains($m, 'pesanan')) {
                return "Checklist Pengerjaan & Template Laporan Selesai:\n" .
                    "• **Checklist Akhir:** Pengecekan arus kerja (ampere rating), tes tekanan freon, pengecekan aliran air pembuangan (tidak ada bocor), pembersihan unit indoor & area kerja.\n" .
                    "• **Draf Laporan Customer:** 'Yth. Pelanggan Terima Jasa, servis telah selesai dikerjakan dengan hasil optimal. Tekanan freon dan ampere kompresor dalam batas normal. Pekerjaan dilindungi garansi 14 hari resmi Terima Jasa. Terima kasih!'";
            }

            // 7. Cek Kapasitor Umum
            if (str_contains($m, 'kapasitor') || str_contains($m, 'multimeter') || str_contains($m, 'ukur')) {
                return "Cara cek kapasitor AC dengan multimeter:\n" .
                    "1. **Discharge:** Buang sisa muatan listrik kapasitor menggunakan resistor atau obeng berisolasi.\n" .
                    "2. **Lepas Kabel:** Cabut kabel dari terminal (foto posisi kabel terlebih dahulu).\n" .
                    "3. **Uji µF:** Putar multimeter ke mode Capacitance (µF), hubungkan probe ke terminal. Nilai terukur harus sesuai badan kapasitor (toleransi ±5-10%).\n" .
                    "4. Jika multimeter hanya Ohm (Ω), jarum/angka harus bergerak cepat lalu kembali ke tak hingga (∞). Jika 0 atau putus total, kapasitor rusak.";
            }

            return "Halo Rekan Teknisi {$userName}. Saya AI Copilot Teknisi Terima Jasa. Silakan tanyakan kode error peralatan, nilai komponen, SOP K3, template chat WhatsApp customer, atau checklist servis.";
        }

        if ($role === 'admin') {
            // 1. SOP Verifikasi Sertifikat & KTP Teknisi
            if (str_contains($m, 'verif') || str_contains($m, 'keaslian') || str_contains($m, 'sertifikat') || str_contains($m, 'ktp') || str_contains($m, 'pemalsuan')) {
                return "SOP Standar Verifikasi Mitra Teknisi Baru (Anti-Pemalsuan):\n" .
                    "1. **Validasi NIK KTP (16 Digit):** Verifikasi kesesuaian nama dan NIK dengan data rekening bank pencairan dana mitra (nama bank harus identik 100%).\n" .
                    "2. **Verifikasi Sertifikat Kompetensi:** Cek keabsahan nomor registrasi sertifikat BNSP atau bukti keanggotaan asosiasi teknisi pendingin resmi (APITU/ASISI).\n" .
                    "3. **Audit Portofolio:** Tinjau bukti foto pekerjaan terdahulu dan kelengkapan alat kerja standar (tangga, manifold, vacuum pump, tang ampere).\n" .
                    "4. **Wawancara Singkat:** Lakukan konfirmasi via telepon untuk memverifikasi domisili, kesiapan jam kerja, dan pemahaman SOP K3 Terima Jasa.";
            }

            // 2. Mediasi Komplain Customer & Kebijakan Garansi 14 Hari
            if (str_contains($m, 'komplain') || str_contains($m, 'mediasi') || str_contains($m, 'terlambat') || str_contains($m, 'garansi') || str_contains($m, 'bocor kembali')) {
                return "Protokol Mediasi Komplain & Penegakan Garansi 14 Hari Terima Jasa:\n" .
                    "1. **Aktivasi Klaim Garansi 14 Hari:** Setiap keluhan dalam masa 14 hari kalender pasca pesanan selesai ditangani secara prioritas tanpa biaya tambahan bagi customer.\n" .
                    "2. **Penjadwalan Kunjungan Ulang (Re-Visit):** Hubungi teknisi yang bersangkutan untuk melakukan inspeksi perbaikan ulang dalam kurun waktu maksimal 1x24 jam.\n" .
                    "3. **Investigasi Keterlambatan Teknisi:** Jika teknisi terlambat tanpa pemberitahuan darurat yang valid, berikan catatan peringatan kedisiplinan dan tawarkan voucher kompensasi diskon ke customer.\n" .
                    "4. **Eskalasi Teknisi Pengganti:** Jika teknisi awal tidak responsif dalam 6 jam, segera alihkan re-visit ke teknisi senior terverifikasi lain di area yang sama.";
            }

            // 3. Rekomendasi Tarif Pasar Servis AC & Listrik Jabodetabek
            if (str_contains($m, 'tarif pasar') || str_contains($m, 'rekomendasi tarif') || str_contains($m, 'tarif wajar')) {
                return "Rekomendasi Tarif Pasar Wajar Layanan Servis Wilayah Jabodetabek:\n" .
                    "• **Cuci AC Split Standard (0.5 – 2 PK):** Rp 75.000 – Rp 90.000 per unit (Bongkar talang air + Rp 30.000).\n" .
                    "• **Isi/Tambah Freon R32/R410A:** Rp 150.000 – Rp 250.000 (tergantung tekanan psi yang ditambahkan).\n" .
                    "• **Perbaikan Korsleting Listrik / MCB:** Rp 120.000 – Rp 180.000 per titik pemeriksaan.\n" .
                    "• **Servis Pompa Air & Otomatis:** Rp 110.000 – Rp 160.000 (belum termasuk penggantian dinamo/sparepart besar).\n" .
                    "• **Hardware Laptop Mati Total:** Rp 180.000 – Rp 350.000 untuk jasa diagnosa & pengerjaan jalur power.";
            }

            // 4. Strategi Peningkatan Retensi Repeat-Order
            if (str_contains($m, 'retensi') || str_contains($m, 'repeat') || str_contains($m, 'loyalitas') || str_contains($m, 'pelanggan')) {
                return "Strategi Meningkatkan Retensi Repeat-Order Pelanggan Terima Jasa:\n" .
                    "1. **Siklus Reminder Otomatis:** Kirim notifikasi pengingat cuci AC berkala via WhatsApp setiap 3 bulan sekali sesuai riwayat order customer.\n" .
                    "2. **Program Loyalty & Voucher Diskon:** Berikan voucher potongan jasa 10–15% untuk pemesanan servis kedua pada kategori berbeda.\n" .
                    "3. **Teknisi Favorit (Preferred Partner):** Berikan opsi bagi customer untuk memesan kembali mitra teknisi yang sebelumnya diberi rating ★5.\n" .
                    "4. **Transparansi Garansi 14 Hari:** Tampilkan jaminan garansi di setiap invoice digital untuk meningkatkan rasa aman dan kepercayaan customer.";
            }

            // 5. Draf Surat Peringatan Resmi (SP-1) Mitra Teknisi
            if (str_contains($m, 'sp-1') || str_contains($m, 'sp1') || str_contains($m, 'surat peringatan') || str_contains($m, 'menolak order')) {
                return "Draf Resmi Surat Peringatan (SP-1) untuk Mitra Teknisi:\n\n" .
                    "**SURAT PERINGATAN TINGKAT I (SP-1)**\n" .
                    "Nomor: SP1/TJ-OPS/" . date('Y/m') . "/001\n" .
                    "Kepada Yth: Rekan Mitra Teknisi\n\n" .
                    "Berdasarkan tinjauan data log operasional platform Terima Jasa, tercatat bahwa Anda telah menolak pesanan yang ditugaskan sebanyak 3 (tiga) kali berturut-turut tanpa konfirmasi alasan operasional yang valid.\n\n" .
                    "Tindakan tersebut melanggar Perjanjian Kemitraan Bab IV terkait Tingkat Responsivitas dan Komitmen Penerimaan Order. Dengan surat ini kami memberikan Peringatan Tertulis Pertama (SP-1).\n\n" .
                    "Konsekuensi: Apabila penolakan order kembali terulang dalam 30 hari ke depan, status akun kemitraan Anda akan ditangguhkan sementara (suspend) selama 14 hari kalender.\n\n" .
                    "Departemen Operasional & Hubungan Mitra Terima Jasa";
            }

            return "Halo Admin {$userName}. Saya Admin AI Copilot. Silakan tanyakan evaluasi metrik order, SOP verifikasi teknisi, mediasi sengketa komplain pelanggan, strategi retensi, atau tarif pasar Terima Jasa.";
        }

        if ($role === 'customer') {
            if (str_contains($m, 'ac') && (str_contains($m, 'bocor') || str_contains($m, 'dingin') || str_contains($m, 'air'))) {
                return "Penyebab AC bocor/tidak dingin biasanya karena filter udara kotor atau pipa pembuangan air tersumbat lendir.\n\n" .
                    "• **Solusi:** Cuci AC berkala (estimasi Rp 75.000 di Terima Jasa).\n" .
                    "• **Garansi:** 14 hari bebas bocor dan dingin kembali.\n" .
                    "Pesan langsung melalui menu **Buat Pesanan Baru**.";
            }

            if (str_contains($m, 'laptop') || str_contains($m, 'komputer') || str_contains($m, 'mati')) {
                return "Laptop/komputer yang mati total umumnya disebabkan oleh masalah adaptor charger, short circuit pada jalur IC power motherboard, atau baterai drop.\n\n" .
                    "• **Rekomendasi Layanan:** Perbaikan Hardware Laptop / Komputer Mati Total (Estimasi Rp 180.000).\n" .
                    "• **Garansi:** 14 hari garansi pengerjaan teknisi profesional Terima Jasa.";
            }

            if (str_contains($m, 'mcb') || str_contains($m, 'listrik') || str_contains($m, 'anjlok') || str_contains($m, 'korslet')) {
                return "MCB listrik yang sering anjlok menandakan beban pemakaian melebihi daya terpasang atau terdapat kebocoran arus/korsleting kabel di dalam rumah.\n\n" .
                    "• **Rekomendasi Layanan:** Perbaikan Korsleting Listrik & Pengecekan MCB (Estimasi Rp 120.000).\n" .
                    "• **Garansi:** Teknisi listrik bersertifikat siap melakukan pengecekan beban aman.";
            }

            if (str_contains($m, 'pipa') || str_contains($m, 'air') || str_contains($m, 'bocor') || str_contains($m, 'pompa') || str_contains($m, 'mampet')) {
                return "Saluran air mampet atau pipa bocor memerlukan pembersihan sumbatan atau penggantian sambungan pipa PVC berkualitas.\n\n" .
                    "• **Rekomendasi Layanan:** Plumbing & Servis Saluran Air Mampet / Servis Pompa Air (Estimasi Rp 110.000).\n" .
                    "• **Garansi:** Jaminan aliran lancar dan bebas kebocoran bergaransi 14 hari.";
            }

            return "Halo Kak {$userName}! Saya Asisten AI Terima Jasa. Ceritakan masalah perangkat Anda (AC, listrik, pompa air, kulkas, laptop), saya siap memberikan diagnosa dan estimasi biaya.";
        }

        return "Selamat datang di Terima Jasa. Kami menyediakan layanan servis AC, kelistrikan, pompa air, dan komputer dengan teknisi terverifikasi dan bergaransi resmi.";
    }
}
