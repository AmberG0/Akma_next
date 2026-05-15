-- Исправленный запрос для добавления поля Status в таблицу Orders
-- Выполните этот запрос в phpMyAdmin или через консоль MySQL

ALTER TABLE Orders 
ADD COLUMN Status ENUM('новая', 'в работе', 'выполнена', 'отменена') DEFAULT 'новая' 
AFTER Type_pay;

-- Обновление существующих заявок на "новая"
UPDATE Orders SET Status = 'новая' WHERE Status IS NULL OR Status = '';
