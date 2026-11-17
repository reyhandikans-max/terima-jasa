<?php
use App\Core\Router;
use App\Core\Response;
use App\Models\ServiceCategory;
use App\Controllers\ChatbotController;

/**
 * API Routes (Stubs for future microservices / Mobile / AI endpoints)
 */

Router::get('/api/health', function() {
    Response::json([
        'status'  => 'online',
        'service' => 'Terima Jasa Core API',
        'version' => config('app.app_version'),
        'time'    => date('Y-m-d H:i:s')
    ]);
});

Router::get('/api/categories', function() {
    Response::json([
        'status' => 'success',
        'data'   => ServiceCategory::all()
    ]);
});

// ----------------------------------------------------------------
// WhatsApp OTP Authentication Endpoints
// ----------------------------------------------------------------
use App\Controllers\WhatsAppAuthController;

Router::post('/api/auth/whatsapp/request-otp', [WhatsAppAuthController::class, 'requestOtp']);
Router::post('/api/auth/whatsapp/verify-otp', [WhatsAppAuthController::class, 'verifyOtp']);
Router::post('/api/auth/whatsapp/resend-otp', [WhatsAppAuthController::class, 'resendOtp']);
Router::post('/api/auth/logout', [WhatsAppAuthController::class, 'logout']);

// ----------------------------------------------------------------
// AI Chatbot & Conversation Endpoints (Chatbot+)
// ----------------------------------------------------------------
Router::post('/api/chatbot', [ChatbotController::class, 'chat']);
Router::post('/api/ai/chat', [ChatbotController::class, 'chat']);
Router::get('/api/ai/conversations', [ChatbotController::class, 'getConversations']);
Router::get('/api/ai/conversations/{id}/messages', [ChatbotController::class, 'getConversationMessages']);

// ----------------------------------------------------------------
// Notifications Endpoints
// ----------------------------------------------------------------
use App\Controllers\NotificationController;

Router::get('/api/notifications', [NotificationController::class, 'getNotificationsApi']);

// ----------------------------------------------------------------
// WhatsApp Gateway Management Endpoints
// ----------------------------------------------------------------
use App\Controllers\WhatsAppGatewayController;

Router::get('/api/admin/whatsapp/status', [WhatsAppGatewayController::class, 'status'], ['auth', 'role:admin']);
Router::post('/api/admin/whatsapp/qr', [WhatsAppGatewayController::class, 'qr'], ['auth', 'role:admin']);
Router::post('/api/admin/whatsapp/pairing-code', [WhatsAppGatewayController::class, 'pairingCode'], ['auth', 'role:admin']);
Router::post('/api/admin/whatsapp/test-send', [WhatsAppGatewayController::class, 'testSend'], ['auth', 'role:admin']);

Router::get('/api/notifications/unread-count', [NotificationController::class, 'getUnreadCountApi']);
Router::post('/api/notifications/{id}/read', [NotificationController::class, 'markAsReadApi']);
Router::post('/api/notifications/read-all', [NotificationController::class, 'markAllAsReadApi']);



