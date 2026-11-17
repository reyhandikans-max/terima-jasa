<?php
use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\CustomerDashboardController;
use App\Controllers\CustomerServiceController;
use App\Controllers\CustomerOrderController;
use App\Controllers\TechnicianDashboardController;
use App\Controllers\AdminDashboardController;

/**
 * Web Routes — Terima Jasa
 */

// ----------------------------------------------------------------
// Landing Page
// ----------------------------------------------------------------
Router::get('/', [HomeController::class, 'index']);

// ----------------------------------------------------------------
// Auth Routes (Guest only — logged-in users get redirected away)
// ----------------------------------------------------------------
Router::get('/login', [AuthController::class, 'login']);
Router::post('/login', [AuthController::class, 'postLogin']);
Router::get('/register', [AuthController::class, 'register']);
Router::post('/register', [AuthController::class, 'postRegister']);
Router::get('/logout', [AuthController::class, 'logout']);

// WhatsApp OTP Login Flow (Web Pages)
use App\Controllers\WhatsAppAuthController;
Router::get('/login/whatsapp', [WhatsAppAuthController::class, 'showLogin']);
Router::get('/login/whatsapp/verify', [WhatsAppAuthController::class, 'showVerify']);

// ----------------------------------------------------------------
// Generic /dashboard & /notifications — Protected routes for all roles
// ----------------------------------------------------------------
Router::get('/dashboard', [DashboardController::class, 'index'], ['auth']);
use App\Controllers\NotificationController;
Router::get('/notifications', [NotificationController::class, 'index'], ['auth']);

// ----------------------------------------------------------------
// Customer Routes  — only role:customer may enter
// ----------------------------------------------------------------
Router::get('/customer/dashboard', [CustomerDashboardController::class, 'index'], ['auth', 'role:customer']);

// Customer Services Katalog & Detail
Router::get('/customer/services', [CustomerServiceController::class, 'index'], ['auth', 'role:customer']);
Router::get('/customer/services/{id}', [CustomerServiceController::class, 'show'], ['auth', 'role:customer']);

// Customer Orders: Listing, Create, Store, Show, Cancel, Review
Router::get('/customer/orders', [CustomerOrderController::class, 'index'], ['auth', 'role:customer']);
Router::get('/customer/orders/create', [CustomerOrderController::class, 'create'], ['auth', 'role:customer']);
Router::post('/customer/orders', [CustomerOrderController::class, 'store'], ['auth', 'role:customer']);
Router::get('/customer/orders/{id}', [CustomerOrderController::class, 'show'], ['auth', 'role:customer']);
Router::post('/customer/orders/{id}/cancel', [CustomerOrderController::class, 'cancel'], ['auth', 'role:customer']);
Router::post('/customer/orders/{id}/review', [CustomerOrderController::class, 'storeReview'], ['auth', 'role:customer']);

// AI Assistant Konsultasi & Diagnosa (Prompt 7)
use App\Controllers\CustomerAiController;
Router::get('/customer/ai-assistant', [CustomerAiController::class, 'index'], ['auth', 'role:customer']);
Router::post('/customer/ai-assistant', [CustomerAiController::class, 'chat'], ['auth', 'role:customer']);
Router::post('/customer/ai-assistant/clear', [CustomerAiController::class, 'clearHistory'], ['auth', 'role:customer']);
// ----------------------------------------------------------------
// Technician Routes — only role:teknisi may enter
// ----------------------------------------------------------------
use App\Controllers\TechnicianProfileController;
use App\Controllers\TechnicianJobController;
Router::get('/technician/dashboard', [TechnicianDashboardController::class, 'index'], ['auth', 'role:teknisi']);

// Profil & Ketersediaan Teknisi
Router::get('/technician/profile', [TechnicianProfileController::class, 'edit'], ['auth', 'role:teknisi']);
Router::post('/technician/profile', [TechnicianProfileController::class, 'update'], ['auth', 'role:teknisi']);
Router::post('/technician/profile/toggle-availability', [TechnicianProfileController::class, 'toggleAvailability'], ['auth', 'role:teknisi']);

// Permintaan Jasa Masuk (Pending)
Router::get('/technician/requests', [TechnicianJobController::class, 'requests'], ['auth', 'role:teknisi']);
Router::post('/technician/orders/{id}/accept', [TechnicianJobController::class, 'accept'], ['auth', 'role:teknisi']);
Router::post('/technician/orders/{id}/reject', [TechnicianJobController::class, 'reject'], ['auth', 'role:teknisi']);

// Pekerjaan Aktif & Transisi Status (accepted -> in_progress -> completed)
Router::get('/technician/active-jobs', [TechnicianJobController::class, 'activeJobs'], ['auth', 'role:teknisi']);
Router::post('/technician/orders/{id}/start', [TechnicianJobController::class, 'start'], ['auth', 'role:teknisi']);
Router::post('/technician/orders/{id}/complete', [TechnicianJobController::class, 'complete'], ['auth', 'role:teknisi']);

// Riwayat Selesai & Rating/Ulasan
Router::get('/technician/history', [TechnicianJobController::class, 'history'], ['auth', 'role:teknisi']);
Router::get('/technician/reviews', [TechnicianJobController::class, 'reviews'], ['auth', 'role:teknisi']);

// AI Copilot Teknisi (Diagnosa Teknis, Kode Error, SOP K3, Template Chat)
use App\Controllers\TechnicianAiController;
Router::get('/technician/ai-assistant', [TechnicianAiController::class, 'index'], ['auth', 'role:teknisi']);
Router::post('/technician/ai-assistant/chat', [TechnicianAiController::class, 'chat'], ['auth', 'role:teknisi']);
Router::post('/technician/ai-assistant/clear', [TechnicianAiController::class, 'clearHistory'], ['auth', 'role:teknisi']);

// ----------------------------------------------------------------
// Admin Routes — only role:admin may enter
// ----------------------------------------------------------------
use App\Controllers\AdminCustomerController;
use App\Controllers\AdminTechnicianController;
use App\Controllers\AdminCategoryController;
use App\Controllers\AdminServiceController;
use App\Controllers\AdminOrderController;
use App\Controllers\AdminReportController;
use App\Controllers\AdminProfileController;

// 1. Dashboard & Profile
Router::get('/admin/dashboard', [AdminDashboardController::class, 'index'], ['auth', 'role:admin']);
Router::get('/admin/profile', [AdminProfileController::class, 'edit'], ['auth', 'role:admin']);
Router::post('/admin/profile', [AdminProfileController::class, 'update'], ['auth', 'role:admin']);

// 2. Customer Management
Router::get('/admin/customers', [AdminCustomerController::class, 'index'], ['auth', 'role:admin']);
Router::get('/admin/customers/{id}', [AdminCustomerController::class, 'show'], ['auth', 'role:admin']);
Router::get('/admin/customers/{id}/edit', [AdminCustomerController::class, 'edit'], ['auth', 'role:admin']);
Router::post('/admin/customers/{id}/edit', [AdminCustomerController::class, 'update'], ['auth', 'role:admin']);
Router::post('/admin/customers/{id}/toggle-status', [AdminCustomerController::class, 'toggleStatus'], ['auth', 'role:admin']);
Router::post('/admin/customers/{id}/delete', [AdminCustomerController::class, 'delete'], ['auth', 'role:admin']);

// 3. Technician Management
Router::get('/admin/technicians', [AdminTechnicianController::class, 'index'], ['auth', 'role:admin']);
Router::get('/admin/technicians/{id}', [AdminTechnicianController::class, 'show'], ['auth', 'role:admin']);
Router::post('/admin/technicians/{id}/verify', [AdminTechnicianController::class, 'verify'], ['auth', 'role:admin']);
Router::post('/admin/technicians/{id}/reject', [AdminTechnicianController::class, 'reject'], ['auth', 'role:admin']);
Router::post('/admin/technicians/{id}/toggle-status', [AdminTechnicianController::class, 'toggleStatus'], ['auth', 'role:admin']);

// 4. Category Management (CRUD)
Router::get('/admin/categories', [AdminCategoryController::class, 'index'], ['auth', 'role:admin']);
Router::get('/admin/categories/create', [AdminCategoryController::class, 'create'], ['auth', 'role:admin']);
Router::post('/admin/categories', [AdminCategoryController::class, 'store'], ['auth', 'role:admin']);
Router::get('/admin/categories/{id}/edit', [AdminCategoryController::class, 'edit'], ['auth', 'role:admin']);
Router::post('/admin/categories/{id}', [AdminCategoryController::class, 'update'], ['auth', 'role:admin']);
Router::post('/admin/categories/{id}/delete', [AdminCategoryController::class, 'delete'], ['auth', 'role:admin']);

// 5. Service Management (CRUD)
Router::get('/admin/services', [AdminServiceController::class, 'index'], ['auth', 'role:admin']);
Router::get('/admin/services/create', [AdminServiceController::class, 'create'], ['auth', 'role:admin']);
Router::post('/admin/services', [AdminServiceController::class, 'store'], ['auth', 'role:admin']);
Router::get('/admin/services/{id}/edit', [AdminServiceController::class, 'edit'], ['auth', 'role:admin']);
Router::post('/admin/services/{id}', [AdminServiceController::class, 'update'], ['auth', 'role:admin']);
Router::post('/admin/services/{id}/delete', [AdminServiceController::class, 'delete'], ['auth', 'role:admin']);

// 6. Order Management & Filter
Router::get('/admin/orders', [AdminOrderController::class, 'index'], ['auth', 'role:admin']);
Router::get('/admin/orders/{id}', [AdminOrderController::class, 'show'], ['auth', 'role:admin']);

// 7. Reports
Router::get('/admin/reports', [AdminReportController::class, 'index'], ['auth', 'role:admin']);

// 8. Admin AI Copilot & Platform Intelligence
use App\Controllers\AdminAiController;
Router::get('/admin/ai-assistant', [AdminAiController::class, 'index'], ['auth', 'role:admin']);
Router::post('/admin/ai-assistant/chat', [AdminAiController::class, 'chat'], ['auth', 'role:admin']);
Router::post('/admin/ai-assistant/clear', [AdminAiController::class, 'clearHistory'], ['auth', 'role:admin']);

// 9. Admin AI Model & Provider Configuration
use App\Controllers\AdminAiSettingsController;
Router::get('/admin/ai-settings', [AdminAiSettingsController::class, 'index'], ['auth', 'role:admin']);
Router::post('/admin/ai-settings', [AdminAiSettingsController::class, 'update'], ['auth', 'role:admin']);
Router::post('/admin/ai-settings/test', [AdminAiSettingsController::class, 'testConnection'], ['auth', 'role:admin']);
Router::post('/admin/ai-settings/fetch-models', [AdminAiSettingsController::class, 'fetchModels'], ['auth', 'role:admin']);
Router::post('/admin/ai-settings/activate', [AdminAiSettingsController::class, 'activateProvider'], ['auth', 'role:admin']);
Router::post('/admin/ai-settings/toggle-service', [AdminAiSettingsController::class, 'toggleAiService'], ['auth', 'role:admin']);
Router::post('/admin/ai-settings/features', [AdminAiSettingsController::class, 'updateFeatureConfig'], ['auth', 'role:admin']);
Router::post('/admin/ai-settings/reset-defaults', [AdminAiSettingsController::class, 'resetParameters'], ['auth', 'role:admin']);

// 10. Admin WhatsApp Gateway & OTP Connection
use App\Controllers\WhatsAppGatewayController;
Router::get('/admin/whatsapp', [WhatsAppGatewayController::class, 'index'], ['auth', 'role:admin']);




