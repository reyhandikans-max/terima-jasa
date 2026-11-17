<footer class="tj-footer">
    <div class="container">
        <div class="row g-4 justify-content-between">
            <!-- Brand Info -->
            <div class="col-lg-4 col-md-6">
                <a class="navbar-brand text-white d-flex align-items-center gap-2 mb-3 text-decoration-none fw-bold fs-4" href="<?= url() ?>">
                    <span class="tj-brand-badge">
                        <i class="bi bi-wrench-adjustable-circle"></i>
                    </span>
                    <span>Terima<span class="text-info">Jasa</span></span>
                </a>
                <p class="small text-secondary mb-4" style="max-width: 320px;">
                    Platform penyedia solusi teknisi dan jasa servis terlengkap, aman, dan bergaransi resmi di Indonesia. Dilengkapi teknologi AI Assistant untuk diagnosis akurat keluhan Anda.
                </p>
                <div class="d-flex gap-3">
                    <a href="#" class="btn btn-sm btn-outline-secondary rounded-circle" style="width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center;"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="btn btn-sm btn-outline-secondary rounded-circle" style="width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center;"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="btn btn-sm btn-outline-secondary rounded-circle" style="width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center;"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" class="btn btn-sm btn-outline-secondary rounded-circle" style="width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center;"><i class="bi bi-linkedin"></i></a>
                </div>
            </div>

            <!-- Layanan Populer -->
            <div class="col-lg-2 col-md-6 col-6">
                <h5>Layanan Utama</h5>
                <ul class="list-unstyled d-flex flex-direction-column flex-column gap-2">
                    <li><a href="<?= url('#kategori-jasa') ?>">Servis AC & Pendingin</a></li>
                    <li><a href="<?= url('#kategori-jasa') ?>">Elektronik & TV LED</a></li>
                    <li><a href="<?= url('#kategori-jasa') ?>">Komputer & Laptop</a></li>
                    <li><a href="<?= url('#kategori-jasa') ?>">Kelistrikan Rumah</a></li>
                    <li><a href="<?= url('#kategori-jasa') ?>">Plumbing & Pompa Air</a></li>
                    <li><a href="<?= url('#kategori-jasa') ?>">Montir & Kendaraan</a></li>
                </ul>
            </div>

            <!-- Perusahaan & Mitra -->
            <div class="col-lg-2 col-md-6 col-6">
                <h5>Platform & Mitra</h5>
                <ul class="list-unstyled d-flex flex-direction-column flex-column gap-2">
                    <li><a href="<?= url('register?role=teknisi') ?>">Gabung Mitra Teknisi</a></li>
                    <li><a href="<?= url('#cara-kerja') ?>">Cara Kerja Pemesanan</a></li>
                    <li><a href="<?= url('#keunggulan') ?>">Garansi & Perlindungan</a></li>
                    <li><a href="<?= url('#ai-assistant') ?>">Konsep Asisten AI</a></li>
                    <li><a href="<?= url('dashboard') ?>">Portal Dashboard</a></li>
                </ul>
            </div>

            <!-- Bantuan & Kontak -->
            <div class="col-lg-3 col-md-6">
                <h5>Pusat Bantuan</h5>
                <p class="small text-secondary mb-2">Butuh bantuan cepat atau pertanyaan seputar servis?</p>
                <div class="d-flex align-items-center gap-2 mb-2 text-secondary small">
                    <i class="bi bi-envelope-fill text-info"></i>
                    <span>support@terimajasa.id</span>
                </div>
                <div class="d-flex align-items-center gap-2 mb-3 text-secondary small">
                    <i class="bi bi-telephone-fill text-info"></i>
                    <span>+62 (021) 500-JASA</span>
                </div>
                <div class="p-3 rounded-3" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08);">
                    <div class="d-flex align-items-center gap-2 text-white small fw-bold mb-1">
                        <i class="bi bi-patch-check-fill text-success"></i> 100% Teknisi Terverifikasi
                    </div>
                    <small class="text-secondary" style="font-size: 0.78rem;">Setiap mitra teknisi melewati proses kurasi identitas dan keahlian.</small>
                </div>
            </div>
        </div>

        <!-- Bottom bar -->
        <div class="tj-footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <div>
                &copy; <?= date('Y') ?> <strong>Terima Jasa</strong>. Hak Cipta Dilindungi Undang-Undang.
            </div>
            <div class="d-flex gap-4">
                <a href="#">Syarat & Ketentuan</a>
                <a href="#">Kebijakan Privasi</a>
                <a href="#">Peta Situs</a>
            </div>
        </div>
    </div>
</footer>
