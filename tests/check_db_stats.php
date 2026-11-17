<?php
$conn = new mysqli('localhost', 'root', '', 'terima_jasa_db');
$r = $conn->query('SELECT role, COUNT(*) as n FROM users GROUP BY role');
while ($row = $r->fetch_assoc()) {
    echo $row['role'] . ': ' . $row['n'] . "\n";
}
$r2 = $conn->query('SELECT COUNT(*) as n FROM otp_verifications');
$otp = $r2->fetch_assoc();
echo "OTP Records: " . $otp['n'] . "\n";
$r3 = $conn->query('SELECT COUNT(*) as n FROM ai_chats');
$chats = $r3->fetch_assoc();
echo "AI Chats: " . $chats['n'] . "\n";
$r4 = $conn->query('SELECT COUNT(*) as n FROM services WHERE status="active"');
$svc = $r4->fetch_assoc();
echo "Active Services: " . $svc['n'] . "\n";
$conn->close();

