<?php
namespace Database\Seeders;

use PDO;

class DatabaseSeeder
{
    public static function run(PDO $pdo): void
    {
        $pdo->beginTransaction();

        try {
            // 1. Seed Users (Total 12 users: 1 Admin, 4 Customer, 7 Teknisi)
            $passwordHash = password_hash('password123', PASSWORD_BCRYPT);

            $stmtUser = $pdo->prepare("
                INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`, `phone`) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    `name` = VALUES(`name`),
                    `email` = VALUES(`email`),
                    `role` = VALUES(`role`),
                    `status` = VALUES(`status`),
                    `phone` = VALUES(`phone`)
            ");

            $users = [
                [1, 'Budi Santoso', 'customer@terimajasa.id', $passwordHash, 'customer', 'active', '081234567890'],
                [2, 'Hendra Wijaya', 'teknisi@terimajasa.id', $passwordHash, 'teknisi', 'active', '082198765432'],
                [3, 'Super Admin Terima Jasa', 'admin@terimajasa.id', $passwordHash, 'admin', 'active', '081100001111'],
                [4, 'Siti Rahmawati', 'siti@customer.id', $passwordHash, 'customer', 'active', '081399887766'],
                [5, 'Ahmad Fauzi (Teknisi Listrik)', 'ahmad@teknisi.id', $passwordHash, 'teknisi', 'active', '081287654321'],
                [6, 'Dimas Pratama', 'dimas@teknisi.id', $passwordHash, 'teknisi', 'active', '085211223344'],
                [7, 'Rian Syahputra', 'rian@teknisi.id', $passwordHash, 'teknisi', 'inactive', '087799001122'],
                [8, 'Dewi Lestari', 'dewi@customer.id', $passwordHash, 'customer', 'active', '081900112233'],
                [9, 'Anton Kusuma', 'anton@customer.id', $passwordHash, 'customer', 'inactive', '082233445566'],
                [10, 'Bambang Irawan, S.Kom', 'bambang@teknisi.id', $passwordHash, 'teknisi', 'active', '081377889900'],
                [11, 'Dedi Setiawan', 'dedi@teknisi.id', $passwordHash, 'teknisi', 'active', '085711223344'],
                [12, 'Joko Susilo', 'joko@teknisi.id', $passwordHash, 'teknisi', 'active', '081822334455']
            ];

            foreach ($users as $u) {
                $stmtUser->execute($u);
            }

            // 2. Seed Technician Profiles (Total 7 teknisi: 5 verified, 1 pending, 1 rejected)
            $stmtTech = $pdo->prepare("
                INSERT INTO `technician_profiles` 
                (`user_id`, `expertise`, `experience`, `phone`, `address`, `location`, `availability`, `verification_status`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    `expertise` = VALUES(`expertise`),
                    `experience` = VALUES(`experience`),
                    `phone` = VALUES(`phone`),
                    `address` = VALUES(`address`),
                    `location` = VALUES(`location`),
                    `availability` = VALUES(`availability`),
                    `verification_status` = VALUES(`verification_status`)
            ");

            $techs = [
                [2, 'Servis AC, Kulkas & Chiller', '6 Tahun', '082198765432', 'Jl. Tebet Raya No. 45', 'Jakarta Selatan', 'available', 'verified'],
                [5, 'Instalasi Listrik & Panel MCB', '4 Tahun', '081287654321', 'Jl. Sukajadi No. 12', 'Bandung', 'available', 'verified'],
                [6, 'Reparasi Mesin Cuci & Pompa Air', '3 Tahun', '085211223344', 'Jl. Kalimalang Raya No. 18', 'Jakarta Timur', 'offline', 'pending'],
                [7, 'Perbaikan Komputer & Jaringan LAN', '2 Tahun', '087799001122', 'Jl. Ahmad Yani No. 5', 'Bekasi', 'busy', 'rejected'],
                [10, 'Spesialis Servis Laptop Mati Total, Motherboard & Komputer PC', '7 Tahun', '081377889900', 'Jl. Fatmawati No. 22', 'Jakarta Selatan', 'available', 'verified'],
                [11, 'Servis Laptop, Ganti LCD & Keyboard, Komputer PC', '3 Tahun', '085711223344', 'Jl. Panjang No. 8', 'Jakarta Barat', 'available', 'verified'],
                [12, 'Plumbing, Pipa Bocor, Saluran Mampet & Pompa Air', '5 Tahun', '081822334455', 'Jl. Bangka Raya No. 15', 'Jakarta Selatan', 'available', 'verified']
            ];

            foreach ($techs as $t) {
                $stmtTech->execute($t);
            }

            // 3. Seed Categories (5 Kategori)
            $stmtCat = $pdo->prepare("
                INSERT INTO `categories` (`id`, `name`, `description`, `status`)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    `name` = VALUES(`name`),
                    `description` = VALUES(`description`),
                    `status` = VALUES(`status`)
            ");

            $categories = [
                [1, 'Servis AC & Pendingin', 'Layanan cuci AC, perbaikan freon, kompresor dan instalasi AC.', 'active'],
                [2, 'Elektronik & TV', 'Perbaikan TV LED, microwave, kulkas, dan audio.', 'active'],
                [3, 'Komputer & Laptop', 'Servis laptop, ganti komponen, instalasi software, jaringan.', 'active'],
                [4, 'Kelistrikan Rumah', 'Perbaikan korsleting, pasang titik lampu, instalasi baru.', 'active'],
                [5, 'Plumbing & Pompa Air', 'Perbaikan pipa bocor, instalasi toren, pompa air jet pump.', 'active']
            ];

            foreach ($categories as $c) {
                $stmtCat->execute($c);
            }

            // 4. Seed Services (Total 12 Layanan Lengkap)
            $stmtService = $pdo->prepare("
                INSERT INTO `services` (`id`, `category_id`, `name`, `description`, `estimated_price`, `status`)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    `category_id` = VALUES(`category_id`),
                    `name` = VALUES(`name`),
                    `description` = VALUES(`description`),
                    `estimated_price` = VALUES(`estimated_price`),
                    `status` = VALUES(`status`)
            ");

            $services = [
                [1, 1, 'Cuci AC Standard 0.5 - 2 PK', 'Pembersihan evaporator, kondensor, dan talang pembuangan air.', 75000.00, 'active'],
                [2, 1, 'Isi & Tambah Freon R32 / R410A', 'Pengisian ulang freon AC inverter / non-inverter.', 150000.00, 'active'],
                [3, 1, 'Bongkar Pasang & Relokasi AC', 'Pelepasan unit lama dan instalasi di lokasi baru, vacuum jalur pipa freon.', 250000.00, 'active'],
                [4, 2, 'Perbaikan Kulkas 1 & 2 Pintu', 'Diagnosa timer defrost, kompresor macet, dan kebocoran pendingin.', 180000.00, 'active'],
                [5, 2, 'Servis TV LED Layar Gelap / Mati Total', 'Perbaikan lampu strip backlight TV LED, modul power supply board.', 200000.00, 'active'],
                [6, 3, 'Instalasi Ulang OS & Optimalisasi Laptop', 'Instalasi bersih Windows 10/11, pembersihan virus, backup data aman.', 100000.00, 'active'],
                [7, 3, 'Ganti Keyboard & Baterai Laptop', 'Penggantian suku cadang keyboard macet dan baterai drop/kembung.', 120000.00, 'active'],
                [8, 4, 'Perbaikan Korsleting Listrik Darurat', 'Pelacakan kabel konslet dalam dinding, penanganan MCB anjlok tiba-tiba.', 120000.00, 'active'],
                [9, 4, 'Pemasangan Titik Lampu & Stop Kontak', 'Penarikan jalur kabel baru standar SNI, instalasi saklar dan stop kontak.', 65000.00, 'active'],
                [10, 5, 'Servis Pompa Air Mati / Cetak-Cetek', 'Perbaikan otomatis pressure switch dan kapasitor dinamo pompa.', 110000.00, 'active'],
                [11, 5, 'Pelancaran Pipa Saluran Air Mampet', 'Pembersihan sumbatan lemak dapur atau kotoran wastafel tanpa bahan kimia berbahaya.', 135000.00, 'active'],
                [12, 3, 'Perbaikan Hardware Laptop / Komputer Mati Total', 'Pemeriksaan jalur power motherboard, perbaikan IC charging dan laptop tidak bisa menyala.', 180000.00, 'active']
            ];

            foreach ($services as $s) {
                $stmtService->execute($s);
            }

            // 5. Seed Orders (Total 6 order: 4 pending, 1 in_progress, 1 completed)
            $stmtOrder = $pdo->prepare("
                INSERT INTO `orders` 
                (`id`, `customer_id`, `technician_id`, `service_id`, `description`, `location`, `schedule`, `status`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    `customer_id` = VALUES(`customer_id`),
                    `technician_id` = VALUES(`technician_id`),
                    `service_id` = VALUES(`service_id`),
                    `description` = VALUES(`description`),
                    `location` = VALUES(`location`),
                    `schedule` = VALUES(`schedule`),
                    `status` = VALUES(`status`)
            ");

            $orders = [
                [
                    1, 1, 2, 1, 
                    'AC kamar utama kurang dingin dan mengeluarkan hembusan bau apek.',
                    'Jl. Kemang Timur No. 10, Jakarta Selatan',
                    '2026-10-10 10:00:00',
                    'completed'
                ],
                [
                    2, 4, 5, 8, 
                    'MCB sering anjlok saat menyalakan dispenser dan mesin cuci secara bersamaan.',
                    'Jl. Cihampelas No. 88, Bandung',
                    '2026-10-11 14:00:00',
                    'in_progress'
                ],
                [
                    3, 1, 2, 2, 
                    'Tambah freon untuk 2 unit AC di ruang tamu.',
                    'Jl. Kemang Timur No. 10, Jakarta Selatan',
                    '2026-10-12 09:30:00',
                    'pending'
                ],
                [
                    4, 4, 10, 12, 
                    'Laptop Asus ROG tiba-tiba mati total setelah di-charge semalaman.',
                    'Jl. Tebet Barat Dalam No. 15, Jakarta Selatan',
                    '2026-10-13 11:00:00',
                    'pending'
                ],
                [
                    5, 8, 11, 6, 
                    'Laptop kantor lemot dan sering bluescreen, butuh instal ulang bersih.',
                    'Jl. Kebon Jeruk No. 4, Jakarta Barat',
                    '2026-10-14 13:00:00',
                    'pending'
                ],
                [
                    6, 1, 12, 10, 
                    'Pompa air jet pump bunyi cetak-cetek tapi air tidak naik ke toren.',
                    'Jl. Mampang Prapatan No. 20, Jakarta Selatan',
                    '2026-10-15 15:30:00',
                    'pending'
                ]
            ];

            foreach ($orders as $o) {
                $stmtOrder->execute($o);
            }

            // 6. Seed Reviews (Order 1 : 1 Review)
            $stmtReview = $pdo->prepare("
                INSERT INTO `reviews` (`order_id`, `customer_id`, `technician_id`, `rating`, `comment`)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    `customer_id` = VALUES(`customer_id`),
                    `technician_id` = VALUES(`technician_id`),
                    `rating` = VALUES(`rating`),
                    `comment` = VALUES(`comment`)
            ");

            $reviews = [
                [1, 1, 2, 5, 'Pelayanan teknisi Hendra sangat rapi dan cepat! AC langsung dingin semriwing. Sangat puas dengan Terima Jasa.'],
                [2, 4, 5, 5, 'Pak Ahmad sangat teliti memperbaiki MCB yang sering anjlok. Mantap!'],
                [3, 1, 10, 5, 'Laptop mati total bisa hidup lagi dengan sempurna. Teknisi Bambang sangat profesional!']
            ];

            foreach ($reviews as $r) {
                $stmtReview->execute($r);
            }

            // 7. Seed AI Chats
            $stmtAi = $pdo->prepare("
                INSERT INTO `ai_chats` (`customer_id`, `question`, `answer`)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    `question` = VALUES(`question`),
                    `answer` = VALUES(`answer`)
            ");

            $chats = [
                [
                    1, 
                    'Kenapa AC saya netes air terus dari indoor unit?',
                    'Tetesan air pada unit indoor AC biasanya disebabkan oleh talang drainase pembuangan yang tersumbat lendir atau lumut debu. Disarankan untuk memesan layanan Cuci AC Standard agar teknisi dapat melakukan flushing pada selang pembuangan.'
                ],
                [
                    1,
                    'Berapa estimasi biaya isi freon AC R32?',
                    'Estimasi biaya pengisian freon R32 di platform Terima Jasa berkisar antara Rp 120.000 hingga Rp 175.000 per unit, sudah termasuk pengecekan manifold dan kebocoran nepel.'
                ]
            ];

            foreach ($chats as $ch) {
                $stmtAi->execute($ch);
            }

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
