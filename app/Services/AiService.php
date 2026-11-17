<?php
namespace App\Services;

use App\Models\Category;
use App\Models\Service;
use App\Models\TechnicianProfile;
use App\Models\User;
use App\Models\Review;

/**
 * AIService — AI Recommendation & Diagnostic Engine untuk Terima Jasa
 *
 * Menganalisis keluhan customer, mendeteksi kategori jasa,
 * mencocokkan layanan dari database, dan merekomendasikan teknisi
 * terverifikasi dengan perangkingan prioritas ketat:
 * 1. Expertise sesuai
 * 2. Status Verified
 * 3. Status Available
 * 4. Rating tinggi
 * 5. Lokasi terdekat
 */
class AIService
{
    private string $provider;
    private string $apiKey;
    private string $model;

    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';

    public function __construct(?string $provider = null, ?string $apiKey = null, ?string $model = null)
    {
        $this->provider = $provider ?? (string)env('AI_PROVIDER', 'groq');
        $this->apiKey   = $apiKey ?? (string)env('AI_API_KEY', env('GROQ_API_KEY', ''));
        $this->model    = $model ?? (string)env('AI_MODEL', 'openai/gpt-oss-120b');
    }

    public function hasApiKey(): bool
    {
        return !empty(trim($this->apiKey));
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Analisis Keluhan Customer & Bangun Rekomendasi Jasa Serta Teknisi
     *
     * @param string $complaint Keluhan atau deskripsi masalah dari customer
     * @param string|null $customerLocation Lokasi customer untuk ranking jarak (opsional)
     * @return array Respon terstruktur berisi rekomendasi jasa dan teknisi
     */
    public function analyzeComplaint(string $complaint, ?string $customerLocation = null): array
    {
        $cleanComplaint = trim($complaint);

        // 1. Safety Guard: Cek apakah keluhan berbau medis / kesehatan
        if ($this->detectMedicalInquiry($cleanComplaint)) {
            return $this->buildMedicalRefusalResponse($cleanComplaint);
        }

        // 1b. Out-of-Scope Guard: Cek apakah pertanyaan di luar cakupan layanan Terima Jasa
        $chatbotService = new ChatbotService();
        if ($chatbotService->isOutOfScope($cleanComplaint)) {
            return $this->buildOutOfScopeRefusalResponse($cleanComplaint);
        }

        // 2. Coba minta analisis mendalam dari AI Model yang aktif
        $llmResult = null;
        $source = 'local_fallback';

        $featConfig = \App\Services\AI\AIConfigurationService::getFeatureConfig('complaint_analysis');

        $isTestInvalidKey = !empty($this->apiKey) && (str_starts_with($this->apiKey, 'gsk_invalid') || str_starts_with($this->apiKey, 'fake_'));

        if (!$isTestInvalidKey && $featConfig && $featConfig['enabled'] && !empty($featConfig['provider_adapter'])) {
            $llmResult = $this->queryLlmWithAdapter($cleanComplaint, $featConfig['provider_adapter'], $featConfig['model'] ?? $this->model);
            if ($llmResult !== null) {
                $source = $featConfig['provider_adapter']->getProviderName() . '_api';
            }
        } elseif (!$isTestInvalidKey && $this->hasApiKey()) {
            $llmResult = $this->queryGroqLlm($cleanComplaint);
            if ($llmResult !== null) {
                $source = 'groq_api';
            }
        }

        // 3. Jika LLM gagal / offline / no API key, gunakan Local Semantic Engine
        $semantic = $this->resolveLocalSemanticAnalysis($cleanComplaint);

        // Cari Category ID dari Database secara dinamis
        $dbCategories = Category::all();
        $dbCategory = null;
        if (!empty($llmResult['category'])) {
            $catLower = strtolower(trim($llmResult['category']));
            foreach ($dbCategories as $cat) {
                $cNameLower = strtolower($cat->name);
                if ($cNameLower === $catLower || str_contains($cNameLower, $catLower) || str_contains($catLower, $cNameLower)) {
                    $dbCategory = $cat;
                    break;
                }
            }
        }

        if ($dbCategory) {
            $categoryId = (int)$dbCategory->id;
            $categoryName = $dbCategory->name;
        } else {
            $categoryName = $llmResult['category'] ?? $semantic['category_name'];
            $categoryId = $semantic['category_id'];
        }

        $diagnosisSummary   = $llmResult['diagnosis'] ?? $semantic['diagnosis'];
        $explanation        = $llmResult['explanation'] ?? $semantic['explanation'];
        $recommendedService = $llmResult['service'] ?? $semantic['primary_service_name'];
        $urgency            = $llmResult['urgency'] ?? $semantic['urgency'];
        $disclaimer         = $semantic['disclaimer'];

        // 4. Cari Service yang Sesuai dari Database (Service::all())
        $matchedServices = $this->findMatchingServices($categoryId, $cleanComplaint, $recommendedService);

        // ID Jasa Utama yang direkomendasikan
        $primaryService = !empty($matchedServices) ? $matchedServices[0] : null;
        $primaryServiceId = $primaryService['id'] ?? 1;

        // 5. Cari & Urutkan Teknisi Sesuai 5 Prioritas Ranking dari Database (TechnicianProfile::all())
        $recommendedTechnicians = $this->rankTechnicians(
            $cleanComplaint,
            $categoryName,
            $semantic['keywords'],
            $customerLocation,
            $primaryServiceId
        );

        // URL pencarian katalog manual
        $searchQuery = !empty($matchedServices) ? $matchedServices[0]['name'] : $categoryName;
        $searchUrl = url('customer/services?q=' . urlencode($searchQuery));

        // Format teks chat untuk penyimpanan riwayat percakapan di database (ai_chats)
        $chatFormattedText = $this->buildChatSummaryText(
            $diagnosisSummary,
            $categoryName,
            $recommendedService,
            $matchedServices,
            $recommendedTechnicians,
            $explanation
        );

        return [
            'success'                 => true,
            'source'                  => $source,
            'complaint'               => $cleanComplaint,
            'diagnosis_summary'       => $diagnosisSummary,
            'recommended_category'    => $categoryName,
            'category_id'             => $categoryId,
            'recommended_service'     => $recommendedService,
            'explanation'             => $explanation,
            'urgency'                 => $urgency,
            'disclaimer'              => $disclaimer,
            'matched_services'        => $matchedServices,
            'recommended_technicians' => $recommendedTechnicians,
            'search_url'              => $searchUrl,
            'is_medical'              => false,
            'chat_formatted_text'     => $chatFormattedText,
        ];
    }

    /**
     * Cari Jasa dari Database yang Sesuai Kategori dan Kata Kunci Masalah
     */
    private function findMatchingServices(int $categoryId, string $complaint, string $primaryServiceName): array
    {
        $allServices = Service::all();
        $matched = [];
        $complaintLower = strtolower($complaint);

        foreach ($allServices as $srv) {
            if ($srv->status !== Service::STATUS_ACTIVE && $srv->status !== 'active') {
                continue;
            }

            $catId = (int)$srv->category_id;
            $nameLower = strtolower($srv->name);
            $descLower = strtolower($srv->description ?? '');

            // Hitung relevansi
            $score = 0;
            if ($catId === $categoryId) {
                $score += 10;
            }

            if (str_contains($nameLower, strtolower($primaryServiceName))) {
                $score += 20;
            }

            // Cek kata kunci dalam keluhan
            $words = preg_split('/\s+/', $complaintLower);
            foreach ($words as $w) {
                if (strlen($w) >= 3 && (str_contains($nameLower, $w) || str_contains($descLower, $w))) {
                    $score += 2;
                }
            }

            if ($score > 0) {
                $matched[] = [
                    'id'              => (int)$srv->id,
                    'category_id'     => $catId,
                    'name'            => $srv->name,
                    'description'     => $srv->description,
                    'estimated_price' => (float)$srv->estimated_price,
                    'formatted_price' => 'Rp ' . number_format($srv->estimated_price, 0, ',', '.'),
                    'order_url'       => url('customer/orders/create?service_id=' . $srv->id),
                    'score'           => $score
                ];
            }
        }

        // Urutkan berdasarkan skor relevansi tertinggi
        usort($matched, fn($a, $b) => $b['score'] <=> $a['score']);

        // Jika tidak ada yang cocok, ambil service dari kategori terkait
        if (empty($matched)) {
            foreach ($allServices as $srv) {
                if ((int)$srv->category_id === $categoryId) {
                    $matched[] = [
                        'id'              => (int)$srv->id,
                        'category_id'     => (int)$srv->category_id,
                        'name'            => $srv->name,
                        'description'     => $srv->description,
                        'estimated_price' => (float)$srv->estimated_price,
                        'formatted_price' => 'Rp ' . number_format($srv->estimated_price, 0, ',', '.'),
                        'order_url'       => url('customer/orders/create?service_id=' . $srv->id),
                        'score'           => 1
                    ];
                }
            }
        }

        return array_slice($matched, 0, 4);
    }

    /**
     * Rangking Teknisi Berdasarkan 5 Prioritas Ketat:
     * 1. Expertise Sesuai
     * 2. Status Verified
     * 3. Status Available
     * 4. Rating Tinggi
     * 5. Lokasi Terdekat
     *
     * JANGAN MENGARANG DATA TEKNISI: Semua data teknisi berasal dari TechnicianProfile, User, & Review!
     */
    private function rankTechnicians(
        string $complaint,
        string $categoryName,
        array $keywords,
        ?string $customerLocation,
        int $primaryServiceId
    ): array {
        $profiles = TechnicianProfile::all();
        $ranked = [];
        $complaintLower = strtolower($complaint);

        foreach ($profiles as $profile) {
            $user = $profile->user();
            if (!$user) {
                $user = User::find((int)$profile->user_id);
            }

            // Nama teknisi dari database
            $techName = $user ? $user->name : 'Teknisi Mitra #' . $profile->user_id;

            // Rating dinamis dari Review model
            $ratingStats = Review::getRatingStatsForTechnician((int)$profile->user_id);

            // 1. Hitung Kesesuaian Keahlian (Expertise Match Score)
            $expertiseLower = strtolower($profile->expertise);
            $expertiseScore = 0;

            foreach ($keywords as $kw) {
                if (str_contains($expertiseLower, strtolower($kw))) {
                    $expertiseScore += 5;
                }
                if (str_contains($complaintLower, strtolower($kw)) && str_contains($expertiseLower, strtolower($kw))) {
                    $expertiseScore += 10;
                }
            }

            // Cek nama kategori
            if (str_contains($expertiseLower, 'laptop') && str_contains(strtolower($categoryName), 'laptop')) {
                $expertiseScore += 15;
            }
            if (str_contains($expertiseLower, 'komputer') && str_contains(strtolower($categoryName), 'komputer')) {
                $expertiseScore += 15;
            }
            if (str_contains($expertiseLower, 'ac') && str_contains(strtolower($categoryName), 'ac')) {
                $expertiseScore += 15;
            }
            if (str_contains($expertiseLower, 'listrik') && str_contains(strtolower($categoryName), 'listrik')) {
                $expertiseScore += 15;
            }
            if (str_contains($expertiseLower, 'pompa') && str_contains(strtolower($categoryName), 'pompa')) {
                $expertiseScore += 15;
            }
            if (str_contains($expertiseLower, 'pipa') && str_contains(strtolower($categoryName), 'plumbing')) {
                $expertiseScore += 15;
            }

            // 2. Status Verified (verified = 1, pending/rejected = 0)
            $isVerified = ($profile->verification_status === TechnicianProfile::VERIFICATION_VERIFIED);
            $verifiedScore = $isVerified ? 1 : 0;

            // 3. Status Available (available = 1, busy/offline = 0)
            $isAvailable = ($profile->availability === TechnicianProfile::AVAILABILITY_AVAILABLE);
            $availableScore = $isAvailable ? 1 : 0;

            // 4. Rating Tinggi
            $ratingScore = (float)$ratingStats['average'];

            // 5. Lokasi Terdekat
            $locationScore = $this->calculateLocationScore($profile->location, $customerLocation);

            // Label ketersediaan ramah pengguna
            $availLabel = match ($profile->availability) {
                TechnicianProfile::AVAILABILITY_AVAILABLE => 'Tersedia Sekarang',
                TechnicianProfile::AVAILABILITY_BUSY      => 'Sedang Bertugas',
                TechnicianProfile::AVAILABILITY_OFFLINE   => 'Offline / Istirahat',
                default                                   => ucfirst($profile->availability)
            };

            $ranked[] = [
                'id'                  => (int)$profile->id,
                'user_id'             => (int)$profile->user_id,
                'name'                => $techName,
                'expertise'           => $profile->expertise,
                'rating'              => $ratingScore,
                'rating_label'        => '⭐ ' . number_format($ratingScore, 1) . ($ratingStats['count'] > 0 ? " ({$ratingStats['count']} ulasan)" : ' (Baru)'),
                'reviews_count'       => $ratingStats['count'],
                'experience'          => $profile->experience,
                'location'            => $profile->location,
                'availability'        => $profile->availability,
                'availability_label'  => $availLabel,
                'verification_status' => $profile->verification_status,
                'is_verified'         => $isVerified,
                'avatar'              => $user->avatar ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150',
                'order_url'           => url('customer/orders/create?service_id=' . $primaryServiceId . '&technician_id=' . $profile->user_id),
                // Data internal ranking
                '_expertise_score'    => $expertiseScore,
                '_verified_score'     => $verifiedScore,
                '_available_score'    => $availableScore,
                '_rating_score'       => $ratingScore,
                '_location_score'     => $locationScore,
            ];
        }

        // SORTING DENGAN 5 PRIORITAS KETAT
        usort($ranked, function ($a, $b) {
            // Prioritas 1: Expertise Sesuai
            if ($a['_expertise_score'] !== $b['_expertise_score']) {
                return $b['_expertise_score'] <=> $a['_expertise_score'];
            }

            // Prioritas 2: Verified Status
            if ($a['_verified_score'] !== $b['_verified_score']) {
                return $b['_verified_score'] <=> $a['_verified_score'];
            }

            // Prioritas 3: Available Status
            if ($a['_available_score'] !== $b['_available_score']) {
                return $b['_available_score'] <=> $a['_available_score'];
            }

            // Prioritas 4: Rating Tinggi
            if (abs($a['_rating_score'] - $b['_rating_score']) > 0.01) {
                return $b['_rating_score'] <=> $a['_rating_score'];
            }

            // Prioritas 5: Lokasi Terdekat
            if ($a['_location_score'] !== $b['_location_score']) {
                return $b['_location_score'] <=> $a['_location_score'];
            }

            return 0;
        });

        // Filter teknisi yang relevan: prioritaskan yang match expertise
        $filtered = array_filter($ranked, fn($t) => $t['_expertise_score'] > 0);
        if (empty($filtered)) {
            $filtered = $ranked;
        }

        // Bersihkan atribut internal scoring dari array output
        return array_map(function ($item) {
            unset($item['_expertise_score'], $item['_verified_score'], $item['_available_score'], $item['_rating_score'], $item['_location_score']);
            return $item;
        }, array_values($filtered));
    }

    /**
     * Hitung Kedekatan Lokasi Teknisi dengan Customer
     */
    private function calculateLocationScore(string $techLocation, ?string $customerLocation): int
    {
        if (empty($customerLocation)) {
            // Default: jika Jabodetabek / Jakarta Selatan beri skor netral
            return str_contains(strtolower($techLocation), 'jakarta') ? 5 : 2;
        }

        $custLower = strtolower($customerLocation);
        $techLower = strtolower($techLocation);

        if ($custLower === $techLower) {
            return 20; // Lokasi persis sama
        }

        // Cek kemiripan kota / wilayah
        $areas = ['jakarta selatan', 'jakarta timur', 'jakarta barat', 'jakarta utara', 'jakarta pusat', 'bandung', 'bekasi', 'tangerang', 'depok', 'bogor'];
        foreach ($areas as $area) {
            if (str_contains($custLower, $area) && str_contains($techLower, $area)) {
                return 15;
            }
        }

        if (str_contains($custLower, 'jakarta') && str_contains($techLower, 'jakarta')) {
            return 10;
        }

        if (str_contains($techLower, 'jabodetabek') && (str_contains($custLower, 'jakarta') || str_contains($custLower, 'bekasi') || str_contains($custLower, 'depok') || str_contains($custLower, 'tangerang'))) {
            return 8;
        }

        return 1;
    }

    /**
     * Local Semantic Rule Engine (Fallback Pintar Berbasis NLP Rules & Domain Knowledge)
     */
    private function resolveLocalSemanticAnalysis(string $text): array
    {
        $lower = strtolower($text);

        // Kasus 1: Komputer / Laptop (Contoh Prompt User)
        if (
            str_contains($lower, 'laptop') ||
            str_contains($lower, 'komputer') ||
            str_contains($lower, 'pc') ||
            str_contains($lower, 'mati total') ||
            str_contains($lower, 'tidak bisa menyala') ||
            str_contains($lower, 'tidak menyala') ||
            str_contains($lower, 'mati') && (str_contains($lower, 'laptop') || str_contains($lower, 'pc') || str_contains($lower, 'layar'))
        ) {
            return [
                'category_id'          => 3,
                'category_name'        => 'Service Laptop/Komputer',
                'primary_service_name' => 'Perbaikan Hardware Laptop / Komputer Mati Total',
                'diagnosis'            => 'Laptop mengalami kendala supply daya utama (power line failure) atau kerusakan motherboard/IC power.',
                'explanation'          => 'Laptop yang tiba-tiba mati total dan tidak bisa menyala umumnya disebabkan oleh korsleting jalur power motherboard, kerusakan regulator IC charging, baterai drop, atau adaptor bermasalah. Teknisi spesialis akan melakukan diagnosa multitester pada blok tegangan 19V/3V/5V untuk menemukan komponen short circuit.',
                'urgency'              => 'Tinggi',
                'disclaimer'           => 'Jangan mencoba mencolokkan charger berulang kali jika tercium bau komponen terbakar untuk mencegah kerusakan menyebar ke chipset processor.',
                'keywords'             => ['laptop', 'komputer', 'mati total', 'motherboard', 'pc', 'hardware', 'power']
            ];
        }

        // Kasus 2: Servis AC & Pendingin
        if (
            str_contains($lower, 'ac') ||
            str_contains($lower, 'air conditioner') ||
            str_contains($lower, 'dingin') ||
            str_contains($lower, 'bocor air') ||
            str_contains($lower, 'netes') ||
            str_contains($lower, 'tetesan') ||
            str_contains($lower, 'freon')
        ) {
            return [
                'category_id'          => 1,
                'category_name'        => 'Servis AC & Pendingin',
                'primary_service_name' => 'Cuci AC Standard 0.5 - 2 PK',
                'diagnosis'            => 'AC kurang dingin disertai kebocoran tetesan air akibat saluran pembuangan drainase mampet lendir lumut.',
                'explanation'          => 'Hembusan angin AC tidak dingin dan mengeluarkan air di unit indoor menandakan filter dan talang drainase tersumbat debu lendir, serta tekanan freon berkurang. Disarankan pengerjaan pembersihan jet pump flushing dan pengecekan tekanan freon manifold.',
                'urgency'              => 'Sedang',
                'disclaimer'           => 'Matikan aliran listrik saklar AC segera jika rembesan air menetes mengenai stop kontak dinding.',
                'keywords'             => ['ac', 'pendingin', 'freon', 'cuci ac', 'chiller', 'kulkas']
            ];
        }

        // Kasus 3: Kelistrikan Rumah
        if (
            str_contains($lower, 'listrik') ||
            str_contains($lower, 'mcb') ||
            str_contains($lower, 'anjlok') ||
            str_contains($lower, 'konslet') ||
            str_contains($lower, 'korslet') ||
            str_contains($lower, 'sekring') ||
            str_contains($lower, 'stop kontak') ||
            str_contains($lower, 'kabel')
        ) {
            return [
                'category_id'          => 4,
                'category_name'        => 'Kelistrikan Rumah',
                'primary_service_name' => 'Perbaikan Korsleting Listrik Darurat',
                'diagnosis'            => 'Terjadi hubungan pendek arus listrik (short circuit) atau beban peralatan melampaui kapasitas ampere MCB.',
                'explanation'          => 'MCB yang sering anjlok menandakan adanya kabel fasa-netral yang bersentuhan di dalam dinding atau grounding bocor saat menyalakan beban berat. Diperlukan penelusuran jalur kabel berstandar SNI oleh teknisi listrik bersertifikat.',
                'urgency'              => 'Darurat',
                'disclaimer'           => 'Jangan memaksa menaikkan tuas MCB berkali-kali jika langsung memercikkan bunga api atau trip seketika demi mencegah kebakaran.',
                'keywords'             => ['listrik', 'mcb', 'panel', 'stop kontak', 'korsleting', 'instalasi']
            ];
        }

        // Kasus 4: Plumbing & Pompa Air
        if (
            str_contains($lower, 'pipa') ||
            str_contains($lower, 'wastafel') ||
            str_contains($lower, 'mampet') ||
            str_contains($lower, 'pompa air') ||
            str_contains($lower, 'jet pump') ||
            str_contains($lower, 'cetak-cetek') ||
            str_contains($lower, 'keran') ||
            str_contains($lower, 'toren')
        ) {
            return [
                'category_id'          => 5,
                'category_name'        => 'Plumbing & Pompa Air',
                'primary_service_name' => 'Pelancaran Pipa Saluran Air Mampet',
                'diagnosis'            => 'Penyumbatan endapan sisa kotoran pada pipa leher angsa wastafel atau gangguan otomatis pressure switch pompa.',
                'explanation'          => 'Saluran pembuangan air yang meluap biasanya disebabkan penumpukan lemak beku pada jalur P-trap. Disarankan pembersihan mekanik menggunakan spiral drain auger profesional tanpa merusak sambungan pipa PVC.',
                'urgency'              => 'Sedang',
                'disclaimer'           => 'Hindari penggunaan soda api berlebih yang dapat membuat pipa PVC melengkung dan melelehkan lem sambungan.',
                'keywords'             => ['plumbing', 'pipa', 'pompa air', 'saluran', 'mampet', 'bocor']
            ];
        }

        // Kasus 5: Elektronik & TV / Kulkas
        if (
            str_contains($lower, 'kulkas') ||
            str_contains($lower, 'tv') ||
            str_contains($lower, 'mesin cuci') ||
            str_contains($lower, 'microwave') ||
            str_contains($lower, 'elektronik')
        ) {
            return [
                'category_id'          => 2,
                'category_name'        => 'Elektronik & TV',
                'primary_service_name' => 'Perbaikan Kulkas 1 & 2 Pintu',
                'diagnosis'            => 'Gangguan pada modul power supply elektronik atau komponen kompresor/timer pendingin.',
                'explanation'          => 'Perangkat elektronik yang tidak berfungsi normal umumnya mengalami gangguan pada relay start, board inverter, atau kebocoran sistem sirkulasi freon pendingin.',
                'urgency'              => 'Sedang',
                'disclaimer'           => 'Cabut steker dari sumber listrik sebelum teknisi melakukan pembongkaran casing unit.',
                'keywords'             => ['elektronik', 'tv', 'kulkas', 'mesin cuci', 'chiller']
            ];
        }

        // Default Umum
        return [
            'category_id'          => 1,
            'category_name'        => 'Servis AC & Peralatan Rumah',
            'primary_service_name' => 'Pemeriksaan & Servis Perangkat Rumah Tangga',
            'diagnosis'            => 'Pemeriksaan komprehensif kendala fungsi perangkat oleh teknisi terpercaya.',
            'explanation'          => 'Berdasarkan keluhan yang Anda ceritakan, disarankan dilakukan pengecekan langsung oleh teknisi berpengalaman untuk memastikan komponen yang perlu diservis atau diganti.',
            'urgency'              => 'Sedang',
            'disclaimer'           => 'Gunakan jasa teknisi terverifikasi Terima Jasa untuk keamanan dan garansi pengerjaan optimal.',
            'keywords'             => ['servis', 'teknisi', 'perbaikan']
        ];
    }

    /**
     * Query Groq Cloud LLM API
     */
    private function queryGroqLlm(string $complaint): ?array
    {
        // Fast-fail untuk API Key test / dummy / non-existent untuk mencegah network delay
        if (empty($this->apiKey) || str_starts_with($this->apiKey, 'gsk_invalid_test') || str_starts_with($this->apiKey, 'fake_')) {
            return null;
        }

        $dbCategories = Category::all();
        $catNames = array_map(fn($c) => $c->name, $dbCategories);
        $categoriesSchema = !empty($catNames) ? implode('|', $catNames) : "Service Laptop/Komputer|Servis AC & Pendingin|Kelistrikan Rumah|Plumbing & Pompa Air|Elektronik & TV";

        $dbServices = Service::all();
        $sampleServices = array_slice(array_map(fn($s) => $s->name, $dbServices), 0, 8);
        $servicesHint = !empty($sampleServices) ? implode(', ', $sampleServices) : "Cuci AC Standard, Perbaikan Korsleting Listrik, Perbaikan Laptop Mati Total";

        $systemPrompt = "Kamu adalah AI Diagnostic Engine Terima Jasa (Platform Teknisi Servis Indonesia). " .
            "Data Kategori Database: [{$categoriesSchema}]. " .
            "Contoh Layanan di Database: [{$servicesHint}]. " .
            "Analisis keluhan customer dan kembalikan HANYA JSON murni tanpa markdown dengan schema: " .
            "{" .
            "\"category\": \"Pilih salah satu kategori database: {$categoriesSchema}\", " .
            "\"service\": \"Nama spesifik layanan jasa yang dibutuhkan\", " .
            "\"diagnosis\": \"Ringkasan diagnosa 1 kalimat\", " .
            "\"explanation\": \"Penjelasan teknis 2-3 kalimat penyebab dan solusi\", " .
            "\"urgency\": \"Rendah|Sedang|Tinggi|Darurat\"" .
            "}";

        $ch = curl_init(self::GROQ_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS     => json_encode([
                'model'          => $this->model,
                'messages'       => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => "Keluhan customer: {$complaint}"]
                ],
                'temperature'    => (float)env('AI_TEMPERATURE', 0.2),
                'max_tokens'     => (int)env('AI_MAX_TOKENS', 500),
            ]),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $raw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || empty($raw)) {
            return null;
        }

        $decoded = json_decode($raw, true);
        $content = $decoded['choices'][0]['message']['content'] ?? '';
        $content = preg_replace('/<think>.*?<\/think>/si', '', $content);
        $content = trim($content);

        // Ambil JSON block
        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $parsed = json_decode($matches[0], true);
            if (is_array($parsed) && !empty($parsed['category'])) {
                return $parsed;
            }
        }

        return null;
    }

    /**
     * Query LLM Menggunakan Adapter Provider yang Terkonfigurasi Dinamis
     */
    private function queryLlmWithAdapter(string $complaint, \App\Services\AI\AIProviderInterface $adapter, string $model): ?array
    {
        $dbCategories = Category::all();
        $categoriesSchema = implode(', ', array_map(fn($c) => $c->name, $dbCategories));

        $dbServices = Service::all();
        $sampleServices = array_slice(array_map(fn($s) => $s->name, $dbServices), 0, 8);
        $servicesHint = !empty($sampleServices) ? implode(', ', $sampleServices) : "Cuci AC Standard, Perbaikan Korsleting Listrik";

        $systemPrompt = "Kamu adalah AI Diagnostic Engine Terima Jasa (Platform Teknisi Servis Indonesia). " .
            "Data Kategori Database: [{$categoriesSchema}]. " .
            "Contoh Layanan di Database: [{$servicesHint}]. " .
            "Analisis keluhan customer dan kembalikan HANYA JSON murni tanpa markdown dengan schema: " .
            "{" .
            "\"category\": \"Pilih salah satu kategori database: {$categoriesSchema}\", " .
            "\"service\": \"Nama spesifik layanan jasa yang dibutuhkan\", " .
            "\"diagnosis\": \"Ringkasan diagnosa 1 kalimat\", " .
            "\"explanation\": \"Penjelasan teknis 2-3 kalimat penyebab dan solusi\", " .
            "\"urgency\": \"Rendah|Sedang|Tinggi|Darurat\"" .
            "}";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => "Keluhan customer: {$complaint}"]
        ];

        $reply = $adapter->chat($messages, [
            'model'       => $model,
            'temperature' => 0.2,
            'max_tokens'  => 500,
        ]);

        if (empty($reply)) {
            return null;
        }

        if (preg_match('/\{.*\}/s', $reply, $matches)) {
            $parsed = json_decode($matches[0], true);
            if (is_array($parsed) && !empty($parsed['category'])) {
                return $parsed;
            }
        }

        return null;
    }


    /**
     * Deteksi Apakah Pertanyaan Merupakan Konsultasi Medis
     */
    private function detectMedicalInquiry(string $text): bool
    {
        $lower = strtolower($text);
        $medicalKeywords = [
            'dokter', 'demam', 'batuk', 'berdarah', 'obat', 'resep', 'sakit kepala',
            'mual', 'muntah', 'diare', 'pusing', 'gejala penyakit', 'flu', 'infeksi',
            'nyeri dada', 'sesak nafas', 'tensimeter', 'asam urat', 'kolesterol', 'stroke'
        ];

        foreach ($medicalKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Respon Penolakan Aman untuk Pertanyaan di Luar Cakupan Terima Jasa
     */
    private function buildOutOfScopeRefusalResponse(string $complaint): array
    {
        return [
            'success'                 => true,
            'source'                  => 'local_safety_guard',
            'complaint'               => $complaint,
            'diagnosis_summary'       => 'Peringatan: Pertanyaan di Luar Cakupan Layanan',
            'recommended_category'    => 'Di Luar Cakupan Terima Jasa',
            'category_id'             => 0,
            'recommended_service'     => 'Hanya Layanan Servis Rumah & Elektronik',
            'explanation'             => 'Terima Jasa adalah platform khusus jasa perbaikan perangkat rumah tangga, kelistrikan, elektronik, komputer/laptop, dan saluran air. Pertanyaan seputar pembuatan kode pemrograman, resep masakan, tugas umum, atau topik di luar katalog jasa kami tidak dapat diproses.',
            'urgency'                 => 'Rendah',
            'disclaimer'              => 'Silakan sampaikan keluhan kerusakan perangkat rumah atau elektronik yang ingin Anda servis.',
            'matched_services'        => [],
            'recommended_technicians' => [],
            'search_url'              => url('customer/services'),
            'is_medical'              => false,
            'is_out_of_scope'         => true,
            'chat_formatted_text'     => "❌ Maaf, pertanyaan Anda di luar cakupan layanan Terima Jasa.\n\nKami hanya melayani jasa perbaikan:\n• Servis AC & Pendingin\n• Kelistrikan Rumah\n• Service Laptop/Komputer\n• Plumbing & Pompa Air\n• Elektronik & TV\n\nSilakan sampaikan kendala atau keluhan perangkat Anda.",
        ];
    }

    /**
     * Respon Penolakan Aman untuk Klaim Medis
     */
    private function buildMedicalRefusalResponse(string $complaint): array
    {
        return [
            'success'                 => true,
            'source'                  => 'local_safety_guard',
            'complaint'               => $complaint,
            'diagnosis_summary'       => 'Peringatan Keamanan: Pertanyaan Berindikasi Medis / Kesehatan',
            'recommended_category'    => 'Layanan Medis (Di Luar Jangkauan Terima Jasa)',
            'category_id'             => 0,
            'recommended_service'     => 'Konsultasi Dokter / Fasilitas Kesehatan Terdekat',
            'explanation'             => 'Terima Jasa adalah platform khusus jasa perbaikan perangkat rumah, elektronik, kelistrikan, dan komputer. AI kami tidak berwenang dan dilarang memberikan diagnosa medis atau resep obat apapun. Untuk keselamatan Anda, segera hubungi dokter profesional, puskesmas, atau instalasi gawat darurat rumah sakit terdekat.',
            'urgency'                 => 'Darurat',
            'disclaimer'              => 'Informasi kesehatan harus selalu dikonsultasikan langsung dengan tenaga medis berizin resmi.',
            'matched_services'        => [],
            'recommended_technicians' => [],
            'search_url'              => url('customer/services'),
            'is_medical'              => true,
            'chat_formatted_text'     => 'Maaf, Terima Jasa tidak berwenang memberikan diagnosa medis. Mohon segera konsultasikan gejala Anda dengan dokter atau fasilitas kesehatan terdekat.',
        ];
    }

    /**
     * Bangun Ringkasan Teks untuk Database Storage di tabel ai_chats
     */
    private function buildChatSummaryText(
        string $diagnosis,
        string $category,
        string $service,
        array $services,
        array $technicians,
        string $explanation
    ): string {
        $text = "Kategori: {$category}\n";
        $text .= "Diagnosa: {$diagnosis}\n";
        $text .= "Penjelasan: {$explanation}\n";
        $text .= "Rekomendasi Jasa Utama: {$service}\n\n";

        if (!empty($technicians)) {
            $text .= "Rekomendasi Teknisi Tersedia:\n";
            foreach (array_slice($technicians, 0, 2) as $t) {
                $text .= "- {$t['name']} ({$t['expertise']}) | Rating: {$t['rating_label']} | Lokasi: {$t['location']} | Status: {$t['availability_label']}\n";
            }
        }

        return trim($text);
    }
}
