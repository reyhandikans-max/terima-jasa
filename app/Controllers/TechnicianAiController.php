<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Auth;
use App\Models\User;
use App\Models\Order;
use App\Models\TechnicianProfile;

/**
 * TechnicianAiController — Asisten AI Khusus Mitra Teknisi (Troubleshooting, SOP K3, Template Chat)
 */
class TechnicianAiController extends Controller
{
    /**
     * Tampilkan Halaman Asisten AI Teknisi
     * GET /technician/ai-assistant
     */
    public function index(Request $request): void
    {
        $user = Auth::user();
        $techId = (int)($user['id'] ?? 0);
        $userModel = User::find($techId);
        $profile = $userModel ? $userModel->technicianProfile() : null;

        // Ambil order aktif teknisi untuk referensi konteks
        $orders = $userModel ? $userModel->technicianOrders() : [];
        $activeOrders = array_filter($orders, fn($o) => in_array($o->status, [Order::STATUS_ACCEPTED, Order::STATUS_IN_PROGRESS]));

        // Riwayat chat teknisi dari session
        $chatHistory = $_SESSION['technician_ai_chat_' . $techId] ?? [];

        $this->view('technician/ai_assistant/index', [
            'pageTitle'    => 'AI Copilot Teknisi — Terima Jasa',
            'currentUser'  => $user,
            'profile'      => $profile,
            'activeOrders' => $activeOrders,
            'chatHistory'  => $chatHistory,
        ], 'dashboard');
    }

    /**
     * Endpoint Chat Khusus Halaman AI Teknisi
     * POST /technician/ai-assistant/chat
     */
    public function chat(Request $request): void
    {
        $user = Auth::user();
        $techId = (int)($user['id'] ?? 0);

        $body = file_get_contents('php://input');
        $payload = json_decode($body, true) ?? [];
        $question = trim((string)($payload['question'] ?? $request->input('question', '')));

        if (empty($question)) {
            Response::json(['success' => false, 'error' => 'Pertanyaan teknis tidak boleh kosong.'], 400);
            return;
        }

        // Out-of-Scope Guard: Tolak pertanyaan di luar tugas servis teknisi (misal: kode Python, resep, dll)
        $chatbotService = new \App\Services\ChatbotService();
        if ($chatbotService->isOutOfScope($question)) {
            $reply = "❌ **Pertanyaan di Luar Cakupan Teknisi**\n\n" .
                     "Maaf, AI Copilot Teknisi hanya membantu pekerjaan perbaikan perangkat di platform **Terima Jasa**.\n\n" .
                     "Saya dapat membantu seputar:\n" .
                     "• Diagnosa teknis kerusakan perangkat (AC, Laptop, MCB Listrik, Pipa, Pompa, Elektronik)\n" .
                     "• Prosedur keselamatan kerja (SOP K3)\n" .
                     "• Status pekerjaan & penugasan order aktif Anda\n" .
                     "• Template komunikasi ramah ke pelanggan\n\n" .
                     "Pertanyaan di luar servis teknis Terima Jasa tidak didukung.";

            if (!isset($_SESSION['technician_ai_chat_' . $techId])) {
                $_SESSION['technician_ai_chat_' . $techId] = [];
            }
            $_SESSION['technician_ai_chat_' . $techId][] = [
                'question'  => $question,
                'reply'     => $reply,
                'timestamp' => date('d M Y, H:i') . ' WIB'
            ];

            Response::json([
                'success'   => true,
                'question'  => $question,
                'reply'     => $reply,
                'source'    => 'scope_guard',
            ]);
            return;
        }

        // Panggil controller chatbot dengan role teknisi
        $chatbot = new ChatbotController();
        $messages = [
            ['role' => 'user', 'content' => $question]
        ];

        // Format payload untuk ChatbotController
        $payloadReq = [
            'messages'  => $messages,
            'role'      => 'teknisi',
            'user_name' => $user['name'] ?? 'Mitra Teknisi',
        ];

        $grounding = $chatbot->fetchDatabaseGrounding($techId, 'teknisi');
        $systemPrompt = [
            'role'    => 'system',
            'content' => "Kamu adalah AI Copilot Resmi Mitra Teknisi Terima Jasa untuk teknisi {$user['name']}.\n" .
                         "Kamu terhubung langsung ke database Terima Jasa untuk membantu pekerjaan teknis di lapangan.\n\n" .
                         $grounding . "\n\n" .
                         "PEDOMAN PENTING GAYA JAWABAN (WAJIB DIPATUHI):\n" .
                         "0. IDENTITAS & SAPAAN: Jika disapa (halo/hai) atau ditanya siapa kamu / tugasmu, jawab dengan ramah bahwa kamu adalah AI Copilot Mitra Teknisi Terima Jasa yang siap membantu panduan teknis, troubleshooting, SOP K3, data pekerjaan aktif, dan tarif resmi di database.\n" .
                         "1. JAWAB TO THE POINT & NYAMBUNG: Langsung ke inti jawaban tanpa basa-basi pengantar formal. Maksimal 2-4 kalimat atau poin-poin padat.\n" .
                         "2. MENJAWAB SEMUA DATA DATABASE: Gunakan fakta dan angka aktual dari database di atas jika ditanya data teknisi, order aktif, profil, atau tarif dasar resmi.\n" .
                         "3. DILARANG MEMBUAT TABEL MARKDOWN LEBAR karena akan terpotong di widget chat. Gunakan bullet point (•) atau penomoran rapi.\n" .
                         "4. BATASAN CAKUPAN: DILARANG membuat kode pemrograman (Python, PHP, JS, dll) atau menjawab topik umum di luar perbaikan teknis Terima Jasa."
        ];

        $groqMessages = [$systemPrompt, ['role' => 'user', 'content' => $question]];
        $apiKey = trim((string)env('AI_API_KEY', env('GROQ_API_KEY', '')));
        $model = trim((string)env('AI_MODEL', 'openai/gpt-oss-120b'));

        $reply = null;
        $source = 'local_engine';

        if (!empty($apiKey) && !str_starts_with($apiKey, 'fake_') && !str_starts_with($apiKey, 'gsk_invalid')) {
            $reply = $chatbot->queryGroq($groqMessages, $apiKey, $model);
            if (!empty($reply)) {
                $source = 'groq_ai';
            }
        }

        if (empty($reply)) {
            $reply = $chatbot->generateRoleFallbackReply('teknisi', $question, $user['name'] ?? 'Rekan Teknisi', $techId);
            $source = 'smart_fallback';
        }

        // Simpan ke sesi riwayat
        if (!isset($_SESSION['technician_ai_chat_' . $techId])) {
            $_SESSION['technician_ai_chat_' . $techId] = [];
        }
        $_SESSION['technician_ai_chat_' . $techId][] = [
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
     * Hapus Riwayat Chat
     * POST /technician/ai-assistant/clear
     */
    public function clearHistory(Request $request): void
    {
        $user = Auth::user();
        $techId = (int)($user['id'] ?? 0);
        unset($_SESSION['technician_ai_chat_' . $techId]);
        $_SESSION['flash_success'] = 'Riwayat percakapan AI Teknisi berhasil dibersihkan.';
        $this->redirect('technician/ai-assistant');
    }
}
