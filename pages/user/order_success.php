<?php
session_start();
require_once '../../i/WebsiteBackend/db.php';

$page_title = "Заказ оформлен";
include 'includes/header.php';

$order_count = isset($_GET['order_count']) ? (int)$_GET['order_count'] : 0;
?>

<div class="container">
    <div class="success-message">
        <div class="success-icon">✓</div>
        <h1 class="page-title">Заказ успешно оформлен!</h1>
        
        <p class="success-text">
            Спасибо за ваш заказ! Мы получили <?= $order_count ?> позицию(и) услуг.
        </p>
        
        <div class="success-info">
            <p>Наш менеджер свяжется с вами в ближайшее время для уточнения деталей.</p>
            <p>Если вы указали email, подтверждение будет отправлено на него.</p>
        </div>
        
        <div class="success-actions">
            <a href="catalog.php" class="btn-primary">Продолжить покупки</a>
            <a href="../../index.php" class="btn-outline">На главную</a>
        </div>
        
        <div class="contact-info">
            <h3>Контакты для связи:</h3>
            <p>Телефон: <a href="tel:+78453528292">+7 845 352-82-92</a></p>
            <p>Адрес: 413124, Саратовская обл., г. Энгельс, пр-д 1-й Студенческий, д. 2а</p>
            <p>Режим работы: Пн–Пт, 8:30–17:00</p>
        </div>
    </div>
</div>

<style>
.success-message {
    max-width: 600px;
    margin: 60px auto;
    text-align: center;
    padding: 40px;
    background-color: var(--bg-light);
    border-radius: 8px;
    box-shadow: var(--shadow);
    border-top: 4px solid var(--primary-yellow);
}

.success-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
    background-color: #4CAF50;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    font-weight: bold;
}

.success-text {
    font-size: 18px;
    margin-bottom: 20px;
    color: var(--text-dark);
}

.success-info {
    background-color: var(--bg-gray);
    padding: 20px;
    border-radius: 4px;
    margin-bottom: 30px;
    text-align: left;
}

.success-info p {
    margin-bottom: 10px;
}

.success-info p:last-child {
    margin-bottom: 0;
}

.success-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-bottom: 30px;
}

.contact-info {
    border-top: 1px solid var(--border-color);
    padding-top: 20px;
    text-align: left;
}

.contact-info h3 {
    margin-bottom: 15px;
    color: var(--primary-dark);
}

body.dark-theme .contact-info h3 {
    color: var(--primary-yellow);
}

.contact-info p {
    margin-bottom: 8px;
}

.contact-info a {
    color: var(--primary-yellow);
    text-decoration: none;
}

@media (max-width: 768px) {
    .success-actions {
        flex-direction: column;
    }
    
    .success-message {
        margin: 20px;
        padding: 20px;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
