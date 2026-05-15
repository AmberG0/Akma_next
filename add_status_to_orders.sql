-- SQL скрипт для добавления поля Status в таблицу Orders (если таблица уже существует)
-- Выполните этот скрипт в вашей базе данных construction_site

ALTER TABLE Orders 
ADD COLUMN IF NOT EXISTS Status ENUM('новая', 'в работе', 'выполнена', 'отменена') DEFAULT 'новая' AFTER Other_inform;

-- Обновление существующих заявок на статус 'новая'
UPDATE Orders SET Status = 'новая' WHERE Status IS NULL OR Status = '';

-- Проверка результата
SELECT ID_order, Client, Status FROM Orders LIMIT 10;
