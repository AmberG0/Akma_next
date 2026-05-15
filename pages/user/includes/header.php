<?php
session_start();
// Относительный путь для возврата на уровень выше к папке i
$assets_path = '../../i/';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ИП Барабарян К.А. - Строительные услуги</title>
    <link rel="stylesheet" href="<?php echo $assets_path; ?>Styles/main.css">
</head>
<body>
    <!-- Верхняя панель -->
    <div class="top-bar">
        <div class="container">
            <div class="top-links">
                <a href="about.php">О нас</a>
                <a href="policy.php">Наша политика</a>
                <button id="theme-toggle" class="theme-btn">🌙 Темная тема</button>
            </div>
            <div class="city-selector">
                <span>г. Энгельс</span>
            </div>
        </div>
    </div>

    <!-- Основная навигация -->
    <header class="main-header">
        <div class="container header-content">
            <div class="admin-area">
                <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="../../pages/admin/dashboard.php" class="admin-link">Админ панель</a>
                <?php endif; ?>
            </div>
            
            <a href="main.php" class="logo">Строй<span>Сервис</span></a>
            
            <nav class="main-nav">
                <a href="catalog.php">Каталог</a>
                <a href="works.php">Наши работы</a>
                <a href="cart.php" class="cart-btn">Подбор услуг</a>
            </nav>
            
            <button class="cta-button" onclick="alert('Форма заявки откроется здесь')">Оставить заявку</button>
        </div>
    </header>
