<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Autentikasi — Terima Jasa') ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/chatbot.css') ?>">
</head>
<body class="tj-auth-page">

    <div class="container">
        <!-- Flash Alert Messages -->
        <?php foreach (['flash_success' => 'success', 'flash_error' => 'danger', 'flash_info' => 'info', 'flash_warning' => 'warning'] as $key => $type): ?>
            <?php if (!empty($_SESSION[$key])): ?>
                <div class="alert alert-<?= $type ?> alert-dismissible fade show mx-auto mb-4 shadow-sm" style="max-width: 960px;" role="alert">
                    <i class="bi bi-<?= $type === 'success' ? 'check-circle-fill' : ($type === 'danger' ? 'exclamation-triangle-fill' : ($type === 'info' ? 'info-circle-fill' : 'exclamation-diamond-fill')) ?> me-2"></i>
                    <?= e($_SESSION[$key]) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION[$key]); ?>
            <?php endif; ?>
        <?php endforeach; ?>

        <?= $content ?? '' ?>
    </div>

    <!-- Bootstrap 5.3 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= asset('js/main.js') ?>"></script>

    <!-- AI Chatbot Widget -->
    <script>
        window.APP_BASE_URL = '<?= rtrim(base_url(), '/') ?>';
        window.CURRENT_USER_ID = 0;
        window.CURRENT_USER_ROLE = 'guest';
        window.CURRENT_USER_NAME = '';
    </script>
    <script src="<?= asset('js/chatbot.js') ?>"></script>
</body>
</html>
