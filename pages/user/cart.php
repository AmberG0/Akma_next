<?php
session_start();
// Подключение к базе данных (относительный путь из pages/user/)
require_once '../../i/WebsiteBackend/db.php';

$page_title = "Подборка услуг";
// Подключаем header с правильным относительным путем
include 'includes/header.php';

// Получаем корзину из сессии
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

// Считаем общую сумму
$total = 0;
foreach ($cart as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Получаем данные клиента из сессии (если заполнялись ранее)
$client_name = isset($_SESSION['client']['name']) ? $_SESSION['client']['name'] : '';
$client_phone = isset($_SESSION['client']['phone']) ? $_SESSION['client']['phone'] : '';
$client_email = isset($_SESSION['client']['email']) ? $_SESSION['client']['email'] : '';
$client_address = isset($_SESSION['client']['address']) ? $_SESSION['client']['address'] : '';
?>

<div class="container">
    <h1 class="page-title">Подборка услуг</h1>

    <?php if (empty($cart)): ?>
        <div class="empty-cart">
            <p>Ваша подборка пуста.</p>
            <a href="catalog.php" class="btn-primary">Перейти в каталог</a>
        </div>
    <?php else: ?>
        <!-- Список услуг в подборке -->
        <section class="cart-section">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Услуга</th>
                        <th>Цена за ед.</th>
                        <th>Количество</th>
                        <th>Сумма</th>
                        <th>Действие</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart as $index => $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td><?= number_format($item['price'], 2) ?> ₽ / <?= htmlspecialchars($item['unit']) ?></td>
                            <td>
                                <input type="number" 
                                       class="quantity-input" 
                                       data-index="<?= $index ?>" 
                                       value="<?= $item['quantity'] ?>" 
                                       min="1" 
                                       data-unit="<?= htmlspecialchars($item['unit']) ?>">
                            </td>
                            <td><span class="item-total"><?= number_format($item['price'] * $item['quantity'], 2) ?></span> ₽</td>
                            <td>
                                <button class="btn-remove" data-index="<?= $index ?>">Удалить</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="cart-total">
                <strong>Итого:</strong> <span id="cart-total-amount"><?= number_format($total, 2) ?></span> ₽
            </div>
        </section>

        <!-- Форма оформления заявки -->
        <section class="order-form-section">
            <h2 class="section-title">Оформление заявки</h2>
            <form id="checkoutForm" method="POST" action="checkout.php">
                <div class="form-row">
                    <div class="form-group">
                        <label for="client_name">Ваше имя *</label>
                        <input type="text" id="client_name" name="client_name" value="<?= htmlspecialchars($client_name) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="client_phone">Телефон *</label>
                        <input type="tel" id="client_phone" name="client_phone" value="<?= htmlspecialchars($client_phone) ?>" required placeholder="+7 (___) ___-__-__">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="client_email">Email *</label>
                        <input type="email" id="client_email" name="client_email" value="<?= htmlspecialchars($client_email) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="client_address">Адрес объекта</label>
                        <input type="text" id="client_address" name="client_address" value="<?= htmlspecialchars($client_address) ?>" placeholder="г. Энгельс, ул...">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="desired_date">Желаемая дата начала работ</label>
                        <input type="date" id="desired_date" name="desired_date" min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="comments">Комментарий к заказу</label>
                    <textarea id="comments" name="comments" rows="4" placeholder="Дополнительная информация..."></textarea>
                </div>
                
                <!-- Блок оплаты картой -->
                <div class="payment-card-section">
                    <h3 class="payment-title">💳 Оплата банковской картой</h3>
                    <div class="card-inputs-compact">
                        <div class="form-group-compact">
                            <label for="card_number">Номер карты *</label>
                            <input type="text" id="card_number" name="card_number" required placeholder="0000 0000 0000 0000" maxlength="19" pattern="[0-9\s]{13,19}">
                        </div>
                        
                        <div class="form-row-compact">
                            <div class="form-group-compact">
                                <label for="card_expiry">Срок *</label>
                                <input type="text" id="card_expiry" name="card_expiry" required placeholder="ММ/ГГ" maxlength="5" pattern="(0[1-9]|1[0-2])/[0-9]{2}">
                            </div>
                            
                            <div class="form-group-compact">
                                <label for="card_cvv">CVV *</label>
                                <input type="password" id="card_cvv" name="card_cvv" required placeholder="123" maxlength="3" pattern="[0-9]{3}">
                            </div>
                        </div>
                        
                        <div class="form-group-compact">
                            <label for="card_holder">Имя держателя *</label>
                            <input type="text" id="card_holder" name="card_holder" required placeholder="IVAN IVANOV" pattern="[A-Z\s]+">
                        </div>
                    </div>
                    <p class="payment-note">🔒 3D Secure</p>
                </div>
                
                <div class="form-group privacy-consent">
                    <label class="checkbox-label">
                        <input type="checkbox" id="privacy_consent" name="privacy_consent" required>
                        <span>Я согласен на <a href="policy.php" target="_blank" class="privacy-link">обработку персональных данных</a> *</span>
                    </label>
                </div>
                
                <div class="form-submit">
                    <button type="submit" class="btn-submit-order">Оплатить и оформить заявку</button>
                </div>
            </form>
        </section>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Обновление количества и пересчет суммы
    const quantityInputs = document.querySelectorAll('.quantity-input');
    const removeButtons = document.querySelectorAll('.btn-remove');
    
    quantityInputs.forEach(input => {
        input.addEventListener('change', function() {
            const index = this.dataset.index;
            const quantity = parseInt(this.value);
            
            if (quantity < 1) {
                alert('Минимальное количество: 1');
                this.value = 1;
                return;
            }
            
            // Отправляем AJAX запрос на обновление
            fetch('update_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_quantity&index=${index}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Обновляем сумму позиции
                    const row = this.closest('tr');
                    row.querySelector('.item-total').textContent = data.item_total;
                    // Обновляем общую сумму
                    document.getElementById('cart-total-amount').textContent = data.total;
                } else {
                    alert('Ошибка обновления: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при обновлении');
            });
        });
    });
    
    // Удаление из корзины
    removeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const index = this.dataset.index;
            
            if (!confirm('Удалить эту услугу из подборки?')) {
                return;
            }
            
            fetch('update_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=remove_item&index=${index}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.cart_empty) {
                        location.reload();
                    } else {
                        location.reload();
                    }
                } else {
                    alert('Ошибка удаления: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при удалении');
            });
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
