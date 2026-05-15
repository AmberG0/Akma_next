-- SQL скрипт для обновления базы данных construction_site
-- Выполните этот скрипт в вашей базе данных

-- 1. Добавляем поле Status в таблицу Orders (если еще не добавлено)
ALTER TABLE Orders 
ADD COLUMN IF NOT EXISTS Status ENUM('новая', 'в работе', 'выполнена', 'отменена') DEFAULT 'новая' AFTER Other_inform;

-- 2. Обновляем существующие заявки на статус 'новая'
UPDATE Orders SET Status = 'новая' WHERE Status IS NULL OR Status = '';

-- 3. Создаем таблицу Works для отзывов с фото (если не существует)
CREATE TABLE IF NOT EXISTS Works (
    ID_work INT AUTO_INCREMENT PRIMARY KEY,
    Title VARCHAR(255) NOT NULL,
    Description TEXT,
    Photo VARCHAR(255) NOT NULL,
    Date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    Is_published ENUM('да', 'Нет') DEFAULT 'Нет'
) ENGINE=InnoDB;

-- Проверка структуры таблицы Orders
DESCRIBE Orders;

-- Проверка количества заявок
SELECT COUNT(*) as total_orders FROM Orders;
SELECT ID_order, Client, Status FROM Orders LIMIT 5;
