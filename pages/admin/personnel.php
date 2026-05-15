<?php
session_start();
require_once '../../i/WebsiteBackend/db.php';

// Проверка авторизации и прав администратора
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
        $login = trim($_POST['login'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $fio = trim($_POST['fio'] ?? '');
        $role_user = $_POST['role'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if (empty($login) || empty($password) || empty($fio) || empty($role_user)) {
            $message = 'Заполните обязательные поля';
            $message_type = 'error';
        } else {
            try {
                if ($action === 'add') {
                    // Проверка уникальности логина
                    $stmt = $pdo->prepare("SELECT ID_personal FROM Personnel WHERE Login = ?");
                    $stmt->execute([$login]);
                    if ($stmt->fetch()) {
                        $message = 'Пользователь с таким логином уже существует';
                        $message_type = 'error';
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO Personnel (Login, Password, Fio, Role, Num_phone, Mail) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$login, $password, $fio, $role_user, $phone, $email]);
                        $message = 'Пользователь успешно добавлен';
                        $message_type = 'success';
                    }
                } else {
                    $id = (int)$_POST['id'];
                    // Проверка, не редактирует ли админ сам себя
                    if ($id == $user_id && $role_user !== 'admin') {
                        $message = 'Нельзя изменить свою роль';
                        $message_type = 'error';
                    } else {
                        $stmt = $pdo->prepare("UPDATE Personnel SET Login = ?, Fio = ?, Role = ?, Num_phone = ?, Mail = ? WHERE ID_personal = ?");
                        $stmt->execute([$login, $fio, $role_user, $phone, $email, $id]);
                        
                        // Обновление пароля если указан
                        if (!empty($password)) {
                            $stmt = $pdo->prepare("UPDATE Personnel SET Password = ? WHERE ID_personal = ?");
                            $stmt->execute([$password, $id]);
                        }
                        
                        $message = 'Пользователь успешно обновлен';
                        $message_type = 'success';
                    }
                }
            } catch (PDOException $e) {
                $message = 'Ошибка базы данных: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        
        // Нельзя удалить самого себя
        if ($id == $user_id) {
            $message = 'Нельзя удалить свою учетную запись';
            $message_type = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM Personnel WHERE ID_personal = ?");
                $stmt->execute([$id]);
                $message = 'Пользователь успешно удален';
                $message_type = 'success';
            } catch (PDOException $e) {
                $message = 'Ошибка базы данных: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}

// Получение списка персонала
$personnel = [];
try {
    $stmt = $pdo->query("SELECT * FROM Personnel ORDER BY Role, Fio");
    $personnel = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = 'Ошибка при загрузке списка: ' . $e->getMessage();
    $message_type = 'error';
}

// Получение пользователя для редактирования
$editUser = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM Personnel WHERE ID_personal = ?");
        $stmt->execute([$id]);
        $editUser = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = 'Ошибка при загрузке пользователя';
        $message_type = 'error';
    }
}

$page_title = "Управление персоналом";
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
                <li><a href="orders.php">📋 Заявки</a></li>
                <li><a href="services.php">🛠️ Услуги</a></li>
                <li><a href="categories.php">📁 Категории</a></li>
                <li><a href="personnel.php" class="active">👥 Персонал</a></li>
                <li><a href="reviews.php">⭐ Отзывы</a></li>
                <li><a href="../../index.php">🏠 На сайт</a></li>
                <li><a href="logout.php" class="logout">Выйти</a></li>
            </ul>
        </aside>
        
        <!-- Основной контент -->
        <main class="admin-content">
            <div class="admin-header">
                <h1>👥 Управление персоналом</h1>
                <div class="user-info">
                    <span><?= htmlspecialchars($user_fio) ?></span>
                    <span class="user-role">Администратор</span>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <div style="margin-bottom: 20px;">
                <button class="btn btn-primary" onclick="openAddModal()">+ Добавить сотрудника</button>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Логин</th>
                            <th>ФИО</th>
                            <th>Роль</th>
                            <th>Телефон</th>
                            <th>Email</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($personnel as $user): ?>
                        <tr>
                            <td><?= $user['ID_personal'] ?></td>
                            <td><strong><?= htmlspecialchars($user['Login']) ?></strong></td>
                            <td><?= htmlspecialchars($user['Fio']) ?></td>
                            <td>
                                <span class="badge <?= $user['Role'] === 'admin' ? 'badge-admin' : 'badge-manager' ?>">
                                    <?= $user['Role'] === 'admin' ? 'Администратор' : 'Менеджер' ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($user['Num_phone'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($user['Mail'] ?? '—') ?></td>
                            <td class="actions">
                                <a href="?edit=<?= $user['ID_personal'] ?>" class="btn btn-success" style="padding: 5px 10px;">✏️</a>
                                <?php if ($user['ID_personal'] != $user_id): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Удалить пользователя?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $user['ID_personal'] ?>">
                                    <button type="submit" class="btn btn-danger" style="padding: 5px 10px;">🗑️</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Модальное окно добавления/редактирования -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle">Добавить сотрудника</h2>
            <form method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="userId">
                
                <div class="form-group">
                    <label for="login">Логин <span class="required">*</span></label>
                    <input type="text" id="login" name="login" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль <span class="required">*</span></label>
                    <input type="password" id="password" name="password" required>
                    <small style="color: #666;">При редактировании оставьте пустым, чтобы не менять</small>
                </div>
                
                <div class="form-group">
                    <label for="fio">ФИО <span class="required">*</span></label>
                    <input type="text" id="fio" name="fio" required>
                </div>
                
                <div class="form-group">
                    <label for="role">Роль <span class="required">*</span></label>
                    <select id="role" name="role" required>
                        <option value="manager">Менеджер</option>
                        <option value="admin">Администратор</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="phone">Телефон</label>
                    <input type="text" id="phone" name="phone">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email">
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('userModal');
        const modalTitle = document.getElementById('modalTitle');
        const formAction = document.getElementById('formAction');
        const userId = document.getElementById('userId');
        const loginInput = document.getElementById('login');
        const passwordInput = document.getElementById('password');
        const fioInput = document.getElementById('fio');
        const roleInput = document.getElementById('role');
        const phoneInput = document.getElementById('phone');
        const emailInput = document.getElementById('email');

        function openAddModal() {
            modalTitle.textContent = 'Добавить сотрудника';
            formAction.value = 'add';
            userId.value = '';
            loginInput.value = '';
            passwordInput.value = '';
            passwordInput.required = true;
            fioInput.value = '';
            roleInput.value = 'manager';
            phoneInput.value = '';
            emailInput.value = '';
            modal.style.display = 'block';
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target === modal) {
                closeModal();
            }
        }

        // Автозаполнение при редактировании из URL
        <?php if ($editUser): ?>
        modalTitle.textContent = 'Редактировать сотрудника';
        formAction.value = 'edit';
        userId.value = '<?= $editUser['ID_personal'] ?>';
        loginInput.value = '<?= htmlspecialchars($editUser['Login']) ?>';
        passwordInput.value = '';
        passwordInput.required = false;
        fioInput.value = '<?= htmlspecialchars($editUser['Fio']) ?>';
        roleInput.value = '<?= htmlspecialchars($editUser['Role']) ?>';
        phoneInput.value = '<?= htmlspecialchars($editUser['Num_phone'] ?? '') ?>';
        emailInput.value = '<?= htmlspecialchars($editUser['Mail'] ?? '') ?>';
        modal.style.display = 'block';
        <?php endif; ?>
    </script>
</body>
</html>
