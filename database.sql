-- ================================================================
-- БАЗА ДАННЫХ: construction_site
-- Проект: Сайт строительной организации "Строй сервис"
-- Студенты: Арыков Антон Андреевич, ИП Барабарян К.А.
-- Специальность: 09.02.07 Информационные системы и программирование
-- ================================================================
-- Инструкция по развертыванию:
-- 1. Создайте базу данных MySQL с именем construction_site
-- 2. Импортируйте этот SQL файл через phpMyAdmin или консоль
-- 3. По умолчанию созданы пользователи:
--    admin / admin (Администратор)
--    manager / manager (Менеджер)
-- ================================================================

-- Создание базы данных
CREATE DATABASE IF NOT EXISTS construction_site;
USE construction_site;

-- ================================================================
-- ТАБЛИЦЫ
-- ================================================================

-- 1. Таблица Category (Категории услуг)
CREATE TABLE IF NOT EXISTS Category (
    ID_category INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(255) NOT NULL,
    Description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Таблица Services (Услуги)
CREATE TABLE IF NOT EXISTS Services (
    ID_services INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(255) NOT NULL,
    Description TEXT,
    Unit VARCHAR(255),
    Price FLOAT,
    Category INT,
    Relevance ENUM('да', 'Нет') DEFAULT 'да',
    Photo TEXT,
    FOREIGN KEY (Category) REFERENCES Category(ID_category) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Таблица Personnel (Персонал/Модераторы)
CREATE TABLE IF NOT EXISTS Personnel (
    ID_personal INT AUTO_INCREMENT PRIMARY KEY,
    Login VARCHAR(255) UNIQUE NOT NULL,
    Password VARCHAR(255) NOT NULL,
    Fio VARCHAR(255),
    Role VARCHAR(255),
    Num_phone VARCHAR(255),
    Mail VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Таблица Services_tab (Связка заявок и услуг)
CREATE TABLE IF NOT EXISTS Services_tab (
    Service_tab_ID INT AUTO_INCREMENT PRIMARY KEY,
    Description TEXT,
    Data_create DATE,
    Data_start DATE,
    Service_list_ID INT,
    Service_list_count TEXT,
    Count_pay FLOAT,
    FOREIGN KEY (Service_list_ID) REFERENCES Services(ID_services) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Таблица Orders (Заявки)
CREATE TABLE IF NOT EXISTS Orders (
    ID_order INT AUTO_INCREMENT PRIMARY KEY,
    Client VARCHAR(255) NOT NULL,
    Num_phone VARCHAR(255),
    Mail VARCHAR(255),
    Service_tab_ID INT,
    Time_the_bell DATETIME,
    Type_pay VARCHAR(255),
    Face_client VARCHAR(255),
    Accept_order_Per_ID INT,
    Other_inform TEXT,
    Status ENUM('новая', 'в работе', 'выполнена', 'отменена') DEFAULT 'новая',
    FOREIGN KEY (Service_tab_ID) REFERENCES Services_tab(Service_tab_ID) ON DELETE SET NULL,
    FOREIGN KEY (Accept_order_Per_ID) REFERENCES Personnel(ID_personal) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Таблица Reviews (Отзывы)
CREATE TABLE IF NOT EXISTS Reviews (
    ID_review INT AUTO_INCREMENT PRIMARY KEY,
    Client_name VARCHAR(255) NOT NULL,
    Review_text TEXT NOT NULL,
    Rating INT CHECK (Rating >= 1 AND Rating <= 5),
    Date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    Is_published ENUM('да', 'Нет') DEFAULT 'Нет'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Таблица Works (Наши работы с фото)
CREATE TABLE IF NOT EXISTS Works (
    ID_work INT AUTO_INCREMENT PRIMARY KEY,
    Title VARCHAR(255) NOT NULL,
    Description TEXT,
    Photo VARCHAR(255) NOT NULL,
    Date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    Is_published ENUM('да', 'Нет') DEFAULT 'Нет'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- ТЕСТОВЫЕ ДАННЫЕ
-- ================================================================

-- Категории услуг
INSERT INTO Category (Name, Description) VALUES
('Земляные работы', 'Услуги по копке траншей, котлованов, планировке участка'),
('Аренда спецтехники', 'Предоставление тракторов и другой спецтехники'),
('Благоустройство', 'Работы по благоустройству территории');

-- Услуги
INSERT INTO Services (Name, Description, Unit, Price, Category, Relevance, Photo) VALUES
('Копка траншей', 'Рытье траншей под коммуникации', 'м.пог.', 500, 1, 'да', 'trench.jpg'),
('Копка котлованов', 'Устройство котлованов под фундамент', 'м³', 800, 1, 'да', 'pit.jpg'),
('Планировка участка', 'Выравнивание территории', 'сотка', 3000, 1, 'да', 'leveling.jpg'),
('Аренда трактора', 'Аренда трактора с водителем', 'час', 2500, 2, 'да', 'tractor.jpg'),
('Вывоз грунта', 'Погрузка и вывоз грунта', 'м³', 1200, 1, 'да', 'removal.jpg'),
('Укладка брусчатки', 'Монтаж тротуарной плитки', 'м²', 1500, 3, 'да', 'paving.jpg');

-- Персонал (пароли для локальной разработки)
INSERT INTO Personnel (Login, Password, Fio, Role, Num_phone, Mail) VALUES
('admin', 'admin', 'Иванов Иван Иванович', 'Администратор', '+7 999 000-00-01', 'admin@stroyservice.ru'),
('manager', 'manager', 'Петров Петр Петрович', 'Менеджер', '+7 999 000-00-02', 'manager@stroyservice.ru');

-- Отзывы (тестовые данные)
INSERT INTO Reviews (Client_name, Review_text, Rating, Is_published) VALUES
('Иван Петров', 'Отличная компания! Сделали ремонт в квартире быстро и качественно. Очень доволен результатом.', 5, 'да'),
('Мария Сидорова', 'Заказывала установку окон. Работники вежливые, сделали всё в срок. Рекомендую!', 4, 'да'),
('Алексей Козлов', 'Строили дачный дом. Качество хорошее, но немного задержали сроки. В целом нормально.', 3, 'да'),
('Елена Новикова', 'Профессиональный подход к делу. Менеджеры всегда на связи, прораб контролировал каждый этап. Спасибо!', 5, 'да');

-- ================================================================
-- ИНДЕКСЫ ДЛЯ ОПТИМИЗАЦИИ
-- ================================================================

CREATE INDEX IF NOT EXISTS idx_published ON Reviews(Is_published);
CREATE INDEX IF NOT EXISTS idx_date ON Reviews(Date_created DESC);
CREATE INDEX IF NOT EXISTS idx_status ON Orders(Status);
CREATE INDEX IF NOT EXISTS idx_relevance ON Services(Relevance);
