<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Auth;
use App\Services\WhatsAppService;

/**
 * WhatsAppGatewayController - Manajemen Koneksi WhatsApp Gateway (Fonnte)
 */
class WhatsAppGatewayController extends Controller
{
    private WhatsAppService $whatsAppService;

    public function __construct()
    {
        $this->whatsAppService = new WhatsAppService();
    }

    /**
     * Tampilkan Halaman Manajemen WhatsApp Gateway
     * GET /admin/whatsapp
     */
    public function index(Request $request): void
    {
        $deviceInfo = $this->whatsAppService->getDeviceStatus();

        $this->view('admin/whatsapp_gateway', [
            'pageTitle'   => 'WhatsApp Gateway & OTP — Terima Jasa',
            'deviceInfo'  => $deviceInfo,
            'currentUser' => Auth::user()
        ], 'dashboard');
    }

    /**
     * API Status Perangkat
     * GET /api/admin/whatsapp/status
     */
    public function status(Request $request): void
    {
        $deviceInfo = $this->whatsAppService->getDeviceStatus();
        Response::json([
            'success' => true,
            'data'    => $deviceInfo
        ]);
    }

    /**
     * API Ambil QR Code
     * POST /api/admin/whatsapp/qr
     */
    public function qr(Request $request): void
    {
        $qrData = $this->whatsAppService->getQrCode();
        Response::json([
            'success'   => ($qrData['status'] ?? false) || !empty($qrData['url']),
            'url'       => $qrData['url'] ?? '',
            'connected' => $qrData['connected'] ?? false,
            'message'   => $qrData['message'] ?? ''
        ]);
    }

    /**
     * API Ambil Pairing Code
     * POST /api/admin/whatsapp/pairing-code
     */
    public function pairingCode(Request $request): void
    {
        $phone = (string)$request->input('phone', '085213595836');
        $codeData = $this->whatsAppService->getPairingCode($phone);

        Response::json([
            'success'   => !empty($codeData['code']),
            'code'      => $codeData['code'] ?? '',
            'connected' => $codeData['connected'] ?? false,
            'message'   => $codeData['message'] ?? ''
        ]);
    }

    /**
     * API Test Kirim Pesan
     * POST /api/admin/whatsapp/test-send
     */
    public function testSend(Request $request): void
    {
        $phone   = (string)$request->input('phone', '');
        $message = (string)$request->input('message', 'Halo dari Terima Jasa! Ini adalah pesan uji coba WhatsApp Gateway.');

        if (empty(trim($phone))) {
            Response::json(['success' => false, 'message' => 'Nomor tujuan wajib diisi.'], 422);
            return;
        }

        $result = $this->whatsAppService->sendTextMessage($phone, $message);
        Response::json($result, $result['success'] ? 200 : 400);
    }
}

