<?php
session_start();
require_once '../../i/WebsiteBackend/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

// Проверка наличия корзины
if (!isset($_SESSION['cart'])) {
    echo json_encode(['success' => false, 'message' => 'Корзина пуста']);
    exit;
}

switch ($action) {
    case 'update_quantity':
        $index = isset($_POST['index']) ? (int)$_POST['index'] : -1;
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        
        if ($index < 0 || $index >= count($_SESSION['cart'])) {
            echo json_encode(['success' => false, 'message' => 'Неверный индекс']);
            exit;
        }
        
        if ($quantity < 1) {
            echo json_encode(['success' => false, 'message' => 'Количество должно быть больше 0']);
            exit;
        }
        
        // Обновляем количество
        $_SESSION['cart'][$index]['quantity'] = $quantity;
        
        // Считаем новую сумму позиции и общую сумму
        $item_total = number_format($_SESSION['cart'][$index]['price'] * $quantity, 2);
        $total = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        
        echo json_encode([
            'success' => true,
            'item_total' => $item_total,
            'total' => number_format($total, 2)
        ]);
        break;
        
    case 'remove_item':
        $index = isset($_POST['index']) ? (int)$_POST['index'] : -1;
        
        if ($index < 0 || $index >= count($_SESSION['cart'])) {
            echo json_encode(['success' => false, 'message' => 'Неверный индекс']);
            exit;
        }
        
        // Удаляем элемент
        array_splice($_SESSION['cart'], $index, 1);
        
        $cart_empty = empty($_SESSION['cart']);
        
        echo json_encode([
            'success' => true,
            'cart_empty' => $cart_empty
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Неизвестное действие']);
        break;
}
