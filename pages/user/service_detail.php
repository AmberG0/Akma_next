<?php
session_start();
require_once '../../i/WebsiteBackend/db.php';

// Получение ID услуги
$service_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($service_id <= 0) {
    header('Location: catalog.php');
    exit;
}

// Получение информации об услуге
$service = null;
try {
    $stmt = $pdo->prepare("SELECT s.*, c.Name as category_name 
                           FROM Services s 
                           LEFT JOIN Category c ON s.Category = c.ID_category 
                           WHERE s.ID_services = ?");
    $stmt->execute([$service_id]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $service = null;
}

if (!$service) {
    header('Location: catalog.php');
    exit;
}

// Получение похожих услуг из той же категории
$similar_services = [];
if ($service['Category']) {
    try {
        $stmt = $pdo->prepare("SELECT s.*, c.Name as category_name 
                               FROM Services s 
                               LEFT JOIN Category c ON s.Category = c.ID_category 
                               WHERE s.Category = ? AND s.ID_services != ? AND s.Relevance = 'да' 
                               LIMIT 4");
        $stmt->execute([$service['Category'], $service_id]);
        $similar_services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $similar_services = [];
    }
}

$page_title = $service['Name'];
include 'includes/header.php';
?>

<div class="container">
    <!-- Карточка услуги -->
    <section class="service-detail-section" style="margin: 30px 0;">
        <div class="service-detail-card">
            <!-- Левая колонка: Фото и основная информация -->
            <div class="detail-left">
                <div class="detail-photo">
                    <?php if (!empty($service['Photo']) && file_exists('../../uploads/' . $service['Photo'])): ?>
                        <img src="../../uploads/<?= htmlspecialchars($service['Photo']) ?>" alt="<?= htmlspecialchars($service['Name']) ?>">
                    <?php else: ?>
                        <div class="photo-placeholder-large">Нет фото</div>
                    <?php endif; ?>
                </div>
                
                <div class="detail-price-block">
                    <div class="detail-price"><?= number_format($service['Price'], 2) ?> ₽</div>
                    <div class="detail-unit">за <?= htmlspecialchars($service['Unit']) ?></div>
                    <button class="btn-order-large" data-id="<?= $service['ID_services'] ?>" 
                            data-name="<?= htmlspecialchars($service['Name']) ?>" 
                            data-price="<?= $service['Price'] ?>" 
                            data-unit="<?= htmlspecialchars($service['Unit']) ?>">
                        Добавить в подборку
                    </button>
                </div>
            </div>
            
            <!-- Правая колонка: Описание и характеристики -->
            <div class="detail-right">
                <span class="service-category-badge"><?= htmlspecialchars($service['category_name'] ?? 'Услуга') ?></span>
                <h1 class="detail-title"><?= htmlspecialchars($service['Name']) ?></h1>
                
                <div class="detail-description">
                    <h3>Описание услуги</h3>
                    <p><?= nl2br(htmlspecialchars($service['Description'] ?: 'Описание отсутствует')) ?></p>
                </div>
                
                <div class="detail-specs">
                    <h3>Характеристики</h3>
                    <div class="spec-row">
                        <span class="spec-label">Единица измерения:</span>
                        <span class="spec-value"><?= htmlspecialchars($service['Unit']) ?></span>
                    </div>
                    <div class="spec-row">
                        <span class="spec-label">Время выполнения:</span>
                        <span class="spec-value">1 день за ед. изм</span>
                    </div>
                    <div class="spec-row">
                        <span class="spec-label">Минимальный заказ:</span>
                        <span class="spec-value">1 <?= htmlspecialchars($service['Unit']) ?></span>
                    </div>
                    <div class="spec-row">
                        <span class="spec-label">Максимальный заказ:</span>
                        <span class="spec-value">Не ограничен</span>
                    </div>
                    <div class="spec-row">
                        <span class="spec-label">Уборка территории:</span>
                        <span class="spec-value">Включена</span>
                    </div>
                    <div class="spec-row">
                        <span class="spec-label">Вынос мусора:</span>
                        <span class="spec-value">Да</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Похожие услуги -->
    <?php if (!empty($similar_services)): ?>
    <section class="similar-services-section" style="margin: 40px 0;">
        <h2 class="section-title">Похожие услуги</h2>
        <div class="services-grid">
            <?php foreach ($similar_services as $similar): ?>
                <div class="service-card" onclick="window.location.href='service_detail.php?id=<?= $similar['ID_services'] ?>'">
                    <div class="service-left">
                        <span class="service-type"><?= htmlspecialchars($similar['category_name'] ?? 'Услуга') ?></span>
                        <div class="service-photo">
                            <?php if (!empty($similar['Photo']) && file_exists('../../uploads/' . $similar['Photo'])): ?>
                                <img src="../../uploads/<?= htmlspecialchars($similar['Photo']) ?>" alt="<?= htmlspecialchars($similar['Name']) ?>">
                            <?php else: ?>
                                <div class="photo-placeholder">Нет фото</div>
                            <?php endif; ?>
                        </div>
                        <button class="btn-order" data-id="<?= $similar['ID_services'] ?>" 
                                data-name="<?= htmlspecialchars($similar['Name']) ?>" 
                                data-price="<?= $similar['Price'] ?>" 
                                data-unit="<?= htmlspecialchars($similar['Unit']) ?>"
                                onclick="event.stopPropagation()">
                            Оформить
                        </button>
                    </div>
                    
                    <div class="service-center">
                        <h3 class="service-title"><?= htmlspecialchars($similar['Name']) ?></h3>
                        <p class="service-description"><?= nl2br(htmlspecialchars(mb_substr($similar['Description'] ?: '', 0, 100))) ?>...</p>
                    </div>
                    
                    <div class="service-right">
                        <div class="info-item price-item">
                            <span class="info-label">Цена:</span>
                            <span class="info-value price"><?= number_format($similar['Price'], 2) ?> ₽ / <?= htmlspecialchars($similar['Unit']) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
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

<style>
.service-detail-section {
    background-color: #FFFFFF;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.service-detail-card {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: 40px;
}

.detail-left {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.detail-photo img {
    width: 100%;
    height: auto;
    border-radius: 8px;
    object-fit: cover;
}

.photo-placeholder-large {
    width: 100%;
    height: 300px;
    background-color: #F5F5F5;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
    border-radius: 8px;
}

.detail-price-block {
    background-color: #F9F9F9;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    border: 2px solid #FFD700;
}

.detail-price {
    font-size: 32px;
    font-weight: bold;
    color: #1A1A1A;
}

.detail-unit {
    font-size: 14px;
    color: #666;
    margin-bottom: 15px;
}

.btn-order-large {
    background-color: #FFD700;
    color: #1A1A1A;
    border: none;
    padding: 15px 30px;
    font-size: 16px;
    font-weight: bold;
    border-radius: 4px;
    cursor: pointer;
    width: 100%;
    transition: all 0.3s;
}

.btn-order-large:hover {
    background-color: #e6c200;
    transform: scale(1.02);
}

.detail-right {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.service-category-badge {
    background-color: #FFD700;
    color: #1A1A1A;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    display: inline-block;
    width: fit-content;
}

.detail-title {
    font-size: 32px;
    color: #1A1A1A;
    margin: 0;
}

.detail-description h3,
.detail-specs h3 {
    font-size: 18px;
    color: #1A1A1A;
    margin-bottom: 10px;
    border-bottom: 2px solid #FFD700;
    padding-bottom: 5px;
}

.detail-description p {
    color: #333;
    line-height: 1.6;
}

.detail-specs {
    background-color: #F9F9F9;
    padding: 20px;
    border-radius: 8px;
}

.spec-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #EEEEEE;
}

.spec-row:last-child {
    border-bottom: none;
}

.spec-label {
    color: #666;
    font-weight: 500;
}

.spec-value {
    color: #1A1A1A;
    font-weight: 600;
}

.section-title {
    font-size: 24px;
    color: #1A1A1A;
    border-bottom: 2px solid #FFD700;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.service-card {
    background-color: #FFFFFF;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s;
    cursor: pointer;
    display: grid;
    grid-template-columns: 200px 1fr auto;
    gap: 20px;
    padding: 20px;
}

.service-card:hover {
    transform: translateY(-5px);
}

.service-left {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.service-type {
    background-color: #FFD700;
    color: #1A1A1A;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    display: inline-block;
}

.service-photo {
    width: 100%;
    height: 120px;
    background-color: #F5F5F5;
    border-radius: 4px;
    overflow: hidden;
}

.service-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.photo-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
    font-size: 12px;
}

.btn-order {
    background-color: #FFD700;
    color: #1A1A1A;
    border: none;
    padding: 10px 15px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.3s;
}

.btn-order:hover {
    background-color: #e6c200;
}

.service-center {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.service-title {
    font-size: 18px;
    color: #1A1A1A;
    margin: 0;
}

.service-description {
    color: #666;
    font-size: 14px;
    line-height: 1.4;
}

.service-right {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.info-label {
    font-size: 12px;
    color: #999;
}

.info-value {
    font-size: 14px;
    color: #1A1A1A;
    font-weight: 600;
}

.info-value.price {
    font-size: 18px;
    color: #FFD700;
}

@media (max-width: 900px) {
    .service-detail-card {
        grid-template-columns: 1fr;
    }
    
    .service-card {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('orderModal');
    const closeModal = document.querySelector('.close-modal');
    const orderButtons = document.querySelectorAll('.btn-order, .btn-order-large');
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
