<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Auth;
use App\Models\User;
use App\Models\Order;
use App\Models\Category;
use App\Models\Service;
use App\Models\TechnicianProfile;

/**
 * AdminAiController — AI Copilot Khusus Administrator & Manajemen Platform Terima Jasa
 */
class AdminAiController extends Controller
{
    /**
     * Tampilkan Halaman Admin AI Copilot
     * GET /admin/ai-assistant
     */
    public function index(Request $request): void
    {
        $user = Auth::user();
        $adminId = (int)($user['id'] ?? 0);

        // Agregasi metrik platform aktual untuk konteks AI
        $allUsers = User::all();
        $customersCount = count(array_filter($allUsers, fn($u) => $u->role === 'customer'));
        $techniciansCount = count(array_filter($allUsers, fn($u) => $u->role === 'teknisi'));

        $allOrders = Order::all();
        $pendingOrders = count(array_filter($allOrders, fn($o) => $o->status === Order::STATUS_PENDING));
        $activeOrders = count(array_filter($allOrders, fn($o) => in_array($o->status, [Order::STATUS_ACCEPTED, Order::STATUS_IN_PROGRESS])));
        $completedOrders = count(array_filter($allOrders, fn($o) => $o->status === Order::STATUS_COMPLETED));
        $cancelledOrders = count(array_filter($allOrders, fn($o) => $o->status === Order::STATUS_CANCELLED));

        $allProfiles = TechnicianProfile::all();
        $unverifiedTechs = count(array_filter($allProfiles, fn($p) => $p->verification_status === 'pending' || $p->verification_status === 'unverified'));

        $metrics = [
            'total_users'        => count($allUsers),
            'customers'          => $customersCount,
            'technicians'        => $techniciansCount,
            'total_orders'       => count($allOrders),
            'pending_orders'     => $pendingOrders,
            'active_orders'      => $activeOrders,
            'completed_orders'   => $completedOrders,
            'cancelled_orders'   => $cancelledOrders,
            'unverified_techs'   => $unverifiedTechs,
            'categories_count'   => count(Category::all()),
            'services_count'     => count(Service::all()),
        ];

        // Riwayat percakapan admin dari sesi
        $chatHistory = $_SESSION['admin_ai_chat_' . $adminId] ?? [];

        $this->view('admin/ai_assistant/index', [
            'pageTitle'    => 'Admin AI Copilot & Intelligence — Terima Jasa',
            'currentUser'  => $user,
            'metrics'      => $metrics,
            'chatHistory'  => $chatHistory,
        ], 'dashboard');
    }

    /**
     * Endpoint Chat Khusus Halaman AI Admin
     * POST /admin/ai-assistant/chat
     */
    public function chat(Request $request): void
    {
        $user = Auth::user();
        $adminId = (int)($user['id'] ?? 0);

        $body = file_get_contents('php://input');
        $payload = json_decode($body, true) ?? [];
        $question = trim((string)($payload['question'] ?? $request->input('question', '')));

        if (empty($question)) {
            Response::json(['success' => false, 'error' => 'Pertanyaan operasional tidak boleh kosong.'], 400);
            return;
        }

        // Out-of-Scope Guard: Tolak pertanyaan di luar platform Terima Jasa (misal: kode Python, resep, dll)
        $chatbotService = new \App\Services\ChatbotService();
        if ($chatbotService->isOutOfScope($question)) {
            $reply = "❌ **Pertanyaan di Luar Cakupan Operasional**\n\n" .
                     "Maaf, Asisten AI Administrator hanya bertugas untuk operasional dan pemantauan platform **Terima Jasa**.\n\n" .
                     "Saya dapat membantu Anda seputar data nyata di database:\n" .
                     "• Ringkasan metrik platform (total pengguna, teknisi, pesanan)\n" .
                     "• Status verifikasi mitra teknisi\n" .
                     "• Pemantauan pesanan & transaksi aktif\n" .
                     "• Katalog kategori dan tarif jasa resmi\n\n" .
                     "Pertanyaan umum seperti pembuatan kode pemrograman atau topik di luar sistem tidak didukung.";

            if (!isset($_SESSION['admin_ai_chat_' . $adminId])) {
                $_SESSION['admin_ai_chat_' . $adminId] = [];
            }
            $_SESSION['admin_ai_chat_' . $adminId][] = [
                'question'  => $question,
                'reply'     => $reply,
                'timestamp' => date('d M Y, H:i') . ' WIB'
            ];

            Response::json([
                'success'   => true,
                'question'  => $question,
                'reply'     => $reply,
                'source'    => 'scope_guard',
                'model'     => 'scope_guard',
            ]);
            return;
        }

        $chatbot = new ChatbotController();
        $apiKey = trim((string)env('AI_API_KEY', env('GROQ_API_KEY', '')));
        $model = trim((string)env('AI_MODEL', 'openai/gpt-oss-120b'));

        $grounding = $chatbot->fetchDatabaseGrounding($adminId, 'admin');
        $systemPrompt = [
            'role'    => 'system',
            'content' => "[SYSTEM INSTRUCTION & DATA ACCURACY GUARDRAIL]\n\n" .
                         "Peran: Anda adalah Asisten AI Copilot Resmi Dashboard Administrator \"Terima Jasa\" (berbicara dengan Administrator {$user['name']}).\n" .
                         "Anda terhubung langsung dan real-time ke database operasional Terima Jasa.\n\n" .
                         "Aturan Utama:\n\n" .
                         "0. Identitas & Sapaan:\n" .
                         "- Jika admin menyapa (halo/hai) atau menanyakan siapa Anda / fungsi Anda ('siapa anda', 'siapa kamu', 'kamu bisa apa'):\n" .
                         "  Jawab dengan ramah dan profesional bahwa Anda adalah Admin AI Copilot resmi Terima Jasa yang terhubung ke database realtime untuk memantau metrik platform, data order, verifikasi teknisi, dan performa sistem.\n\n" .
                         "- Jika admin bertanya mengenai identitas dirinya ('siapa saya', 'siapa aku', 'nama saya'):\n" .
                         "  Jawab langsung to the point: 'Anda adalah {$user['name']}, Administrator platform Terima Jasa.'\n\n" .
                         "- Jika admin bertanya 'siapa saja', 'siapa saja customernya', atau meminta daftar pelanggan:\n" .
                         "  Sebutkan langsung daftar nama pelanggan terdaftar dari data database secara to the point. Administrator berhak penuh melihat seluruh data pengguna.\n\n" .
                         "1. Sumber Kebenaran (Single Source of Truth):\n" .
                         "- Selalu gunakan angka agregat/total resmi dari ringkasan statistik Dashboard Administrator (seperti Total Customer, Total Teknisi, Total Order, Order Pending, dll.) sebagai jawaban utama.\n" .
                         "- Jangan pernah menghitung ulang total secara independen hanya berdasarkan potongan data/list yang tampil (misalnya hanya menghitung teknisi yang berstatus verified atau available).\n\n" .
                         "2. Menjawab Semua Pertanyaan Terkait Database:\n" .
                         "- Jawab dengan lengkap dan akurat SEMUA pertanyaan yang berkaitan dengan data di database (tabel users, orders, technician_profiles, categories, services, reviews, metrik dashboard operasional).\n" .
                         "- Jika ditanyakan total data (contoh: \"berapa total teknisi\"), sebutkan angka Total Teknisi keseluruhan yang terdaftar di sistem terlebih dahulu, lalu rincikan statusnya jika relevan.\n\n" .
                         "3. Konsistensi & Transparansi:\n" .
                         "- Jika terdapat ketidaksesuaian antara jumlah list yang dikembalikan oleh query dengan angka summary dashboard, utamakan angka summary dashboard dan beri keterangan status ringkasnya secara jelas.\n\n" .
                         "4. Pedoman Gaya & Format Jawaban:\n" .
                         "- JAWAB LANGSUNG TO THE POINT & NYAMBUNG: Langsung ke inti tanpa basa-basi pengantar formal. Maksimal 2-4 kalimat atau poin-poin padat.\n" .
                         "- DILARANG MEMBUAT TABEL MARKDOWN LEBAR karena akan terpotong dan rusak di widget chat. Gunakan bullet point (•) atau penomoran rapi.\n\n" .
                         "5. BATASAN CAKUPAN:\n" .
                         "- Anda bertugas untuk platform Terima Jasa. DILARANG membuat kode pemrograman (Python, PHP, JS, dll) atau menjawab topik umum di luar sistem (seperti resep, soal ujian sekolah).\n" .
                         "- Jika diminta membuat kode pemrograman komputer, tolak dengan sopan bahwa Anda bertugas untuk operasional Terima Jasa.\n\n" .
                         $grounding
        ];

        $groqMessages = [$systemPrompt, ['role' => 'user', 'content' => $question]];
        $activeConfig = \App\Services\AI\AIConfigurationService::getActiveConfig();
        $model = $activeConfig ? $activeConfig->default_model : trim((string)env('AI_MODEL', 'llama-3.3-70b-versatile'));
        $reply = null;
        $source = 'local_engine';

        if ($activeConfig && (int)$activeConfig->is_active === 1) {
            $apiKey = \App\Services\AI\AIConfigurationService::decryptApiKey($activeConfig->api_key_encrypted);
            if (empty($apiKey)) {
                $apiKey = trim((string)env('AI_API_KEY', env('GROQ_API_KEY', '')));
            }

            if (!empty($apiKey)) {
                $adapter = \App\Services\AI\AIProviderManager::create($activeConfig->provider, [
                    'api_key'           => $apiKey,
                    'base_url'          => $activeConfig->base_url,
                    'default_model'     => $model,
                    'temperature'       => (float)$activeConfig->temperature,
                    'max_output_tokens' => (int)$activeConfig->max_output_tokens,
                ]);
                $adapterReply = $adapter->chat($groqMessages, ['model' => $model]);
                if (!empty($adapterReply)) {
                    $reply = $adapterReply;
                    $source = $adapter->getProviderName() . '_ai';
                }
            }
        }

        if (empty($reply)) {
            $apiKey = trim((string)env('AI_API_KEY', env('GROQ_API_KEY', '')));
            if (!empty($apiKey) && !str_starts_with($apiKey, 'fake_') && !str_starts_with($apiKey, 'gsk_invalid')) {
                $reply = $chatbot->queryGroq($groqMessages, $apiKey, $model);
                if (!empty($reply)) {
                    $source = 'groq_ai';
                }
            }
        }

        if (empty($reply)) {
            $reply = $chatbot->generateRoleFallbackReply('admin', $question, $user['name'] ?? 'Administrator', $adminId);
            $source = 'smart_fallback';
        }

        if (!isset($_SESSION['admin_ai_chat_' . $adminId])) {
            $_SESSION['admin_ai_chat_' . $adminId] = [];
        }
        $_SESSION['admin_ai_chat_' . $adminId][] = [
            'question'  => $question,
            'reply'     => $reply,
            'timestamp' => date('d M Y, H:i') . ' WIB'
        ];

        Response::json([
            'success'   => true,
            'question'  => $question,
            'reply'     => $reply,
            'source'    => $source,
            'timestamp' => date('d M Y, H:i') . ' WIB',
        ]);
    }

    /**
     * Hapus Riwayat Chat Admin
     * POST /admin/ai-assistant/clear
     */
    public function clearHistory(Request $request): void
    {
        $user = Auth::user();
        $adminId = (int)($user['id'] ?? 0);
        unset($_SESSION['admin_ai_chat_' . $adminId]);
        $_SESSION['flash_success'] = 'Riwayat percakapan AI Admin berhasil dibersihkan.';
        $this->redirect('admin/ai-assistant');
    }
}
