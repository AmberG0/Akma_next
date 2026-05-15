-- SQL запрос для создания таблицы отзывов (Reviews)
-- Выполнить в базе данных construction_site

CREATE TABLE IF NOT EXISTS Reviews (
    ID_review INT AUTO_INCREMENT PRIMARY KEY,
    Client_name VARCHAR(255) NOT NULL,
    Review_text TEXT NOT NULL,
    Rating INT NOT NULL CHECK (Rating >= 1 AND Rating <= 5),
    Date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Is_published ENUM('да', 'Нет') DEFAULT 'Нет'
) ENGINE=InnoDB;

-- Индексы для оптимизации
CREATE INDEX idx_published ON Reviews(Is_published);
CREATE INDEX idx_date ON Reviews(Date_created DESC);

-- Тестовые данные (4 отзыва)
INSERT INTO Reviews (Client_name, Review_text, Rating, Is_published) VALUES
('Иван Петров', 'Отличная компания! Сделали ремонт в квартире быстро и качественно. Очень доволен результатом.', 5, 'да'),
('Мария Сидорова', 'Заказывала установку окон. Работники вежливые, сделали всё в срок. Рекомендую!', 4, 'да'),
('Алексей Козлов', 'Строили дачный дом. Качество хорошее, но немного задержали сроки. В целом нормально.', 3, 'да'),
('Елена Новикова', 'Профессиональный подход к делу. Менеджеры всегда на связи, прораб контролировал каждый этап. Спасибо!', 5, 'да');
