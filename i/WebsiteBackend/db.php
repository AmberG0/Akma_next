<?php
/**
 * Подключение к базе данных
 * ИП Барбарян Карен Аветикович
 * Специальность 09.02.07 Информационные системы и программирование
 */

$host = 'localhost';
$dbname = 'construction_site';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
    // В продакшене не выводим детальную ошибку
    error_log($e->getMessage());
    die('Ошибка подключения к базе данных. Попробуйте позже.');
}
