-- Проверка наличия столбца Status в таблице Orders
SELECT COLUMN_NAME 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'construction_site' 
  AND TABLE_NAME = 'Orders' 
  AND COLUMN_NAME = 'Status';
