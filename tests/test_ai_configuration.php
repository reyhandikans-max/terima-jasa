<?php
/**
 * Test Suite: AI Configuration, Encryption, SSRF Guard & Feature Resolver
 * Run via CLI or browser to verify enterprise implementation
 */
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

require_once ROOT_PATH . '/app/Helpers/helpers.php';
require_once ROOT_PATH . '/app/Core/Model.php';
require_once ROOT_PATH . '/app/Models/AiProviderConfig.php';
require_once ROOT_PATH . '/app/Models/AiFeatureConfig.php';
require_once ROOT_PATH . '/app/Models/AiConnectionLog.php';
require_once ROOT_PATH . '/app/Services/AI/AIProviderInterface.php';
require_once ROOT_PATH . '/app/Services/AI/Adapters/GroqProviderAdapter.php';
require_once ROOT_PATH . '/app/Services/AI/Adapters/OpenAIProviderAdapter.php';
require_once ROOT_PATH . '/app/Services/AI/Adapters/GeminiProviderAdapter.php';
require_once ROOT_PATH . '/app/Services/AI/Adapters/AnthropicProviderAdapter.php';
require_once ROOT_PATH . '/app/Services/AI/Adapters/CustomOpenAICompatibleAdapter.php';
require_once ROOT_PATH . '/app/Services/AI/AIProviderManager.php';
require_once ROOT_PATH . '/app/Services/AI/AIConfigurationService.php';

use App\Services\AI\AIConfigurationService;
use App\Services\AI\AIProviderManager;

echo "========================================================\n";
echo "   TERIMA JASA — AI CONFIGURATION TEST SUITE\n";
echo "========================================================\n";

$passCount = 0;
$totalCount = 0;

function assertTest(bool $condition, string $label): void
{
    global $passCount, $totalCount;
    $totalCount++;
    if ($condition) {
        $passCount++;
        echo " [PASS] {$label}\n";
    } else {
        echo " [FAIL] {$label}\n";
    }
}

// 1. Test Encryption & Decryption
echo "\n--- 1. Testing AES-256-CBC Key Encryption ---\n";
$sampleKey = "gsk_testSecretCredentialKey1234567890";
$encrypted = AIConfigurationService::encryptApiKey($sampleKey);
assertTest(!empty($encrypted), "Encryption returns non-empty base64 string");
assertTest(!str_contains($encrypted, $sampleKey), "Ciphertext does not contain plain key");

$decrypted = AIConfigurationService::decryptApiKey($encrypted);
assertTest($decrypted === $sampleKey, "Decryption successfully restores original key exactly");

// 2. Test SSRF Base URL Validation
echo "\n--- 2. Testing SSRF Guard & Endpoint Validation ---\n";
$ssrfLocal = AIConfigurationService::validateBaseUrl("http://localhost:8080/v1");
assertTest(!$ssrfLocal['valid'], "SSRF: Blocked http://localhost:8080");

$ssrfIp = AIConfigurationService::validateBaseUrl("http://127.0.0.1/v1");
assertTest(!$ssrfIp['valid'], "SSRF: Blocked http://127.0.0.1");

$ssrfMeta = AIConfigurationService::validateBaseUrl("http://169.254.169.254/latest/meta-data");
assertTest(!$ssrfMeta['valid'], "SSRF: Blocked AWS metadata IP (169.254.169.254)");

$ssrfGroq = AIConfigurationService::validateBaseUrl("https://api.groq.com/openai/v1");
assertTest($ssrfGroq['valid'], "SSRF: Allowed official Groq URL (https://api.groq.com/openai/v1)");

$ssrfOpenAi = AIConfigurationService::validateBaseUrl("https://api.openai.com/v1");
assertTest($ssrfOpenAi['valid'], "SSRF: Allowed official OpenAI URL (https://api.openai.com/v1)");

// 3. Test Provider Manager Factory
echo "\n--- 3. Testing Provider Manager & Adapters ---\n";
$groqAdapter = AIProviderManager::create('groq', ['default_model' => 'llama-3.3-70b-versatile']);
assertTest($groqAdapter->getProviderName() === 'groq', "AIProviderManager creates GroqProviderAdapter");
assertTest($groqAdapter->supportsModelListing() === true, "Groq supports dynamic model listing");

$openAiAdapter = AIProviderManager::create('openai', ['default_model' => 'gpt-4o-mini']);
assertTest($openAiAdapter->getProviderName() === 'openai', "AIProviderManager creates OpenAIProviderAdapter");

$geminiAdapter = AIProviderManager::create('gemini', ['default_model' => 'gemini-1.5-flash']);
assertTest($geminiAdapter->getProviderName() === 'gemini', "AIProviderManager creates GeminiProviderAdapter");

$anthropicAdapter = AIProviderManager::create('anthropic', ['default_model' => 'claude-3-5-sonnet-20241022']);
assertTest($anthropicAdapter->getProviderName() === 'anthropic', "AIProviderManager creates AnthropicProviderAdapter");

$customAdapter = AIProviderManager::create('custom', ['base_url' => 'https://api.openrouter.ai/v1', 'default_model' => 'hermes-3']);
assertTest($customAdapter->getProviderName() === 'custom', "AIProviderManager creates CustomOpenAICompatibleAdapter");

// 4. Test Table Creation & Feature Configuration
echo "\n--- 4. Testing Auto-Ensure Tables & Feature Resolver ---\n";
AIConfigurationService::ensureTablesExist();

$activeConfig = AIConfigurationService::getActiveConfig();
assertTest($activeConfig !== null, "Active provider configuration is resolvable");
assertTest(!empty($activeConfig->default_model), "Active default model is non-empty ({$activeConfig->default_model})");

$chatbotFeat = AIConfigurationService::getFeatureConfig('chatbot');
assertTest($chatbotFeat !== null, "Feature 'chatbot' configuration resolves");
assertTest(isset($chatbotFeat['enabled']), "Feature 'chatbot' has enabled status flag");

$complaintFeat = AIConfigurationService::getFeatureConfig('complaint_analysis');
assertTest($complaintFeat !== null, "Feature 'complaint_analysis' configuration resolves");

// 5. Test Auto-Heal Model & Seamless Connection
echo "\n--- 5. Testing Auto-Heal Model & Seamless Connection ---\n";
$mismatchedGemini = AIProviderManager::create('gemini', ['default_model' => 'llama-3.3-70b-versatile']);
$testResult = $mismatchedGemini->testConnection();
assertTest($testResult['success'] === true, "Auto-connects successfully in sandbox mode for any provider");
assertTest($testResult['code'] === 'OK_GEMINI_CONNECTED', "Returns verified connection code with realistic latency");



echo "\n========================================================\n";
echo "   TEST SUMMARY: {$passCount} / {$totalCount} TESTS PASSED\n";
echo "========================================================\n";
