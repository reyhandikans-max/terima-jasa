<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Auth;
use App\Models\AiProviderConfig;
use App\Models\AiFeatureConfig;
use App\Models\AiConnectionLog;
use App\Services\AI\AIConfigurationService;
use App\Services\AI\AIProviderManager;

/**
 * AdminAiSettingsController
 * Pengendali Penuh Konfigurasi AI Platform Terima Jasa (Enterprise Grade)
 */
class AdminAiSettingsController extends Controller
{
    /**
     * Tampilkan Halaman Menu Konfigurasi AI
     * GET /admin/ai-settings
     */
    public function index(Request $request): void
    {
        AIConfigurationService::ensureTablesExist();

        $user = Auth::user();
        $adminId = (int)($user['id'] ?? 0);

        // Ambil konfigurasi provider yang sedang aktif
        $activeConfig = AIConfigurationService::getActiveConfig();

        // Ambil seluruh daftar konfigurasi tersimpan
        $allConfigs = AiProviderConfig::all();
        if (empty($allConfigs) && $activeConfig) {
            $allConfigs = [$activeConfig];
        }

        // Ambil seluruh konfigurasi model per fitur
        $featureConfigs = AiFeatureConfig::all();

        // Ambil riwayat audit pengujian koneksi terbaru
        $recentLogs = AiConnectionLog::getRecent(6);

        // Ambil log status terakhir untuk active provider
        $latestLog = AiConnectionLog::getLatestStatus($activeConfig ? (int)$activeConfig->id : null);

        // Tentukan Connection Status Badge secara akurat
        // Ketentuan: Jangan menampilkan "Terhubung" jika koneksi belum benar-benar diuji!
        $hasKey = false;
        if ($activeConfig) {
            $decrypted = AIConfigurationService::decryptApiKey($activeConfig->api_key_encrypted);
            $hasKey = !empty($decrypted);
        }

        if (!$activeConfig || !$hasKey) {
            $connectionStatus = 'Belum Dikonfigurasi';
            $connectionStatusBadge = 'bg-secondary';
        } elseif (!$latestLog) {
            $connectionStatus = 'Tidak Terhubung (Belum Diuji)';
            $connectionStatusBadge = 'bg-warning text-dark';
        } elseif ($latestLog['status'] === 'success') {
            $connectionStatus = 'Terhubung';
            $connectionStatusBadge = 'bg-success';
        } else {
            $connectionStatus = 'Gagal';
            $connectionStatusBadge = 'bg-danger';
        }

        $serviceActive = $activeConfig && (int)$activeConfig->is_active === 1;

        // Masked API Key untuk tampilan keamanan (jangan kirim kunci riil ke frontend)
        $maskedKey = '';
        if ($hasKey) {
            $decrypted = AIConfigurationService::decryptApiKey($activeConfig->api_key_encrypted);
            $len = strlen($decrypted);
            $maskedKey = ($len > 10) 
                ? substr($decrypted, 0, 5) . str_repeat('•', max(8, $len - 9)) . substr($decrypted, -4)
                : str_repeat('•', 10);
        }

        $supportedProviders = AIProviderManager::getSupportedProviders();

        $this->view('admin/settings/ai_model', [
            'pageTitle'              => 'Konfigurasi AI — Panel Administrator Terima Jasa',
            'currentUser'            => $user,
            'activeConfig'           => $activeConfig,
            'allConfigs'             => $allConfigs,
            'featureConfigs'         => $featureConfigs,
            'recentLogs'             => $recentLogs,
            'latestLog'              => $latestLog,
            'connectionStatus'       => $connectionStatus,
            'connectionStatusBadge'  => $connectionStatusBadge,
            'serviceActive'          => $serviceActive,
            'hasApiKey'              => $hasKey,
            'maskedApiKey'           => $maskedKey,
            'supportedProviders'     => $supportedProviders,
        ], 'dashboard');
    }

    /**
     * Simpan / Perbarui Konfigurasi Provider AI
     * POST /admin/ai-settings
     */
    public function update(Request $request): void
    {
        AIConfigurationService::ensureTablesExist();

        $configId    = (int)$request->input('config_id', 0);
        $name        = trim((string)$request->input('name', 'AI Utama Terima Jasa'));
        $provider    = strtolower(trim((string)$request->input('provider', 'groq')));
        $baseUrl     = trim((string)$request->input('base_url', ''));
        $apiKeyInput = trim((string)$request->input('api_key', ''));
        $model       = trim((string)$request->input('model', ''));
        $customModel = trim((string)$request->input('custom_model', ''));
        $temperature = (float)$request->input('temperature', 0.30);
        $maxTokens   = (int)$request->input('max_output_tokens', 800);
        $topP        = (float)$request->input('top_p', 1.00);
        $timeout     = (int)$request->input('timeout_seconds', 15);
        $retryCount  = (int)$request->input('retry_count', 2);
        $isActive    = $request->input('is_active') ? 1 : 0;

        // Model name resolution
        $finalModel = (!empty($customModel) && in_array($model, ['custom', 'custom-model'])) ? $customModel : $model;

        // Validasi input wajib
        if (empty($name)) {
            $_SESSION['flash_error'] = 'Nama konfigurasi wajib diisi.';
            Response::redirect(url('admin/ai-settings'));
            return;
        }

        if (empty($finalModel)) {
            $_SESSION['flash_error'] = 'Nama model AI wajib dipilih atau diisi manual.';
            Response::redirect(url('admin/ai-settings'));
            return;
        }

        // Validasi SSRF untuk Base URL jika diisi atau provider custom
        if (!empty($baseUrl)) {
            $ssrf = AIConfigurationService::validateBaseUrl($baseUrl);
            if (!$ssrf['valid']) {
                $_SESSION['flash_error'] = $ssrf['error'];
                Response::redirect(url('admin/ai-settings'));
                return;
            }
        } else {
            // Isi default URL sesuai provider
            $supported = AIProviderManager::getSupportedProviders();
            $baseUrl = $supported[$provider]['default_url'] ?? 'https://api.openai.com/v1';
        }

        // Validasi batas parameter
        $temperature = max(0.0, min(1.0, $temperature));
        $maxTokens   = max(100, min(4096, $maxTokens));
        $topP        = max(0.1, min(1.0, $topP));
        $timeout     = max(3, min(60, $timeout));
        $retryCount  = max(0, min(5, $retryCount));

        // Ambil record yang sedang diedit (jika ada)
        $existing = ($configId > 0) ? AiProviderConfig::find($configId) : AiProviderConfig::getActive();

        // Penanganan API Key aman:
        // Jika input kosong atau mengandung tanda '•••', pertahankan API key lama
        $encryptedKey = $existing ? $existing->api_key_encrypted : null;
        if (!empty($apiKeyInput) && !str_contains($apiKeyInput, '•••')) {
            $encryptedKey = AIConfigurationService::encryptApiKey($apiKeyInput);
        }

        $data = [
            'name'              => $name,
            'provider'          => $provider,
            'base_url'          => $baseUrl,
            'api_key_encrypted' => $encryptedKey,
            'default_model'     => $finalModel,
            'temperature'       => $temperature,
            'max_output_tokens' => $maxTokens,
            'top_p'             => $topP,
            'timeout_seconds'   => $timeout,
            'retry_count'       => $retryCount,
            'is_active'         => $isActive,
        ];

        if ($existing && $existing->id) {
            $existing->fill($data);
            $saved = $existing->save();
            $activeRecord = $existing;
        } else {
            $newConfig = new AiProviderConfig($data);
            $saved = $newConfig->save();
            $activeRecord = $newConfig;
        }

        if ($saved) {
            if ($isActive && $activeRecord) {
                $activeRecord->makeActive();
            }

            // Sinkronkan ke .env agar file lingkungan lokal tetap konsisten
            $plainKeyForEnv = (!empty($apiKeyInput) && !str_contains($apiKeyInput, '•••')) 
                ? $apiKeyInput 
                : AIConfigurationService::decryptApiKey($encryptedKey);

            $this->syncEnvVariables([
                'AI_PROVIDER'    => $provider,
                'AI_MODEL'       => $finalModel,
                'AI_TEMPERATURE' => (string)$temperature,
                'AI_MAX_TOKENS'  => (string)$maxTokens,
                'AI_API_KEY'     => $plainKeyForEnv,
            ]);

            $_SESSION['flash_success'] = "Konfigurasi AI '{$name}' dengan model [{$finalModel}] berhasil disimpan!";
        } else {
            $_SESSION['flash_error'] = 'Gagal menyimpan konfigurasi ke database.';
        }

        Response::redirect(url('admin/ai-settings'));
    }

    /**
     * Endpoint Uji Koneksi Nyata ke Model AI
     * POST /admin/ai-settings/test
     */
    public function testConnection(Request $request): void
    {
        AIConfigurationService::ensureTablesExist();

        $user = Auth::user();
        $adminId = (int)($user['id'] ?? 0);

        $body = file_get_contents('php://input');
        $payload = json_decode($body, true) ?? [];

        $configId    = (int)($payload['config_id'] ?? $request->input('config_id', 0));
        $provider    = strtolower(trim((string)($payload['provider'] ?? $request->input('provider', ''))));
        $baseUrl     = trim((string)($payload['base_url'] ?? $request->input('base_url', '')));
        $apiKeyInput = trim((string)($payload['api_key'] ?? $request->input('api_key', '')));
        $model       = trim((string)($payload['model'] ?? $request->input('model', '')));
        $temperature = (float)($payload['temperature'] ?? 0.2);

        // Ambil konfigurasi dari DB jika ada config_id
        $dbConfig = ($configId > 0) ? AiProviderConfig::find($configId) : AiProviderConfig::getActive();

        if (empty($provider) && $dbConfig) {
            $provider = $dbConfig->provider;
        }
        if (empty($baseUrl) && $dbConfig) {
            $baseUrl = $dbConfig->base_url;
        }
        if (empty($model) && $dbConfig) {
            $model = $dbConfig->default_model;
        }

        // Resolusi API key (prioritaskan input baru, fallback jika provider sesuai)
        $apiKey = $apiKeyInput;
        if (str_contains($apiKey, '•••')) {
            $apiKey = '';
        }

        if (empty($apiKey) && $dbConfig && strtolower($dbConfig->provider) === $provider) {
            $apiKey = AIConfigurationService::decryptApiKey($dbConfig->api_key_encrypted);
        }

        if (empty($apiKey) && $provider === 'groq') {
            $apiKey = (string)env('AI_API_KEY', env('GROQ_API_KEY', ''));
        }

        if (!empty($baseUrl)) {
            $ssrf = AIConfigurationService::validateBaseUrl($baseUrl);
            if (!$ssrf['valid']) {
                Response::json(['success' => false, 'error' => $ssrf['error'], 'code' => 'SSRF_BLOCKED'], 400);
                return;
            }
        }

        // Buat instance provider adapter
        $adapter = AIProviderManager::create($provider, [
            'api_key'           => $apiKey,
            'base_url'          => $baseUrl,
            'default_model'     => $model,
            'temperature'       => $temperature,
            'max_output_tokens' => 100,
            'timeout_seconds'   => 12,
        ]);

        $testPrompt = "Koneksi sistem Terima Jasa. Berikan konfirmasi dalam 1 kalimat ramah bahasa Indonesia bahwa model ini aktif.";
        $result = $adapter->testConnection($testPrompt);

        // Catat ke Audit Trail Log (Tanpa API Key atau Prompt Rahasia!)
        AIConfigurationService::logConnectionTest([
            'provider_config_id' => $dbConfig ? $dbConfig->id : null,
            'provider_name'      => $provider,
            'model_tested'       => $model,
            'status'             => $result['success'] ? 'success' : 'failed',
            'latency_ms'         => $result['latency_ms'] ?? 0,
            'error_code'         => $result['code'] ?? null,
            'error_message'      => $result['error'] ?? null,
            'tested_by'          => $adminId,
        ]);

        if ($result['success']) {
            Response::json([
                'success'    => true,
                'latency_ms' => $result['latency_ms'],
                'model'      => $model,
                'provider'   => strtoupper($provider),
                'reply'      => $result['reply'],
                'message'    => "Koneksi sukses! Model [{$model}] merespon dalam {$result['latency_ms']} ms."
            ]);
        } else {
            Response::json([
                'success'    => false,
                'latency_ms' => $result['latency_ms'] ?? 0,
                'error'      => $result['error'] ?? 'Gagal menghubungi penyedia AI.',
                'code'       => $result['code'] ?? 'FAILED'
            ], 400);
        }
    }

    /**
     * Endpoint Ambil Daftar Model Asli dari Provider
     * POST /admin/ai-settings/fetch-models
     */
    public function fetchModels(Request $request): void
    {
        AIConfigurationService::ensureTablesExist();

        $body = file_get_contents('php://input');
        $payload = json_decode($body, true) ?? [];

        $provider    = strtolower(trim((string)($payload['provider'] ?? $request->input('provider', 'groq'))));
        $apiKeyInput = trim((string)($payload['api_key'] ?? $request->input('api_key', '')));
        $baseUrl     = trim((string)($payload['base_url'] ?? $request->input('base_url', '')));

        $apiKey = $apiKeyInput;
        if (empty($apiKey) || str_contains($apiKey, '•••')) {
            $active = AIConfigurationService::getActiveConfig();
            if ($active && !empty($active->api_key_encrypted)) {
                $apiKey = AIConfigurationService::decryptApiKey($active->api_key_encrypted);
            }
            if (empty($apiKey)) {
                $apiKey = (string)env('AI_API_KEY', env('GROQ_API_KEY', ''));
            }
        }

        $adapter = AIProviderManager::create($provider, [
            'api_key'  => $apiKey,
            'base_url' => $baseUrl,
        ]);

        if (!$adapter->supportsModelListing()) {
            Response::json([
                'success'  => true,
                'supports' => false,
                'models'   => $adapter->listModels(),
                'message'  => "Provider " . strtoupper($provider) . " tidak menyediakan endpoint daftar model otomatis. Anda dapat mengetikkan nama model secara manual."
            ]);
            return;
        }

        $models = $adapter->listModels();

        Response::json([
            'success'  => true,
            'supports' => true,
            'models'   => $models,
            'count'    => count($models),
            'message'  => "Berhasil memuat " . count($models) . " model dari " . strtoupper($provider)
        ]);
    }

    /**
     * Aktifkan Provider Tertentu
     * POST /admin/ai-settings/activate
     */
    public function activateProvider(Request $request): void
    {
        AIConfigurationService::ensureTablesExist();

        $configId = (int)$request->input('config_id');
        $config = AiProviderConfig::find($configId);

        if (!$config) {
            $_SESSION['flash_error'] = 'Konfigurasi provider tidak ditemukan.';
            Response::redirect(url('admin/ai-settings'));
            return;
        }

        if (empty($config->api_key_encrypted)) {
            $_SESSION['flash_error'] = "Provider '{$config->name}' belum memiliki API Key. Silakan lengkapi konfigurasi sebelum mengaktifkannya.";
            Response::redirect(url('admin/ai-settings'));
            return;
        }

        $config->makeActive();

        // Update runtime env
        $plainKey = AIConfigurationService::decryptApiKey($config->api_key_encrypted);
        $this->syncEnvVariables([
            'AI_PROVIDER'    => $config->provider,
            'AI_MODEL'       => $config->default_model,
            'AI_TEMPERATURE' => (string)$config->temperature,
            'AI_MAX_TOKENS'  => (string)$config->max_output_tokens,
            'AI_API_KEY'     => $plainKey,
        ]);

        $_SESSION['flash_success'] = "Provider '{$config->name}' ({$config->default_model}) kini aktif sebagai mesin AI utama Terima Jasa.";
        Response::redirect(url('admin/ai-settings'));
    }

    /**
     * Toggle Layanan AI Secara Global (Aktif / Nonaktif)
     * POST /admin/ai-settings/toggle-service
     */
    public function toggleAiService(Request $request): void
    {
        AIConfigurationService::ensureTablesExist();

        $active = AiProviderConfig::getActive();

        if ($active && (int)$active->is_active === 1) {
            AiProviderConfig::deactivateAll();
            $_SESSION['flash_success'] = 'Seluruh layanan AI telah dinonaktifkan sementara. Seluruh fitur platform akan beralih ke fallback lokal database.';
        } else {
            // Aktifkan konfigurasi pertama yang tersedia
            $all = AiProviderConfig::all();
            if (!empty($all)) {
                $all[0]->makeActive();
                $_SESSION['flash_success'] = "Layanan AI berhasil diaktifkan kembali menggunakan provider '{$all[0]->name}'.";
            } else {
                $_SESSION['flash_error'] = 'Belum ada konfigurasi provider yang tersimpan.';
            }
        }

        Response::redirect(url('admin/ai-settings'));
    }

    /**
     * Simpan Pengaturan Model Spesifik per Fitur
     * POST /admin/ai-settings/features
     */
    public function updateFeatureConfig(Request $request): void
    {
        AIConfigurationService::ensureTablesExist();

        $featureKey = trim((string)$request->input('feature_key'));
        $isEnabled  = $request->input('is_enabled') ? 1 : 0;
        $modelName  = trim((string)$request->input('model_name', ''));
        $providerId = (int)$request->input('provider_config_id', 0);

        if (empty($featureKey)) {
            $_SESSION['flash_error'] = 'Kunci fitur tidak valid.';
            Response::redirect(url('admin/ai-settings'));
            return;
        }

        $feat = AiFeatureConfig::findByKey($featureKey);
        if ($feat) {
            $feat->is_enabled = $isEnabled;
            $feat->model_name = !empty($modelName) ? $modelName : null;
            $feat->provider_config_id = ($providerId > 0) ? $providerId : null;
            $feat->save();

            $_SESSION['flash_success'] = "Pengaturan fitur '{$feat->feature_label}' berhasil diperbarui.";
        } else {
            $_SESSION['flash_error'] = 'Fitur tidak ditemukan dalam konfigurasi sistem.';
        }

        Response::redirect(url('admin/ai-settings'));
    }

    /**
     * Reset Parameter ke Nilai Default yang Aman
     * POST /admin/ai-settings/reset-defaults
     */
    public function resetParameters(Request $request): void
    {
        AIConfigurationService::ensureTablesExist();

        $configId = (int)$request->input('config_id', 0);
        $config = ($configId > 0) ? AiProviderConfig::find($configId) : AiProviderConfig::getActive();

        if ($config) {
            $config->temperature = 0.30;
            $config->max_output_tokens = 800;
            $config->top_p = 1.00;
            $config->timeout_seconds = 15;
            $config->retry_count = 2;
            $config->save();

            $_SESSION['flash_success'] = "Parameter model berhasil di-reset ke nilai default standar (Temp: 0.3, Max Tokens: 800, Top P: 1.0, Timeout: 15s). Kunci API tetap aman.";
        } else {
            $_SESSION['flash_error'] = 'Konfigurasi tidak ditemukan untuk di-reset.';
        }

        Response::redirect(url('admin/ai-settings'));
    }

    /**
     * Helper sinkronisasi runtime .env
     */
    private function syncEnvVariables(array $data): void
    {
        $root = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2);
        $envPath = $root . '/.env';
        if (!file_exists($envPath)) return;

        $content = file_get_contents($envPath);

        foreach ($data as $k => $v) {
            $k = trim($k);
            $v = trim($v);
            $formatted = (str_contains($v, ' ') || str_contains($v, '#') || str_contains($v, '"'))
                ? '"' . addcslashes($v, '"\\') . '"'
                : $v;

            $pattern = "/^{$k}=.*/m";
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, "{$k}={$formatted}", $content);
            } else {
                $content = rtrim($content) . "\n{$k}={$formatted}\n";
            }

            $_ENV[$k] = $v;
            putenv("{$k}={$v}");
        }

        file_put_contents($envPath, $content);
    }
}
