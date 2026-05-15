-- Исправленный SQL запрос для добавления поля Status в таблицу Orders
-- Выполнить в базе данных construction_site

ALTER TABLE Orders ADD COLUMN Status ENUM('новая', 'в работе', 'выполнена', 'отменена') DEFAULT 'новая' AFTER Type_pay;

-- Обновление существующих заявок на "новая"
UPDATE Orders SET Status = 'новая' WHERE Status IS NULL OR Status = '';
