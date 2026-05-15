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

<?php include 'includes/footer.php'; ?>
