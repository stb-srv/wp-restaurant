<?php
$csrf_token = generate_csrf_token();
$stats = get_stats();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Server - Admin Panel</title>
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <h1>🔑 License Server</h1>
                <div class="header-actions">
                    <span class="user">👤 <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="?logout" class="btn-logout">Logout</a>
                </div>
            </div>
        </header>
        
        <!-- Tabs -->
        <nav class="tabs">
            <a href="?tab=dashboard" class="tab <?php echo $current_tab === 'dashboard' ? 'active' : ''; ?>">
                📊 Dashboard
            </a>
            <a href="?tab=licenses" class="tab <?php echo $current_tab === 'licenses' ? 'active' : ''; ?>">
                🎫 Lizenzen
            </a>
            <a href="?tab=clients" class="tab <?php echo $current_tab === 'clients' ? 'active' : ''; ?>">
                🌐 Clients
            </a>
            <a href="?tab=pricing" class="tab <?php echo $current_tab === 'pricing' ? 'active' : ''; ?>">
                💰 Preise
            </a>
            <a href="?tab=api" class="tab <?php echo $current_tab === 'api' ? 'active' : ''; ?>">
                🔌 API
            </a>
            <a href="?tab=settings" class="tab <?php echo $current_tab === 'settings' ? 'active' : ''; ?>">
                ⚙️ Einstellungen
            </a>
        </nav>
        
        <!-- Content -->
        <main class="content">
            <?php
            $tab_file = __DIR__ . '/tabs/' . $current_tab . '.php';
            if (file_exists($tab_file)) {
                require_once $tab_file;
            } else {
                echo '<p>Tab nicht gefunden.</p>';
            }
            ?>
        </main>
    </div>
</body>
</html>
