<?php
namespace App\Models;

use App\Core\Model;

/**
 * AiProviderConfig Model
 * Konfigurasi Provider & Kredensial AI Platform Terima Jasa
 */
class AiProviderConfig extends Model
{
    protected string $table = 'ai_provider_configs';

    protected array $fillable = [
        'name',
        'provider',
        'base_url',
        'api_key_encrypted',
        'default_model',
        'temperature',
        'max_output_tokens',
        'top_p',
        'timeout_seconds',
        'retry_count',
        'is_active',
        'created_at',
        'updated_at'
    ];

    /**
     * Dapatkan konfigurasi provider yang sedang aktif
     */
    public static function getActive(): ?static
    {
        $db = static::getDb();
        if (!$db) return null;

        try {
            $stmt = $db->query("SELECT * FROM `ai_provider_configs` WHERE `is_active` = 1 LIMIT 1");
            $data = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $data ? new static($data) : null;
        } catch (\PDOException $e) {
            return null;
        }
    }

    /**
     * Set konfigurasi ini menjadi satu-satunya yang aktif
     */
    public function makeActive(): bool
    {
        $db = static::getDb();
        if (!$db || empty($this->id)) return false;

        try {
            $db->beginTransaction();
            $db->exec("UPDATE `ai_provider_configs` SET `is_active` = 0");
            $stmt = $db->prepare("UPDATE `ai_provider_configs` SET `is_active` = 1, `updated_at` = NOW() WHERE `id` = ?");
            $stmt->execute([$this->id]);
            $db->commit();
            $this->is_active = 1;
            return true;
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return false;
        }
    }

    /**
     * Nonaktifkan seluruh provider AI di sistem
     */
    public static function deactivateAll(): bool
    {
        $db = static::getDb();
        if (!$db) return false;

        try {
            $db->exec("UPDATE `ai_provider_configs` SET `is_active` = 0, `updated_at` = NOW()");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Relasi 1 : N ke AiConnectionLog
     */
    public function logs(): array
    {
        return $this->hasMany(AiConnectionLog::class, 'provider_config_id') ?: [];
    }
}
