<?php
namespace App\Models;

use App\Core\Model;

/**
 * AiChat Model
 * Riwayat Percakapan & Konsultasi Customer dengan Asisten AI
 */
class AiChat extends Model
{
    protected string $table = 'ai_chats';

    protected array $fillable = [
        'customer_id',
        'question',
        'answer',
        'created_at'
    ];

    /**
     * Aturan Validasi untuk AiChat
     */
    public static function validationRules(): array
    {
        return [
            'customer_id' => 'required|numeric',
            'question'    => 'required|min:3',
            'answer'      => 'required|min:3'
        ];
    }

    // ========================================================
    // RELATIONSHIPS
    // ========================================================

    /**
     * Customer/User 1 : N AI Chats (Inverse)
     */
    public function customer(): ?User
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Ambil riwayat chat berdasarkan customer_id
     */
    public static function getByCustomerId(int $customerId): array
    {
        $dbChats = static::where('customer_id', $customerId);
        if (!empty($dbChats)) {
            // Urutkan ascending berdasarkan id agar alur chat berurutan dari lama ke baru
            usort($dbChats, fn($a, $b) => (int)$a->id <=> (int)$b->id);
            return $dbChats;
        }

        // Session fallback jika DB kosong atau offline
        $sessionStore = $_SESSION['ai_chat_history'][$customerId] ?? [];
        $models = [];
        foreach ($sessionStore as $data) {
            $m = new static($data);
            $m->id = $data['id'] ?? time();
            $models[] = $m;
        }
        return $models;
    }

    /**
     * Simpan chat baru untuk customer (dengan pencatatan session fallback)
     */
    public static function recordChat(int $customerId, string $question, string $answer): static
    {
        $now = date('Y-m-d H:i:s');
        $chat = new static([
            'customer_id' => $customerId,
            'question'    => $question,
            'answer'      => $answer,
            'created_at'  => $now,
        ]);
        $chat->save();

        // Simpan juga ke session untuk demo & kehandalan sistem
        if (!isset($_SESSION['ai_chat_history'][$customerId])) {
            $_SESSION['ai_chat_history'][$customerId] = [];
        }
        $_SESSION['ai_chat_history'][$customerId][] = [
            'id'          => $chat->id ?: (count($_SESSION['ai_chat_history'][$customerId]) + 1),
            'customer_id' => $customerId,
            'question'    => $question,
            'answer'      => $answer,
            'created_at'  => $now,
        ];

        return $chat;
    }
}
