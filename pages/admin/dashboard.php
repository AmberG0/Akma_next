<?php
session_start();
require_once '../../i/WebsiteBackend/db.php';

// Проверка авторизации
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

$role = $_SESSION['role'];
$user_fio = $_SESSION['fio'];

// Получаем статистику для dashboard
try {
    // Количество заявок за все время
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM Orders");
    $total_orders = $stmt->fetch()['count'];
    
    // Последние заявки
    $stmt = $pdo->query("
        SELECT o.ID_order, o.Client, o.Num_phone, o.Time_the_bell, 
               st.Description as service_name
        FROM Orders o
        JOIN Services_tab st ON o.Service_tab_ID = st.Service_tab_ID
        ORDER BY o.Time_the_bell DESC
        LIMIT 10
    ");
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Количество услуг
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM Services WHERE Relevance = 'да'");
    $services_count = $stmt->fetch()['count'];
    
} catch (PDOException $e) {
    error_log('Ошибка при загрузке данных dashboard: ' . $e->getMessage());
    $total_orders = 0;
    $recent_orders = [];
    $services_count = 0;
}

$page_title = "Панель управления";
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - СтройСервис</title>
    <link rel="stylesheet" href="../../i/Styles/main.css">
    <link rel="stylesheet" href="../../i/Styles/admin.css">
</head>
<body>
    <div class="admin-layout">
        <!-- Боковое меню -->
        <aside class="admin-sidebar">
            <div class="admin-logo">Строй<span>Сервис</span></div>
            
            <ul class="admin-nav">
                <li><a href="dashboard.php" class="active">📊 Dashboard</a></li>
                <?php if ($role === 'admin'): ?>
                    <li><a href="orders.php">📋 Заявки</a></li>
                    <li><a href="services.php">🛠️ Услуги</a></li>
                    <li><a href="categories.php">📁 Категории</a></li>
                    <li><a href="personnel.php">👥 Персонал</a></li>
                <?php else: ?>
                    <li><a href="orders.php">📋 Заявки</a></li>
                    <li><a href="services.php">🛠️ Услуги</a></li>
                    <li><a href="categories.php">📁 Категории</a></li>
                <?php endif; ?>
                <li><a href="reviews.php">⭐ Отзывы</a></li>
                <li><a href="../../index.php">🏠 На сайт</a></li>
                <li><a href="logout.php" class="logout">Выйти</a></li>
            </ul>
        </aside>
        
        <!-- Основной контент -->
        <main class="admin-content">
            <div class="admin-header">
                <h1>Панель управления</h1>
                <div class="user-info">
                    <span><?= htmlspecialchars($user_fio) ?></span>
                    <span class="user-role"><?= $role === 'admin' ? 'Администратор' : 'Менеджер' ?></span>
                </div>
            </div>
            
            <!-- Статистика -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= $total_orders ?></div>
                    <div class="stat-label">Всего заявок</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $services_count ?></div>
                    <div class="stat-label">Активных услуг</div>
                </div>
            </div>
            
            <!-- Последние заявки -->
            <div class="content-section">
                <h2 class="section-title">Последние заявки</h2>
                
                <?php if (empty($recent_orders)): ?>
                    <p>Заявок пока нет.</p>
                <?php else: ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Клиент</th>
                                <th>Услуга</th>
                                <th>Телефон</th>
                                <th>Дата/Время</th>
                                <th>Действие</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td>#<?= $order['ID_order'] ?></td>
                                    <td><?= htmlspecialchars($order['Client']) ?></td>
                                    <td><?= htmlspecialchars($order['service_name']) ?></td>
                                    <td><?= htmlspecialchars($order['Num_phone']) ?></td>
                                    <td><?= $order['Time_the_bell'] ? date('d.m.Y H:i', strtotime($order['Time_the_bell'])) : 'Не указано' ?></td>
                                    <td>
                                        <a href="order_view.php?id=<?= $order['ID_order'] ?>" class="btn-small btn-primary">Просмотр</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
