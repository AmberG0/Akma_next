<?php
session_start();
require_once '../../i/WebsiteBackend/db.php';

// Если уже авторизован - перенаправляем в dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = isset($_POST['login']) ? trim($_POST['login']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($login) || empty($password)) {
        $error = 'Введите логин и пароль';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT ID_personal, Login, Password, Fio, Role FROM Personnel WHERE Login = ?");
            $stmt->execute([$login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Временная проверка пароля (простое сравнение)
            // if ($user && password_verify($password, $user['Password'])) {
            if ($user && $password === $user['Password']) {
                // Успешная авторизация
                $_SESSION['user_id'] = $user['ID_personal'];
                $_SESSION['login'] = $user['Login'];
                $_SESSION['fio'] = $user['Fio'];
                $_SESSION['role'] = $user['Role'];
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Неверный логин или пароль';
            }
        } catch (PDOException $e) {
            error_log('Ошибка авторизации: ' . $e->getMessage());
            $error = 'Произошла ошибка при входе. Попробуйте позже.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>окно авторизации - СтройСервис</title>
    <link rel="stylesheet" href="../../i/Styles/main.css">
    <style>
        body {
            background: linear-gradient(135deg, #1A1A1A 0%, #2a2a2a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
        
        /* Заголовок страницы вне блока */
        .page-header-title {
            color: #FFFFFF;
            font-size: 24px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .login-container {
            background-color: #FFFFFF;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
            border-top: 4px solid #FFD700;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-logo {
            font-size: 28px;
            font-weight: bold;
            color: #1A1A1A;
            margin-bottom: 10px;
        }
        
        .login-logo span {
            color: #FFD700;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #DDD;
            border-radius: 4px;
            font-size: 14px;
            background-color: #F5F5F5; /* Серый цвет полей */
            box-sizing: border-box;
        }
        
        .form-group input::placeholder {
            color: #999;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #FFD700;
            box-shadow: 0 0 5px rgba(255, 215, 0, 0.3);
        }
        
        .btn-login {
            width: 100%;
            background-color: #808080; /* Серая кнопка */
            color: #FFFFFF;
            padding: 12px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            background-color: #666666;
            transform: scale(1.02);
        }
        
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 3px solid #c62828;
        }
        
        .login-footer {
            margin-top: 20px;
            text-align: center;
            font-size: 14px;
            color: #666;
        }
        
        /* Подпись внизу */
        .admin-note {
            margin-top: 15px;
            text-align: center;
            font-size: 12px;
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body>
    <!-- Заголовок страницы вне блока -->
    <h1 class="page-header-title">окно авторизации</h1>
    
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">Строй<span>Сервис</span></div>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <input type="text" name="login" placeholder="Логин" required autofocus>
            </div>
            
            <div class="form-group">
                <input type="password" name="password" placeholder="Пароль" required>
            </div>
            
            <button type="submit" class="btn-login">Войти</button>
        </form>
        
        <div class="login-footer">
            <a href="../../index.php" style="color: #FFD700;">← Вернуться на сайт</a>
        </div>
        
        <!-- Подпись внизу -->
        <div class="admin-note">Для админа (только для фигмы)</div>
    </div>
</body>
</html>
