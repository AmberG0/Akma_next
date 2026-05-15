# 🛠️ Исправления и обновления проекта "Строй сервис"

## ✅ Выполненные исправления (15.05.2024)

### 1. Загрузка фотографий услуг
**Файл:** `pages/admin/services.php`

**Изменения:**
- Упрощена логика загрузки фото (только проверка расширения)
- Фото сохраняются в папку `/workspace/photoservice/uploads/`
- Имя файла: `serv_timestamp_random.ext`
- В БД сохраняется только имя файла (без пути)
- При отображении путь формируется как `../../uploads/{имя_файла}`

**Обновленные файлы для отображения фото:**
- `pages/admin/services.php` - добавлена колонка "Фото" в таблицу
- `pages/user/catalog.php` - исправлен путь к фото
- `pages/user/service_detail.php` - исправлен путь к фото (основное и похожие услуги)

**Структура хранения:**
```
/workspace/photoservice/
├── uploads/              ← Папка для всех загрузок
│   └── serv_1234567890_1234.jpg
├── pages/
│   ├── admin/services.php
│   └── user/
│       ├── catalog.php
│       └── service_detail.php
```

### 2. Статусы заявок
**Файлы:** `pages/admin/orders.php`, `pages/admin/order_view.php`

**Реализованные статусы:**
- 🆕 Новая
- 🔄 В работе
- ✅ Выполнена
- ❌ Отменена

**Функционал:**
- Изменение статуса в общем списке заявок (выпадающий список)
- Изменение статуса в детальной странице заявки
- Автоматическое присвоение статуса "в работе" при назначении менеджера
- Цветовая индикация статусов

### 3. SQL файлы для обновления БД

**file:** `update_status_fixed.sql`
```sql
ALTER TABLE Orders ADD COLUMN Status ENUM('новая', 'в работе', 'выполнена', 'отменена') DEFAULT 'новая' AFTER Type_pay;
UPDATE Orders SET Status = 'новая' WHERE Status IS NULL OR Status = '';
```

**file:** `reviews_table.sql`
```sql
CREATE TABLE IF NOT EXISTS Reviews (
    ID_review INT AUTO_INCREMENT PRIMARY KEY,
    Client_name VARCHAR(255) NOT NULL,
    Review_text TEXT NOT NULL,
    Rating INT NOT NULL CHECK (Rating >= 1 AND Rating <= 5),
    Date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Is_published ENUM('да', 'Нет') DEFAULT 'Нет'
) ENGINE=InnoDB;
```

## 📋 Инструкция по установке

### 1. Обновление базы данных
```bash
mysql -u root -p construction_site < /workspace/photoservice/update_status_fixed.sql
mysql -u root -p construction_site < /workspace/photoservice/reviews_table.sql
```

### 2. Проверка прав на папку uploads
```bash
chmod 777 /workspace/photoservice/uploads
```

### 3. Тестирование загрузки фото
1. Войти в админку: `/photoservice/pages/admin/login.php`
2. Логин: `admin`, Пароль: `password`
3. Перейти в "Услуги" → "Добавить услугу"
4. Загрузить изображение (JPG, PNG, GIF, WebP)
5. Проверить появление фото в таблице услуг

## 🔧 Технические детали

### Логика загрузки фото (services.php)
```php
$upload_dir = __DIR__ . '/../../uploads/';
$allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
$new_filename = 'serv_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
move_uploaded_file($file_tmp, $upload_dir . $new_filename);
$photo = $new_filename; // Сохраняем только имя в БД
```

### Отображение фото (catalog.php, service_detail.php)
```php
<?php if (!empty($service['Photo']) && file_exists('../../uploads/' . $service['Photo'])): ?>
    <img src="../../uploads/<?= htmlspecialchars($service['Photo']) ?>" alt="...">
<?php else: ?>
    <div class="photo-placeholder">Нет фото</div>
<?php endif; ?>
```

## ⚠️ Важные замечания

1. **Папка uploads** должна существовать и иметь права на запись
2. **Максимальный размер файла** не ограничен (можно добавить проверку при необходимости)
3. **Проверка типа файла** осуществляется по расширению (не по MIME-типу)
4. **Старые фото** не удаляются автоматически при загрузке новых (можно добавить функционал)

## 📞 Контакты

Студент: Арыков Антон Андреевич  
Руководитель ВКР: Тугушев Динислям Умярович  
Дата защиты: 07 июня 2026 года
