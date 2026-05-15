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
$user_id = $_SESSION['user_id'];

// Обработка назначения менеджера на заявку
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'assign_manager' && isset($_POST['order_id'], $_POST['manager_id'])) {
        $order_id = (int)$_POST['order_id'];
        $manager_id = (int)$_POST['manager_id'];
        
        try {
            $stmt = $pdo->prepare("UPDATE Orders SET Accept_order_Per_ID = ?, Status = 'в работе' WHERE ID_order = ?");
            $stmt->execute([$manager_id, $order_id]);
            header('Location: orders.php?msg=Менеджер назначен&type=success');
            exit;
        } catch (PDOException $e) {
            header('Location: orders.php?msg=Ошибка при назначении&type=error');
            exit;
        }
    }
    
    if ($_POST['action'] === 'update_status' && isset($_POST['order_id'], $_POST['status'])) {
        $order_id = (int)$_POST['order_id'];
        $status = trim($_POST['status']);
        $allowed_statuses = ['новая', 'в работе', 'выполнена', 'отменена'];
        
        if (in_array($status, $allowed_statuses)) {
            try {
                $stmt = $pdo->prepare("UPDATE Orders SET Status = ? WHERE ID_order = ?");
                $stmt->execute([$status, $order_id]);
                header('Location: orders.php?msg=Статус обновлен&type=success');
                exit;
            } catch (PDOException $e) {
                header('Location: orders.php?msg=Ошибка при обновлении статуса&type=error');
                exit;
            }
        } else {
            header('Location: orders.php?msg=Недопустимый статус&type=error');
            exit;
        }
    }
}

// Получаем сообщение из URL
$message = isset($_GET['msg']) ? $_GET['msg'] : '';
$message_type = isset($_GET['type']) ? $_GET['type'] : '';

// Получаем все заявки
try {
    $stmt = $pdo->query("
        SELECT o.ID_order, o.Client, o.Num_phone, o.Mail, o.Time_the_bell, 
               o.Type_pay, o.Face_client, o.Other_inform, o.Status,
               st.Description as service_name, st.Service_list_count, st.Count_pay,
               p.Fio as manager_name, p.ID_personal as manager_id
        FROM Orders o
        JOIN Services_tab st ON o.Service_tab_ID = st.Service_tab_ID
        LEFT JOIN Personnel p ON o.Accept_order_Per_ID = p.ID_personal
        ORDER BY o.Time_the_bell DESC
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log('Ошибка при загрузке заявок: ' . $e->getMessage());
    $orders = [];
}

// Получаем список менеджеров для назначения
try {
    $stmt = $pdo->query("SELECT ID_personal, Fio FROM Personnel WHERE Role IN ('admin', 'manager') ORDER BY Fio");
    $managers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $managers = [];
}

$page_title = "Управление заявками";
require_once __DIR__ . '/includes/admin_header.php';
?>

<!-- Фильтры -->
<?php if ($message): ?>
    <div class="message <?= $message_type === 'error' ? 'error' : 'success' ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<div class="filter-bar">
    <input type="text" id="searchInput" placeholder="Поиск по клиенту или телефону..." onkeyup="filterTable()">
</div>

<!-- Таблица заявок -->
<div class="content-section">
    <h2 class="section-title">Все заявки (<?= count($orders) ?>)</h2>
    
    <?php if (empty($orders)): ?>
        <p>Заявок пока нет.</p>
    <?php else: ?>
        <table class="orders-table" id="ordersTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Клиент</th>
                    <th>Телефон</th>
                    <th>Email</th>
                    <th>Услуга</th>
                    <th>Дата/Время</th>
                    <th>Оплата</th>
                    <th>Статус</th>
                    <th>Менеджер</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <?php
                        // Определяем класс строки и бейдж статуса
                        $row_class = 'order-row-normal';
                        $status_class = '';
                        $status_text = $order['Status'] ?? 'новая';
                        
                        // Новая заявка
                        if ($status_text === 'новая') {
                            $row_class = 'order-row-new';
                            $status_class = 'status-badge-new';
                        } elseif ($status_text === 'в работе') {
                            $status_class = 'status-badge-in-progress';
                        } elseif ($status_text === 'выполнена') {
                            $status_class = 'status-badge-completed';
                        } elseif ($status_text === 'отменена') {
                            $status_class = 'status-badge-cancelled';
                        }
                        
                        // Заявка без менеджера (приоритет над статусом)
                        if (empty($order['manager_id'])) {
                            $row_class = 'order-row-no-manager';
                        }
                    ?>
                    <tr class="<?= $row_class ?>">
                        <td>#<?= $order['ID_order'] ?></td>
                        <td><?= htmlspecialchars($order['Client']) ?></td>
                        <td><?= htmlspecialchars($order['Num_phone']) ?></td>
                        <td><?= htmlspecialchars($order['Mail']) ?></td>
                        <td><?= htmlspecialchars($order['service_name']) ?></td>
                        <td><?= $order['Time_the_bell'] ? date('d.m.Y H:i', strtotime($order['Time_the_bell'])) : 'Не указано' ?></td>
                        <td><?= $order['Type_pay'] === 'card' ? 'Карта' : ($order['Type_pay'] === 'cash' ? 'Наличные' : 'Безнал') ?></td>
                        <td>
                            <span class="status-badge <?= $status_class ?>"><?= htmlspecialchars($status_text) ?></span>
                        </td>
                        <td>
                            <?= $order['manager_name'] ? '<strong>' . htmlspecialchars($order['manager_name']) . '</strong>' : '<em style="color: #dc3545;">Не назначен</em>' ?>
                        </td>
                        <td>
                            <a href="order_view.php?id=<?= $order['ID_order'] ?>" class="btn-small btn-primary">Просмотр</a>
                            
                            <!-- Форма изменения статуса -->
                            <form method="POST" style="display: inline-block; margin-left: 5px;">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="order_id" value="<?= $order['ID_order'] ?>">
                                <select name="status" onchange="this.form.submit()" style="padding: 4px 8px; font-size: 12px; border-radius: 4px; border: 1px solid #ddd;">
                                    <option value="новая" <?= $status_text === 'новая' ? 'selected' : '' ?>>🆕 Новая</option>
                                    <option value="в работе" <?= $status_text === 'в работе' ? 'selected' : '' ?>>🔄 В работе</option>
                                    <option value="выполнена" <?= $status_text === 'выполнена' ? 'selected' : '' ?>>✅ Выполнена</option>
                                    <option value="отменена" <?= $status_text === 'отменена' ? 'selected' : '' ?>>❌ Отменена</option>
                                </select>
                            </form>
                            
                            <?php if (empty($order['manager_id']) && $role === 'admin'): ?>
                                <button class="btn-small btn-success" onclick="assignManager(<?= $order['ID_order'] ?>)" style="margin-left: 5px;">Назначить</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Модальное окно назначения менеджера -->
<div id="assignModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <span class="close-modal" onclick="closeAssignModal()">&times;</span>
        <h3>Назначение менеджера</h3>
        <form method="POST">
            <input type="hidden" name="action" value="assign_manager">
            <input type="hidden" id="modal_order_id" name="order_id">
            <div class="form-group">
                <label>Выберите менеджера:</label>
                <select name="manager_id" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="">-- Выберите --</option>
                    <?php foreach ($managers as $mgr): ?>
                        <option value="<?= $mgr['ID_personal'] ?>"><?= htmlspecialchars($mgr['Fio']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-submit-order" style="margin-top: 15px;">Назначить</button>
        </form>
    </div>
</div>

<script>
    function filterTable() {
        const input = document.getElementById('searchInput');
        const filter = input.value.toUpperCase();
        const table = document.getElementById('ordersTable');
        const tr = table.getElementsByTagName('tr');
        
        for (let i = 1; i < tr.length; i++) {
            const tdClient = tr[i].getElementsByTagName('td')[1];
            const tdPhone = tr[i].getElementsByTagName('td')[2];
            
            if (tdClient || tdPhone) {
                const clientValue = tdClient.textContent || tdClient.innerText;
                const phoneValue = tdPhone.textContent || tdPhone.innerText;
                
                if (clientValue.toUpperCase().indexOf(filter) > -1 || 
                    phoneValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = '';
                } else {
                    tr[i].style.display = 'none';
                }
            }
        }
    }
    
    function assignManager(orderId) {
        document.getElementById('modal_order_id').value = orderId;
        document.getElementById('assignModal').style.display = 'block';
    }
    
    function closeAssignModal() {
        document.getElementById('assignModal').style.display = 'none';
    }
    
    // Закрытие модального окна при клике вне его
    window.onclick = function(event) {
        const modal = document.getElementById('assignModal');
        if (event.target === modal) {
            closeAssignModal();
        }
    }
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
