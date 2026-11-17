# TERIMA JASA — Marketplace Layanan Jasa Servis & Teknisi Terpercaya

> **Tugas Besar / UAS Rekayasa Perangkat Lunak (RPL)**  
> Aplikasi Web Marketplace Terintegrasi dengan Multi-Role Authorization, WhatsApp OTP Authentication, dan Database Grounding AI Copilot (Chatbot+).

---

## 👥 1. Identitas Anggota Kelompok

| No | Nama Lengkap | NIM | Peran / Tanggung Jawab |
|:--:|:-------------|:---:|:-----------------------|
| 1  | [NAMA LENGKAP ANGGOTA 1] | [NIM ANGGOTA 1] | [Role / Full-Stack Developer] |
| 2  | [NAMA LENGKAP ANGGOTA 2] | [NIM ANGGOTA 2] | [Role / Backend & Database Engineer] |
| 3  | [NAMA LENGKAP ANGGOTA 3] | [NIM ANGGOTA 3] | [Role / Frontend & UI/UX Designer] |
| 4  | [NAMA LENGKAP ANGGOTA 4] | [NIM ANGGOTA 4] | [Role / System Analyst & QA Tester] |

- **Nomor Kelompok** : [NOMOR KELOMPOK, contoh: Kelompok 05]
- **Kelas**          : [KELAS, contoh: IF-44-01 / TI-2024]
- **Program Studi**  : [PROGRAM STUDI, contoh: Teknik Informatika / Rekayasa Perangkat Lunak]
- **Fakultas**       : [FAKULTAS]
- **Perguruan Tinggi**: [NAMA UNIVERSITAS / INSTITUT]

---

## 📌 2. Tema & Topik Kelompok

- **Tema Proyek**: *On-Demand Services Marketplace & Intelligent Automation Platform*
- **Judul Proyek**: **TERIMA JASA — Platform Marketplace Layanan Jasa Teknisi Panggilan Berbasis MVC dengan Verifikasi OTP WhatsApp, Multi-Role Access, dan Asisten Cerdas AI Berbasis Database Realtime (Chatbot+)**

---

## 📖 3. Deskripsi Aplikasi

### A. Penjelasan Aplikasi
**TERIMA JASA** adalah platform web *marketplace* modern yang menghubungkan **Customer** (pengguna yang membutuhkan jasa servis perbaikan rumah tangga, kelistrikan, elektronik, dan komputer) dengan **Mitra Teknisi** profesional yang terverifikasi secara legal dan kompeten. Platform ini juga dilengkapi dengan panel **Administrator** untuk monitoring transaksi, verifikasi mitra baru, serta konfigurasi sistem cerdas berbasis AI.

### B. Tujuan Pembuatan Aplikasi
1. Memberikan kemudahan bagi masyarakat dalam menemukan teknisi panggilan yang kredibel dan memiliki keahlian terverifikasi.
2. Membuka peluang kerja dan kemitraan digital bagi para teknisi lokal profesional di Indonesia.
3. Menjamin transparansi biaya servis melalui sistem 3-lapisan harga (*Harga Dasar Database*, *Estimasi AI*, dan *Harga Final Kesepakatan*).
4. Menyediakan perlindungan konsumen berupa jaminan garansi resmi pengerjaan selama 14 hari kalender.
5. Mengintegrasikan teknologi kecerdasan buatan (*Artificial Intelligence*) untuk diagnosa awal keluhan teknis dan konsultasi berbasis data riil aplikasi (*Chatbot+*).

### C. Permasalahan yang Diselesaikan
- **Asimetri Informasi & Tarif Liar**: Menghilangkan ketidakpastian ongkos perbaikan dengan adanya katalog tarif resmi transparan.
- **Kesulitan Menemukan Teknisi Terpercaya**: Menggantikan cara konvensional mencari teknisi lewat selebaran dengan profil terverifikasi, rating bintang, dan testimoni asli.
- **Ketiadaan Garansi Servis**: Memberikan rasa aman kepada pelanggan dengan klausul garansi 14 hari setelah servis diselesaikan.
- **Lambatnya Diagnosa Kerusakan Awal**: AI Assistant membantu pelanggan mendeteksi potensi kerusakan dan langkah pengamanan awal (misal: mematikan MCB saat korsleting) sebelum teknisi tiba di lokasi.

---

## ✨ 4. Fitur-Fitur Utama Aplikasi

Aplikasi dibangun memenuhi **5 Fitur Wajib Rekayasa Perangkat Lunak**:

### 1. Autentikasi & Otorisasi Peran (RBAC)
- **Registrasi & Login**: Tersedia registrasi publik terpisah untuk Customer dan Mitra Teknisi, sedangkan registrasi akun Admin diblokir dari publik demi keamanan.
- **Verifikasi OTP WhatsApp**: Verifikasi kode OTP 6 digit yang dikirimkan secara otomatis via WhatsApp Gateway (Fonnte Provider Adapter) dengan batas masa berlaku 5 menit (*expiry*) dan pembatasan frekuensi kirim (*rate limiting & resend cooldown*).
- **Keamanan Kredensial**: Penyimpanan kata sandi menggunakan hashing aman `Bcrypt` (`$2y$`).
- **Role-Based Access Control (RBAC)**: Pembatasan akses berbasis peran (`customer`, `teknisi`, `admin`) yang dilindungi oleh `AuthMiddleware` dan `RoleMiddleware`. Akses silang (*cross-role*) ditolak secara otomatis.
- **Pengelolaan Profil**: Customer, Teknisi, dan Admin dapat memperbarui nama, email unik, kontak WhatsApp, serta kata sandi akun secara mandiri.

### 2. Modul Customer (Pelanggan)
- **Overview Dashboard**: Menampilkan status pesanan aktif, total transaksi, dan pintasan konsultasi AI.
- **Katalog & Pencarian Jasa**: Pencarian instan berdasarkan nama jasa/deskripsi dan filter kategori (Servis AC, Kelistrikan, Laptop/Komputer, Plumbing, Elektronik).
- **Pemesanan Jasa Transparan**: Form pemesanan dengan penentuan jadwal kedatangan teknisi, alamat, deskripsi kerusakan, serta upload foto unit.
- **Visual Status Tracking (Stepper)**: Pemantauan alur pesanan secara visual (`Pending` $\rightarrow$ `Accepted` $\rightarrow$ `In Progress` $\rightarrow$ `Completed`).
- **Pembatalan Mandiri**: Customer berhak membatalkan pesanan secara mandiri selama status masih `Pending`.
- **Rating & Ulasan Testimoni**: Pemberian rating bintang 1–5 dan ulasan setelah pesanan tuntas (proteksi ketat: 1 order hanya dapat diulas 1 kali oleh pemilik sah).

### 3. Modul Mitra Teknisi
- **Dashboard Teknisi**: Monitoring order masuk, pekerjaan berjalan, estimasi rating performa, dan reputasi.
- **Profil & Ketersediaan**: Manajemen bidang keahlian, pengalaman kerja, wilayah operasional, serta tombol cepat *Quick Toggle Availability* (`Available` / `Unavailable`).
- **Manajemen Permintaan Masuk**: Menerima (*Accept*) atau menolak (*Reject*) order servis yang diajukan oleh customer.
- **Transisi Tahapan Pengerjaan**: Kontrol alur pekerjaan lapangan: Mulai Servis (`In Progress`) hingga Selesaikan Servis (`Completed`).
- **Riwayat Pekerjaan & Testimoni**: Arsip pekerjaan selesai dan rekapitulasi rating ulasan dari seluruh pelanggan.

### 4. Modul Administrator
- **Dashboard Eksekutif**: Monitoring 8 indikator statistik utama (*Total Customer*, *Total Teknisi*, *Teknisi Menunggu Verifikasi*, *Total Order*, *Order Pending*, *Order Aktif*, *Order Selesai*, dan *Katalog Jasa*).
- **Manajemen Customer**: Melihat daftar seluruh pelanggan, detail riwayat belanja, dan status aktif/nonaktif.
- **Verifikasi Mitra Teknisi**: Meninjau berkas pendaftaran mitra dan memverifikasi (*Approve*) atau menolak (*Reject*).
- **Manajemen Kategori & Katalog Jasa (CRUD)**: Tambah, edit, perbarui tarif dasar, dan hapus layanan jasa.
- **Kelola Transaksi Order**: Filter pesanan berdasarkan status transaksi secara multikriteria.
- **Laporan & Statistik**: Rekapitulasi transaksi, performa teknisi terlaris, dan jasa paling diminati.
- **Profil Administrator**: Pengaturan identitas admin (Nama, Email, WhatsApp) dan pembaruan kata sandi sistem.

### 5. Notification System (Sistem Notifikasi Terintegrasi)
- **In-App Real-Time Notification**: Lonceng notifikasi pada navbar dengan polling berkala, badge jumlah belum dibaca, dan fitur *Mark as Read*.
- **Otomatisasi Perubahan Status**: Customer menerima notifikasi instan saat teknisi menerima pesanan dan saat pekerjaan selesai; teknisi menerima notifikasi saat ada permintaan order baru.
- **Deduplikasi Notifikasi**: Perlindungan dari pengiriman notifikasi ganda dalam jendela waktu 10 detik.

### 6. System Configuration (Pengaturan Sistem & Keamanan)
- **AI Settings Hub**: Pengaturan pemilihan provider AI (Groq Cloud, OpenAI, Google Gemini, atau Custom Endpoint).
- **Keamanan Enkripsi Kunci API**: Kunci API AI disimpan terenkripsi dengan algoritma `AES-256-CBC` di basis data.
- **SSRF Protection**: Penyaringan alamat IP privat/link-local (*loopback*, `169.254.x.x`, dll.) pada URL provider AI kustom.

---

## 🤖 5. Fitur AI (Chatbot & Chatbot+)

### A. Konsep & Peran AI dalam Aplikasi
Modul AI pada aplikasi **TERIMA JASA** diposisikan sebagai **Intelligent Service Layer (Asisten Cerdas Sistem)**, bukan pengganti peran pengguna (Admin, Customer, atau Teknisi). AI bertindak sebagai asisten percakapan yang membantu setiap pengguna sesuai kewenangannya.

### B. Ragam Fitur AI Berdasarkan Peran
1. **Chatbot+ Floating Widget (Multi-Role)**:
   - Widget chat mengambang yang dapat diakses di seluruh halaman aplikasi.
   - Otomatis menyesuaikan konteks percakapan sesuai peran pengguna aktif (Guest, Customer, Teknisi, atau Administrator).
   - Menjawab pertanyaan seputar katalog layanan, tarif resmi, profil teknisi terverifikasi, status pesanan aktif, hingga statistik sistem.
2. **AI Diagnosa Kerusakan (Customer AI Assistant)**:
   - Pelanggan dapat menceritakan kendala perangkat (contoh: *"AC netes air dan tidak dingin"* atau *"Laptop mati total setelah kena air"*).
   - AI mendiagnosa kemungkinan kerusakan komponen, memberikan langkah penanganan awal yang aman, merekomendasikan jasa servis di katalog, serta menampilkan estimasi transparansi biaya 3-lapisan.
3. **AI Copilot Lapangan (Teknisi AI Assistant)**:
   - Membantu teknisi mencari arti kode error pendingin/elektronik (misal: *Error E3 Daikin*), panduan keselamatan kerja (SOP K3), serta membuat template pesan konfirmasi kedatangan ke pelanggan.
4. **Admin AI Intelligence Hub (Admin AI Copilot)**:
   - Membantu Administrator menganalisis tren performa order, SOP pemeriksaan berkas verifikasi teknisi baru, serta rekomendasi mediasi komplain garansi.

### C. Teknologi & Model AI yang Digunakan
- **Large Language Model (LLM)**: `llama-3.3-70b-versatile` / `openai/gpt-oss-120b` yang diakses melalui arsitektur berkecepatan tinggi **Groq Cloud LPU**.
- **Multi-Provider Architecture**: Mendukung peralihan provider secara dinamis ke OpenAI (`gpt-4o-mini`) atau Google Gemini.
- **Smart Local Fallback**: Apabila koneksi internet/API AI sedang offline atau kehabisan kuota, sistem otomatis beralih ke `LocalProviderAdapter` berbasis *Rule-Engine* dan pencarian data lokal sehingga asisten tetap dapat melayani pengguna tanpa *error/downtime*.

### D. Arsitektur Integrasi Database (Chatbot+) & Guardrails Keamanan
- **Retrieval-Augmented Generation (RAG) Aman**: AI tidak diberikan akses query SQL langsung ke database (*anti-SQL Injection*). Data disuplai ke *system prompt* melalui Model/Repository internal yang sudah disanitasi dan difilter (*Live Database Grounding*).
- **Anti-IDOR & Minimalisasi Data**: Customer hanya dapat mengakses data pesanannya sendiri; data pribadi sensitif (seperti nomor telepon pribadi dan alamat rumah) disaring sehingga tidak bocor ke publik.
- **Out-of-Scope Guard**: Sistem secara otomatis menolak pertanyaan di luar ruang lingkup aplikasi (misalnya permintaan membuat kode pemrograman Python/JavaScript, resep masakan, atau soal pelajaran sekolah) guna mencegah pemborosan token dan menjaga fokus layanan.
- **Anti-Halusinasi**: AI diinstruksikan menggunakan data resmi database sebagai sumber kebenaran tunggal (*Single Source of Truth*). Jika suatu layanan belum tersedia (misal: servis pesawat/kapal selam), AI jujur menyatakan belum tersedia.
- **To-The-Point Sanitizer**: Respon AI dibersihkan dari salam pengantar berulang yang panjang sehingga langsung menjawab inti pertanyaan pengguna.

---

## 🎥 6. Video Demonstrasi Aplikasi

Tonton video demonstrasi alur kerja lengkap aplikasi **TERIMA JASA** (Registrasi, OTP WhatsApp, Transaksi Customer, Workflow Teknisi, Dashboard Admin, dan Fitur Chatbot+):

👉 **[Link Video Demonstrasi Aplikasi di YouTube](https://www.youtube.com/watch?v=PLACEHOLDER_LINK_VIDEO_YOUTUBE)**  
*(Silakan ganti URL placeholder di atas dengan link video YouTube presentasi kelompok Anda)*

---

## 💻 7. Panduan Instalasi & Menjalankan Aplikasi

### A. Persyaratan Perangkat Lunak (Prerequisites)
1. **Web Server & Database**: XAMPP (disarankan versi dengan **PHP 8.2 atau lebih baru** dan **MariaDB/MySQL** aktif).
2. **Web Browser**: Google Chrome, Mozilla Firefox, atau Microsoft Edge versi terbaru.
3. **Akun WhatsApp Gateway (Opsional untuk OTP)**: Akun gratis di [Fonnte.com](https://fonnte.com).
4. **Kunci API AI (Opsional untuk AI Online)**: API Key gratis dari [Groq Console](https://console.groq.com).

---

### B. Langkah Instalasi Langkah demi Langkah

#### 1. Letakkan Folder Proyek
Pastikan folder proyek berada di dalam direktori `htdocs` XAMPP:
```text
C:\xampp\htdocs\PRD\
```

#### 2. Konfigurasi Basis Data (Database)
1. Buka **XAMPP Control Panel**, lalu klik tombol **Start** pada modul **Apache** dan **MySQL**.
2. Buka browser dan akses **phpMyAdmin**: [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/).
3. Buat database baru dengan nama: `terima_jasa_db`.
4. Klik tab **Import**, pilih file DDL skema database yang telah disediakan di:
   ```text
   C:\xampp\htdocs\PRD\database\schema.sql
   ```
5. Klik tombol **Import / Go** di bagian bawah dan pastikan 12 tabel berhasil terbuat (*users*, *technician_profiles*, *categories*, *services*, *orders*, *reviews*, *notifications*, *otp_verifications*, *ai_configurations*, *ai_chats*, dll).

#### 3. Konfigurasi Environment Variable (`.env`)
Salin file template `.env.example` menjadi `.env` di root proyek:
```bash
# Melalui Command Prompt / Terminal
copy .env.example .env
```
Buka file `.env` dan sesuaikan nilainya:
```env
APP_NAME="Terima Jasa"
APP_ENV=development
APP_DEBUG=true

# Pengaturan Database MySQL
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=terima_jasa_db
DB_USERNAME=root
DB_PASSWORD=

# Pengaturan AI Assistant (Groq Cloud)
AI_PROVIDER=groq
AI_API_KEY=gsk_your_groq_api_key_here
AI_MODEL=llama-3.3-70b-versatile
AI_FALLBACK_MODE=1

# Pengaturan WhatsApp Gateway (Fonnte)
WHATSAPP_PROVIDER=fonnte
WHATSAPP_API_TOKEN=your_fonnte_token_here
WHATSAPP_API_URL=https://api.fonnte.com/send
OTP_EXPIRY_SECONDS=300
OTP_MAX_ATTEMPTS=5
OTP_RESEND_COOLDOWN_SECONDS=60
```
> *Catatan: Jika API Key AI atau Token WhatsApp belum diisi, aplikasi tetap dapat berjalan secara optimal menggunakan mode fallback lokal bawaan sistem.*

---

### C. Menjalankan Aplikasi

Aplikasi dapat dijalankan melalui 2 metode:

#### Metode 1: Menggunakan Apache XAMPP (Rekomendasi)
1. Pastikan Apache & MySQL pada XAMPP Control Panel dalam status **Running**.
2. Buka web browser Anda, lalu akses alamat:
   👉 **[http://localhost/PRD/public/](http://localhost/PRD/public/)**

#### Metode 2: Menggunakan Built-in PHP Development Server
Buka terminal / PowerShell di direktori `C:\xampp\htdocs\PRD`, lalu jalankan:
```bash
php -S localhost:8000 -t public
```
Lalu buka browser Anda pada alamat:
👉 **[http://localhost:8000](http://localhost:8000)**

---

### D. Akun Pengujian Demo (Pre-Seeded Accounts)

Untuk mempermudah demonstrasi dan penilaian, sistem telah menyediakan akun demo siap pakai:

| Peran (Role) | Alamat Email / Akun | Password Default | Keterangan Fitur |
|:---|:---|:---|:---|
| **Administrator** | `admin@terimajasa.id` | `admin123` | Akses penuh dashboard, verifikasi teknisi, kelola kategori/jasa, konfigurasi AI, WhatsApp gateway, & profil admin. |
| **Customer** | `customer@terimajasa.id` | `password123` | Buat pesanan, cari katalog servis, tracking order stepper, rating & ulasan, serta Chatbot diagnosa. |
| **Mitra Teknisi** | `teknisi@terimajasa.id` | `password123` | Terima order pending, mulai pengerjaan, selesaikan order, atur status ketersediaan (*available*), & profil keahlian. |

> *Tips Penguji: Pada halaman login ([http://localhost/PRD/public/login](http://localhost/PRD/public/login)), tersedia tombol **Quick 1-Click Demo Login** untuk mencoba tiap peran secara instan.*

---

## 🧪 8. Pengujian Sistem Otomatis (Automated Test Suite)

Aplikasi telah dilengkapi dengan rangkaian pengujian otomatis (*automated regression test suites*) dengan hasil kelulusan **100% (342 dari 342 pengujian lulus)**:

```bash
# Menjalankan pengujian fitur utama
php tests/test_auth_role.php           # 38 Tests - Autentikasi, Bcrypt, & RBAC Guard
php tests/test_notification_system.php # 12 Tests - Sistem Notifikasi & Deduplikasi
php tests/test_chatbot_plus.php        # 22 Tests - Chatbot Database Grounding & Anti-IDOR
php tests/test_out_of_scope_guard.php  # 31 Tests - Out-of-Scope Guardrail
php tests/test_to_the_point_db.php     # 10 Tests - Jawaban Faktual Database To The Point
php tests/test_admin_profile.php       # 16 Tests - Manajemen Profil & Keamanan Admin
php tests/test_admin_module.php        # 35 Tests - Manajemen & Verifikasi Admin
php tests/test_customer_module.php     # 38 Tests - Alur Pemesanan & Ulasan Customer
php tests/test_technician_module.php   # 29 Tests - Alur Workflow Teknisi Lapangan
php tests/test_ai_configuration.php    # 21 Tests - Enkripsi AES-256 & SSRF Guard
```

---

## 📂 9. Struktur Direktori Proyek

```text
PRD/
├── app/
│   ├── Controllers/             # Lapisan pengendali alur request (MVC Controller)
│   │   ├── AdminCustomerController.php
│   │   ├── AdminDashboardController.php
│   │   ├── AdminProfileController.php    # Edit identitas & password administrator
│   │   ├── AdminTechnicianController.php
│   │   ├── AuthController.php
│   │   ├── ChatbotController.php         # Endpoint API asisten AI Chatbot+
│   │   ├── CustomerOrderController.php
│   │   ├── TechnicianJobController.php
│   │   └── WhatsAppAuthController.php    # Autentikasi OTP via WhatsApp
│   │
│   ├── Core/                    # Framework Kernel & Routing Engine
│   │   ├── App.php              # Lifecycle runner
│   │   ├── Auth.php             # Authentication & session manager
│   │   ├── Model.php            # Base PDO database model & query builder
│   │   ├── Request.php          # HTTP Request abstraction
│   │   ├── Response.php         # HTTP Response abstraction (JSON & HTML)
│   │   ├── Router.php           # RESTful URL dispatcher
│   │   └── View.php             # Template layout & partial renderer
│   │
│   ├── Helpers/                 # Fungsi bantuan global (url, asset, phone helper, csrf)
│   ├── Middleware/              # Filter keamanan sesi & otorisasi peran (RBAC)
│   ├── Models/                  # Relasi entitas database (User, Order, Service, Review, dll.)
│   ├── Services/                # Service layer (ChatbotService, AiService, AI Provider Manager)
│   └── Views/                   # Antarmuka pengguna (layouts, partials, modul views)
│
├── config/                      # Konfigurasi aplikasi & database
├── database/                    # Skema basis data SQL (`schema.sql`) & seeder
├── public/                      # Titik masuk publik (`index.php`) & aset statis (CSS, JS, Gambar)
├── routes/                      # Definisi rute web (`web.php`) dan API (`api.php`)
├── tests/                       # Rangkaian pengujian otomatis unit & integrasi
├── .env.example                 # Template variabel lingkungan aman
├── .gitignore                   # Daftar file rahasia/lokal yang diabaikan Git
└── README.md                    # Dokumentasi lengkap proyek
```

---

## 📄 10. Lisensi & Hak Cipta

Proyek ini dikembangkan untuk keperluan akademik tugas besar mata kuliah **Rekayasa Perangkat Lunak (RPL)**.  
Hak Cipta &copy; 2026 — **Tim Pengembang Terima Jasa**. Seluruh hak cipta dilindungi undang-undang.
