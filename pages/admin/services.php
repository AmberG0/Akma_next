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

// Обработка действий
$action = isset($_GET['action']) ? $_GET['action'] : '';
$message = '';
$message_type = '';

// Удаление услуги
if ($action === 'delete' && $role === 'admin' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        // Сначала получаем имя файла, чтобы удалить его с диска
        $stmt = $pdo->prepare("SELECT Photo FROM Services WHERE ID_services = ?");
        $stmt->execute([$id]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($service && !empty($service['Photo'])) {
            $old_photo_path = dirname(__DIR__, 2) . '/uploads/' . $service['Photo'];
            if (file_exists($old_photo_path)) {
                unlink($old_photo_path);
            }
        }

        $stmt = $pdo->prepare("DELETE FROM Services WHERE ID_services = ?");
        $stmt->execute([$id]);
        $message = 'Услуга успешно удалена';
        $message_type = 'success';
    } catch (PDOException $e) {
        $message = 'Ошибка при удалении: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Обработка формы сохранения (добавление/редактирование)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'admin') {
    $service_id = isset($_POST['service_id']) && $_POST['service_id'] !== '' ? (int)$_POST['service_id'] : null;
    $name = trim($_POST['name']);
    $category = isset($_POST['category']) && $_POST['category'] !== '' ? (int)$_POST['category'] : null;
    $description = trim($_POST['description']);
    $unit = trim($_POST['unit']);
    $price = (float)$_POST['price'];
    $relevance = $_POST['relevance'];
    
    // --- НАЧАЛО БЛОКА ЗАГРУЗКИ ФОТО ---
    $photo = '';
    $upload_error = false;

    // Определяем абсолютный путь к папке uploads в корне проекта
    // __DIR__ - это текущая папка (админка)
    // dirname(__DIR__, 2) - поднимается на 2 уровня вверх до корня сайта
    $upload_dir = dirname(__DIR__, 2) . '/uploads/';
    
    // Проверяем существование папки
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            $message = 'Ошибка: Не удалось создать папку uploads в корне сайта.';
            $message_type = 'error';
            $upload_error = true;
        }
    } elseif (!is_writable($upload_dir)) {
        $message = 'Ошибка: Нет прав на запись в папку uploads. Установите права 777.';
        $message_type = 'error';
        $upload_error = true;
    }

    if (!$upload_error) {
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $file_tmp = $_FILES['photo']['tmp_name'];
            $file_name = basename($_FILES['photo']['name']);
            
            // Получаем расширение
            $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Проверка расширения
            if (!in_array($extension, $allowed_ext)) {
                $message = 'Разрешены только изображения (JPG, PNG, GIF, WebP)';
                $message_type = 'error';
                $upload_error = true;
            } else {
                // Генерация уникального имени
                $new_filename = 'serv_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    // Сохраняем только имя файла в БД (путь будет собираться при выводе)
                    $photo = $new_filename;
                    
                    // Если это редактирование и было старое фото, удаляем его
                    if ($service_id) {
                        $stmt_old = $pdo->prepare("SELECT Photo FROM Services WHERE ID_services = ?");
                        $stmt_old->execute([$service_id]);
                        $old_service = $stmt_old->fetch(PDO::FETCH_ASSOC);
                        if ($old_service && !empty($old_service['Photo']) && $old_service['Photo'] !== $new_filename) {
                            $old_path = $upload_dir . $old_service['Photo'];
                            if (file_exists($old_path)) {
                                unlink($old_path);
                            }
                        }
                    }
                } else {
                    $message = 'Ошибка при перемещении файла. Проверьте права на папку uploads.';
                    $message_type = 'error';
                    $upload_error = true;
                }
            }
        } elseif ($service_id) {
            // При редактировании, если новое фото НЕ загружено, оставляем старое
            $stmt_old = $pdo->prepare("SELECT Photo FROM Services WHERE ID_services = ?");
            $stmt_old->execute([$service_id]);
            $old_service = $stmt_old->fetch(PDO::FETCH_ASSOC);
            $photo = $old_service['Photo'] ?? '';
        }
        // Если service_id нет (добавление нового) и фото не загружено, $photo остается пустым ('')
    }
    // --- КОНЕЦ БЛОКА ЗАГРУЗКИ ФОТО ---
    
    // Если не было критических ошибок загрузки, продолжаем сохранение в БД
    if (!$upload_error || empty($message)) {
        if (empty($name) || empty($unit) || $price < 0) {
            $message = 'Заполните обязательные поля (Название, Ед. изм., Цена >= 0)';
            $message_type = 'error';
        } else {
            try {
                if ($service_id) {
                    // Обновление существующей услуги
                    $stmt = $pdo->prepare("UPDATE Services SET 
                        Name = ?, 
                        Category = ?, 
                        Description = ?, 
                        Unit = ?, 
                        Price = ?, 
                        Relevance = ?, 
                        Photo = ? 
                        WHERE ID_services = ?");
                    $stmt->execute([$name, $category, $description, $unit, $price, $relevance, $photo, $service_id]);
                    $message = 'Услуга успешно обновлена';
                    $message_type = 'success';
                } else {
                    // Добавление новой услуги
                    $stmt = $pdo->prepare("INSERT INTO Services (Name, Category, Description, Unit, Price, Relevance, Photo) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $category, $description, $unit, $price, $relevance, $photo]);
                    $message = 'Услуга успешно добавлена';
                    $message_type = 'success';
                }
            } catch (PDOException $e) {
                $message = 'Ошибка БД: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
    
    // Перезагрузка страницы для обновления списка
    header('Location: services.php?msg=' . urlencode($message) . '&type=' . $message_type);
    exit;
}

// Получение сообщения из URL
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $message_type = isset($_GET['type']) ? $_GET['type'] : 'success';
}

// Получение категорий
$categories = [];
try {
    $stmt = $pdo->query("SELECT ID_category, Name FROM Category ORDER BY Name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}

// Получение всех услуг
$services = [];
try {
    $stmt = $pdo->query("SELECT s.*, c.Name as category_name 
                         FROM Services s 
                         LEFT JOIN Category c ON s.Category = c.ID_category 
                         ORDER BY s.Name");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $services = [];
}

$page_title = "Управление услугами";
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
                    <li><a href="services.php" class="active">🛠️ Услуги</a></li>
                    <li><a href="categories.php">📁 Категории</a></li>
                    <li><a href="personnel.php">👥 Персонал</a></li>
                <?php else: ?>
                    <li><a href="orders.php">📋 Заявки</a></li>
                    <li><a href="services.php" class="active">🛠️ Услуги</a></li>
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
                <h1>Управление услугами</h1>
                <div class="user-info">
                    <span><?= htmlspecialchars($user_fio) ?></span>
                    <span class="user-info-span">
                        <?= $role === 'admin' ? 'Администратор' : 'Менеджер' ?>
                    </span>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="message <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <div class="content-section">
                <div class="content-header-flex">
                    <h2 class="content-title-no-margin">Все услуги (<?= count($services) ?>)</h2>
                    <?php if ($role === 'admin'): ?>
                        <button class="btn-small btn-success" onclick="openAddModal()">+ Добавить услугу</button>
                    <?php endif; ?>
                </div>
                
                <div class="filter-bar">
                    <input type="text" id="searchInput" placeholder="Поиск по названию..." onkeyup="filterTable()">
                    <select id="categoryFilter" onchange="filterTable()">
                        <option value="">Все категории</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['ID_category'] ?>"><?= htmlspecialchars($cat['Name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <?php if (empty($services)): ?>
                    <p>Услуги не найдены.</p>
                <?php else: ?>
                    <table class="services-table" id="servicesTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Фото</th>
                                <th>Название</th>
                                <th>Категория</th>
                                <th>Цена</th>
                                <th>Ед. изм.</th>
                                <th>Актуальна</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $service): ?>
                                <tr data-category="<?= $service['Category'] ?>">
                                    <td>#<?= $service['ID_services'] ?></td>
                                    <td>
                                        <?php if (!empty($service['Photo'])): ?>
                                            <!-- Путь к фото: ../../uploads/имя_файла -->
                                            <img src="../../uploads/<?= htmlspecialchars($service['Photo']) ?>" alt="Фото" class="photo-thumbnail">
                                        <?php else: ?>
                                            <div class="photo-placeholder">Нет фото</div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($service['Name']) ?></td>
                                    <td><?= htmlspecialchars($service['category_name'] ?? 'Без категории') ?></td>
                                    <td><?= number_format($service['Price'], 2) ?> ₽</td>
                                    <td><?= htmlspecialchars($service['Unit']) ?></td>
                                    <td>
                                        <span class="relevance-badge <?= $service['Relevance'] === 'да' ? 'relevance-yes' : 'relevance-no' ?>">
                                            <?= $service['Relevance'] === 'да' ? 'Да' : 'Нет' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="../../pages/user/service_detail.php?id=<?= $service['ID_services'] ?>" class="btn-small btn-primary" target="_blank">Просмотр</a>
                                        <?php if ($role === 'admin'): ?>
                                            <button class="btn-small btn-primary" onclick="openEditModal(<?= htmlspecialchars(json_encode($service)) ?>)">Ред.</button>
                                            <button class="btn-small btn-danger" onclick="deleteService(<?= $service['ID_services'] ?>)">Удалить</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Модальное окно добавления/редактирования -->
    <div id="serviceModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Добавить услугу</h2>
            
            <!-- ВАЖНО: enctype="multipart/form-data" обязателен для загрузки файлов! -->
            <form id="serviceForm" method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" id="service_id" name="service_id" value="">
                
                <div class="form-group">
                    <label for="name">Название услуги *</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="category">Категория</label>
                    <select id="category" name="category">
                        <option value="">Без категории</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['ID_category'] ?>"><?= htmlspecialchars($cat['Name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="description">Описание</label>
                    <textarea id="description" name="description"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="unit">Единица измерения *</label>
                    <input type="text" id="unit" name="unit" placeholder="например: м², шт., час" required>
                </div>
                
                <div class="form-group">
                    <label for="price">Цена (₽) *</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="relevance">Актуальна</label>
                    <select id="relevance" name="relevance">
                        <option value="да">Да</option>
                        <option value="Нет">Нет</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="photo">Фото услуги</label>
                    <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/gif,image/webp">
                    <small class="form-help-text">Разрешенные форматы: JPEG, PNG, GIF, WebP. Макс. размер: 5MB</small>
                    <div id="photoPreview" class="photo-preview-container"></div>
                </div>
                
                <button type="submit" class="btn-submit">Сохранить</button>
            </form>
        </div>
    </div>
    
    <script>
        function filterTable() {
            const searchInput = document.getElementById('searchInput').value.toUpperCase();
            const categoryFilter = document.getElementById('categoryFilter').value;
            const table = document.getElementById('servicesTable');
            const tr = table.getElementsByTagName('tr');
            
            for (let i = 1; i < tr.length; i++) {
                const tdName = tr[i].getElementsByTagName('td')[2]; // Название теперь в 3-й колонке (индекс 2) из-за фото
                const category = tr[i].getAttribute('data-category');
                
                let showRow = true;
                
                if (searchInput && tdName) {
                    const nameValue = tdName.textContent || tdName.innerText;
                    if (nameValue.toUpperCase().indexOf(searchInput) === -1) {
                        showRow = false;
                    }
                }
                
                if (categoryFilter && category !== categoryFilter) {
                    showRow = false;
                }
                
                tr[i].style.display = showRow ? '' : 'none';
            }
        }
        
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Добавить услугу';
            document.getElementById('serviceForm').reset();
            document.getElementById('service_id').value = '';
            document.getElementById('photoPreview').innerHTML = '';
            document.getElementById('serviceModal').style.display = 'block';
        }
        
        function openEditModal(service) {
            document.getElementById('modalTitle').textContent = 'Редактировать услугу';
            document.getElementById('service_id').value = service.ID_services;
            document.getElementById('name').value = service.Name;
            document.getElementById('category').value = service.Category || '';
            document.getElementById('description').value = service.Description || '';
            document.getElementById('unit').value = service.Unit;
            document.getElementById('price').value = service.Price;
            document.getElementById('relevance').value = service.Relevance;
            document.getElementById('photo').value = ''; // Очищаем input file
            
            // Показываем текущее фото если оно есть
            const previewDiv = document.getElementById('photoPreview');
            if (service.Photo && service.Photo !== '') {
                // Путь к превью: ../../uploads/имя_файла
                previewDiv.innerHTML = '<div class="photo-preview-container"><img src="../../uploads/' + service.Photo + '" alt="Текущее фото" class="modal-preview-img"></div><small>Загрузите новое фото, чтобы заменить текущее</small>';
            } else {
                previewDiv.innerHTML = '';
            }
            
            document.getElementById('serviceModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('serviceModal').style.display = 'none';
        }
        
        function deleteService(id) {
            if (confirm('Вы уверены, что хотите удалить эту услугу? Фото также будет удалено.')) {
                window.location.href = '?action=delete&id=' + id;
            }
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('serviceModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>