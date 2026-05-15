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

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'publish') {
        $id = (int)$_POST['id'];
        try {
            $stmt = $pdo->prepare("UPDATE Reviews SET Is_published = 'да' WHERE ID_review = ?");
            $stmt->execute([$id]);
            $message = 'Отзыв опубликован';
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = 'Ошибка: ' . $e->getMessage();
            $message_type = 'error';
        }
    } elseif ($action === 'unpublish') {
        $id = (int)$_POST['id'];
        try {
            $stmt = $pdo->prepare("UPDATE Reviews SET Is_published = 'Нет' WHERE ID_review = ?");
            $stmt->execute([$id]);
            $message = 'Отзыв снят с публикации';
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = 'Ошибка: ' . $e->getMessage();
            $message_type = 'error';
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM Reviews WHERE ID_review = ?");
            $stmt->execute([$id]);
            $message = 'Отзыв удален';
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = 'Ошибка: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Получение всех отзывов
$reviews = [];
try {
    $stmt = $pdo->query("SELECT * FROM Reviews ORDER BY Date_created DESC");
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = 'Ошибка при загрузке отзывов: ' . $e->getMessage();
    $message_type = 'error';
}

$page_title = "Модерация отзывов";
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
                <li><a href="dashboard.php">📊 Dashboard</a></li>
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
                <li><a href="reviews.php" class="active">⭐ Отзывы</a></li>
                <li><a href="../../index.php">🏠 На сайт</a></li>
                <li><a href="logout.php" class="logout">Выйти</a></li>
            </ul>
        </aside>
        
        <!-- Основной контент -->
        <main class="admin-content">
            <div class="admin-header">
                <h1>⭐ Модерация отзывов</h1>
                <div class="user-info">
                    <span><?= htmlspecialchars($user_fio) ?></span>
                    <span class="user-role"><?= $role === 'admin' ? 'Администратор' : 'Менеджер' ?></span>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if (empty($reviews)): ?>
                <p>Отзывов пока нет.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Автор</th>
                                <th>Рейтинг</th>
                                <th>Текст</th>
                                <th>Дата</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reviews as $review): ?>
                            <tr>
                                <td>#<?= $review['ID_review'] ?></td>
                                <td><?= htmlspecialchars($review['Client_name']) ?></td>
                                <td>
                                    <div class="rating-display">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?= $i <= $review['Rating'] ? '★' : '☆' ?>
                                        <?php endfor; ?>
                                    </div>
                                </td>
                                <td class="review-text-preview">
                                    <?= htmlspecialchars(substr($review['Review_text'], 0, 50)) ?>...
                                </td>
                                <td><?= date('d.m.Y H:i', strtotime($review['Date_created'])) ?></td>
                                <td>
                                    <span class="badge <?= $review['Is_published'] === 'да' ? 'badge-published' : 'badge-unpublished' ?>">
                                        <?= $review['Is_published'] === 'да' ? 'Опубликован' : 'На модерации' ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <?php if ($review['Is_published'] === 'да'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="unpublish">
                                            <input type="hidden" name="id" value="<?= $review['ID_review'] ?>">
                                            <button type="submit" class="btn btn-warning">Скрыть</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="publish">
                                            <input type="hidden" name="id" value="<?= $review['ID_review'] ?>">
                                            <button type="submit" class="btn btn-success">Опубликовать</button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Удалить отзыв?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $review['ID_review'] ?>">
                                        <button type="submit" class="btn btn-danger">Удалить</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
