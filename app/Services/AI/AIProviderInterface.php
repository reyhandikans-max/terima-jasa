<?php
namespace App\Services\AI;

/**
 * AIProviderInterface
 * Kontrak standar untuk seluruh adapter vendor LLM (Groq, OpenAI, Gemini, Anthropic, Custom)
 */
interface AIProviderInterface
{
    /**
     * Dapatkan nama penyedia (e.g., 'groq', 'openai', 'gemini', 'anthropic', 'custom')
     */
    public function getProviderName(): string;

    /**
     * Kirim percakapan / completion ke model AI
     *
     * @param array $messages Daftar pesan [['role' => 'user|system|assistant', 'content' => '...']]
     * @param array $options Opsi parameter: temperature, max_tokens, top_p, model, dll.
     * @return string|null Jawaban teks dari model
     */
    public function chat(array $messages, array $options = []): ?string;

    /**
     * Ambil daftar model aktual yang didukung / tersedia dari provider
     *
     * @return array Daftar model [['id' => '...', 'name' => '...', 'desc' => '...']]
     */
    public function listModels(): array;

    /**
     * Uji koneksi nyata ke model AI dengan latency tracking
     *
     * @param string $testPrompt Prompt uji coba
     * @return array ['success' => bool, 'latency_ms' => int, 'reply' => ?string, 'error' => ?string, 'code' => ?string]
     */
    public function testConnection(string $testPrompt = ''): array;

    /**
     * Validasi kelayakan konfigurasi sebelum disimpan
     *
     * @param array $config Data konfigurasi: provider, base_url, api_key, default_model, dll.
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateConfiguration(array $config): array;

    /**
     * Apakah provider ini mendukung pemanggilan endpoint daftar model otomatis
     */
    public function supportsModelListing(): bool;
}
