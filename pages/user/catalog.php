<?php
session_start();
require_once '../../i/WebsiteBackend/db.php';

// Получение категорий для фильтра
$categories = [];
try {
    $stmt = $pdo->query("SELECT ID_category, Name FROM Category WHERE Name IS NOT NULL ORDER BY Name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}

// Параметры поиска и сортировки
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$filter_category = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Построение SQL запроса
$sql = "SELECT s.*, c.Name as category_name 
        FROM Services s 
        LEFT JOIN Category c ON s.Category = c.ID_category 
        WHERE s.Relevance = 'да'";

$params = [];

// Поиск по названию или описанию
if (!empty($search_query)) {
    $sql .= " AND (s.Name LIKE ? OR s.Description LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}

// Фильтр по категории
if ($filter_category > 0) {
    $sql .= " AND s.Category = ?";
    $params[] = $filter_category;
}

// Сортировка
switch ($sort_by) {
    case 'price_asc':
        $sql .= " ORDER BY s.Price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY s.Price DESC";
        break;
    case 'name':
    default:
        $sql .= " ORDER BY s.Name";
        break;
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Ошибка при загрузке услуг: ' . $e->getMessage());
    $services = [];
}

$page_title = "Каталог услуг";
include 'includes/header.php';
?>

<div class="container">
    <h1 class="page-title">Каталог услуг</h1>

    <!-- Фильтры и поиск -->
    <section class="filters-section">
        <div class="filters-wrapper">
            <form method="GET" action="" class="filters-form">
                <div class="filter-group">
                    <label for="search">Поиск:</label>
                    <input type="text" name="search" id="search" placeholder="Название или описание..." value="<?= htmlspecialchars($search_query) ?>">
                </div>
                
                <div class="filter-group">
                    <label for="category">Категория:</label>
                    <select name="category" id="category" onchange="this.form.submit()">
                        <option value="0">Все категории</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['ID_category'] ?>" <?= $filter_category == $cat['ID_category'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['Name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="sort">Сортировка:</label>
                    <select name="sort" id="sort" onchange="this.form.submit()">
                        <option value="name" <?= $sort_by == 'name' ? 'selected' : '' ?>>По названию</option>
                        <option value="price_asc" <?= $sort_by == 'price_asc' ? 'selected' : '' ?>>Цена: по возрастанию</option>
                        <option value="price_desc" <?= $sort_by == 'price_desc' ? 'selected' : '' ?>>Цена: по убыванию</option>
                    </select>
                </div>
                
                <?php if (!empty($search_query) || $filter_category > 0 || $sort_by !== 'name'): ?>
                    <div class="filter-group">
                        <a href="catalog.php" class="btn-reset">✕ Сбросить</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </section>

    <!-- Каталог услуг -->
    <section class="catalog-section">
        <?php if (empty($services)): ?>
            <p class="no-services">Услуги не найдены.</p>
        <?php else: ?>
            <div class="services-grid">
                <?php foreach ($services as $service): ?>
                    <div class="service-card" onclick="window.location.href='service_detail.php?id=<?= $service['ID_services'] ?>'">
                        <!-- Левый блок: Тип, Фото, Кнопка -->
                        <div class="service-left">
                            <span class="service-type"><?= htmlspecialchars($service['category_name'] ?? 'Услуга') ?></span>
                            <div class="service-photo">
                                <?php if (!empty($service['Photo']) && file_exists('../../uploads/' . $service['Photo'])): ?>
                                    <img src="../../uploads/<?= htmlspecialchars($service['Photo']) ?>" alt="<?= htmlspecialchars($service['Name']) ?>">
                                <?php else: ?>
                                    <div class="photo-placeholder">Нет фото</div>
                                <?php endif; ?>
                            </div>
                            <button class="btn-order" data-id="<?= $service['ID_services'] ?>" 
                                    data-name="<?= htmlspecialchars($service['Name']) ?>" 
                                    data-price="<?= $service['Price'] ?>" 
                                    data-unit="<?= htmlspecialchars($service['Unit']) ?>"
                                    onclick="event.stopPropagation()">
                                Оформить
                            </button>
                        </div>

                        <!-- Центральный блок: Описание -->
                        <div class="service-center">
                            <h3 class="service-title"><a href="service_detail.php?id=<?= $service['ID_services'] ?>" style="color: inherit; text-decoration: none;"><?= htmlspecialchars($service['Name']) ?></a></h3>
                            <p class="service-description"><?= nl2br(htmlspecialchars($service['Description'])) ?></p>
                        </div>

                        <!-- Правый блок: Информация -->
                        <div class="service-right">
                            <div class="info-item">
                                <span class="info-label">Время выполнения:</span>
                                <span class="info-value">1 день за ед. изм</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Уборка:</span>
                                <span class="info-value">Включена</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Вынос мусора:</span>
                                <span class="info-value">Да</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Мин. заказ:</span>
                                <span class="info-value">1 <?= htmlspecialchars($service['Unit']) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Макс. заказ:</span>
                                <span class="info-value">Не ограничен</span>
                            </div>
                            <div class="info-item price-item">
                                <span class="info-label">Цена:</span>
                                <span class="info-value price"><?= number_format($service['Price'], 2) ?> ₽ / <?= htmlspecialchars($service['Unit']) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<!-- Модальное окно оформления -->
<div id="orderModal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2>Оформление услуги</h2>
        <form id="orderForm" method="POST" action="add_to_cart.php">
            <input type="hidden" id="service_id" name="service_id">
            <input type="hidden" id="service_name" name="service_name">
            <input type="hidden" id="service_price" name="service_price">
            <input type="hidden" id="service_unit" name="service_unit">
            
            <div class="form-group">
                <label for="service_info">Услуга:</label>
                <input type="text" id="service_info" readonly>
            </div>
            
            <div class="form-group">
                <label for="quantity">Количество (<span id="unit-label">ед.</span>):</label>
                <input type="number" id="quantity" name="quantity" min="1" value="1" required>
            </div>
            
            <button type="submit" class="btn-submit">Добавить в подборку</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('orderModal');
    const closeModal = document.querySelector('.close-modal');
    const orderButtons = document.querySelectorAll('.btn-order');
    const orderForm = document.getElementById('orderForm');
    
    orderButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const price = this.dataset.price;
            const unit = this.dataset.unit;
            
            document.getElementById('service_id').value = id;
            document.getElementById('service_name').value = name;
            document.getElementById('service_price').value = price;
            document.getElementById('service_unit').value = unit;
            document.getElementById('service_info').value = `${name} - ${price} ₽ / ${unit}`;
            document.getElementById('unit-label').textContent = unit;
            
            modal.style.display = 'block';
        });
    });
    
    closeModal.addEventListener('click', function() {
        modal.style.display = 'none';
    });
    
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
    
    orderForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('add_to_cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Услуга добавлена в подборку!');
                modal.style.display = 'none';
                location.reload();
            } else {
                alert('Ошибка: ' + data.message);
            }
        })
        .catch(error => {
            alert('Произошла ошибка при добавлении услуги');
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
