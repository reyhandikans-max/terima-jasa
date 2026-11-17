<?php
$conn = new mysqli('localhost', 'root', '', 'terima_jasa_db');
if ($conn->connect_error) {
    echo "FAIL: " . $conn->connect_error . "\n";
    exit(1);
}
echo "OK: MySQL connected\n";
$r = $conn->query('SHOW TABLES');
echo "Tables: " . $r->num_rows . "\n";
while ($row = $r->fetch_array()) {
    echo " - " . $row[0] . "\n";
}
$conn->close();

