<?php
namespace App\Helpers;

/**
 * PhoneHelper - Normalisasi, Validasi, dan Masking Nomor WhatsApp Indonesia
 */
class PhoneHelper
{
    /**
     * Normalisasi nomor telepon ke standar E.164 (+628xxxxxxxxx)
     * Mengembalikan null jika nomor tidak valid
     */
    public static function normalize(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Hapus karakter non-digit kecuali tanda plus (+) di awal
        $cleaned = trim($phone);
        $hasPlus = str_starts_with($cleaned, '+');
        $digits = preg_replace('/[^\d]/', '', $cleaned);

        if (empty($digits)) {
            return null;
        }

        // Tangani awalan
        if (str_starts_with($digits, '08')) {
            // Awalan 08xxxxxxxx -> ganti 0 dengan 62
            $normalizedDigits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '628')) {
            // Awalan 628xxxxxxxx
            $normalizedDigits = $digits;
        } elseif ($hasPlus && str_starts_with($digits, '628')) {
            $normalizedDigits = $digits;
        } else {
            // Bukan format seluler Indonesia standar
            return null;
        }

        // Validasi panjang digit nomor seluler Indonesia:
        // Standar ID: 62 + 8 + (7-11 digit berikutnya) = total 10 sampai 14 digit
        if (strlen($normalizedDigits) < 10 || strlen($normalizedDigits) > 15) {
            return null;
        }

        return '+' . $normalizedDigits;
    }

    /**
     * Dapatkan semua variasi representasi nomor untuk pencarian fleksibel di database
     * e.g. ['+6281234567890', '081234567890', '6281234567890']
     */
    public static function getLookupVariants(string $phone): array
    {
        $normalized = self::normalize($phone);
        if (!$normalized) {
            $digits = preg_replace('/[^\d]/', '', $phone);
            return array_filter([$phone, $digits]);
        }

        $e164 = $normalized; // +6281234567890
        $rawWithCountry = substr($e164, 1); // 6281234567890
        $localFormat = '0' . substr($e164, 3); // 081234567890

        return array_values(array_unique([$e164, $localFormat, $rawWithCountry]));
    }

    /**
     * Format tampilan nomor internasional ramah pengguna: +62 812-3456-7890
     */
    public static function formatDisplay(?string $phone): string
    {
        $norm = self::normalize($phone);
        if (!$norm) {
            return (string)$phone;
        }

        // Format: +62 812-3456-7890
        $country = substr($norm, 0, 3); // +62
        $rest = substr($norm, 3); // 81234567890

        $part1 = substr($rest, 0, 3); // 812
        $part2 = substr($rest, 3, 4); // 3456
        $part3 = substr($rest, 7);    // 7890

        return "{$country} {$part1}-{$part2}-{$part3}";
    }

    /**
     * Masking nomor telepon untuk perlindungan privasi: +62 812-****-7890
     */
    public static function mask(?string $phone): string
    {
        $norm = self::normalize($phone);
        if (!$norm) {
            return '••••••••••';
        }

        $country = substr($norm, 0, 3); // +62
        $rest = substr($norm, 3); // 81234567890

        $prefix = substr($rest, 0, 3); // 812
        $suffix = substr($rest, -4);   // 7890

        return "{$country} {$prefix}-••••-{$suffix}";
    }
}
