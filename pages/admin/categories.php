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
    <link rel="stylesheet" href="../../i/Styles/admin.css">
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
