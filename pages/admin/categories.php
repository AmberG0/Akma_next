<?php
session_start();
require_once '../../i/WebsiteBackend/db.php';

// Проверка авторизации
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

// Проверка прав администратора
if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$role = $_SESSION['role'];
$user_fio = $_SESSION['fio'];
$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($name)) {
            $message = 'Название категории обязательно';
            $message_type = 'error';
        } else {
            try {
                if ($action === 'add') {
                    $stmt = $pdo->prepare("INSERT INTO Category (Name, Description) VALUES (?, ?)");
                    $stmt->execute([$name, $description]);
                    $message = 'Категория успешно добавлена';
                    $message_type = 'success';
                } else {
                    $id = (int)$_POST['id'];
                    $stmt = $pdo->prepare("UPDATE Category SET Name = ?, Description = ? WHERE ID_category = ?");
                    $stmt->execute([$name, $description, $id]);
                    $message = 'Категория успешно обновлена';
                    $message_type = 'success';
                }
            } catch (PDOException $e) {
                $message = 'Ошибка базы данных: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        try {
            // Проверяем, есть ли услуги в этой категории
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM Services WHERE Category = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $message = 'Нельзя удалить категорию, в которой есть услуги';
                $message_type = 'error';
            } else {
                $stmt = $pdo->prepare("DELETE FROM Category WHERE ID_category = ?");
                $stmt->execute([$id]);
                $message = 'Категория успешно удалена';
                $message_type = 'success';
            }
        } catch (PDOException $e) {
            $message = 'Ошибка базы данных: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Получение списка категорий
$categories = [];
try {
    $stmt = $pdo->query("SELECT c.*, COUNT(s.ID_services) as services_count 
                         FROM Category c 
                         LEFT JOIN Services s ON c.ID_category = s.Category 
                         GROUP BY c.ID_category 
                         ORDER BY c.Name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = 'Ошибка при загрузке категорий: ' . $e->getMessage();
    $message_type = 'error';
}

// Получение категории для редактирования
$editCategory = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM Category WHERE ID_category = ?");
        $stmt->execute([$id]);
        $editCategory = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = 'Ошибка при загрузке категории';
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Управление категориями' ?> - СтройСервис</title>
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
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background-color: #FFD700;
            color: #000;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #FFD700;
            font-weight: bold;
        }
        
        tr:hover {
            background: #f5f5f5;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .actions {
            display: flex;
            gap: 5px;
        }
        
        .badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
        }
        
        .badge-info {
            background: #17a2b8;
            color: white;
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
                <li><a href="orders.php">📋 Заявки</a></li>
                <li><a href="services.php">🛠️ Услуги</a></li>
                <li><a href="categories.php" class="active">📁 Категории</a></li>
                <li><a href="personnel.php">👥 Персонал</a></li>
                <li><a href="reviews.php">⭐ Отзывы</a></li>
                <li><a href="../../index.php">🏠 На сайт</a></li>
                <li><a href="logout.php" class="logout">Выйти</a></li>
            </ul>
        </aside>
        
        <!-- Основной контент -->
        <main class="admin-content">
            <div class="admin-header">
                <h1>📁 Управление категориями</h1>
                <div class="user-info">
                    <span><?= htmlspecialchars($user_fio) ?></span>
                    <span class="user-role">Администратор</span>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <div style="margin-bottom: 20px;">
                <button class="btn btn-primary" onclick="openAddModal()">+ Добавить категорию</button>
            </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Описание</th>
                        <th>Количество услуг</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><?= $cat['ID_category'] ?></td>
                        <td><strong><?= htmlspecialchars($cat['Name']) ?></strong></td>
                        <td><?= htmlspecialchars(substr($cat['Description'], 0, 100)) ?><?= strlen($cat['Description']) > 100 ? '...' : '' ?></td>
                        <td><span class="badge badge-info"><?= $cat['services_count'] ?></span></td>
                        <td class="actions">
                            <a href="?edit=<?= $cat['ID_category'] ?>" class="btn btn-success" style="padding: 5px 10px;">✏️</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Удалить категорию?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $cat['ID_category'] ?>">
                                <button type="submit" class="btn btn-danger" style="padding: 5px 10px;">🗑️</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        </main>
    </div>

    <!-- Модальное окно добавления/редактирования -->
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle">Добавить категорию</h2>
            <form method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="categoryId">
                
                <div class="form-group">
                    <label for="name">Название *</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Описание</label>
                    <textarea id="description" name="description"></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('categoryModal');
        const modalTitle = document.getElementById('modalTitle');
        const formAction = document.getElementById('formAction');
        const categoryId = document.getElementById('categoryId');
        const nameInput = document.getElementById('name');
        const descInput = document.getElementById('description');

        function openAddModal() {
            modalTitle.textContent = 'Добавить категорию';
            formAction.value = 'add';
            categoryId.value = '';
            nameInput.value = '';
            descInput.value = '';
            modal.style.display = 'block';
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        // Закрытие по клику вне модального окна
        window.onclick = function(event) {
            if (event.target === modal) {
                closeModal();
            }
        }

        // Автозаполнение при редактировании из URL
        <?php if ($editCategory): ?>
        modalTitle.textContent = 'Редактировать категорию';
        formAction.value = 'edit';
        categoryId.value = '<?= $editCategory['ID_category'] ?>';
        nameInput.value = '<?= htmlspecialchars($editCategory['Name']) ?>';
        descInput.value = '<?= htmlspecialchars($editCategory['Description']) ?>';
        modal.style.display = 'block';
        <?php endif; ?>
    </script>
</body>
</html>
