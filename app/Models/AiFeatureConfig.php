<?php
namespace App\Models;

use App\Core\Model;

/**
 * AiFeatureConfig Model
 * Konfigurasi Model AI Spesifik per Fitur di Platform Terima Jasa
 */
class AiFeatureConfig extends Model
{
    protected string $table = 'ai_feature_configs';

    protected array $fillable = [
        'feature_key',
        'feature_label',
        'provider_config_id',
        'model_name',
        'is_enabled',
        'custom_parameters',
        'created_at',
        'updated_at'
    ];

    /**
     * Cari konfigurasi berdasarkan feature_key
     */
    public static function findByKey(string $key): ?static
    {
        $db = static::getDb();
        if (!$db) return null;

        try {
            $stmt = $db->prepare("SELECT * FROM `ai_feature_configs` WHERE `feature_key` = ? LIMIT 1");
            $stmt->execute([$key]);
            $data = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $data ? new static($data) : null;
        } catch (\PDOException $e) {
            return null;
        }
    }

    /**
     * Relasi ke AiProviderConfig jika fitur memakai provider spesifik
     */
    public function providerConfig(): ?AiProviderConfig
    {
        if (empty($this->provider_config_id)) return null;
        return AiProviderConfig::find($this->provider_config_id);
    }
}
