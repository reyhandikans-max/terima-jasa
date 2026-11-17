<nav class="navbar navbar-expand-lg sticky-top tj-navbar">
    <div class="container">
        <!-- Brand Logo -->
        <a class="navbar-brand" href="<?= url() ?>">
            <span class="tj-brand-badge">
                <i class="bi bi-wrench-adjustable-circle"></i>
            </span>
            <span>Terima<span class="text-primary">Jasa</span></span>
        </a>

        <!-- Mobile Toggler -->
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Links -->
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link tj-nav-link <?= is_active('/') ?>" href="<?= url() ?>">Beranda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link tj-nav-link" href="<?= url('#kategori-jasa') ?>">Kategori Jasa</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link tj-nav-link" href="<?= url('#cara-kerja') ?>">Cara Kerja</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link tj-nav-link" href="<?= url('#keunggulan') ?>">Keunggulan</a>
                </li>
                <li class="nav-item d-flex align-items-center ms-lg-2">
                    <a href="javascript:void(0)" onclick="if(window.openChatbotWidget){window.openChatbotWidget();}else{window.location.href='<?= url('#ai-assistant') ?>';}" class="tj-ai-pill text-decoration-none" title="Buka Asisten AI Terima Jasa">
                        <span class="pulse-dot"></span>
                        <span>AI Assistant</span>
                    </a>
                </li>
            </ul>

            <!-- Auth / Action Buttons -->
            <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                <?php if (session('user')): ?>
                    <a href="<?= url('dashboard') ?>" class="btn btn-primary-tj btn-sm">
                        <i class="bi bi-grid-fill me-1"></i> Dashboard
                    </a>
                    <a href="<?= url('logout') ?>" class="btn btn-outline-danger btn-sm" title="Keluar">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                <?php else: ?>
                    <a href="<?= url('login') ?>" class="btn btn-outline-tj btn-sm">
                        Masuk
                    </a>
                    <a href="<?= url('register') ?>" class="btn btn-primary-tj btn-sm">
                        Daftar Akun
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
