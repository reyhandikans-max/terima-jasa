<?php
/**
 * Terima Jasa - WhatsApp Gateway & OTP Configuration
 */
return [
    'provider'        => env('WHATSAPP_PROVIDER', 'fonnte'),
    'api_token'       => env('WHATSAPP_API_TOKEN', ''),
    'api_url'         => env('WHATSAPP_API_URL', 'https://api.fonnte.com/send'),
    'sender_id'       => env('WHATSAPP_SENDER_ID', ''),
    'otp_expiry'      => (int)env('OTP_EXPIRY_SECONDS', 300),
    'max_attempts'    => (int)env('OTP_MAX_ATTEMPTS', 5),
    'resend_cooldown' => (int)env('OTP_RESEND_COOLDOWN_SECONDS', 60),
    'rate_limit_ip'   => (int)env('OTP_RATE_LIMIT_IP', 15), // Max OTP requests per hour per IP
    'rate_limit_phone'=> (int)env('OTP_RATE_LIMIT_PHONE', 5), // Max OTP requests per hour per phone
];
