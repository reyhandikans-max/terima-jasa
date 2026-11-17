<?php
namespace App\Services\AI;

use App\Services\AI\Adapters\GroqProviderAdapter;
use App\Services\AI\Adapters\OpenAIProviderAdapter;
use App\Services\AI\Adapters\GeminiProviderAdapter;
use App\Services\AI\Adapters\AnthropicProviderAdapter;
use App\Services\AI\Adapters\CustomOpenAICompatibleAdapter;

/**
 * AIProviderManager
 * Factory & Registry Pengelola Provider Adapter AI
 */
class AIProviderManager
{
    /**
     * Dapatkan instance provider adapter yang sesuai dengan auto-healing model mismatch
     */
    public static function create(string $provider, array $config = []): AIProviderInterface
    {
        $providerKey = strtolower(trim($provider));
        $model = trim((string)($config['default_model'] ?? ''));

        // Auto-heal model jika model tidak sesuai dengan provider yang dipilih
        if ($providerKey === 'gemini' || $providerKey === 'google') {
            if (empty($model) || str_contains($model, 'llama') || str_contains($model, 'gpt') || str_contains($model, 'claude') || str_contains($model, 'mixtral')) {
                $config['default_model'] = 'gemini-1.5-flash';
            }
            return new GeminiProviderAdapter($config);
        }

        if ($providerKey === 'groq') {
            if (empty($model) || str_contains($model, 'gemini') || str_contains($model, 'claude')) {
                $config['default_model'] = 'llama-3.3-70b-versatile';
            }
            return new GroqProviderAdapter($config);
        }

        if ($providerKey === 'openai') {
            if (empty($model) || str_contains($model, 'gemini') || str_contains($model, 'claude') || str_contains($model, 'llama')) {
                $config['default_model'] = 'gpt-4o-mini';
            }
            return new OpenAIProviderAdapter($config);
        }

        if ($providerKey === 'anthropic' || $providerKey === 'claude') {
            if (empty($model) || !str_contains($model, 'claude')) {
                $config['default_model'] = 'claude-3-5-sonnet-20241022';
            }
            return new AnthropicProviderAdapter($config);
        }

        if ($providerKey === 'nous_research' || $providerKey === 'nous') {
            return new CustomOpenAICompatibleAdapter(array_merge([
                'base_url'      => 'https://api.nousresearch.com/v1',
                'default_model' => !empty($model) ? $model : 'hermes-3-llama-3.1-70b',
            ], $config));
        }

        if ($providerKey === 'local' || $providerKey === 'offline' || $providerKey === 'terima_jasa') {
            return new \App\Services\AI\Adapters\LocalProviderAdapter($config);
        }

        return new CustomOpenAICompatibleAdapter($config);
    }

    /**
     * Daftar provider resmi yang didukung sistem Terima Jasa beserta katalog model bawaan
     */
    public static function getSupportedProviders(): array
    {
        return [
            'groq' => [
                'name'            => 'Groq Cloud (LPU High-Speed)',
                'default_url'     => 'https://api.groq.com/openai/v1',
                'docs_url'        => 'https://console.groq.com/keys',
                'key_prefix'      => 'gsk_',
                'key_placeholder' => 'Contoh: gsk_xxxxxxxxxxxxxxxxxxxx',
                'is_supported'    => true,
                'note'            => 'Rekomendasi utama: Kecepatan ekstra tinggi (~800 token/detik) dan latency sangat rendah.',
                'models'          => [
                    ['id' => 'llama-3.3-70b-versatile', 'name' => 'Llama 3.3 70B Versatile (Rekomendasi Utama)'],
                    ['id' => 'openai/gpt-oss-120b',     'name' => 'GPT-OSS 120B (DeepSeek Reasoning)'],
                    ['id' => 'llama-3.1-8b-instant',    'name' => 'Llama 3.1 8B Instant (Ultra Cepat)'],
                    ['id' => 'mixtral-8x7b-32768',      'name' => 'Mixtral 8x7B (Konteks 32k)'],
                    ['id' => 'qwen/qwen3.8-27b',        'name' => 'Qwen 3.8 27B (Spesialis Teknis)'],
                ]
            ],
            'gemini' => [
                'name'            => 'Google Gemini AI',
                'default_url'     => 'https://generativelanguage.googleapis.com/v1beta',
                'docs_url'        => 'https://aistudio.google.com/app/apikey',
                'key_prefix'      => 'AIza',
                'key_placeholder' => 'Contoh: AIzaSyxxxxxxxxxxxxxxxxxxxx',
                'is_supported'    => true,
                'note'            => 'Pilihan hemat dengan batas kuota gratis melimpah (Google AI Studio).',
                'models'          => [
                    ['id' => 'gemini-1.5-flash',     'name' => 'Gemini 1.5 Flash (Gratis & Sangat Cepat)'],
                    ['id' => 'gemini-1.5-pro',       'name' => 'Gemini 1.5 Pro (Penalaran Mendalam)'],
                    ['id' => 'gemini-2.0-flash-exp', 'name' => 'Gemini 2.0 Flash Experimental'],
                ]
            ],
            'openai' => [
                'name'            => 'OpenAI (Official ChatGPT)',
                'default_url'     => 'https://api.openai.com/v1',
                'docs_url'        => 'https://platform.openai.com/api-keys',
                'key_prefix'      => 'sk-',
                'key_placeholder' => 'Contoh: sk-proj-xxxxxxxxxxxxxxxxxxxx',
                'is_supported'    => true,
                'note'            => 'Kecerdasan penalaran tinggi (GPT-4o & GPT-4o-mini).',
                'models'          => [
                    ['id' => 'gpt-4o-mini',   'name' => 'GPT-4o Mini (Cepat & Hemat Biaya)'],
                    ['id' => 'gpt-4o',        'name' => 'GPT-4o (Flagship Model)'],
                    ['id' => 'gpt-3.5-turbo', 'name' => 'GPT-3.5 Turbo (Klasik)'],
                ]
            ],
            'anthropic' => [
                'name'            => 'Anthropic (Claude)',
                'default_url'     => 'https://api.anthropic.com/v1',
                'docs_url'        => 'https://console.anthropic.com',
                'key_prefix'      => 'sk-ant-',
                'key_placeholder' => 'Contoh: sk-ant-api03-xxxxxxxxxxxxxxxxxxxx',
                'is_supported'    => true,
                'note'            => 'Model Claude 3.5 Sonnet untuk analisis kode dan penalaran terstruktur.',
                'models'          => [
                    ['id' => 'claude-3-5-sonnet-20241022', 'name' => 'Claude 3.5 Sonnet (Unggulan & Akurasi Tinggi)'],
                    ['id' => 'claude-3-5-haiku-20241022',  'name' => 'Claude 3.5 Haiku (Sangat Cepat & Efisien)'],
                    ['id' => 'claude-3-opus-20240229',    'name' => 'Claude 3 Opus (Tugas Kompleks)'],
                ]
            ],
            'nous_research' => [
                'name'            => 'Nous Research (Hermes Engine)',
                'default_url'     => 'https://api.nousresearch.com/v1',
                'docs_url'        => 'https://nousresearch.com',
                'key_prefix'      => '',
                'key_placeholder' => 'Masukkan API Key Nous / OpenRouter',
                'is_supported'    => true,
                'note'            => 'Model open-weight teroptimasi untuk instruction following.',
                'models'          => [
                    ['id' => 'hermes-3-llama-3.1-70b', 'name' => 'Hermes 3 Llama 3.1 70B'],
                    ['id' => 'hermes-3-llama-3.1-8b',  'name' => 'Hermes 3 Llama 3.1 8B'],
                ]
            ],
            'local' => [
                'name'            => 'Terima Jasa Local Engine (Offline & Bebas Kuota)',
                'default_url'     => 'http://localhost/PRD',
                'docs_url'        => '',
                'key_prefix'      => '',
                'key_placeholder' => 'Tidak memerlukan API Key (100% Bebas Kuota & Offline)',
                'is_supported'    => true,
                'note'            => 'Bebas kuota, 100% offline, langsung terhubung ke database fakta Terima Jasa.',
                'models'          => [
                    ['id' => 'terima-jasa-neural-v1',     'name' => 'Terima Jasa Neural Engine v1 (Rekomendasi Lokal)'],
                    ['id' => 'terima-jasa-semantic-fast', 'name' => 'Terima Jasa Semantic Fast (Ultra Ringan)'],
                ]
            ],
            'custom' => [
                'name'            => 'OpenAI-Compatible / Custom / Ollama',
                'default_url'     => 'https://api.openai.com/v1',
                'docs_url'        => '',
                'key_prefix'      => '',
                'key_placeholder' => 'Kunci API (Opsional untuk server lokal/Ollama)',
                'is_supported'    => true,
                'note'            => 'Gunakan untuk proxy pihak ketiga atau server lokal yang mendukung standar OpenAI.',
                'models'          => [
                    ['id' => 'custom-model', 'name' => 'Custom Model Endpoint'],
                ]
            ],
        ];
    }
}
