<?php
session_start();
require_once '../../i/WebsiteBackend/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
    exit;
}

// Получаем данные из формы
$service_id = isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0;
$service_name = isset($_POST['service_name']) ? trim($_POST['service_name']) : '';
$service_price = isset($_POST['service_price']) ? (float)$_POST['service_price'] : 0;
$service_unit = isset($_POST['service_unit']) ? trim($_POST['service_unit']) : 'ед.';
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

// Данные клиента (сохраняем в сессию для последующего оформления)
$client_name = isset($_POST['client_name']) ? trim($_POST['client_name']) : '';
$client_phone = isset($_POST['client_phone']) ? trim($_POST['client_phone']) : '';
$client_email = isset($_POST['client_email']) ? trim($_POST['client_email']) : '';

// Валидация
if ($service_id <= 0 || empty($service_name)) {
    echo json_encode(['success' => false, 'message' => 'Некорректные данные услуги']);
    exit;
}

if ($quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Количество должно быть больше 0']);
    exit;
}

// Сохраняем контакты клиента в сессию (если переданы)
if (!empty($client_name) && !empty($client_phone) && !empty($client_email)) {
    $_SESSION['client'] = [
        'name' => $client_name,
        'phone' => $client_phone,
        'email' => $client_email
    ];
}

// Инициализируем корзину в сессии, если её нет
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Проверяем, есть ли уже такая услуга в корзине
$found = false;
foreach ($_SESSION['cart'] as &$item) {
    if ($item['service_id'] == $service_id) {
        $item['quantity'] += $quantity;
        $found = true;
        break;
    }
}
unset($item);

if (!$found) {
    // Добавляем новую услугу в корзину
    $_SESSION['cart'][] = [
        'service_id' => $service_id,
        'name' => $service_name,
        'price' => $service_price,
        'unit' => $service_unit,
        'quantity' => $quantity
    ];
}

// Возвращаем успешный ответ
echo json_encode([
    'success' => true,
    'message' => 'Услуга добавлена в подборку',
    'cart_count' => count($_SESSION['cart'])
]);
