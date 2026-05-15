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
$message = '';
$message_type = '';

// Получение ID заявки
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    header('Location: orders.php');
    exit;
}

// Обработка изменения статуса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $allowed_statuses = ['новая', 'в работе', 'выполнена', 'отменена'];
    
    if (in_array($status, $allowed_statuses)) {
        try {
            $stmt = $pdo->prepare("UPDATE Orders SET Status = ? WHERE ID_order = ?");
            $stmt->execute([$status, $order_id]);
            $message = 'Статус успешно обновлен';
            $message_type = 'success';
            
            // Перезагружаем страницу чтобы увидеть изменения
            header('Location: order_view.php?id=' . $order_id . '&msg=' . urlencode($message) . '&type=' . $message_type);
            exit;
        } catch (PDOException $e) {
            $message = 'Ошибка при обновлении статуса: ' . $e->getMessage();
            $message_type = 'error';
        }
    } else {
        $message = 'Недопустимый статус';
        $message_type = 'error';
    }
}

// Обработка назначения менеджера
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_manager'])) {
    $manager_id = (int)$_POST['manager_id'];
    try {
        $stmt = $pdo->prepare("UPDATE Orders SET Accept_order_Per_ID = ? WHERE ID_order = ?");
        $stmt->execute([$manager_id, $order_id]);
        $message = 'Менеджер успешно назначен';
        $message_type = 'success';
    } catch (PDOException $e) {
        $message = 'Ошибка при назначении: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Получение сообщения из URL
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $message_type = isset($_GET['type']) ? $_GET['type'] : 'success';
}

// Получение данных заявки
try {
    $stmt = $pdo->prepare("
        SELECT o.*, 
               st.Description as service_description, 
               st.Service_list_count, 
               st.Count_pay,
               p.Fio as manager_name,
               p.ID_personal as manager_id
        FROM Orders o
        JOIN Services_tab st ON o.Service_tab_ID = st.Service_tab_ID
        LEFT JOIN Personnel p ON o.Accept_order_Per_ID = p.ID_personal
        WHERE o.ID_order = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Location: orders.php');
        exit;
    }
    
    // Получение списка услуг в заявке
    $stmt = $pdo->prepare("
        SELECT s.Name, s.Price, st.Service_list_count, st.Count_pay
        FROM Services_tab st
        JOIN Services s ON st.Service_list_ID = s.ID_services
        WHERE st.Service_tab_ID = ?
    ");
    $stmt->execute([$order['Service_tab_ID']]);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log('Ошибка при загрузке заявки: ' . $e->getMessage());
    header('Location: orders.php');
    exit;
}

// Получение списка менеджеров для назначения
$managers = [];
try {
    $stmt = $pdo->query("SELECT ID_personal, Fio, Role FROM Personnel WHERE Role IN ('admin', 'manager') ORDER BY Fio");
    $managers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $managers = [];
}

$page_title = "Заявка #" . $order_id;
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
        
        .content-section {
            background-color: #FFFFFF;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 20px;
            margin-bottom: 20px;
            color: #1A1A1A;
            border-bottom: 2px solid #FFD700;
            padding-bottom: 10px;
        }
        
        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .detail-item {
            padding: 15px;
            background-color: #F9F9F9;
            border-radius: 8px;
            border-left: 4px solid #FFD700;
        }
        
        .detail-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
            text-transform: uppercase;
            font-weight: bold;
        }
        
        .detail-value {
            font-size: 16px;
            color: #333;
            font-weight: 600;
        }
        
        .services-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .services-table th,
        .services-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #EEEEEE;
        }
        
        .services-table th {
            background-color: #F9F9F9;
            font-weight: 600;
            color: #333;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            border: none;
        }
        
        .btn-primary {
            background-color: #FFD700;
            color: #1A1A1A;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        
        .message {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border-left: 3px solid #28a745;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 3px solid #dc3545;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #DDD;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .manager-badge {
            display: inline-block;
            padding: 8px 15px;
            background-color: #e3f2fd;
            color: #1976d2;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .no-manager {
            display: inline-block;
            padding: 8px 15px;
            background-color: #ffebee;
            color: #c62828;
            border-radius: 20px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Боковое меню -->
        <aside class="admin-sidebar">
            <div class="admin-logo">Строй<span>Сервис</span></div>
            
            <ul class="admin-nav">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <?php if ($role === 'admin'): ?>
                    <li><a href="orders.php" class="active">📋 Заявки</a></li>
                    <li><a href="services.php">🛠️ Услуги</a></li>
                    <li><a href="categories.php">📁 Категории</a></li>
                    <li><a href="personnel.php">👥 Персонал</a></li>
                <?php else: ?>
                    <li><a href="orders.php" class="active">📋 Заявки</a></li>
                    <li><a href="services.php">🛠️ Услуги</a></li>
                    <li><a href="categories.php">📁 Категории</a></li>
                <?php endif; ?>
                <li><a href="../../index.php">🏠 На сайт</a></li>
                <li><a href="logout.php" class="logout">Выйти</a></li>
            </ul>
        </aside>
        
        <!-- Основной контент -->
        <main class="admin-content">
            <div class="admin-header">
                <h1>Заявка #<?= $order_id ?></h1>
                <div class="user-info">
                    <span><?= htmlspecialchars($user_fio) ?></span>
                    <span class="user-role"><?= $role === 'admin' ? 'Администратор' : 'Менеджер' ?></span>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="message <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <div style="margin-bottom: 20px;">
                <a href="orders.php" class="btn-small btn-secondary">← Назад к списку</a>
            </div>
            
            <!-- Информация о клиенте -->
            <div class="content-section">
                <h2 class="section-title">Информация о клиенте</h2>
                <div class="order-details">
                    <div class="detail-item">
                        <div class="detail-label">ФИО клиента</div>
                        <div class="detail-value"><?= htmlspecialchars($order['Client']) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Тип клиента</div>
                        <div class="detail-value"><?= htmlspecialchars($order['Face_client']) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Телефон</div>
                        <div class="detail-value"><?= htmlspecialchars($order['Num_phone'] ?? 'Не указан') ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Email</div>
                        <div class="detail-value"><?= htmlspecialchars($order['Mail'] ?? 'Не указан') ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Дата создания</div>
                        <div class="detail-value"><?= date('d.m.Y H:i', strtotime($order['Time_the_bell'])) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Способ оплаты</div>
                        <div class="detail-value"><?= htmlspecialchars($order['Type_pay']) ?></div>
                    </div>
                </div>
                
                <?php if ($order['Other_inform']): ?>
                    <div style="margin-top: 20px;">
                        <div class="detail-label">Дополнительная информация</div>
                        <div class="detail-value" style="font-weight: normal;"><?= nl2br(htmlspecialchars($order['Other_inform'])) ?></div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Услуги в заявке -->
            <div class="content-section">
                <h2 class="section-title">Услуги</h2>
                <p><strong>Описание:</strong> <?= htmlspecialchars($order['service_description']) ?></p>
                
                <table class="services-table">
                    <thead>
                        <tr>
                            <th>Услуга</th>
                            <th>Цена за ед.</th>
                            <th>Количество</th>
                            <th>Стоимость</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td><?= htmlspecialchars($service['Name']) ?></td>
                                <td><?= number_format($service['Price'], 2) ?> ₽</td>
                                <td><?= htmlspecialchars($service['Service_list_count']) ?></td>
                                <td><strong><?= number_format($service['Count_pay'], 2) ?> ₽</strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Статус и назначение менеджера -->
            <div class="content-section">
                <h2 class="section-title">Статус заявки и менеджер</h2>
                
                <!-- Изменение статуса -->
                <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
                    <form method="POST" style="display: flex; gap: 15px; align-items: flex-end;">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="order_id" value="<?= $order_id ?>">
                        
                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <label for="status">Статус заявки:</label>
                            <select name="status" id="status" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                                <option value="новая" <?= ($order['Status'] ?? '') === 'новая' ? 'selected' : '' ?>>🆕 Новая</option>
                                <option value="в работе" <?= ($order['Status'] ?? '') === 'в работе' ? 'selected' : '' ?>>🔄 В работе</option>
                                <option value="выполнена" <?= ($order['Status'] ?? '') === 'выполнена' ? 'selected' : '' ?>>✅ Выполнена</option>
                                <option value="отменена" <?= ($order['Status'] ?? '') === 'отменена' ? 'selected' : '' ?>>❌ Отменена</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn-small btn-primary">Изменить статус</button>
                    </form>
                    
                    <div style="margin-top: 15px;">
                        <span class="detail-label">Текущий статус:</span>
                        <?php
                        $status_class = '';
                        $status_text = $order['Status'] ?? 'новая';
                        if ($status_text === 'новая') $status_class = 'status-badge-new';
                        elseif ($status_text === 'в работе') $status_class = 'status-badge-in-progress';
                        elseif ($status_text === 'выполнена') $status_class = 'status-badge-completed';
                        elseif ($status_text === 'отменена') $status_class = 'status-badge-cancelled';
                        ?>
                        <span class="status-badge <?= $status_class ?>" style="margin-left: 10px;"><?= htmlspecialchars($status_text) ?></span>
                    </div>
                </div>
                
                <!-- Назначение менеджера -->
                <div>
                    <?php if ($order['manager_name']): ?>
                        <div style="margin-bottom: 15px;">
                            <span class="manager-badge">
                                👤 <?= htmlspecialchars($order['manager_name']) ?>
                            </span>
                        </div>
                    <?php else: ?>
                        <div style="margin-bottom: 15px;">
                            <span class="no-manager">
                                ⚠️ Менеджер не назначен
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($role === 'admin'): ?>
                        <form method="POST">
                            <div class="form-group">
                                <label for="manager_id">Назначить менеджера:</label>
                                <select name="manager_id" id="manager_id" required>
                                    <option value="">-- Выберите менеджера --</option>
                                    <?php foreach ($managers as $manager): ?>
                                        <option value="<?= $manager['ID_personal'] ?>" 
                                                <?= $order['manager_id'] == $manager['ID_personal'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($manager['Fio']) ?> (<?= $manager['Role'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" name="assign_manager" class="btn-small btn-primary">
                                <?= $order['manager_name'] ? 'Изменить менеджера' : 'Назначить менеджера' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
