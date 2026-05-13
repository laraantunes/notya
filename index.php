<?php
require_once 'auth.php';

$page = $_GET['page'] ?? 'home';

// If no users exist, force setup
if (!hasUsers() && $page !== 'setup') {
    header('Location: index.php?page=setup');
    exit;
}

// Redirect to login if not logged in and not on setup or login page
if (!isLoggedIn() && !in_array($page, ['login', 'setup'])) {
    header('Location: index.php?page=login');
    exit;
}

// Handle Logout
if ($page === 'logout') {
    logout();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notya - Organize seus Pensamentos</title>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css">
    <script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Outfit:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script> <!-- Placeholder for FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="neon-theme">
    <div id="app-container">
        <?php
        switch ($page) {
            case 'setup':
                include 'templates/setup.php';
                break;
            case 'login':
                include 'templates/login.php';
                break;
            default:
                include 'templates/layout.php';
                break;
        }
        ?>
    </div>

    <!-- Search Modal -->
    <div id="search-modal" class="modal-overlay hidden">
        <div class="modal-content">
            <div class="search-header">
                <i class="fas fa-search"></i>
                <input type="text" id="search-input" name="search" placeholder="Pesquisar cadernos e notas... (Ctrl+K)" 
                       autocomplete="off"
                       hx-get="api.php?action=search" hx-trigger="keyup changed delay:300ms" hx-target="#search-results">
                <button onclick="toggleSearch()" class="close-btn"><i class="fas fa-times"></i></button>
            </div>
            <div id="search-results" class="search-results">
                <!-- Results will be injected here -->
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
</body>
</html>
