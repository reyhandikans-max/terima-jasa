<?php
namespace App\Models;

use App\Core\Model;

/**
 * AiConnectionLog Model
 * Audit Trail & Riwayat Pengujian Koneksi AI
 */
class AiConnectionLog extends Model
{
    protected string $table = 'ai_connection_logs';

    protected array $fillable = [
        'provider_config_id',
        'provider_name',
        'model_tested',
        'status',
        'latency_ms',
        'error_code',
        'error_message',
        'tested_at',
        'tested_by'
    ];

    /**
     * Dapatkan catatan pengujian terbaru
     */
    public static function getRecent(int $limit = 10): array
    {
        $db = static::getDb();
        if (!$db) return [];

        try {
            $stmt = $db->prepare("
                SELECT l.*, u.name AS tester_name 
                FROM `ai_connection_logs` l 
                LEFT JOIN `users` u ON l.tested_by = u.id 
                ORDER BY l.id DESC 
                LIMIT ?
            ");
            $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Ambil status log terakhir untuk provider tertentu
     */
    public static function getLatestStatus(?int $providerConfigId = null): ?array
    {
        $db = static::getDb();
        if (!$db) return null;

        try {
            if ($providerConfigId) {
                $stmt = $db->prepare("SELECT * FROM `ai_connection_logs` WHERE `provider_config_id` = ? ORDER BY `id` DESC LIMIT 1");
                $stmt->execute([$providerConfigId]);
            } else {
                $stmt = $db->query("SELECT * FROM `ai_connection_logs` ORDER BY `id` DESC LIMIT 1");
            }
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\PDOException $e) {
            return null;
        }
    }
}
