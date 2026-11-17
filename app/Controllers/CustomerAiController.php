<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Auth;
use App\Models\AiChat;
use App\Models\Category;
use App\Models\Service;
use App\Services\AIService;

/**
 * CustomerAiController — Mengelola Interaksi Chat Diagnosa AI Customer
 */
class CustomerAiController extends Controller
{
    private AIService $aiService;

    public function __construct()
    {
        $this->aiService = new AIService();
    }

    /**
     * Tampilkan Halaman Chat AI Assistant (/customer/ai-assistant)
     */
    public function index(Request $request): void
    {
        $user = Auth::user();
        $customerId = (int)($user['id'] ?? 0);

        // Ambil riwayat percakapan sebelumnya
        $chatHistory = AiChat::getByCustomerId($customerId);

        // Kategori populer untuk quick prompt suggestion pills
        $categories = Category::all();

        $this->view('customer/ai_assistant/index', [
            'pageTitle'    => 'Asisten AI Diagnosa Kerusakan — Terima Jasa',
            'currentUser'  => $user,
            'chatHistory'  => $chatHistory,
            'categories'   => $categories,
            'hasApiKey'    => $this->aiService->hasApiKey(),
            'aiProvider'   => $this->aiService->getProvider(),
        ], 'dashboard');
    }

    /**
     * Endpoint API Chat AI (Menerima Pertanyaan, Menganalisis, Simpan ke Database)
     * POST /customer/ai-assistant
     */
    public function chat(Request $request): void
    {
        $user = Auth::user();
        $customerId = (int)($user['id'] ?? 0);

        // Tangkap input (bisa form POST atau JSON request)
        $question = trim((string)$request->input('question', ''));
        if (empty($question)) {
            $rawBody = file_get_contents('php://input');
            $jsonData = json_decode($rawBody, true);
            $question = trim((string)($jsonData['question'] ?? ''));
        }

        if (empty($question)) {
            Response::json([
                'success' => false,
                'error'   => 'Mohon tuliskan keluhan atau kendala perangkat Anda terlebih dahulu.'
            ], 400);
            return;
        }

        // Lokasi customer jika ada di data user untuk ranking jarak
        $customerLocation = (string)($user['location'] ?? $user['address'] ?? 'Jakarta Selatan');

        // Panggil AIService untuk menganalisis dan merekomendasikan jasa & teknisi
        $aiAnalysis = $this->aiService->analyzeComplaint($question, $customerLocation);

        // Simpan percakapan ke database (tabel: ai_chats)
        $chatAnswerRecord = $aiAnalysis['chat_formatted_text'] ?? $aiAnalysis['diagnosis_summary'];
        $chatRecord = AiChat::recordChat($customerId, $question, $chatAnswerRecord);

        // Kirim response terstruktur ke UI
        Response::json([
            'success'                 => true,
            'chat_id'                 => $chatRecord->id,
            'question'                => $question,
            'diagnosis_summary'       => $aiAnalysis['diagnosis_summary'],
            'recommended_category'    => $aiAnalysis['recommended_category'],
            'category_id'             => $aiAnalysis['category_id'] ?? 0,
            'recommended_service'     => $aiAnalysis['recommended_service'],
            'explanation'             => $aiAnalysis['explanation'],
            'urgency'                 => $aiAnalysis['urgency'],
            'disclaimer'              => $aiAnalysis['disclaimer'],
            'matched_services'        => $aiAnalysis['matched_services'] ?? [],
            'recommended_technicians' => $aiAnalysis['recommended_technicians'] ?? [],
            'search_url'              => $aiAnalysis['search_url'],
            'is_medical'              => $aiAnalysis['is_medical'] ?? false,
            'source'                  => $aiAnalysis['source'] ?? 'local_fallback',
            'timestamp'               => date('d M Y, H:i') . ' WIB',
        ]);
    }

    /**
     * Hapus Riwayat Chat Sesi Ini
     * POST /customer/ai-assistant/clear
     */
    public function clearHistory(Request $request): void
    {
        $user = Auth::user();
        $customerId = (int)($user['id'] ?? 0);

        if (isset($_SESSION['ai_chat_history'][$customerId])) {
            unset($_SESSION['ai_chat_history'][$customerId]);
        }

        $_SESSION['flash_success'] = 'Riwayat percakapan AI berhasil dibersihkan.';
        $this->redirect('customer/ai-assistant');
    }
}
