-- ================================================================
-- БАЗА ДАННЫХ: construction_site
-- Проект: Сайт строительной организации "Строй сервис"
-- Студент: Арыков Антон Андреевич
-- ================================================================

-- Создание базы данных (если необходимо)
CREATE DATABASE IF NOT EXISTS construction_site;
USE construction_site;

-- 1. Таблица Category (Категории услуг)
CREATE TABLE Category (
    ID_category INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(255) NOT NULL,
    Description TEXT
) ENGINE=InnoDB;

-- 2. Таблица Services (Услуги)
CREATE TABLE Services (
    ID_services INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(255) NOT NULL,
    Description TEXT,
    Unit VARCHAR(255),
    Price FLOAT,
    Category INT,
    Relevance ENUM('да', 'Нет') DEFAULT 'да',
    Photo TEXT,
    FOREIGN KEY (Category) REFERENCES Category(ID_category) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 3. Таблица Personnel (Персонал/Модераторы)
CREATE TABLE Personnel (
    ID_personal INT AUTO_INCREMENT PRIMARY KEY,
    Login VARCHAR(255) UNIQUE NOT NULL,
    Password VARCHAR(255) NOT NULL,
    Fio VARCHAR(255),
    Role VARCHAR(255),
    Num_phone VARCHAR(255),
    Mail VARCHAR(255)
) ENGINE=InnoDB;

-- 4. Таблица Services_tab (Связка заявок и услуг / Список услуг в заказе)
CREATE TABLE Services_tab (
    Service_tab_ID INT AUTO_INCREMENT PRIMARY KEY,
    Description TEXT,
    Data_create DATE,
    Data_start DATE,
    Service_list_ID INT,
    Service_list_count TEXT,
    Count_pay FLOAT,
    FOREIGN KEY (Service_list_ID) REFERENCES Services(ID_services) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 5. Таблица Orders (Заявки)
CREATE TABLE Orders (
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
) ENGINE=InnoDB;

-- 6. Таблица Reviews (Отзывы)
CREATE TABLE Reviews (
    ID_review INT AUTO_INCREMENT PRIMARY KEY,
    Client_name VARCHAR(255) NOT NULL,
    Review_text TEXT NOT NULL,
    Rating INT CHECK (Rating >= 1 AND Rating <= 5),
    Date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    Is_published ENUM('да', 'Нет') DEFAULT 'Нет'
) ENGINE=InnoDB;

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
('Копка траншей', 'Рытье траншей под коммуникации', 'м.пог.', 500, 1, 'да', 'photos/trench.jpg'),
('Копка котлованов', 'Устройство котлованов под фундамент', 'м³', 800, 1, 'да', 'photos/pit.jpg'),
('Планировка участка', 'Выравнивание территории', 'сотка', 3000, 1, 'да', 'photos/leveling.jpg'),
('Аренда трактора', 'Аренда трактора с водителем', 'час', 2500, 2, 'да', 'photos/tractor.jpg'),
('Вывоз грунта', 'Погрузка и вывоз грунта', 'м³', 1200, 1, 'да', 'photos/removal.jpg'),
('Укладка брусчатки', 'Монтаж тротуарной плитки', 'м²', 1500, 3, 'да', 'photos/paving.jpg');

-- Персонал (пароли в реальном проекте должны быть захешированы!)
INSERT INTO Personnel (Login, Password, Fio, Role, Num_phone, Mail) VALUES
('admin', 'password', 'Иванов Иван Иванович', 'Администратор', '+7 999 000-00-01', 'admin@stroyservice.ru'),
('manager', 'password', 'Петров Петр Петрович', 'Менеджер', '+7 999 000-00-02', 'manager@stroyservice.ru');
