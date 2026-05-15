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
    <style>
        body {
            background-color: #F5F5F5;
        }
        
        .admin-layout {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }
        
        .admin-sidebar {
            background-color: #1A1A1A;
            color: #FFFFFF;
            padding: 20px;
        }
        
        .admin-logo {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #333;
        }
        
        .admin-logo span {
            color: #FFD700;
        }
        
        .admin-nav {
            list-style: none;
        }
        
        .admin-nav li {
            margin-bottom: 10px;
        }
        
        .admin-nav a {
            display: block;
            padding: 12px 15px;
            color: #CCCCCC;
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .admin-nav a:hover,
        .admin-nav a.active {
            background-color: #FFD700;
            color: #1A1A1A;
        }
        
        .admin-nav a.logout {
            margin-top: 30px;
            background-color: #c62828;
            color: white;
            text-align: center;
        }
        
        .admin-nav a.logout:hover {
            background-color: #b71c1c;
        }
        
        .admin-content {
            padding: 30px;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #FFD700;
        }
        
        .admin-header h1 {
            color: #1A1A1A;
            font-size: 28px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-role {
            background-color: #FFD700;
            color: #1A1A1A;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: #FFFFFF;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #FFD700;
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #FFD700;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .content-section {
            background-color: #FFFFFF;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 20px;
            margin-bottom: 20px;
            color: #1A1A1A;
            border-bottom: 2px solid #FFD700;
            padding-bottom: 10px;
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .orders-table th,
        .orders-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #EEEEEE;
        }
        
        .orders-table th {
            background-color: #F9F9F9;
            font-weight: 600;
            color: #333;
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-new {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .status-in_progress {
            background-color: #fff3e0;
            color: #f57c00;
        }
        
        .status-completed {
            background-color: #e8f5e9;
            color: #388e3c;
        }
        
        .status-cancelled {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background-color: #FFD700;
            color: #1A1A1A;
            border: none;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid #DDD;
            color: #333;
        }
    </style>
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
