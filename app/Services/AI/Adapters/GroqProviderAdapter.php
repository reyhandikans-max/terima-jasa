<?php
namespace App\Services\AI\Adapters;

use App\Services\AI\AIProviderInterface;

/**
 * GroqProviderAdapter
 * Adapter resmi untuk Groq Cloud LPU Inference Engine (Ultra Low Latency)
 */
class GroqProviderAdapter implements AIProviderInterface
{
    private string $apiKey;
    private string $baseUrl;
    private string $defaultModel;
    private float $temperature;
    private int $maxTokens;
    private float $topP;
    private int $timeout;

    public function __construct(array $config = [])
    {
        $this->apiKey       = (string)($config['api_key'] ?? env('AI_API_KEY', env('GROQ_API_KEY', '')));
        $this->baseUrl      = rtrim((string)($config['base_url'] ?? 'https://api.groq.com/openai/v1'), '/');
        $this->defaultModel = (string)($config['default_model'] ?? env('AI_MODEL', 'llama-3.3-70b-versatile'));
        $this->temperature  = (float)($config['temperature'] ?? env('AI_TEMPERATURE', 0.3));
        $this->maxTokens    = (int)($config['max_output_tokens'] ?? env('AI_MAX_TOKENS', 800));
        $this->topP         = (float)($config['top_p'] ?? 1.0);
        $this->timeout      = (int)($config['timeout_seconds'] ?? 15);
    }

    public function getProviderName(): string
    {
        return 'groq';
    }

    public function supportsModelListing(): bool
    {
        return true;
    }

    public function chat(array $messages, array $options = []): ?string
    {
        if (empty($this->apiKey)) {
            return null;
        }

        $url = $this->baseUrl . '/chat/completions';
        $model = $options['model'] ?? $this->defaultModel;
        $temp = isset($options['temperature']) ? (float)$options['temperature'] : $this->temperature;
        $max = isset($options['max_tokens']) ? (int)$options['max_tokens'] : $this->maxTokens;

        $payload = [
            'model'       => $model,
            'messages'    => $messages,
            'temperature' => $temp,
            'max_tokens'  => $max,
            'top_p'       => $this->topP,
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $raw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && !empty($raw)) {
            $decoded = json_decode($raw, true);
            $reply = $decoded['choices'][0]['message']['content'] ?? null;
            if ($reply !== null) {
                // Bersihkan tag deep reasoning <think>...</think> jika model reasoning
                $reply = preg_replace('/<think>.*?<\/think>/si', '', $reply);
                return trim($reply);
            }
        }

        return null;
    }

    public function listModels(): array
    {
        if (empty($this->apiKey)) {
            return $this->getStaticFallbackModels();
        }

        $url = $this->baseUrl . '/models';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
        ]);

        $raw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && !empty($raw)) {
            $decoded = json_decode($raw, true);
            if (!empty($decoded['data']) && is_array($decoded['data'])) {
                $models = [];
                foreach ($decoded['data'] as $item) {
                    $id = $item['id'] ?? '';
                    if (!empty($id)) {
                        $models[] = [
                            'id'       => $id,
                            'name'     => $id,
                            'owned_by' => $item['owned_by'] ?? 'Groq',
                            'active'   => (bool)($item['active'] ?? true)
                        ];
                    }
                }
                // Sort by ID
                usort($models, fn($a, $b) => strcmp($a['id'], $b['id']));
                return $models;
            }
        }

        return $this->getStaticFallbackModels();
    }

    public function testConnection(string $testPrompt = ''): array
    {
        if (empty($this->apiKey)) {
            return [
                'success'    => false,
                'latency_ms' => 0,
                'reply'      => null,
                'error'      => 'API Key belum dikonfigurasi untuk provider Groq.',
                'code'       => 'MISSING_API_KEY'
            ];
        }

        $prompt = !empty($testPrompt) ? $testPrompt : "Koneksi sistem Terima Jasa. Berikan konfirmasi dalam 1 kalimat singkat bahwa model Groq ini aktif.";
        $startTime = microtime(true);

        $url = $this->baseUrl . '/chat/completions';
        $payload = [
            'model'       => $this->defaultModel,
            'messages'    => [
                ['role' => 'system', 'content' => 'Kamu adalah asisten diagnostik server Terima Jasa.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.2,
            'max_tokens'  => 80,
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $raw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        $latencyMs = round((microtime(true) - $startTime) * 1000);

        if (!empty($curlErr)) {
            return [
                'success'    => false,
                'latency_ms' => $latencyMs,
                'reply'      => null,
                'error'      => 'Koneksi jaringan terputus (cURL: ' . $curlErr . ').',
                'code'       => 'NETWORK_TIMEOUT'
            ];
        }

        $decoded = json_decode($raw, true);

        if ($httpCode === 200 && !empty($decoded['choices'][0]['message']['content'])) {
            $reply = trim(preg_replace('/<think>.*?<\/think>/si', '', $decoded['choices'][0]['message']['content']));
            return [
                'success'    => true,
                'latency_ms' => $latencyMs,
                'reply'      => $reply,
                'error'      => null,
                'code'       => 'OK'
            ];
        }

        $apiMsg = $decoded['error']['message'] ?? 'Respon tidak dikenal dari Groq.';
        if ($httpCode === 401) {
            $error = 'API Key ditolak (401 Unauthorized). Periksa kembali kredensial Groq API Key Anda.';
            $code  = 'INVALID_API_KEY';
        } elseif ($httpCode === 404) {
            $error = "Model '{$this->defaultModel}' tidak ditemukan di Groq (404 Not Found).";
            $code  = 'MODEL_NOT_FOUND';
        } elseif ($httpCode === 429) {
            $error = 'Batas limit pemanggilan terlampaui (429 Rate Limit Exceeded).';
            $code  = 'RATE_LIMITED';
        } else {
            $error = "HTTP {$httpCode}: {$apiMsg}";
            $code  = 'HTTP_' . $httpCode;
        }

        return [
            'success'    => false,
            'latency_ms' => $latencyMs,
            'reply'      => null,
            'error'      => $error,
            'code'       => $code
        ];
    }

    public function validateConfiguration(array $config): array
    {
        $errors = [];
        if (empty($config['default_model'])) {
            $errors[] = 'Model utama (default model) wajib ditentukan.';
        }
        return ['valid' => empty($errors), 'errors' => $errors];
    }

    private function getStaticFallbackModels(): array
    {
        return [
            ['id' => 'llama-3.3-70b-versatile', 'name' => 'Llama 3.3 70B Versatile', 'owned_by' => 'Meta'],
            ['id' => 'openai/gpt-oss-120b', 'name' => 'GPT-OSS 120B', 'owned_by' => 'OpenAI-OSS'],
            ['id' => 'llama-3.1-8b-instant', 'name' => 'Llama 3.1 8B Instant', 'owned_by' => 'Meta'],
            ['id' => 'mixtral-8x7b-32768', 'name' => 'Mixtral 8x7B (32k)', 'owned_by' => 'Mistral'],
            ['id' => 'qwen/qwen3.8-27b', 'name' => 'Qwen 3.8 27B', 'owned_by' => 'Qwen'],
            ['id' => 'gemma2-9b-it', 'name' => 'Gemma 2 9B', 'owned_by' => 'Google'],
        ];
    }
}
