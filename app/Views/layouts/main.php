<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Terima Jasa — Platform Jasa Teknisi & Servis Terpercaya') ?></title>
    <meta name="description" content="Terima Jasa adalah platform penghubung customer dengan teknisi servis profesional terlengkap dan bergaransi dengan dukungan asisten AI pintar.">
    
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

    <!-- AI Chatbot Widget Styles -->
    <link rel="stylesheet" href="<?= asset('css/chatbot.css') ?>">
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Reusable Navbar -->
    <?php \App\Core\View::partial('navbar'); ?>

    <!-- Main Content Injection -->
    <main class="flex-grow-1">
        <?= $content ?? '' ?>
    </main>

    <!-- Reusable Footer -->
    <?php \App\Core\View::partial('footer'); ?>

    <!-- Bootstrap 5.3 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Scripts -->
    <script src="<?= asset('js/main.js') ?>"></script>

    <!-- AI Chatbot Widget -->
    <script>
        window.APP_BASE_URL = '<?= rtrim(base_url(), '/') ?>';
        window.CURRENT_USER_ID = '<?= \App\Core\Auth::check() ? (int)(\App\Core\Auth::user()['id'] ?? 0) : 0 ?>';
        window.CURRENT_USER_ROLE = '<?= \App\Core\Auth::check() ? (\App\Core\Auth::user()['role'] ?? 'guest') : 'guest' ?>';
        window.CURRENT_USER_NAME = '<?= \App\Core\Auth::check() ? addslashes(e(\App\Core\Auth::user()['name'] ?? '')) : '' ?>';
    </script>
    <script src="<?= asset('js/chatbot.js') ?>"></script>
</body>
</html>
