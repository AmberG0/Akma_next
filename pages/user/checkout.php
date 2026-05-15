<?php
session_start();
require_once '../../i/WebsiteBackend/db.php';

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cart.php');
    exit;
}

// Проверяем наличие корзины
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    $_SESSION['error'] = 'Корзина пуста';
    header('Location: cart.php');
    exit;
}

// Получаем данные из формы
$client_name = isset($_POST['client_name']) ? trim($_POST['client_name']) : '';
$client_phone = isset($_POST['client_phone']) ? trim($_POST['client_phone']) : '';
$client_email = isset($_POST['client_email']) ? trim($_POST['client_email']) : '';
$client_address = isset($_POST['client_address']) ? trim($_POST['client_address']) : '';
$desired_date = isset($_POST['desired_date']) && !empty($_POST['desired_date']) ? $_POST['desired_date'] : null;
$payment_type = 'card'; // Только оплата картой
$privacy_consent = isset($_POST['privacy_consent']) ? true : false;
$comments = isset($_POST['comments']) ? trim($_POST['comments']) : '';

// Данные карты
$card_number = isset($_POST['card_number']) ? trim($_POST['card_number']) : '';
$card_expiry = isset($_POST['card_expiry']) ? trim($_POST['card_expiry']) : '';
$card_cvv = isset($_POST['card_cvv']) ? trim($_POST['card_cvv']) : '';
$card_holder = isset($_POST['card_holder']) ? trim($_POST['card_holder']) : '';

// Валидация обязательных полей
$errors = [];
if (empty($client_name)) {
    $errors[] = 'Необходимо указать имя';
}
if (empty($client_phone)) {
    $errors[] = 'Необходимо указать телефон';
}
if (empty($client_email)) {
    $errors[] = 'Необходимо указать email';
}
if (!$privacy_consent) {
    $errors[] = 'Необходимо согласие на обработку персональных данных';
}

// Валидация данных карты
if (empty($card_number)) {
    $errors[] = 'Необходимо указать номер карты';
} elseif (!preg_match('/^[0-9\s]{13,19}$/', $card_number)) {
    $errors[] = 'Некорректный номер карты';
}

if (empty($card_expiry)) {
    $errors[] = 'Необходимо указать срок действия карты';
} elseif (!preg_match('/^(0[1-9]|1[0-2])\/[0-9]{2}$/', $card_expiry)) {
    $errors[] = 'Некорректный срок действия карты (формат ММ/ГГ)';
} else {
    // Проверка срока действия карты
    list($exp_month, $exp_year) = explode('/', $card_expiry);
    $current_year = date('y');
    $current_month = date('n');
    
    if ($exp_year < $current_year || ($exp_year == $current_year && $exp_month < $current_month)) {
        $errors[] = 'Срок действия карты истёк';
    }
}

if (empty($card_cvv)) {
    $errors[] = 'Необходимо указать CVV/CVC код';
} elseif (!preg_match('/^[0-9]{3}$/', $card_cvv)) {
    $errors[] = 'Некорректный CVV/CVC код (должен быть 3 цифры)';
}

if (empty($card_holder)) {
    $errors[] = 'Необходимо указать имя держателя карты';
} elseif (!preg_match('/^[A-Z\s]+$/', strtoupper($card_holder))) {
    $errors[] = 'Имя держателя карты должно быть указано латинскими буквами';
}

if (!empty($errors)) {
    $_SESSION['error'] = implode(', ', $errors);
    header('Location: cart.php');
    exit;
}

try {
    // Начинаем транзакцию
    $pdo->beginTransaction();
    
    // Сохраняем контакты клиента в сессию для будущего использования
    $_SESSION['client'] = [
        'name' => $client_name,
        'phone' => $client_phone,
        'email' => $client_email,
        'address' => $client_address
    ];
    
    // Создаем записи в Services_tab для каждой услуги в корзине
    $service_tab_ids = [];
    $total_amount = 0;
    
    foreach ($_SESSION['cart'] as $item) {
        $item_total = $item['price'] * $item['quantity'];
        $total_amount += $item_total;
        
        $stmt = $pdo->prepare("
            INSERT INTO Services_tab 
            (Description, Data_create, Data_start, Service_list_ID, Service_list_count, Count_pay) 
            VALUES 
            (:description, NOW(), :data_start, :service_id, :count, :pay)
        ");
        
        $stmt->execute([
            ':description' => $item['name'],
            ':data_start' => $desired_date ?: null,
            ':service_id' => $item['service_id'],
            ':count' => $item['quantity'],
            ':pay' => $item_total
        ]);
        
        $service_tab_ids[] = $pdo->lastInsertId();
    }
    
    // Создаем заявку (Orders) - одну на весь заказ
    // Если несколько услуг, создаем отдельную запись заказа для каждой услуги
    foreach ($service_tab_ids as $service_tab_id) {
        $stmt = $pdo->prepare("
            INSERT INTO Orders 
            (Client, Num_phone, Mail, Service_tab_ID, Time_the_bell, Type_pay, Face_client, Other_inform, Status) 
            VALUES 
            (:client, :phone, :email, :service_tab_id, :time_bell, :type_pay, 'physical', :other_inform, 'новая')
        ");
        
        $stmt->execute([
            ':client' => $client_name,
            ':phone' => $client_phone,
            ':email' => $client_email,
            ':service_tab_id' => $service_tab_id,
            ':time_bell' => $desired_date ? $desired_date . ' 09:00:00' : null,
            ':type_pay' => $payment_type,
            ':other_inform' => !empty($client_address) ? 'Адрес: ' . $client_address . '. Комментарий: ' . $comments : $comments
        ]);
    }
    
    // Фиксируем транзакцию
    $pdo->commit();
    
    // Очищаем корзину после успешного оформления
    unset($_SESSION['cart']);
    
    // Перенаправляем на страницу успеха
    header('Location: order_success.php?order_count=' . count($service_tab_ids));
    exit;
    
} catch (PDOException $e) {
    // Откат транзакции в случае ошибки
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('Ошибка при оформлении заказа: ' . $e->getMessage());
    $_SESSION['error'] = 'Произошла ошибка при оформлении заказа. Попробуйте позже.';
    header('Location: cart.php');
    exit;
}
